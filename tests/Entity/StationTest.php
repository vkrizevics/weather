<?php

namespace App\Tests\Entity;

use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback(); // Undo changes
        parent::tearDown();
    }

    public function testStationEntityPersistence(): void
    {
        $beginDate = new \DateTimeImmutable('2020-01-01');
        $endDate = new \DateTimeImmutable('2025-01-01');

        $station = new Station();
        $station
            ->set_Id(123)
            ->setStationId('LV001')
            ->setName('Riga Central')
            ->setWmoId('WMO123')
            ->setBeginDate($beginDate)
            ->setEndDate($endDate)
            ->setLatitude(56)
            ->setLongitude(24)
            ->setGauss1('123.45')
            ->setGauss2('543.21')
            ->setGeogr1('56.123456')
            ->setGeogr2('24.654321')
            ->setElevation('25.50')
            ->setElevationPressure('24.10');

        $this->entityManager->persist($station);
        $this->entityManager->flush();
        $this->entityManager->refresh($station);

        $this->assertNotNull($station->getId());
        $this->assertSame(123, $station->get_Id());
        $this->assertSame('LV001', $station->getStationId());
        $this->assertSame('Riga Central', $station->getName());
        $this->assertSame('WMO123', $station->getWmoId());
        $this->assertEquals($beginDate, $station->getBeginDate());
        $this->assertEquals($endDate, $station->getEndDate());
        $this->assertSame(56, $station->getLatitude());
        $this->assertSame(24, $station->getLongitude());
        $this->assertSame('123.45', $station->getGauss1());
        $this->assertSame('543.21', $station->getGauss2());
        $this->assertSame('56.123456', $station->getGeogr1());
        $this->assertSame('24.654321', $station->getGeogr2());
        $this->assertSame('25.50', $station->getElevation());
        $this->assertSame('24.10', $station->getElevationPressure());
    }
}
