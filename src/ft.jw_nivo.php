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
        'theme' => 'default'
    );


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
    public function display_field($field_data)
    {
        /**
         * - Image matrix
         *   - Image
         *   - Caption
         *   - Link
         *   - Alt text
         * - Theme
         * - Transition
         *   - Slices (for Slice transition)
         *   - Rows + Cols (for Box transition)
         * - Sizing
         *   - size if fixed
         * - Animation speed
         * - Pause Time
         * - Enable Thumbnail Navigation
         * - Enable Direction Navigation (arrows)
         * - Enable Control Navigation (1,2,3...)
         * - Pause on Hover
         * - Manual transition (no auto change)
         * - Random slide start
         */

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

        // Instantiate vars
        $vars = unserialize(base64_decode($field_data));

        $this->prep_prefs_table($vars, 'settings');
        $vars['settings_html'] = $this->EE->table->generate();

        return $this->EE->load->view('field', $vars, true);
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
        $this->EE->load->library('file_field');

        $data = array();

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


// ----------------------------------------------------------------------------- INDIVIDUAL SETTINGS


    /**
     * Display Settings
     *
     * @return string The form displayed on the settings page
     */
    public function display_settings($data)
    {
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
        $this->_themes   = array();

        foreach ($contents as $f) {
            if (is_dir($themes_path.'/'.$f)) {
                $this->_themes[] = $f;
            }
        }
        $this->_themes[] = '_none';

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
        $themes    = $this->get_installed_themes();
        $options   = array();

        foreach ($themes as $theme) {
            $options[$theme] = ucwords(trim(preg_replace('/[._ ]+/', ' ', $theme)));
        }

        return $options;
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

        // Extend defaults
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

        $this->EE->table->add_row(
            lang('theme'),
            ($group !== null) ? form_dropdown($group.'[theme]', $this->get_theme_options(), $current[$group]['theme'])
                              : form_dropdown('theme',          $this->get_theme_options(), $current['theme'])
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
