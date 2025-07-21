<?php
declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SyncStationsCommandTest extends KernelTestCase
{
    public function testSyncStationsCommandOutputsSuccessMessage(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:sync-stations');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Stations synchronized successfully', $output);
    }
}
