<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\StationRepository;
use App\Service\StationSyncService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/stations')]
class StationController extends AbstractController
{
    private const BASE_URL = 'https://data.gov.lv/dati/lv/api/action/datastore_search';

    private const RESOURCE_ID = 'c32c7afd-0d05-44fd-8b24-1de85b4bf11d';
    
    private const LIMIT = 1000;

    public function __construct(
        private readonly StationRepository $stationRepository,
        private readonly StationSyncService $stationSyncService
    )
    {
    }

    #[Route('', methods: ['GET'])]
    public function listStations(): JsonResponse
    {
        // Lazy sync if DB is empty
        if (!$this->stationSyncService->hasStationsSynced()) {
            try {
                $this->stationSyncService->syncStations();
            } catch (\Throwable $e) {
                return $this->json([
                    'error' => 'Failed to sync station data',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }
        }

        // Fetch from local DB
        $stations = $this->stationRepository->findAll();

        $collection = new ArrayCollection($stations);

        $stations = $collection->map(function ($station) {
            return [
                'Station_id' => $station->getStationId(),
                'Name' => $station->getName(),
            ];
        })->toArray();

        return $this->json($stations);
    }

    #[Route('/{station_id}', methods: ['GET'])]
    public function stationDetail(string $station_id): JsonResponse
    {
        // Ensure sync before lookup
        if (!$this->stationSyncService->hasStationsSynced()) {
            try {
                $this->stationSyncService->syncStations();
            } catch (\Throwable $e) {
                return $this->json([
                    'error' => 'Failed to sync station data',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }
        }

        $station = $this->stationRepository->findOneBy(['stationId' => $station_id]);

        if (!$station) {
            return $this->json(['error' => 'Station not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            '_Id' => $station->get_Id(),
            'Station_id' => $station->getStationId(),
            'Name' => $station->getName(),
            'Wmo_id' => $station->getWmoId(),
            'Begin_date' => $station->getBeginDate()?->format('c'),
            'End_date' => $station->getEndDate()?->format('c'),
            'Latitude' => $station->getLatitude(),
            'Longitude' => $station->getLongitude(),
            'Gauss1' => $station->getGauss1(),
            'Gauss2' => $station->getGauss2(),
            'Geogr1' => $station->getGeogr1(),
            'Geogr2' => $station->getGeogr2(),
            'Elevation' => $station->getElevation(),
            'Elevation_pressure' => $station->getElevationPressure(),
        ]);
    }
}
