<?php

$directories = [
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/testing',
    'storage/app/public',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($directories as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}
