<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'jw_nivo/config.php';

/**
 * JW Nivo
 *
 * @package    jw_nivo
 * @author     Jeremy Worboys <jw@jeremyworboys.com>
 * @copyright  Copyright (c) 2012 Jeremy Worboys
 */
class Jw_nivo_ft extends EE_Fieldtype {

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

    private $sizing_options     = array('fixed', 'responsive');
    private $transition_options = array('random', 'fade', 'fold', 'sliceDown',
                                        'sliceDownLeft', 'sliceUp', 'sliceUpLeft',
                                        'sliceUpDown', 'sliceUpDownLeft', 'slideInRight',
                                        'slideInLeft', 'boxRandom', 'boxRain',
                                        'boxRainReverse', 'boxRainGrow',
                                        'boxRainGrowReverse');

// ----------------------------------------------------------------------------- CONSTRUCTOR


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->EE->lang->loadfile('jw_nivo');

        // Initialize cache
        if (!isset($this->EE->session->cache[JW_NIVO_NAME])) {
            $this->EE->session->cache[JW_NIVO_NAME] = $this->_cache;
        }
        $this->cache =& $this->EE->session->cache[JW_NIVO_NAME];
    }


// ----------------------------------------------------------------------------- TEMPLATE TAGS


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
        $this->EE->load->library('file_field');

        $data = unserialize(base64_decode($data));

        // Prep images
        foreach ($data['slides'] as $i => $slide) {
            // Do images need to be resized
            if ($data['settings']['sizing'] === 'fixed') {
                // TODO: Resize images
                $data['slides'][$i]['image'] = $this->EE->file_field->parse_field($data['slides'][$i]['image']);
            }
            else {
                $data['slides'][$i]['image'] = $this->EE->file_field->parse_field($data['slides'][$i]['image']);
            }

            // Do we need to create thumbnails
            if ($data['settings']['thumbnail_nav'] === 'y') {
                // TODO: Create thumbnails
                $data['slides'][$i]['thumb'] = $data['slides'][$i]['image']['url'];
            }
            else {
                $data['slides'][$i]['thumb'] = '';
            }
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
    public function replace_tag($data, $params=array(), $tagdata=FALSE)
    {
        // Only load core assets once per page
        $data['assets'] = array();
        if (!$this->cache['assets_loaded']) {
            $this->cache['assets_loaded'] = true;

            $data['assets'][] = '<link rel="stylesheet" href="'.$this->_theme_url().'nivo-slider/nivo-slider.css?'.JW_NIVO_VERSION.'">';
            $data['assets'][] = '<script>window.jQuery || document.write(\'<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"><\/script>\')</script>';
            $data['assets'][] = '<script src="'.$this->_theme_url().'nivo-slider/jquery.nivo.slider.min.js?'.JW_NIVO_VERSION.'"></script>';
        }

        // Only load themes once as needed
        $theme = $data['settings']['theme'];
        if (!in_array($theme, $this->cache['loaded_themes'])) {
            $data['assets'][] = '<link rel="stylesheet" href="'.$this->_theme_url().'nivo-slider/themes/'.$theme.'/'.$theme.'.css?'.JW_NIVO_VERSION.'">';
        }

        return $this->EE->load->view('template', $data, true);
    }


// ----------------------------------------------------------------------------- PUBLISH PAGE


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
        // Load the table and file_field libs
        $this->EE->load->library('table');
        $this->EE->load->library('file_field');

        // Include assets
        $this->_include_theme_js('js/jquery.tablednd.js');
        $this->_include_theme_js('js/field.js');
        $this->_include_theme_css('css/field.css');

        // Setup file_field
        $this->EE->file_field->browser(array(
            'publish' => true,
            'settings' => '{"content_type": "image", "directory": "1"}',
        ));

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
        $vars['settings_html'] = $this->EE->table->generate();

        return $this->EE->load->view('field', $vars, true);
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
        $this->EE->db->where('entry_id', $this->settings['entry_id'])
                     ->update('channel_data', $data);
    }


// ----------------------------------------------------------------------------- CHANNEL SETTINGS


    /**
     * Display Settings
     *
     * @return string The form displayed on the settings page
     */
    public function display_settings($data)
    {
        $this->_include_theme_js('js/field.js');

        $this->prep_prefs_table(array('nivo_settings' => $data), 'nivo_settings');

        return $this->EE->table->generate();
    }


    /**
     * Save Settings
     *
     * @return array The settings values
     */
    function save_settings($data)
    {
        return $this->prep_settings($this->EE->input->post('nivo_settings'));
    }


