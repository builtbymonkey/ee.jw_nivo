<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * JW Nivo - View Helpers
 *
 * @package    jw_nivo
 * @author     Jeremy Worboys <jw@jeremyworboys.com>
 * @copyright  Copyright (c) 2013 Jeremy Worboys
 */

/**
 * Render image selection field in view
 * @param  string  $id
 * @param  boolean $value
 * @return string
 */
function image_field($use_assets, $id = '#', $value = false)
{
    if ($use_assets) {
        $field             = new Assets_ft();
        $field->settings   = array_merge($field->settings, $assets_settings);
        $field->field_name = "slide_image_{$id}";
        return $field->display_field($value);
    }
    else {
        return ee()->file_field->field("slide_image_{$id}", $value, 'all', 'image');
    }
}
