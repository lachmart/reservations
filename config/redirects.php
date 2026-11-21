<?php

/*
| Hand-picked 301 targets for removed products (closest current product). They
| win over the automatic ones: any product deleted or renamed in the admin is
| remembered in the product_redirects table and redirected to its category or
| new URL without editing this file. These 12 came from Search Console's
| 'Not found (404)' report on 2026-11-21, before the automatic redirects.
*/

return [
    'products' => [
        'sub-class-a-s1200-1200w-1x18' => '/products/subwoofer-mt18-18',
        'superlux-pro-218a' => '/products/subwoofer-mt18-18',
        'jbl-prx425-1200w-2x15-1x1' => '/products/nexo-ps15r2-2000w-1x151x3',
        'podiovy-monitor-the-box-pro-mon-a12-350w-1x121x135' => '/products/db-technologies-opera-12',
        'sennheiser-wirelles-rack' => '/products/sennheiser-bezdratovy-mikrofon',
        'sennheiser-e845' => '/products/shure-sm58',
        'akg-d112' => '/products/audix-d6',
        'adj-d6-dmx-splitter' => '/products/rozvadec-dmx-splitter-1ch-dimmer',
        'stageworx-lb-3-trio-max-vyska-3m-sirka-45m-zatizeni-60kg-vhodne-pro-led-par-fractal' => '/products/gravity-ls-tbtv-28',
        'varytec-wind-up-85kg' => '/products/gravity-ls-tbtv-28',
        '7r-230w' => '/products?category=svetla',
        'manfrotto-126bsu' => '/products?category=svetla',
    ],
];
