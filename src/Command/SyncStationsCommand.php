<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\StationSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-stations',
    description: 'Fetch and sync station data from the API'
)]
class SyncStationsCommand extends Command
{
    public function __construct(
        private readonly StationSyncService $stationSyncService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $syncedCount = $this->stationSyncService->syncStations();

            $output->writeln(sprintf('<info>Synchronized %d station(s) successfully.</info>', $syncedCount));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error occurred during station sync: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
