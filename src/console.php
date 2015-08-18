<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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

//$console = new Application('My Silex Application', 'n/a');
$console = $app['console'];
$console->getDefinition()->addOption(new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'dev'));
$console->setDispatcher($app['dispatcher']);
$helperSet = $console->getHelperSet();
$helperSet->set(new ManagerRegistryHelper($app['doctrine']), 'doctrine');

$console
    ->register('my-command')
    ->setDefinition(array(
        // new InputOption('some-option', null, InputOption::VALUE_NONE, 'Some help'),
    ))
    ->setDescription('My command description')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($app) {
        // do something
    })
;
$console->add(new ClearMetadataCacheDoctrineCommand);
$console->add(new ClearQueryCacheDoctrineCommand);
$console->add(new ClearResultCacheDoctrineCommand);
$console->add(new ConvertMappingDoctrineCommand);
$console->add(new CreateSchemaDoctrineCommand);
$console->add(new DropSchemaDoctrineCommand);
$console->add(new EnsureProductionSettingsDoctrineCommand);
$console->add(new InfoDoctrineCommand);
$console->add(new RunDqlDoctrineCommand);
$console->add(new RunSqlDoctrineCommand);
$console->add(new UpdateSchemaDoctrineCommand);
$console->add(new ValidateSchemaCommand);
$console->add(new AsseticDumpCommand)->addArgument('app', null, '',$app);
//return $console;
