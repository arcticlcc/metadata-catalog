<?php

use Silex\Provider\MonologServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Silex\Provider\DebugServiceProvider;

// enable the debug mode
$app['debug'] = true;

// include the prod configuration
require __DIR__.'/prod.php';

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../var/logs/silex_dev.log',
));

$app->register(new WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../var/cache/profiler',
));

$app->register(new DebugServiceProvider(), array(
    'debug.max_items' => 250, // this is the default
    'debug.max_string_length' => -1, // this is the default
));