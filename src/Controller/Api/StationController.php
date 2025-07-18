<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/stations')]
class StationController extends AbstractController
{
    private const BASE_URL = 'https://data.gov.lv/dati/lv/api/action/datastore_search';

    private const RESOURCE_ID = 'c32c7afd-0d05-44fd-8b24-1de85b4bf11d';
    
    private const LIMIT = 1000;

    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('', methods: ['GET'])]
    public function listStations(): JsonResponse
    {
        $response = $this->client->request('GET', self::BASE_URL, [
            'query' => [
                'resource_id' => self::RESOURCE_ID,
                'limit' => self::LIMIT,
            ]
        ]);

        $data = $response->toArray();
        $records = $data['result']['records'] ?? [];

        $collection = new ArrayCollection($records);

        $stations = $collection->map(function ($record) {
            return [
                'Station_id' => $record['STATION_ID'] ?? null,
                'Name' => $record['NAME'] ?? null,
            ];
        })->toArray();

        return $this->json($stations);
    }
}
