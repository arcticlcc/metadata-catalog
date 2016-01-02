<?php

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');

//only use twig cache in production mode
if(!$app['debug']) {
    $options = array('cache' => __DIR__.'/../var/cache/twig');

    if(isset($app['twig.options'])) {
        $app['twig.options'] = array_merge($app['twig.options'], $options);
    } else {
        $app['twig.options'] = $options;
    }
}