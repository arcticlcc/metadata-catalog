<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(array('127.0.0.1'));
$app->get('/owners', function () use ($app) {
    $owners = [];

    foreach (array_keys($app['config']['white']['entity']) as $key) {
        $owners[$key] =  (array_column($app['mc.cache.owners']($key), 'owner'));
    }

    return $app['twig']->render('owners.html.twig', array(
        'title' => 'Owners List',
        'active_page' => 'owners',
        'path' => ['owners' => ['Owners']],
        'owners' => $owners,
    ));
})
->bind('owners');

$app->mount('/sync', new MetaCat\Controller\SyncController());
$app->mount('/product', new MetaCat\Controller\ProductController());
$app->mount('/project', new MetaCat\Controller\ProjectController());
$app->mount('/', new MetaCat\Controller\MetadataController());
$app->mount('/', new MetaCat\Controller\RedirectController());

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array(
        'title' => 'Home',
        'index_dev' => is_readable(realpath($app['config.dir'].'../web/index_dev.php')),
    ));
})
->bind('homepage');


$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});

//set content-type for "plain" API responses
$app->view(function (array $controllerResult, Request $request) use ($app) {
    $format = $request->get('format');

    if ('json' === $format || 'xml' === $format) {
        return new Response(
            $controllerResult[0],
            200,
            ['Content-Type' => "application/$format"]
        );
    }

    return $controllerResult[0];
});

//default whitelisting
//TODO: add contextual error messages in correct format
$app->before(function (Request $request) use ($app) {
    $white = $app['config']['white'];
    $format = $request->get('format');

    if ($format && !isset($white['format'][$format])) {
        return new Response($app['twig']->render('errors/404.html.twig', array('code' => 404)), 404);
    }

    $e = $request->get('entity');

    if ($e && !isset($white['entity'][$e])) {
        return new Response($app['twig']->render('errors/404.html.twig', array('code' => 404)), 404);
    }
});
