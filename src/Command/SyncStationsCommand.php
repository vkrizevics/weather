<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:sync-stations',
    description: 'Fetch and sync station data from the API'
)]
class SyncStationsCommand extends Command
{
    private const BASE_URL = 'https://data.gov.lv/dati/lv/api/action/datastore_search';

    private const RESOURCE_ID = 'c32c7afd-0d05-44fd-8b24-1de85b4bf11d';
    
    private const LIMIT = 1000;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->client->request('GET', self::BASE_URL, [
            'query' => [
                'resource_id' => self::RESOURCE_ID,
                'limit' => self::LIMIT,
            ]
        ]);

        $data = $response->toArray(false);
        $records = $data['result']['records'] ?? [];

        foreach ($records as $record) {
            // Skip if required fields are missing
            if (!isset($record['_id'], $record['STATION_ID'], $record['NAME'], $record['BEGIN_DATE'])) {
                continue;
            }

            $station = $this->em->getRepository(Station::class)->findOneBy(['_id' => $record['_id']]) ?? new Station();

            $station->set_Id($record['_id'] ?? '');
            $station->setStationId($record['STATION_ID'] ?? '');
            $station->setName($record['NAME'] ?? '');
            $station->setWmoId($record['WMO_ID'] ?? null);
            $station->setBeginDate(!empty($record['BEGIN_DATE']) ? new \DateTime($record['BEGIN_DATE']) : null);
            $station->setEndDate(!empty($record['END_DATE']) ? new \DateTime($record['END_DATE']) : null);
            $station->setLatitude(!empty($record['LATITUDE']) ? (int) $record['LATITUDE'] : null);
            $station->setLongitude(!empty($record['LONGITUDE']) ? (int) $record['LONGITUDE'] : null);
            $station->setGauss1(!empty($record['GAUSS1']) ? (string)$record['GAUSS1'] : null);
            $station->setGauss2(!empty($record['GAUSS2']) ? (string)$record['GAUSS2'] : null);
            $station->setGeogr1(!empty($record['GEOGR1']) ? (string)$record['GEOGR1'] : null);
            $station->setGeogr2(!empty($record['GEOGR2']) ? (string)$record['GEOGR2'] : null);
            $station->setElevation(!empty($record['ELEVATION']) ? (string)$record['ELEVATION'] : null);
            $station->setElevationPressure(!empty($record['ELEVATION_PRESSURE']) ? (string)$record['ELEVATION_PRESSURE'] : null);

            $this->em->persist($station);
        }

        $this->em->flush();

        $output->writeln('<info>Stations synchronized successfully.</info>');

        return Command::SUCCESS;
    }
}