// ----------------------------------------------------------------------------- PRIVATE METHODS


    /**
     * Get POST Data
     */
    private function get_post_data()
    {
        $this->EE->load->library('file_field');

        $data = array();

        // Combine slides into an array
        $data['slides'] = array();
        $count = intval($this->EE->input->post('slide_count')) + 1;
        for ($i=1; $i < $count; $i++) {
            $slide = array();
            $image_file        = $this->EE->input->post('slide_image_'.$i.'_hidden');
            $image_dir         = $this->EE->input->post('slide_image_'.$i.'_hidden_dir');
            $slide['image']    = $this->EE->file_field->format_data($image_file, $image_dir);
            $slide['caption']  = $this->EE->input->post('slide_caption_'.$i);
            $slide['link']     = $this->EE->input->post('slide_link_'.$i);
            $slide['alt_text'] = $this->EE->input->post('slide_alt_text_'.$i);

            $data['slides'][] = $slide;
        }

        $data['settings'] = $this->EE->input->post('settings');

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
        $this->EE->load->library('table');

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
        $this->EE->table->set_template(array(
            'table_open'      => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            'row_start'       => '<tr class="even">',
            'row_alt_start'   => '<tr class="odd">'
        ));

        $this->EE->table->set_heading(array('data' => lang('nivo_preferences'), 'style' => 'width: 40%'), '');

        /*
         * Slider Theme
         */
        $this->EE->table->add_row(
            lang('theme'),
            form_dropdown($this->get_field_name('theme', $group), $this->get_theme_options(), $current['theme'])
        );

        /*
         * Slider Sizing
         */
        $this->EE->table->add_row(
            lang('sizing'),
            form_dropdown($this->get_field_name('sizing', $group), $this->format_options('sizing'), $current['sizing'])
            .'<div class="subtext">'.lang('sizing_help').'</div>'
        );

        /*
         * Slider Size
         */
        $this->EE->table->add_row(
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
        $this->EE->table->add_row(
            lang('transition'),
            form_dropdown($this->get_field_name('transition', $group), $this->format_options('transition'), $current['transition'])
        );

        /*
         * Slices
         */
        $this->EE->table->add_row(
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
        $this->EE->table->add_row(
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
        $this->EE->table->add_row(
            lang('speed'),
            form_input($this->get_field_name('speed',  $group), $current['speed'],  'style="width: 173px"')
            .'<div class="subtext">'.lang('speed_help').'</div>'
        );

        /*
         * Pause Time
         */
        $this->EE->table->add_row(
            lang('pause'),
            form_input($this->get_field_name('pause',  $group), $current['pause'],  'style="width: 173px"')
            .'<div class="subtext">'.lang('pause_help').'</div>'
        );

        /*
         * Random Start
         */
        $this->EE->table->add_row(
            lang('random_start'),
            $this->boolean_field($this->get_field_name('random_start',  $group), $current['random_start'])
        );

        /*
         * Start Slide
         */
        // $this->EE->table->add_row(
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
        $this->EE->table->add_row(
            lang('direction_nav'),
            $this->boolean_field($this->get_field_name('direction_nav',  $group), $current['direction_nav'])
            .'<div class="subtext">'.lang('direction_nav_help').'</div>'
        );

        /*
         * Control Navigation
         */
        $this->EE->table->add_row(
            lang('control_nav'),
            $this->boolean_field($this->get_field_name('control_nav',  $group), $current['control_nav'])
            .'<div class="subtext">'.lang('control_nav_help').'</div>'
        );

        /*
         * Pause on Hover
         */
        $this->EE->table->add_row(
            lang('pause_on_hover'),
            $this->boolean_field($this->get_field_name('pause_on_hover',  $group), $current['pause_on_hover'])
        );

        /*
         * Manual Transitions
         */
        $this->EE->table->add_row(
            lang('manual'),
            $this->boolean_field($this->get_field_name('manual',  $group), $current['manual'])
            .'<div class="subtext">'.lang('manual_help').'</div>'
        );

        /*
         * Thumbnail Navigation
         */
        $this->EE->table->add_row(
            lang('thumbnail_nav'),
            $this->boolean_field($this->get_field_name('thumbnail_nav',  $group), $current['thumbnail_nav'])
        );

        /*
         * Thumbnail Size
         */
        $this->EE->table->add_row(
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

// ----------------------------------------------------------------------------- ASSET LOADING


    /**
     * Theme URL
     */
    private function _theme_url()
    {
        if ($this->cache['theme_url'] === null){
            $theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
            $this->cache['theme_url'] = $theme_folder_url.'jw_nivo/';
        }

        return $this->cache['theme_url'];
    }

    /**
     * Include Theme CSS
     */
    private function _include_theme_css($file)
    {
        $this->EE->cp->add_to_head('<link rel="stylesheet" href="'.$this->_theme_url().$file.'?'.JW_NIVO_VERSION.'">');
    }

    /**
     * Include Theme JS
     */
    private function _include_theme_js($file)
    {
        $this->EE->cp->add_to_foot('<script src="'.$this->_theme_url().$file.'?'.JW_NIVO_VERSION.'"></script>');
    }

}
// END CLASS
