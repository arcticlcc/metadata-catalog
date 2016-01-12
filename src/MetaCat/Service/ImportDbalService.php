<?php

namespace MetaCat\Service;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Ddeboer\DataImport\Workflow;
use Ddeboer\DataImport\Reader\DbalReader;
use Ddeboer\DataImport\Writer\DoctrineWriter;
use Ddeboer\DataImport\ValueConverter\CallbackValueConverter;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Id\AssignedGenerator;

class ImportDbalService implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['import.dbal'] = $app->protect(function (
            $conn = 'import',
            array $tables = ['project', 'product']
        ) use ($app) {
            //database connections
            $em = $app['orm.em'];
            $dbal = $app['dbs'][$conn];

            foreach ($tables as $table) {
                $class = 'MetaCat\Entity\\'.ucfirst($table);
                //grab columns
                $metadata = $em->getClassMetadata($class);
                $columns = $metadata->getColumnNames();
                //add column for related entity
                if ($table === 'product') {
                    $columns['project'] = 'projectid AS project';
                }

                $sql = 'SELECT '.implode(', ', $columns)." FROM $table;";

                //Preserve ids
                $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
                $metadata->setIdGenerator(new AssignedGenerator());

                // Create and configure the reader
                $reader = new DbalReader(
                    $dbal, // Instance of \Doctrine\DBAL\Connection
                    $sql
                );

                // Create the workflow from the reader
                $workflow = new Workflow($reader, isset($app['monolog']) ? $app['monolog'] : null);

                // Create a writer: you need Doctrine’s EntityManager.
                $doctrineWriter = new DoctrineWriter($em, $class);
                $workflow->addWriter($doctrineWriter);

                //set related entity
                if ($table === 'product') {
                    $converter = new CallbackValueConverter(function ($item) use ($em) {
                        $val = $item ? $em->getReference('MetaCat\Entity\Project', $item) : null;

                        return $val;
                    });
                    $workflow->addValueConverter('project', $converter);
                }

                $converter = new CallbackValueConverter(function ($item) {
                    $val = json_decode($item);

                    return $val;
                });
                $workflow->addValueConverter('json', $converter);
                // Process the workflow
                $result[] = $workflow->process();

                //invalidate cache
                $em->getConfiguration()->getResultCacheImpl()->delete("mc_{$table}_owner");
                //touch update
                touch($app['config.dir'] . '../var/data/update');
            }

            return $result;

        });
    }
}
