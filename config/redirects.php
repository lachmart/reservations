<?php

/*
| Permanent (301) redirects for product pages that no longer exist, so links
| and search results for removed gear land on the closest current product or
| category instead of a 404. Keys are old product slugs; values are paths.
| Found in Search Console (Indexing → Not found) on 2026-11-21.
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
