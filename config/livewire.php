<?php

/*
 * Only what differs from Livewire's defaults (vendor/livewire/livewire/config/
 * livewire.php); everything else comes from the package config.
 */
return [

    'payload' => [
        'max_size' => 1024 * 1024,   // 1MB - maximum request payload size in bytes
        // The admin's rich editor syncs descriptions as a nested JSON tree: a
        // list item's text is already 10 levels deep, nested lists and bold
        // text go further. The default of 10 broke editing such products.
        'max_nesting_depth' => 30,
        'max_calls' => 50,           // Maximum method calls per request
        'max_components' => 200,     // Maximum components per batch request
    ],

];
