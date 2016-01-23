<?php

namespace MetaCat\Console;

use Saxulum\Console\Command\AbstractPimpleCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PycswCreate extends AbstractPimpleCommand
{
    protected function configure()
    {
        $this
            ->setName('pycsw:view:create')
            ->setDescription('Create the materialized view for CSW support.')
            ->addOption(
                'drop',
                'd',
                InputOption::VALUE_NONE,
                'Drop the view first.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->container;
        $drop = $input->getOption('drop');

        try {
            $output->writeln('Creating view...');
            $app['db']->beginTransaction();

            if ($drop) {
                $app['db']->exec('DROP MATERIALIZED VIEW records;');
            }

            $sql = file_get_contents($app['config.dir'] . '/cws.sql');
            $app['db']->exec($sql);
            $app['db']->commit();

            $output->writeln('View "records" created successfully.');
        } catch (\Exception $exc) {
            $app['monolog']->addError($exc->getMessage());
            $output->writeln("<error>ERROR: {$exc->getMessage()}</>");
        }
    }
}
