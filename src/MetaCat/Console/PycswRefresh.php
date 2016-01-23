<?php

namespace MetaCat\Console;

use Saxulum\Console\Command\AbstractPimpleCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PycswRefresh extends AbstractPimpleCommand
{
    protected function configure()
    {
        $this
            ->setName('pycsw:view:refresh')
            ->setDescription('Refresh the materialized view for CSW support.')
            ->addOption(
                'concurrently',
                'c',
                InputOption::VALUE_NONE
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->container;
        $concurrently = $input->getOption('concurrently') ? 'CONCURRENTLY' : '';

        try {
            $output->writeln('Refreshing view...');
            $app['db']->exec("REFRESH MATERIALIZED VIEW $concurrently records;");
            $output->writeln('View "records" refreshed successfully.');
        } catch (\Exception $exc) {
            $app['monolog']->addError($exc->getMessage());
            $output->writeln("<error>ERROR: {$exc->getMessage()}</>");
        }
    }
}
