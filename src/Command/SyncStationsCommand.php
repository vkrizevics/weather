<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncStationsCommand extends Command
{
    // Set the command name
    protected static $defaultName = 'app:sync-stations';

    protected function configure(): void
    {
        $this->setDescription('Sync stations from an external API');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // For now, just return a success message
        $output->writeln('Stations synchronized successfully');

        return Command::SUCCESS;
    }
}
