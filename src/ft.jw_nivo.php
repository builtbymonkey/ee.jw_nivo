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


    /**
     * Cache installed slider themes
     *
     * @var null|array
     */
    private $_themes = null;
    private $_default_theme = 'default';


// ----------------------------------------------------------------------------- CONSTRUCTOR


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_slider_path = PATH_THEMES.'third_party/jw_nivo/nivo-slider/';
    }


// ----------------------------------------------------------------------------- FIELDTYPE METHODS


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
        // code..
    }


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
        // code...
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
        // code...
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
            'theme' => $this->_default_theme
        );
    }


// ----------------------------------------------------------------------------- GLOBAL SETTINGS


    /**
     * Display Global Settings
     *
     * @return string The form displayed on the settings page
     */
    public function display_global_settings()
    {
        $val = array_merge($this->settings, $_POST);

        // load the language file
        $this->EE->lang->loadfile('jw_nivo');

        // load the table lib
        $this->EE->load->library('table');

        // use the default template known as
        // $cp_pad_table_template in the views
        $this->EE->table->set_template(array(
            'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            'row_start'     => '<tr class="even">',
            'row_alt_start' => '<tr class="odd">'
        ));

        $this->EE->table->set_heading(array(lang('preference'), lang('setting')));

        $this->EE->table->add_row(
            lang('theme'),
            form_dropdown('theme', $this->get_theme_options(), $val['theme'])
        );

        return $this->EE->table->generate();
    }


    /**
     * Save Global Settings
     *
     * @return array The global settings values
     */
    function save_global_settings()
    {
        return array(
            'theme' => isset($_POST['theme']) ? $_POST['theme'] : $this->_default_theme
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

}
// END CLASS
