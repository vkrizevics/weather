<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\StationSyncService;
use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class StationSyncServiceTest extends TestCase
{
    public function testHasStationsSyncedReturnsTrueWhenCountGreaterThanZero(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('count')->willReturn(5);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->with(Station::class)->willReturn($repoMock);

        $httpClientStub = $this->createStub(\Symfony\Contracts\HttpClient\HttpClientInterface::class);

        $service = new StationSyncService($httpClientStub, $emMock);

        $this->assertTrue($service->hasStationsSynced());
    }

    public function testHasStationsSyncedReturnsFalseWhenCountIsZero(): void
    {
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('count')->willReturn(0);

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->with(Station::class)->willReturn($repoMock);

        $httpClientStub = $this->createStub(\Symfony\Contracts\HttpClient\HttpClientInterface::class);

        $service = new StationSyncService($httpClientStub, $emMock);

        $this->assertFalse($service->hasStationsSynced());
    }
}
