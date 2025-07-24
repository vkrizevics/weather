<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\Station;
use App\Repository\StationRepository;
use App\Service\StationSyncService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StationControllerTest extends WebTestCase
{
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

    public function testListStationsReturnsSyncError(): void
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

    public function testStationDetailReturnsStationFromDatabase(): void
    {
        $station = $this->createMockStation('42', 'Test Station 42');

        $mockRepo = $this->createMock(StationRepository::class);
        $mockRepo->method('findOneBy')->willReturn($station);

        $mockSyncService = $this->createMock(StationSyncService::class);
        $mockSyncService->method('hasStationsSynced')->willReturn(true);

        $client = static::createClient();

        self::getContainer()->set(StationRepository::class, $mockRepo);
        self::getContainer()->set(StationSyncService::class, $mockSyncService);

        $client->request('GET', '/api/stations/42', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $_ENV['API_TOKEN'],
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('42', $data['Station_id']);
        $this->assertEquals('Test Station 42', $data['Name']);
    }

    
    private function createMockStation(string $id, string $name)
    {
        $station = $this->createMock(Station::class);
        $station->method('getStationId')->willReturn($id);
        $station->method('getName')->willReturn($name);
        
        return $station;
    }

}
