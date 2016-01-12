<?php

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Saxulum\DoctrineOrmCommands\Command\CreateDatabaseDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\DropDatabaseDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\ClearMetadataCacheDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\ClearQueryCacheDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\ClearResultCacheDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\ConvertMappingDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\CreateSchemaDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\DropSchemaDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\EnsureProductionSettingsDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\InfoDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\RunDqlDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\RunSqlDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\UpdateSchemaDoctrineCommand;
use Saxulum\DoctrineOrmCommands\Command\Proxy\ValidateSchemaCommand;
use Saxulum\DoctrineOrmCommands\Helper\ManagerRegistryHelper;
use Saxulum\AsseticTwig\Command\AsseticDumpCommand;
use MetaCat\Console\MetadataImportDbal;

$app['console'] = $app->extend('console', function (ConsoleApplication $consoleApplication) use ($app) {
    $consoleApplication->setAutoExit(false);
    $consoleApplication->setName('Metadata Catalog Console');
    $consoleApplication->setVersion('n/a');
    $consoleApplication->getDefinition()->addOption(
        new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev')
    );
    $helperSet = $consoleApplication->getHelperSet();
    $helperSet->set(new ManagerRegistryHelper($app['doctrine']), 'doctrine');

    return $consoleApplication;
});

$app['console.commands'] = $app->extend('console.commands', function ($commands) use ($app) {
    $commands[] = new CreateDatabaseDoctrineCommand();
    $commands[] = new DropDatabaseDoctrineCommand();
    $commands[] = new CreateSchemaDoctrineCommand();
    $commands[] = new UpdateSchemaDoctrineCommand();
    $commands[] = new DropSchemaDoctrineCommand();
    $commands[] = new RunDqlDoctrineCommand();
    $commands[] = new RunSqlDoctrineCommand();
    $commands[] = new ConvertMappingDoctrineCommand();
    $commands[] = new ClearMetadataCacheDoctrineCommand();
    $commands[] = new ClearQueryCacheDoctrineCommand();
    $commands[] = new ClearResultCacheDoctrineCommand();
    $commands[] = new InfoDoctrineCommand();
    $commands[] = new ValidateSchemaCommand();
    $commands[] = new EnsureProductionSettingsDoctrineCommand();
    $commands[] = new AsseticDumpCommand();

    $command = new MetadataImportDbal();
    $command->setContainer($app);
    $commands[] = $command;

    return $commands;
});

/*$console = $app['console'];
$console
    ->register('my-command')
    ->setDefinition(array(
        // new InputOption('some-option', null, InputOption::VALUE_NONE, 'Some help'),
    ))
    ->setDescription('My command description')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        // do something
});*/
