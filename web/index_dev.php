<?php

use Symfony\Component\Debug\Debug;

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
) {
    header('HTTP/1.0 403 Forbidden');
    exit('Not allowed.');
    //exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}
$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}
require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/dev.php';

//prevent match conflicts with wildcard routes and profiler
if (!preg_match('/^(\/index_dev.php)?\/_profiler.*/', $_SERVER['REQUEST_URI'])) {
    require __DIR__.'/../src/controllers.php';
}

$app->run();
