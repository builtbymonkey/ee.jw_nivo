<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'jw_nivo/config.php';

/**
 * JW Nivo
 *
 * @package    jw_nivo
 * @author     Jeremy Worboys <jw@jeremyworboys.com>
 * @copyright  Copyright (c) 2013 Jeremy Worboys
 */
class Jw_nivo_ft extends EE_Fieldtype
{

    /**
     * Fieldtype Info
     *
     * @var array
     */
    public $info = array(
        'name'    => JW_NIVO_NAME,
        'version' => JW_NIVO_VERSION
    );

    private $_cache = array(
        'themes'        => null,
        'theme_url'     => null,
        'assets_loaded' => false,
        'loaded_themes' => array('_none') // Never load '_none' theme
    );

    private $_defaults = array(
        'theme'          => 'default',
        'sizing'         => 'responsive',
        'size'           => array(
            'width'          => 400,
            'height'         => 150
        ),
        'transition'     => 'random',
        'slices'         => 15,
        'box'            => array(
            'cols'           => 8,
            'rows'           => 4
        ),
        'speed'          => 500,
        'pause'          => 3000,
        'direction_nav'  => 'y',
        'control_nav'    => 'y',
        'thumbnail_nav'  => 'n',
        'thumbnail_size' => array(
            'width'          => 70,
            'height'         => 50
        ),
        'pause_on_hover' => 'y',
        'manual'         => 'n',
        'random_start'   => 'n',
        // 'start'          => 0,
    );

    private $_global_defaults = array(); // Filled in __construct

    private $sizing_options     = array('fixed', 'responsive');
    private $transition_options = array('random', 'fade', 'fold', 'sliceDown',
                                        'sliceDownLeft', 'sliceUp', 'sliceUpLeft',
                                        'sliceUpDown', 'sliceUpDownLeft', 'slideInRight',
                                        'slideInLeft', 'boxRandom', 'boxRain',
                                        'boxRainReverse', 'boxRainGrow',
                                        'boxRainGrowReverse');

    // ------------------------------------------------------------------------- CONSTRUCTOR


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee()->lang->loadfile('jw_nivo');

        // Initialize cache
        if (!isset(ee()->session->cache[JW_NIVO_NAME])) {
            ee()->session->cache[JW_NIVO_NAME] = $this->_cache;
        }
        $this->cache =& ee()->session->cache[JW_NIVO_NAME];

