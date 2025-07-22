<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StationSyncService
{
    private const BASE_URL = 'https://data.gov.lv/dati/lv/api/action/datastore_search';
    private const RESOURCE_ID = 'c32c7afd-0d05-44fd-8b24-1de85b4bf11d';
    private const LIMIT = 1000;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly EntityManagerInterface $em
    ) {}

    public function syncStations(): int
    {
        $response = $this->client->request('GET', self::BASE_URL, [
            'query' => [
                'resource_id' => self::RESOURCE_ID,
                'limit' => self::LIMIT,
            ]
        ]);

        $data = $response->toArray(false);
        $records = $data['result']['records'] ?? [];

        return $this->em->wrapInTransaction(function ($em) use ($records): int {
            $fetchedIds = [];
            
            foreach ($records as $record) {
                // Skip if imported primary key is missing
                if (!isset($record['_id'])) {
                    continue;
                }

                $fetchedIds[] = $record['_id'];

                $station = $this->em->getRepository(Station::class)->findOneBy(['_id' => $record['_id']]) ?? new Station();

                $station->set_Id($record['_id'] ?? '');
                $station->setStationId($record['STATION_ID'] ?? '');
                $station->setName($record['NAME'] ?? '');
                $station->setWmoId(isset($record['WMO_ID']) && trim($record['WMO_ID']) ? $record['WMO_ID'] : null);
                
                $station->setBeginDate(isset($record['BEGIN_DATE']) && trim((string)$record['BEGIN_DATE']) 
                    ? new \DateTime($record['BEGIN_DATE']) 
                    : null
                );

                $station->setEndDate(isset($record['END_DATE']) && trim((string)$record['END_DATE'])
                    ? new \DateTime($record['END_DATE']) 
                    : null
                );
                
                $station->setLatitude(isset($record['LATITUDE']) && trim((string)$record['LATITUDE']) 
                    ? (int) $record['LATITUDE'] 
                    : null
                );

                $station->setLongitude(isset($record['LONGITUDE']) && trim((string)$record['LONGITUDE']) 
                    ? (int) $record['LONGITUDE'] 
                    : null
                );

                $station->setGauss1(isset($record['GAUSS1']) && trim((string)$record['GAUSS1']) 
                    ? (string)$record['GAUSS1'] 
                    : null
                );

                $station->setGauss2(isset($record['GAUSS2']) && trim((string)$record['GAUSS2'])
                    ? (string)$record['GAUSS2'] 
                    : null
                );

                $station->setGeogr1(isset($record['GEOGR1']) && trim((string)$record['GEOGR1']) 
                    ? (string)$record['GEOGR1'] 
                    : null
                );
                
                $station->setGeogr2(isset($record['GEOGR2']) && trim((string)$record['GEOGR2'])
                    ? (string)$record['GEOGR2'] 
                    : null
                );
                
                $station->setElevation(isset($record['ELEVATION']) && trim((string)$record['ELEVATION']) 
                    ? (string)$record['ELEVATION'] 
                    : null
                );
                
                $station->setElevationPressure(isset($record['ELEVATION_PRESSURE']) && trim((string)$record['ELEVATION_PRESSURE']) 
                    ? (string)$record['ELEVATION_PRESSURE'] 
                    : null
                );

                $this->em->persist($station);
            }

            // Delete stations not present in API data
            $qb = $this->em->createQueryBuilder();
            $qb->delete(Station::class, 's')
                ->where($qb->expr()->notIn('s._id', ':ids'))
                ->setParameter('ids', $fetchedIds);

            $qb->getQuery()->execute();

            $this->em->flush();

            return count($fetchedIds);
        });
    }

    public function hasStationsSynced(): bool
    {
        return $this->em->getRepository(Station::class)->count([]) > 0;
    }
}
