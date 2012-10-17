
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
        <tr class="js-nivo-no-slides <?= (isset($slides) && count($slides) > 0) ? 'is-hidden' : '' ?>">
            <td colspan="6">
                <em><?= lang('no_slides') ?></em>
            </td>
        </tr>
        <tr class="js-nivo-slide-template is-hidden">
            <td class="js-reorder-handle nivo-handle">&#9776;</td>
            <td><?= $this->file_field->field("slide_image_#") ?></td>
            <td><?= form_textarea("slide_caption_#") ?></td>
            <td><?= form_textarea("slide_link_#") ?></td>
            <td><?= form_textarea("slide_alt_text_#") ?></td>
            <td><a href="#" class="js-nivo-remove-slide nivo-button">&minus;</a></td>
        </tr>
        <?php $j = 0 ?>
        <?php if (isset($slides)): ?>
        <?php foreach ($slides as $i => $slide): $j = $i + 1; ?>
        <tr class="js-nivo-slide">
            <td class="js-reorder-handle nivo-handle">&#9776;</td>
            <td><?= $this->file_field->field("slide_image_{$j}", $slide['image']) ?></td>
            <td><?= form_textarea("slide_caption_{$j}",          $slide['caption']) ?></td>
            <td><?= form_textarea("slide_link_{$j}",             $slide['link']) ?></td>
            <td><?= form_textarea("slide_alt_text_{$j}",         $slide['alt_text']) ?></td>
            <td><a href="#" class="js-nivo-remove-slide nivo-button">&minus;</a></td>
        </tr>
        <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>
<input type="hidden" name="slide_count" value="<?= $j ?>">
<a href="#" class="js-nivo-add-slide nivo-add-link"><span class="nivo-button">+</span> &nbsp; <?= lang('add_slide') ?></a>

<label class="js-nivo-field-label nivo-label hide-field">
    <span>
        <img class="field_collapse" src="<?= $this->cp->cp_theme_url ?>images/field_collapse.png">
        <?= lang('settings') ?>
    </span>
</label>
<div class="js-nivo-field-pane nivo-field-pane" style="display: none"><?= $settings_html ?></div>
