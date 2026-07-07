<?php

require __DIR__.'/../vendor/autoload.php';

$bootstrapDirs = [
    '/tmp/bootstrap/cache',
    '/tmp/views',
    '/tmp/celestial-data',
];

foreach ($bootstrapDirs as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

require __DIR__.'/../public/index.php';
