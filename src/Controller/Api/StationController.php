<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/stations')]
class StationController extends AbstractController
{
    private const BASE_URL = 'https://data.gov.lv/dati/lv/api/action/datastore_search';

    private const RESOURCE_ID = 'c32c7afd-0d05-44fd-8b24-1de85b4bf11d';
    
    private const LIMIT = 1000;

    public function __construct(private readonly HttpClientInterface $client)
    {
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

    #[Route('/{station_id}', methods: ['GET'])]
    public function stationDetail(string $station_id): JsonResponse
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL, [
                'query' => [
                    'resource_id' => self::RESOURCE_ID,
                    'filters' => json_encode([
                        'STATION_ID' => [$station_id],
                    ]),
                    'limit' => self::LIMIT,
                ]
            ]);

            $data = $response->toArray(false);

            $records = $data['result']['records'] ?? [];

            if (!$records) {
                return $this->json(['error' => 'Station not found'], Response::HTTP_NOT_FOUND);
            }

            return $this->json($records[0]); // return the first (and likely only) match

        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Failed to fetch station data',
                'message' => $e->getMessage()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
