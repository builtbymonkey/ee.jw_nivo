
<?php
    // Prep table
    $this->table->set_template(array(
        'table_open'      => '<table class="mainTable padTable js-nivo-table" border="0" cellspacing="0" cellpadding="0">',
        'row_start'       => '<tr class="even">',
        'row_alt_start'   => '<tr class="odd">'
    ));

    // Add heading
    $this->table->set_heading(array(
        array('data' => '',               'style' => 'width: 2%'),
        array('data' => lang('image'),    'style' => 'width: 9%'),
        array('data' => lang('caption'),  'style' => 'width: 29%'),
        array('data' => lang('link'),     'style' => 'width: 29%'),
        array('data' => lang('alt_text'), 'style' => 'width: 29%'),
        array('data' => '',               'style' => 'width: 2%')
    ));

    // Add the no slides row, but hide if there are rows
    $this->table->add_row(array(
        'data'    => '<em>'.lang('no_slides').'</em>',
        'colspan' => 6,
        'style'   => (count($slides) > 0) ? 'display: none;' : ''
    ));

    // Add saved slides
    foreach ($slides as $i => $slide) {
        $this->table->add_row(
            '&#9776;',
            $this->file_field->field("slide_{$i}_image", $slide['image']),
            form_textarea("slide_{$i}_caption",          $slide['caption']),
            form_textarea("slide_{$i}_link",             $slide['link']),
            form_textarea("slide_{$i}_alt_text",         $slide['alt_text']),
            '<a href="#" class="js-nivo-remove-slide nivo-remove-slide">-</a>'
        );
    }

    // Output table
    echo $this->table->generate();
?>
<a href="#" class="js-nivo-add-slide nivo-add-slide">Add Slide</a>
