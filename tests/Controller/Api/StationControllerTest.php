<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\Station;
use App\Repository\StationRepository;
use App\Service\StationSyncService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StationControllerTest extends WebTestCase
{
    public function testListStationsReturns401IfNoToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/stations/', [], [], [
            'HTTP_Authorization' => 'ONE BEER ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Authentication failed'], $data);
    }

    public function testListStationsReturns401IfWrongToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/stations/', [], [], [
            'HTTP_Authorization' => 'Bearer WRONG TOKEN',
        ]);

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Authentication failed'], $data);
    }

    public function testListStationsReturnsFormattedJson(): void
    {
        $stations = [
            $this->createMockStation('001', 'Riga Central'),
            $this->createMockStation('002', 'Daugavpils'),
        ];

        // Mock the StationRepository
        $mockRepo = $this->createMock(StationRepository::class);
        $mockRepo->method('findAll')->willReturn($stations);

        // Mock the StationSyncService
        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(true);

        $client = static::createClient();

        // Replace services in container
        self::getContainer()->set(StationRepository::class, $mockRepo);
        self::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $expected = [
            ['Station_id' => '001', 'Name' => 'Riga Central'],
            ['Station_id' => '002', 'Name' => 'Daugavpils'],
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            $client->getResponse()->getContent()
        );
    }

    public function testListStationsReturnsSyncedStations(): void
    {
        $stations = [
            $this->createMockStation('001', 'Riga Central'),
            $this->createMockStation('002', 'Daugavpils'),
        ];

        // Mock the StationRepository
        $mockRepo = $this->createMock(StationRepository::class);
        $mockRepo->method('findAll')->willReturn($stations);

        // Mock the StationSyncService
        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(false);

        $client = static::createClient();

        // Replace services in container
        self::getContainer()->set(StationRepository::class, $mockRepo);
        self::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $expected = [
            ['Station_id' => '001', 'Name' => 'Riga Central'],
            ['Station_id' => '002', 'Name' => 'Daugavpils'],
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            $client->getResponse()->getContent()
        );
    }

    public function testListStationsReturns503OnSyncFailure(): void
    {
        $client = static::createClient();

        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(false);
        $mockSyncService->method('syncStations')->willThrowException(new \Exception('sync failed'));

        // Override the service in the container
        static::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertEquals(503, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Failed to sync station data']),
            $client->getResponse()->getContent()
        );
    }

    public function testStationDetailReturnsStationFromDatabase(): void
    {
        $station = $this->createMockStation('SIGULDA42', 'Test Station 42');

        $mockRepo = $this->createMock(StationRepository::class);
        $mockRepo->method('findOneBy')->willReturn($station);

        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(true);

        $client = static::createClient();

        self::getContainer()->set(StationRepository::class, $mockRepo);
        self::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations/SIGULDA42', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('SIGULDA42', $data['Station_id']);
        $this->assertEquals('Test Station 42', $data['Name']);
    }

    public function testStationDetailReturnsSyncedStation(): void
    {
        $station = $this->createMockStation('SIGULDA43', 'Test Station 43');

        $mockRepo = $this->createMock(StationRepository::class);
        $mockRepo->method('findOneBy')->willReturn($station);

        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(false);

        $client = static::createClient();

        self::getContainer()->set(StationRepository::class, $mockRepo);
        self::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations/SIGULDA43', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('SIGULDA43', $data['Station_id']);
        $this->assertEquals('Test Station 43', $data['Name']);
    }

    public function testStationDetailReturns503OnSyncFailure(): void
    {
        $client = static::createClient();

        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(false);
        $mockSyncService->method('syncStations')->willThrowException(new \Exception('sync failed'));

        // Override the service in the container
        static::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations/SIGULDA44', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertEquals(503, $client->getResponse()->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['error' => 'Failed to sync station data']),
            $client->getResponse()->getContent()
        );
    }

    public function testStationDetailReturns404IfStationNotFound(): void
    {
        $mockRepo = $this->createMock(StationRepository::class);
        $mockRepo->method('findOneBy')->willReturn(null); // Simulate not found

        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(true);

        $client = static::createClient();

        self::getContainer()->set(StationRepository::class, $mockRepo);
        self::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations/SIGULDA999', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseStatusCodeSame(404);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Station not found'], $data);
    }
    
    private function createMockStation(string $id, string $name)
    {
        $station = $this->createMock(Station::class);
        $station->method('getStationId')->willReturn($id);
        $station->method('getName')->willReturn($name);
        
        return $station;
    }

}
