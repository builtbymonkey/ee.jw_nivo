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


    private $_themes = null;
    private $_theme_url = null;

    private $_defaults = array(
        'theme'          => 'default',
        'sizing'         => 'fixed',
        'size'           => array(
            'width'          => 400,
            'height'         => 150
        ),
        'transition'     => 'fade',
        'slices'         => 15,
        'box'            => array(
            'rows'           => 8,
            'cols'           => 4
        ),
        'speed'          => 500,
        'pause'          => 3000,
        'random_start'   => 'n',
        'start'          => 0,
        'direction_nav'  => 'n',
        'control_nav'    => 'n',
        'thumbnail_nav'  => 'n',
        'thumbnail_size' => array(
            'width'          => 70,
            'height'         => 50
        ),
        'pause_on_hover' => 'n',
        'manual'         => 'n',
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

        $this->_slider_path = PATH_THEMES.'third_party/jw_nivo/nivo-slider/';

        $this->EE->lang->loadfile('jw_nivo');
    }


// ----------------------------------------------------------------------------- TEMPLATE TAGS


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
        return 'hello';
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
        if (!isset($vars['settings'])) {
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
     * This method prepares the data to be saved to the entries table in the
     * database
     *
     * @param  array  The data entered into this field
     * @return string The data to be stored in the database
     */
    public function save($data)
    {
        $data = $this->get_post_data();

        return base64_encode(serialize($data));
    }


// ----------------------------------------------------------------------------- INSTALLATION


    /**
     * Install
     *
     * @return array The global settings values
     */
    public function install()
    {
        return array(
            // 'theme' => $this->_default_theme
        );
    }


// ----------------------------------------------------------------------------- GLOBAL SETTINGS


    // /**
    //  * Display Global Settings
    //  *
    //  * @return string The form displayed on the global settings page
    //  */
    // public function display_global_settings()
    // {
    //     $val = array_merge($this->settings, $_POST);

    //     $this->prep_prefs_table($val);

    //     return $this->EE->table->generate();
    // }


    // /**
    //  * Save Global Settings
    //  *
    //  * @return array The global settings values
    //  */
    // function save_global_settings()
    // {
    //     return array(
    //         'theme' => isset($_POST['theme']) ? $_POST['theme'] : $this->_default_theme
    //     );
    // }


// ----------------------------------------------------------------------------- CHANNEL SETTINGS


    /**
     * Display Settings
     *
     * @return string The form displayed on the settings page
     */
    public function display_settings($data)
    {
        $this->_include_theme_js('js/field.js');

        $this->prep_prefs_table($data);

        return $this->EE->table->generate();
    }


    /**
     * Save Settings
     *
     * @return array The settings values
     */
    function save_settings()
    {
        return array(
            'theme' => $this->EE->input->post('theme')
        );
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
        if ($this->_themes !== null) {
            return $this->_themes;
        }

        $themes_path     = $this->_slider_path.'themes';
        $contents        = array_diff(scandir($themes_path), array('..', '.')); // Strip self and parent
        $this->_themes   = array('_none');

        foreach ($contents as $f) {
            if (is_dir($themes_path.'/'.$f)) {
                $this->_themes[] = $f;
            }
        }

        return $this->_themes;
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
        $this->EE->table->add_row(
            array(
                'data'           => lang('start'),
                'data-condition' => $this->get_field_name('random_start',  $group).'=n'
            ),
            form_input($this->get_field_name('start',  $group), $current['start'],  'style="width: 173px"')
            .'<div class="subtext">'.lang('start_help').'</div>'
        );

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

// ----------------------------------------------------------------------------- ASSET LOADING

    /**
     * Theme URL
     */
    private function _theme_url()
    {
        if ($this->_theme_url === null){
            $theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : $this->EE->config->slash_item('theme_folder_url').'third_party/';
            $this->_theme_url = $theme_folder_url.'jw_nivo/';
        }

        return $this->_theme_url;
    }

    /**
     * Include Theme CSS
     */
    private function _include_theme_css($file)
    {
        $this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().$file.'?'.JW_NIVO_VERSION.'" />');
    }

    /**
     * Include Theme JS
     */
    private function _include_theme_js($file)
    {
        $this->EE->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().$file.'?'.JW_NIVO_VERSION.'"></script>');
    }

}
// END CLASS
