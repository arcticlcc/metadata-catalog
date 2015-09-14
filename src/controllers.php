<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array('title' => 'Home'));
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

    if($format && !isset($white['format'][$format])) {
        return new Response($app['twig']->render('errors/404.html.twig', array('code' => 404)), 404);
    }

    $e = $request->get('entity');

    if($e && !isset($white['entity'][$e])) {
        return new Response($app['twig']->render('errors/404.html.twig', array('code' => 404)), 404);
    }
});
