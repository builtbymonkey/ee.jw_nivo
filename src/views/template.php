<?php

    $i            = 0;
    $captions     = array();
    $theme_class  = ($settings['theme'] !== '_none') ? "theme-{$settings['theme']}" : '';
    $thumbs_class = ($settings['thumbnail_nav'] === 'y') ? 'controlnav-thumbs' : '';
    $sizing       = ($settings['sizing'] === 'fixed') ? "style=\"width:{$settings['size']['width']}px; height:{$settings['size']['height']}px\"" : '';

?>
<?php if ($slides): ?>

    <div class="slider-wrapper <?= $theme_class ?> <?= $thumbs_class ?>">
        <div class="ribbon"></div>
        <div id="nivoslider-<?= $entry_id ?>" class="nivoSlider" <?= $sizing ?>>
<?php   foreach ($slides as $j => $slide) {
            extract($slide);

            $style    = ($j > 0) ? 'style="display:none"' : '';
            $thumb    = ($settings['thumbnail_nav'] === 'y') ? "data-thumb=\"{$thumb}\"" : '';
            $title    = ($caption) ? "title=\"#nivoslider-{$entry_id}-caption-{$i}\"" : '';
            $i       += (!!$caption);
            $img      = "\t\t<img src=\"{$image}\" {$thumb} {$title} alt=\"{$alt_text}\" {$style}>\n";

            if ($link)    $img        = "\t\t<a href=\"{$link}\">\n\t{$img}\t\t</a>\n";
            if ($caption) $captions[] = $caption;

            echo $img;
        } ?>
        </div>
    </div>

    <?php
        $i = 0;
        foreach ($captions as $caption) {
            echo "<div id=\"nivoslider-{$entry_id}-caption-{$i}\" class=\"nivo-html-caption\">{$caption}</div>\n";
            $i++;
        }
    ?>

<?php endif ?>

<?php foreach ($assets as $asset) { echo $asset."\n"; } ?>
<script type="text/javascript">
<?php if (count($slides) > 1): ?>
    $(function(){
        jQuery("#nivoslider-<?= $entry_id ?>").nivoSlider({
            effect:           "<?= $settings['transition'] ?>",
            slices:           <?= $settings['slices'] ?>,
            boxCols:          <?= $settings['box']['cols'] ?>,
            boxRows:          <?= $settings['box']['rows'] ?>,
            animSpeed:        <?= $settings['speed'] ?>,
            pauseTime:        <?= $settings['pause'] ?>,
            directionNav:     <?= ($settings['direction_nav']  === 'y') ? 'true' : 'false' ?>,
            controlNav:       <?= ($settings['control_nav']    === 'y') ? 'true' : 'false' ?>,
            controlNavThumbs: <?= ($settings['thumbnail_nav']  === 'y') ? 'true' : 'false' ?>,
            pauseOnHover:     <?= ($settings['pause_on_hover'] === 'y') ? 'true' : 'false' ?>,
            manualAdvance:    <?= ($settings['manual']         === 'y') ? 'true' : 'false' ?>,
            startSlide:       <?= ($settings['random_start']   === 'y') ? floor(rand(0, count($slides))) : '0' //$settings['start'] ?>

        });
    });
<?php else: ?>
    jQuery(window).load(function(){
        jQuery("#nivoslider-<?= $entry_id ?> img").show();
    });
<?php endif ?>
</script>
