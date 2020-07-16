<?php if (!empty($images)) : ?>

<?php
    $isLazy = $attrs->lazyLoading()->toBool();

    $slideStyles = "";
    $imageStyles = "width: 100%;";
    $imageClasses = "";
    if($isLazy) {
        $slideStyles .= "position: relative; overflow:hidden;";
        $imageStyles .= "position: absolute; width: 100%; top: 0; left: 0;";
        $imageClasses .= " is-lazy";
    }
    $src = $isLazy ? 'data-src' : 'src';
    $srcset = $isLazy ? 'data-srcset' : 'srcset';
?>

<figure class="k-editor-slider-block relative js-slider" itemscope itemtype="http://schema.org/ImageGallery">
  <div class="js-slider__wrapper">
    <div class="keen-slider js-slider__slider"
        data-loop="<?= $attrs->loop() ?>"
        data-autoplay="<?= $attrs->autoplay() ?>"
        data-duration="<?= $attrs->duration() ?>"
        data-slidesPerView="<?= $attrs->slidesPerView() ?>"
        data-arrows="<?= $attrs->arrows() ?>"
        data-dots="<?= $attrs->dots() ?>"
        data-zoom="<?= $attrs->zoom() ?>"
    >
    <?php foreach ($images as $imageEntry) : ?>
        <?php $image = $kirby->file($imageEntry['id']); ?>
        <?php $height = $image->height(); ?>
        <?php $width = $image->width(); ?>
        <?php $imageRatio = $isLazy ? ($height / $width * 100) : 0; ?>

        <div class="keen-slider__slide js-slider__slide">
        <div  style="<?= $slideStyles ?> padding-top:<?= $imageRatio ?>%">
            <img class="js-slider__image<?= $imageClasses ?>"
                style="<?= $imageStyles ?>"
                <?= $src ?>="<?= $image->resize(600)->url() ?>"
                <?= $srcset ?>="<?= $image->srcset() ?>",
                sizes="100vw"
                alt="<?= $imageEntry['altText'] ?>">
            </div>
    </div>
    <?php endforeach ?>
    </div>
  </div>
  <figcaption class="js-slider__caption">
    <?= $attrs->caption() ?>
  </figcaption
</figure>
<?php endif ?>
