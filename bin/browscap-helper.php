#!/usr/bin/env php
<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

$autoloadPaths = [
    'vendor/autoload.php',
    '../../autoload.php',
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;

        break;
    }
}

ini_set('memory_limit', '-1');

$browscapHelper = new \BrowscapHelper\BrowscapHelper();
$browscapHelper->run();
