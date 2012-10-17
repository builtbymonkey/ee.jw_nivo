
<table class="mainTable padTable js-nivo-table nivo-table" border="0" cellspacing="0" cellpadding="0">
    <thead>
        <th style="width:  3%"></th>
        <th style="width: 13%"><?= lang('image') ?></th>
        <th style="width: 27%"><?= lang('caption') ?></th>
        <th style="width: 27%"><?= lang('link') ?></th>
        <th style="width: 27%"><?= lang('alt_text') ?></th>
        <th style="width:  3%"></th>
    </thead>
    <tbody>
        <tr class="js-nivo-no-slides <?= (count($slides) > 0) ? 'is-hidden' : '' ?>">
            <td colspan="6">
                <em><?= lang('no_slides') ?></em>
            </td>
        </tr>
        <tr class="js-nivo-slide-template is-hidden">
            <td>&#9776;</td>
            <td><?= $this->file_field->field("slide_image_#") ?></td>
            <td><?= form_textarea("slide_caption_#") ?></td>
            <td><?= form_textarea("slide_link_#") ?></td>
            <td><?= form_textarea("slide_alt_text_#") ?></td>
            <td><a href="#" class="js-nivo-remove-slide nivo-remove-button">&minus;</a></td>
        </tr>
        <?php foreach ($slides as $i => $slide): ?>
        <tr class="js-nivo-slide">
            <td>&#9776;</td>
            <td><?= $this->file_field->field("slide_image_{$i}", $slide['image']) ?></td>
            <td><?= form_textarea("slide_caption_{$i}",          $slide['caption']) ?></td>
            <td><?= form_textarea("slide_link_{$i}",             $slide['link']) ?></td>
            <td><?= form_textarea("slide_alt_text_{$i}",         $slide['alt_text']) ?></td>
            <td><a href="#" class="js-nivo-remove-slide nivo-remove-button">&minus;</a></td>
        </tr>
        <?php endforeach ?>
        <input type="hidden" name="slide_count" value="<?= $i ?>">
    </tbody>
</table>
<a href="#" class="js-nivo-add-slide nivo-add-link">Add Slide</a>