        // base_url() is only available in the control panel (which is fine here)
        if (REQ === 'CP') {
            // Check if Assets is installed
            $this->cache['has_assets'] = array_key_exists('assets', ee()->addons->get_installed());

            // Setup module defaults as we can't run files outside of a method
            $this->_global_defaults =  array(
                'cache_path' => str_replace(SYSDIR.'/', '', FCPATH).'nivo_cache/',
                'cache_url'  => base_url().'nivo_cache/',
                'use_assets' => $this->cache['has_assets'] ? 'y' : 'n'
            );
        }
    }


    // ------------------------------------------------------------------------- TEMPLATE TAGS


    /**
     * Pre-Process
     *
     * Preprocess the data on the frontend. Multiple tag pairs in the same
     * channel entries tag will cause replace_tag to be called multiple times.
     * To reduce the processing required to extract the original data structure
     * from the string (i.e. unserializing), the pre_process function is called
     * first.
     *
     * @param  array  The field data
     * @return string The prepped data
     */
    public function pre_process($data)
    {
        $data = unserialize(base64_decode($data));

        // Prep images
        foreach ($data['slides'] as $i => $slide) {
            if ($this->settings['use_assets'] === 'y') {
                $row = ee()->db->where('file_id', $slide['image'])->get('assets_files')->row();
                $source = ee()->assets_lib->instantiate_source_type($row);
                $file = $source->get_file($slide['image']);
                $image = array(
                    'url'           => $file->url(),
                    'rel_path'      => $file->server_path(),
                    'extension'     => $file->extension(),
                    'modified_date' => $file->date_modified()
                );
            }
            else {
                ee()->load->library('file_field');
                $image = ee()->file_field->parse_field($slide['image']);
            }

            // Do images need to be resized
            $data['slides'][$i]['image'] =
                ($data['settings']['sizing'] === 'fixed')
                ? $this->resize_image($image, $data['settings']['size'])
                : $image['url'];

            // Do we need to create thumbnails
            $data['slides'][$i]['thumb'] =
                ($data['settings']['thumbnail_nav'] === 'y')
                ? $this->resize_image($image, $data['settings']['thumbnail_size'])
                : '';
        }

        return $data;
    }


    /**
     * Replace Tag
     *
     * This method replaces the field tag on the front-end
     *
     * @param  array  The field data (or prepped data, if using pre_process)
     * @param  array  The field parameters (if any)
     * @param  string The data between tag (for tag pairs)
     * @return string The text/HTML to replace the tag
     */
    public function replace_tag($data, $params=array(), $tagdata=false)
    {
        // Only load core assets once per page
        $data['assets'] = array();
        if (!$this->cache['assets_loaded']) {
            $this->cache['assets_loaded'] = true;

            $data['assets'][] = '<link rel="stylesheet" href="'.$this->_theme_url().'nivo-slider/nivo-slider.css?v='.JW_NIVO_VERSION.'">';

            if (!(isset($params['require_jquery']) && preg_match("/^(no|off)$/i", $params['require_jquery']))) {
                $data['assets'][] = '<script src="'.$this->_theme_url().'jquery-1.10.2.min.js"></script>';
            }

            $data['assets'][] = '<script src="'.$this->_theme_url().'nivo-slider/jquery.nivo.slider.min.js?v='.JW_NIVO_VERSION.'"></script>';
        }

        // Only load themes once as needed
        $theme = $data['settings']['theme'];
        if (!in_array($theme, $this->cache['loaded_themes'])) {
            $data['assets'][] = '<link rel="stylesheet" href="'.$this->_theme_url().'nivo-slider/themes/'.$theme.'/'.$theme.'.css?'.JW_NIVO_VERSION.'">';
        }

        return ee()->load->view('template', $data, true);
    }


    // ------------------------------------------------------------------------- PUBLISH PAGE


    /**
     * Display Field
     *
     * This method runs when displaying the field on the publish page in the CP
     *
     * @param  array  The data previously entered into this field
     * @return string The HTML output to be displayed for this field
     */
    public function display_field($data)
    {
        // Load libraries
        ee()->load->helper('jw_nivo');
        ee()->load->library('table');

        // Get saved data
        if (!empty($data)) {
            $vars = unserialize(base64_decode($data));
        }
        // Failed validation
        else {
            $vars = $this->get_post_data();
        }
        $channel_settings = unserialize(base64_decode($this->settings['field_settings']));

        // Merge entry settings with channel settings
        if (!isset($vars['settings']) OR !is_array($vars['settings'])) {
            $vars['settings'] = array();
        }
        $vars['settings'] = array_merge($channel_settings, $vars['settings']);

        // Build the settings table
        $this->prep_prefs_table($vars, 'settings');
        $vars['settings_html'] = ee()->table->generate();

        // Default value
        $vars['assets_settings'] = null;

        // Check if using Assets
        if ($vars['use_assets'] = ($this->settings['use_assets'] === 'y')) {
            require_once PATH_THIRD.'assets/helper.php';

            $assets_helper = new Assets_helper;
            $assets_helper->include_sheet_resources();

            $vars['assets_settings'] = array(
                'filedirs' => 'all',
                'multi' => false,
                'view' => 'thumbs'
            );
        }
        // Otherwise, native file field
        else {
            ee()->load->library('file_field');

            // Setup file_field
            ee()->file_field->browser(array(
                'publish'  => true,
                'settings' => json_encode(array(
                    'content-type' => 'image',
                    'directory'    => 'all'
                ))
            ));
        }

        // Include themes
        $this->_include_theme_js('jquery.tablednd.js', 'jw_nivo.js');
        $this->_include_theme_css('jw_nivo.css');

        return ee()->load->view('field', $vars, true);
    }


    /**
     * Validates the Field Input
     *
     * @param  array The data entered into this field
     * @return mixed Must return TRUE or an error message
     */
    public function validate($data)
    {
        $data = $this->get_post_data();

        if (count($data['slides']) > 0) {
            foreach ($data['slides'] as $slide) {
                if (empty($slide['image'])) {
                    return lang('image_required');
                }
            }
        }
        else {
            // is this a required field?
            if (isset($this->settings['field_required']) && $this->settings['field_required'] == 'y') {
                return lang('required');
            }
        }

        return true;
    }


    /**
     * Prepare for Saving the Field
     *
     * We need to push this data to the cache until we are sure we have an
     * entry_id.
     *
     * @param  array  The data entered into this field
     * @return string The data to be stored in the database
     */
    public function save($data)
    {
        $this->cache['post_data'][$this->field_id] = $this->get_post_data();

        return '';
    }


    /**
     * Post Save
     *
     * This method prepares the data to be saved to the entries table in the
     * database
     *
     * @param  array  The data entered into this field
     * @return string The data to be stored in the database
     */
    public function post_save($data)
    {
        // Ignore if we can't find the cached post data
        if (empty($this->cache['post_data'][$this->field_id])) return;

        $data = $this->cache['post_data'][$this->field_id];
        $data['settings'] = $this->prep_settings($data['settings']);
        $data['entry_id'] = $this->settings['entry_id'];
        $data = base64_encode(serialize($data));

        $data = array('field_id_'.$this->field_id => $data);
        ee()->db->where('entry_id', $this->settings['entry_id'])
                     ->update('channel_data', $data);
    }


    // ------------------------------------------------------------------------- INSTALLATION


    /**
     * Install
     *
     * @return array The global settings values
     */
    public function install()
    {
        // Attempt to create cache directory
        if (!is_dir($this->_global_defaults['cache_path'])) {
            mkdir($this->_global_defaults['cache_path'], DIR_WRITE_MODE);
        }

        return $this->_global_defaults;
    }


    // ------------------------------------------------------------------------- MODULE SETTINGS


    /**
     * Display Global Settings
     *
     * @return string The form displayed on the global settings page
     */
    public function display_global_settings()
    {
        // Load the table lib
        ee()->load->library('table');

        // Use the default template known as $cp_pad_table_template in the views
        ee()->table->set_template(array(
            'table_open'      => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            'row_start'       => '<tr class="even">',
            'row_alt_start'   => '<tr class="odd">'
        ));

        ee()->table->set_heading(array('data' => lang('nivo_preferences'), 'style' => 'width: 40%'), '');

        ee()->table->add_row(
            lang('cache_path'),
            form_input('cache_path', $this->settings['cache_path'])
            .((!file_exists($this->settings['cache_path']))        ? '<div class="notice">'.lang('cache_doesnt_exist').'</div>' :
             ((!is_really_writable($this->settings['cache_path'])) ? '<div class="notice">'.lang('cache_not_writable').'</div>' : ''))
        );
        ee()->table->add_row(
            lang('cache_url'),
            form_input('cache_url', $this->settings['cache_url'])
        );
        ee()->table->add_row(
            lang('use_assets'),
            $this->boolean_field('use_assets', $this->settings['use_assets'])
        );

        return ee()->table->generate();
    }


    /**
     * Save Global Settings
     *
     * @return array The global settings values
     */
    function save_global_settings()
    {
        $settings = $this->_global_defaults;
        foreach ($settings as $key => $val) {
            if (isset($_POST[$key])) $settings[$key] = $_POST[$key];
        }

        return $settings;
    }


