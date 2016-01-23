<?php

namespace MetaCat\Console;

use Saxulum\Console\Command\AbstractPimpleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PycswDrop extends AbstractPimpleCommand
{
    protected function configure()
    {
        $this
            ->setName('pycsw:view:drop')
            ->setDescription('Drop the materialized view for CSW support.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->container;

        try {
            $output->writeln('Dropping view...');
            $app['db']->beginTransaction();
            $app['db']->exec('DROP MATERIALIZED VIEW records;');
            $app['db']->commit();
            $output->writeln('View "records" dropped successfully.');
        } catch (\Exception $exc) {
            $app['monolog']->addError($exc->getMessage());
            $output->writeln("<error>ERROR: {$exc->getMessage()}</>");
        }
    }
}
