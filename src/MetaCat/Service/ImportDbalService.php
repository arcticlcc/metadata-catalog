<?php

namespace MetaCat\Service;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Ddeboer\DataImport\Workflow;
use Ddeboer\DataImport\Reader\DbalReader;
use Ddeboer\DataImport\Writer\DoctrineWriter;
use Ddeboer\DataImport\ValueConverter\StringToDateTimeValueConverter;
use Ddeboer\DataImport\ValueConverter\CallbackValueConverter;
use Ddeboer\DataImport\ItemConverter\MappingItemConverter;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Id\UuidGenerator;

class ImportDbalService implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['import.dbal'] = $app->protect(function ($conn = 'import', array $tables = ['project', 'product']) use ($app) {
            //database connections
            $em = $app['orm.em'];
            $dbal = $app['dbs'][$conn];

            foreach ($tables as $table) {
                $class = 'MetaCat\Entity\\' . ucfirst($table);
                //grab columns
                $metadata = $em->getClassMetadata($class);
                $columns = $metadata->getColumnNames();
                //add column for related entity
                if($table === 'product') {
                    $columns['project'] = 'projectid AS project';
                }

                $sql = 'SELECT ' . implode(', ', $columns) . " FROM $table;";

                //Preserve ids
                $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
                $metadata->setIdGenerator(new AssignedGenerator());

                // Create and configure the reader
                $reader = new DbalReader(
                    $dbal, // Instance of \Doctrine\DBAL\Connection
                    $sql
                );

                // Create the workflow from the reader
                $workflow = new Workflow($reader, isset($app['monolog']) ? $app['monolog'] : NULL);

                // Create a writer: you need Doctrine’s EntityManager.
                $doctrineWriter = new DoctrineWriter($em, $class);
                $workflow->addWriter($doctrineWriter);

                //set related entity
                if($table === 'product') {
                    $converter = new CallbackValueConverter(function($item) use ($em) {
                        $val = $em->getReference('MetaCat\Entity\Project', $item);
                        return $val;
                    });
                    $workflow->addValueConverter('project', $converter);
                }

                // Add a converter to the workflow that will convert `beginDate` and `endDate`
                // to \DateTime objects
                /*$dateTimeConverter = new StringToDateTimeValueConverter('Ymd');
                $workflow
                    ->addValueConverter('beginDate', $dateTimeConverter)
                    ->addValueConverter('endDate', $dateTimeConverter);*/
                $converter = new CallbackValueConverter(function($item){
                    $val = json_decode($item);
                    return $val;
                });
                $workflow->addValueConverter('json', $converter);
                // Process the workflow
                $result[]  = $workflow->process();

                //Preserve ids
                //$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_UUID);
                //$metadata->setIdGenerator(new UuidGenerator());
            }

        return $result;

        });
    }
}
