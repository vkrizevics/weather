<?php
declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SyncStationsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SyncStationsCommandTest extends TestCase
{
    public function testSyncStationsCommandOutputsSuccessMessage(): void
    {
        // Mock response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn([
            'result' => [
                'records' => [[
                    '_id' => 2,
                    'STATION_ID' => 'SIGULDA',
                    'NAME' => 'Sigulda',
                    'WMO_ID' => '',
                    'BEGIN_DATE' => '1939-01-03T00:00:00',
                    'END_DATE' => '3999-12-31T23:59:00',
                    'LATITUDE' => 570954,
                    'LONGITUDE' => 245112,
                    'GAUSS1' => '551605.75',
                    'GAUSS2' => '336076.09',
                    'GEOGR1' => 24.8533,
                    'GEOGR2' => 57.165,
                    'ELEVATION' => 100.15,
                    'ELEVATION_PRESSURE' => '',
                ]]
            ]
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($mockResponse);

        // Mock StationRepository
        $stationRepo = $this->createMock(ObjectRepository::class);
        $stationRepo->method('findOneBy')->willReturn(null); // simulate new insert

        // Mock EntityManager
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($stationRepo);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        // Create and test command
        $command = new SyncStationsCommand($httpClient, $em);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:sync-stations'));
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Stations synchronized successfully', $output);
    }
}
