<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SyncStationsCommand;
use App\Entity\Station;
use App\Service\StationSyncService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
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
        // Step 1: Mock the API HTTP response
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

        // Step 2: Mock Station repository
        $stationRepo = $this->createMock(ObjectRepository::class);
        $stationRepo->method('findOneBy')->willReturn(null);

        // Step 3: Mock Query and QueryBuilder for deletion
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn(1);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('delete')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);
        $queryBuilder->method('expr')->willReturn(
            $this->getMockBuilder(Expr::class)->disableOriginalConstructor()->getMock()
        );

        // Step 4: Mock the real EntityManager with wrapInTransaction()
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository', 'persist', 'flush', 'createQueryBuilder', 'wrapInTransaction'])
            ->getMock();

        $em->method('getRepository')->willReturn($stationRepo);
        $em->method('createQueryBuilder')->willReturn($queryBuilder);

        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Station::class));
        $em->expects($this->once())->method('flush');

        $em->method('wrapInTransaction')->willReturnCallback(function (callable $callback) use ($em) {
            return $callback($em);
        });

        // Step 5: Construct service and command
        $stationSyncService = new StationSyncService($httpClient, $em);
        $command = new SyncStationsCommand($stationSyncService);

        // Step 6: Run command using CommandTester
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:sync-stations'));
        $commandTester->execute([]);

        // Step 7: Assert expected output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Synchronized 1 station(s) successfully.', $output);
    }
}
