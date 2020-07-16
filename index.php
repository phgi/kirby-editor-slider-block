<?php

load([
    'kirby\editor\sliderblock' => __DIR__ . '/KirbyEditorSliderBlock.php'
]);

Kirby::plugin('phgi/slider-block', [
    'snippets' => [
        'editor/slider' => __DIR__ . '/snippets/slider.php'
    ],
    'translations' => [
        'en'    => @include_once __DIR__ . '/i18n/en.php',
        'de'    => @include_once __DIR__ . '/i18n/de.php',
    ]
]);
