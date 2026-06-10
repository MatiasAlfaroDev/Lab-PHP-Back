<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Vercel functions only allow writes to /tmp, so storage must live there.
$storagePath = '/tmp/storage';
foreach ([
    'app',
    'framework/cache/data',
    'framework/sessions',
    'framework/testing',
    'framework/views',
    'logs',
] as $dir) {
    $path = $storagePath.'/'.$dir;
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}
$app->useStoragePath($storagePath);

$app->handleRequest(Request::capture());
