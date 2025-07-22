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
            
            $station->setBeginDate(isset($record['BEGIN_DATE']) && $record['BEGIN_DATE'] > 0 
                ? new \DateTime($record['BEGIN_DATE']) 
                : null
            );

            $station->setEndDate(isset($record['END_DATE']) && $record['END_DATE'] > 0 
                ? new \DateTime($record['END_DATE']) 
                : null
            );
            
            $station->setLatitude(isset($record['LATITUDE']) && $record['LATITUDE'] > 0 
                ? (int) $record['LATITUDE'] 
                : null
            );

            $station->setLongitude(isset($record['LONGITUDE']) && $record['LONGITUDE'] > 0 
                ? (int) $record['LONGITUDE'] 
                : null
            );

            $station->setGauss1(isset($record['GAUSS1']) && $record['GAUSS1'] > 0 
                ? (string)$record['GAUSS1'] 
                : null
            );

            $station->setGauss2(isset($record['GAUSS2']) && $record['GAUSS2'] > 0 
                ? (string)$record['GAUSS2'] 
                : null
            );

            $station->setGeogr1(isset($record['GEOGR1']) && $record['GEOGR1'] > 0 
                ? (string)$record['GEOGR1'] 
                : null
            );
            
            $station->setGeogr2(isset($record['GEOGR2']) && $record['GEOGR2'] > 0 
                ? (string)$record['GEOGR2'] 
                : null
            );
            
            $station->setElevation(isset($record['ELEVATION']) && $record['ELEVATION'] > 0 
                ? (string)$record['ELEVATION'] 
                : null
            );
            
            $station->setElevationPressure(isset($record['ELEVATION_PRESSURE']) && $record['ELEVATION_PRESSURE'] > 0 
                ? (string)$record['ELEVATION_PRESSURE'] 
                : null
            );

            $this->em->persist($station);
        }

        $this->em->flush();

        $output->writeln('<info>Stations synchronized successfully.</info>');

        return Command::SUCCESS;
    }
}
