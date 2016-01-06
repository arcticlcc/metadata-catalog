<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Saxulum\DoctrineOrmManagerRegistry\Provider\DoctrineOrmManagerRegistryProvider;
use Dflydev\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Saxulum\AsseticTwig\Provider\AsseticTwigProvider;
use DerAlex\Pimple\YamlConfigServiceProvider;
use Aws\Sdk;
//use Boldtrn\JsonbBundle\Types\JsonbArrayType;
//use Doctrine\Common\Annotations\AnnotationRegistry;

//AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$app = new Application();

$app -> register(new RoutingServiceProvider());
$app -> register(new ValidatorServiceProvider());
$app -> register(new ServiceControllerServiceProvider());
$app -> register(new TwigServiceProvider(), [
    'twig.options' => [
        'strict_variables' => FALSE,
    ]
]);
$app -> register(new HttpFragmentServiceProvider());
$app -> register(new DoctrineOrmManagerRegistryProvider());
$app['twig'] = $app -> extend('twig', function($twig, $app) {
    // add custom globals, filters, tags, ...

    $twig -> addFunction(new \Twig_SimpleFunction('asset', function($asset) use ($app) {
        return $app['request_stack'] -> getMasterRequest() -> getBasepath() . '/' . ltrim($asset, '/');
    }));

    $twig -> addFunction(new \Twig_SimpleFunction('baseUrl', function() use ($app) {
        return $app['request_stack'] -> getMasterRequest() -> getSchemeAndHttpHost();
    }));

    return $twig;
});
$app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem',
    function (\Twig_Loader_Filesystem $twigLoaderFilesystem) {
        $twigLoaderFilesystem->addPath(__DIR__.'/../templates', 'MetaCat');

        return $twigLoaderFilesystem;
    }
);
$app->register(new AsseticTwigProvider(), array(
    'assetic.asset.root' => __DIR__.'/..',
    'assetic.asset.asset_root' => __DIR__.'/../web/assets',
));
//set directory for config files
$app['config.dir'] = __DIR__.'/../config/';
$app -> register(new YamlConfigServiceProvider($app['config.dir'] . 'config.yml', [
    'basepath' => realpath(__DIR__.'/..')
]));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => $app['config']['dbs']
));

$app->register(new DoctrineOrmServiceProvider, array(
    'orm.ems.options' => array(
       'psql' => array(
           'connection' => 'psql',
           'mappings' => array(
                // XML/YAML driver (Symfony2 style)
                // Mapping files can be named like Foo.orm.yml
                array(
                    'type' => 'simple_yml',
                    'namespace' => 'MetaCat\Entity',
                    'path' => __DIR__.'/MetaCat/Entity/',
                    'alias' => 'P',
                    //'use_simple_annotation_reader' => false
                ),
           ),
           'types' => array(
                'jsonb' => 'Boldtrn\JsonbBundle\Types\JsonbArrayType',
                'xml' => 'Doctrine\\DBAL\\PostgresTypes\\XmlType'
           )
       )
    ),
    'orm.custom.functions.string' => array(
        'JSONB_AG' => 'Boldtrn\JsonbBundle\Query\JsonbAtGreater',
        'JSONB_HGG' => 'Boldtrn\JsonbBundle\Query\JsonbHashGreaterGreater',
        'JSONB_EX' => 'Boldtrn\JsonbBundle\Query\JsonbExistence',
    ),
    'orm.proxies_dir' => __DIR__.'/../var/cache/doctrine/proxies',
));
$app['orm.em']->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('jsonb','jsonb');

$app->register(new MetaCat\Service\ImportDbalService, array());

$app['aws'] = new Aws\Sdk(
    $app['config']['aws']
);

return $app;