// ----------------------------------------------------------------------------- CHANNEL SETTINGS


    /**
     * Display Settings
     *
     * @return string The form displayed on the settings page
     */
    public function display_settings($data)
    {
        $this->_include_theme_js('field.js');

        $this->prep_prefs_table(array('nivo_settings' => $data), 'nivo_settings');

        return ee()->table->generate();
    }


    /**
     * Save Settings
     *
     * @return array The settings values
     */
    function save_settings($data)
    {
        return $this->prep_settings(ee()->input->post('nivo_settings'));
    }


    // ------------------------------------------------------------------------- PRIVATE METHODS


    /**
     * Resize Image
     *
     * @param array Image details
     * @param array Width, height
     * @return URL to resized image
     */
    private function resize_image($image, $size)
    {
        extract($image);

        $cache_name = md5($rel_path.$size['width'].$size['height'].$modified_date).'.'.$extension;
        $cache_path = $this->settings['cache_path'].$cache_name;

        if (!file_exists($cache_path)) {
            ee()->load->library('image_lib');

            $config['source_image']     = $rel_path;
            $config['new_image']        = $cache_path;
            $config['maintain_ratio']   = true;
            $config['image_library']    = ee()->config->item('image_resize_protocol');
            $config['library_path']     = ee()->config->item('image_library_path');
            $config['width']            = $size['width'];
            $config['height']           = $size['height'];

            ee()->image_lib->initialize($config);
            ee()->image_lib->resize();
        }

        return $this->settings['cache_url'].$cache_name;
    }


    /**
     * Get POST Data
     */
    private function get_post_data()
    {
        ee()->load->library('file_field');

        $data = array();

        // Combine slides into an array
        $data['slides'] = array();
        $count = intval(ee()->input->post('slide_count')) + 1;
        for ($i=1; $i < $count; $i++) {
            $slide = array();
            if ($this->settings['use_assets'] === 'y') {
                foreach (ee()->input->post('slide_image_'.$i) as $image) {
                    if (!empty($image)) {
                        $slide['image'] = $image;
                    }
                }
            }
            else {
                $image_file     = ee()->input->post('slide_image_'.$i.'_hidden_file');
                if (!$image_file) {
                    $image_file = ee()->input->post('slide_image_'.$i.'_hidden');
                }
                $image_dir      = ee()->input->post('slide_image_'.$i.'_hidden_dir');
                $slide['image'] = ee()->file_field->format_data($image_file, $image_dir);
            }
            $slide['caption']   = ee()->input->post('slide_caption_'.$i);
            $slide['link']      = ee()->input->post('slide_link_'.$i);
            $slide['alt_text']  = ee()->input->post('slide_alt_text_'.$i);

            $data['slides'][]   = $slide;
        }

        $data['settings'] = ee()->input->post('settings');

        return $data;
    }


    /**
     * Get Installed Themes
     *
     * Finds installed themes for the Nivo Image Slider
     *
     * @return array The folder names for the installed themes
     */
    private function get_installed_themes()
    {
        if ($this->cache['themes'] !== null) {
            return $this->cache['themes'];
        }

        $themes_path           = PATH_THEMES.'third_party/jw_nivo/nivo-slider/themes';
        $contents              = array_diff(scandir($themes_path), array('..', '.')); // Strip self and parent
        $this->cache['themes'] = array('_none');

        foreach ($contents as $f) {
            if (is_dir($themes_path.'/'.$f)) {
                $this->cache['themes'][] = $f;
            }
        }

        return $this->cache['themes'];
    }


    /**
     * Get Theme Options
     *
     * Gets the themes in an array for use in a dropdown input
     *
     * @return array The theme options
     */
    private function get_theme_options()
    {
        $this->theme_options = $this->get_installed_themes();

        return $this->format_options('theme');
    }


    /**
     * Format Options
     *
     * @param string The options key
     * @return array The formatted options
     */
    private function format_options($key)
    {
        $key     = $key.'_options';
        $options = array();

        foreach ($this->$key as $opt) {
            $options[$opt] = lang($opt);
        }

        return $options;
    }


    /**
     * Get Field Name
     *
     * @param string The field name
     * @param string The field group
     * @return array The formatted name
     */
    private function get_field_name($name, $group)
    {
        if ($group !== null) {
            if (strpos($name, '[') !== false) {
                list($p1, $p2) = explode('[', $name, 2);
                $name = "{$group}[{$p1}][$p2";
            }
            else {
                $name = "{$group}[{$name}]";
            }
        }
        return $name;
    }


    /**
     * Boolean Field
     *
     * @param string The field name
     * @param string The current value
     * @return array The prepared field
     */
    private function boolean_field($name, $current)
    {
        $s_name = str_replace(array('[',']'), '_', $name);

        $out  = form_radio(array('name' => $name, 'id' => $s_name.'_y', 'value' => 'y', 'checked' => ($current==='y'))).NBS.lang('yes', $s_name.'_y').NBS.NBS.NBS.NBS.NBS;
        $out .= form_radio(array('name' => $name, 'id' => $s_name.'_n', 'value' => 'n', 'checked' => ($current==='n'))).NBS.lang('no',  $s_name.'_n');

        return $out;
    }


    /**
     * Prepare Preferences Table
     *
     * @param array Current values for settings
     */
    private function prep_prefs_table($current, $group=null)
    {
        // Load the table lib
        ee()->load->library('table');

        // Extend defaults taking $group into consideration
        if (!is_array($current)) {
            if ($group !== null) {
                $current = array($group => array());
            }
            else {
                $current = array();
            }
        }
        if ($group !== null) {
            $current[$group] = array_merge($this->_defaults, $current[$group]);
            $current         = $current[$group];
        }
        else {
            $current = array_merge($this->_defaults, $current);
        }

        // Use the default template known as $cp_pad_table_template in the views
        ee()->table->set_template(array(
            'table_open'      => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            'row_start'       => '<tr class="even">',
            'row_alt_start'   => '<tr class="odd">'
        ));

        ee()->table->set_heading(array('data' => lang('nivo_preferences'), 'style' => 'width: 40%'), '');

        /*
         * Slider Theme
         */
        ee()->table->add_row(
            lang('theme'),
            form_dropdown($this->get_field_name('theme', $group), $this->get_theme_options(), $current['theme'])
        );

        /*
         * Slider Sizing
         */
        ee()->table->add_row(
            lang('sizing'),
            form_dropdown($this->get_field_name('sizing', $group), $this->format_options('sizing'), $current['sizing'])
            .'<div class="subtext">'.lang('sizing_help').'</div>'
        );

        /*
         * Slider Size
         */
        ee()->table->add_row(
            array(
                'data'           => lang('size'),
                'data-condition' => $this->get_field_name('sizing',  $group).'=fixed'
            ),
            form_input($this->get_field_name('size[width]',  $group), $current['size']['width'],  'style="width: 80px"').NBS.'&times;'.NBS.
            form_input($this->get_field_name('size[height]', $group), $current['size']['height'], 'style="width: 80px"')
            .'<div class="subtext">'.lang('size_help').'</div>'
        );

        /*
         * Transition Effect
         */
        ee()->table->add_row(
            lang('transition'),
            form_dropdown($this->get_field_name('transition', $group), $this->format_options('transition'), $current['transition'])
        );

        /*
         * Slices
         */
        ee()->table->add_row(
            array(
                'data'           => lang('slices'),
                'data-condition' => $this->get_field_name('transition',  $group).'=slice'
            ),
            form_input($this->get_field_name('slices',  $group), $current['slices'],  'style="width: 173px"')
            .'<div class="subtext">'.lang('slices_help').'</div>'
        );

        /*
         * Box
         */
        ee()->table->add_row(
            array(
                'data'           => lang('box'),
                'data-condition' => $this->get_field_name('transition',  $group).'=box'
            ),
            form_input($this->get_field_name('box[cols]',  $group), $current['box']['cols'],  'style="width: 80px"').NBS.'&times;'.NBS.
            form_input($this->get_field_name('box[rows]',  $group), $current['box']['rows'],  'style="width: 80px"')
            .'<div class="subtext">'.lang('box_help').'</div>'
        );

        /*
         * Animation Speed
         */
        ee()->table->add_row(
            lang('speed'),
            form_input($this->get_field_name('speed',  $group), $current['speed'],  'style="width: 173px"')
            .'<div class="subtext">'.lang('speed_help').'</div>'
        );

        /*
         * Pause Time
         */
        ee()->table->add_row(
            lang('pause'),
            form_input($this->get_field_name('pause',  $group), $current['pause'],  'style="width: 173px"')
            .'<div class="subtext">'.lang('pause_help').'</div>'
        );

        /*
         * Random Start
         */
        ee()->table->add_row(
            lang('random_start'),
            $this->boolean_field($this->get_field_name('random_start',  $group), $current['random_start'])
        );

        /*
         * Start Slide
         */
        // ee()->table->add_row(
        //     array(
        //         'data'           => lang('start'),
        //         'data-condition' => $this->get_field_name('random_start',  $group).'=n'
        //     ),
        //     form_input($this->get_field_name('start',  $group), $current['start'],  'style="width: 173px"')
        //     .'<div class="subtext">'.lang('start_help').'</div>'
        // );

        /*
         * Direction Navigation
         */
        ee()->table->add_row(
            lang('direction_nav'),
            $this->boolean_field($this->get_field_name('direction_nav',  $group), $current['direction_nav'])
            .'<div class="subtext">'.lang('direction_nav_help').'</div>'
        );

        /*
         * Control Navigation
         */
        ee()->table->add_row(
            lang('control_nav'),
            $this->boolean_field($this->get_field_name('control_nav',  $group), $current['control_nav'])
            .'<div class="subtext">'.lang('control_nav_help').'</div>'
        );

        /*
         * Pause on Hover
         */
        ee()->table->add_row(
            lang('pause_on_hover'),
            $this->boolean_field($this->get_field_name('pause_on_hover',  $group), $current['pause_on_hover'])
        );

        /*
         * Manual Transitions
         */
        ee()->table->add_row(
            lang('manual'),
            $this->boolean_field($this->get_field_name('manual',  $group), $current['manual'])
            .'<div class="subtext">'.lang('manual_help').'</div>'
        );

        /*
         * Thumbnail Navigation
         */
        ee()->table->add_row(
            lang('thumbnail_nav'),
            $this->boolean_field($this->get_field_name('thumbnail_nav',  $group), $current['thumbnail_nav'])
        );

        /*
         * Thumbnail Size
         */
        ee()->table->add_row(
            array(
                'data'           => lang('thumbnail_size'),
                'data-condition' => $this->get_field_name('thumbnail_nav',  $group).'=y'
            ),
            form_input($this->get_field_name('thumbnail_size[width]',  $group), $current['thumbnail_size']['width'],  'style="width: 80px"').NBS.'&times;'.NBS.
            form_input($this->get_field_name('thumbnail_size[height]', $group), $current['thumbnail_size']['height'], 'style="width: 80px"')
            .'<div class="subtext">'.lang('thumbnail_size_help').'</div>'
        );
    }


    /**
     * Prep Settings
     *
     * @param array Settings to be saved
     * @return array Prepped settings
     */
    private function prep_settings($settings)
    {
        if(!is_numeric($settings['size']['width'])            OR $settings['size']['width']            <= 0 ) $settings['size']['width']            = $this->_defaults['size']['width'];
        if(!is_numeric($settings['size']['height'])           OR $settings['size']['height']           <= 0 ) $settings['size']['height']           = $this->_defaults['size']['height'];
        if(!is_numeric($settings['slices'])                   OR $settings['slices']                   <= 0 ) $settings['slices']                   = $this->_defaults['slices'];
        if(!is_numeric($settings['box']['cols'])              OR $settings['box']['cols']              <= 0 ) $settings['box']['cols']              = $this->_defaults['box']['cols'];
        if(!is_numeric($settings['box']['rows'])              OR $settings['box']['rows']              <= 0 ) $settings['box']['rows']              = $this->_defaults['box']['rows'];
        if(!is_numeric($settings['speed'])                    OR $settings['speed']                    <= 0 ) $settings['speed']                    = $this->_defaults['speed'];
        if(!is_numeric($settings['pause'])                    OR $settings['pause']                    <= 0 ) $settings['pause']                    = $this->_defaults['pause'];
        // if(!is_numeric($settings['start'])                    OR $settings['start']                    <  0 ) $settings['start']                    = $this->_defaults['start'];
        if(!is_numeric($settings['thumbnail_size']['width'])  OR $settings['thumbnail_size']['width']  <= 0 ) $settings['thumbnail_size']['width']  = $this->_defaults['thumbnail_size']['width'];
        if(!is_numeric($settings['thumbnail_size']['height']) OR $settings['thumbnail_size']['height'] <= 0 ) $settings['thumbnail_size']['height'] = $this->_defaults['thumbnail_size']['height'];

        return $settings;
    }


    // ------------------------------------------------------------------------- ASSET LOADING


    /**
     * Theme URL
     */
    private function _theme_url()
    {
        if ($this->cache['theme_url'] === null) {
            $theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : ee()->config->slash_item('theme_folder_url').'third_party/';
            $this->cache['theme_url'] = $theme_folder_url.'jw_nivo/';
        }

        return $this->cache['theme_url'];
    }

    /**
     * Include Theme CSS
     */
    private function _include_theme_css()
    {
        foreach (func_get_args() as $file) {
            ee()->cp->add_to_head('<link rel="stylesheet" href="'.$this->_theme_url().$file.'?'.JW_NIVO_VERSION.'">');
        }
    }

    /**
     * Include Theme JS
     */
    private function _include_theme_js()
    {
        foreach (func_get_args() as $file) {
            ee()->cp->add_to_foot('<script src="'.$this->_theme_url().$file.'?'.JW_NIVO_VERSION.'"></script>');
        }
    }

}
// END CLASS
