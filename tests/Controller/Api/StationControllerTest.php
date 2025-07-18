<?php
namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StationControllerTest extends WebTestCase
{
    public function testListStationsReturnsFormattedJson(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'result' => [
                'records' => [
                    ['ID' => '001', 'NAME' => 'Riga Central'],
                    ['ID' => '002', 'NAME' => 'Daugavpils'],
                ]
            ]
        ]));

        $mockClient = $this->createMock(HttpClientInterface::class);
        $mockClient->method('request')
            ->willReturn($mockResponse);

        $client = static::createClient();

        self::getContainer()->set(HttpClientInterface::class, $mockClient);

        $client->request('GET', '/api/stations');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $expected = [
            ['station_id' => '001', 'name' => 'Riga Central'],
            ['station_id' => '002', 'name' => 'Daugavpils'],
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), $client->getResponse()->getContent());
    }
}
