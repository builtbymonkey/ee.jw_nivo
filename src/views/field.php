
<?php
    // Prep table
    $this->table->set_template($cp_pad_table_template);

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
            '&#9776;'.form_hidden('slide_id[]',          $i),
            $this->file_field->field("slide_{$i}_image", $slide['image']),
            form_textarea("slide_{$i}_caption",          $slide['caption']),
            form_textarea("slide_{$i}_link",             $slide['link']),
            form_textarea("slide_{$i}_alt_text",         $slide['alt_text']),
            '-'
        );
    }

    // Output table
    echo $this->table->generate();
?>
<div class="store_ft_add">
    <a href="#" id="store_product_modifiers_add" data-new-mod-key="2">
        <i>+</i>
        Add Slide
    </a>
</div>
