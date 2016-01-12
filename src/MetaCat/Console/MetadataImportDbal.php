<?php

namespace MetaCat\Console;

use Saxulum\Console\Command\AbstractPimpleCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MetadataImportDbal extends AbstractPimpleCommand
{
    protected function configure()
    {
        $this
            ->setName('metadata:import:dbal')
            ->setDescription('Import from a DBAL source.')
            ->addOption(
                'tables',
                't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Which tables(s) to import?',
                array('project', 'product')
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED,
                'Name of the DBAL connection (from config/db.yml).',
                'import'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->container;
        $tables = $input->getOption('tables');
        $conn = $input->getOption('connection');

        try {
            $output->writeln('Importing <fg=white;bg=blue>'.
                implode(', ', $tables)."</> from <options=bold>$conn</>.");
            $app['import.dbal']($conn, $tables);
            $output->writeln('Import complete.');
        } catch (\Exception $exc) {
            $app['monolog']->addError($exc->getMessage());
            $output->writeln("<error>ERROR: {$exc->getMessage()}</>");
        }
    }
}
