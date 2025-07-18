<?php
declare(strict_types = 1);

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StationControllerTest extends WebTestCase
{
    public function testListStationsReturnsFormattedJson(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'result' => [
                'records' => [
                    ['STATION_ID' => '001', 'NAME' => 'Riga Central'],
                    ['STATION_ID' => '002', 'NAME' => 'Daugavpils'],
                ]
            ]
        ]));
        
        // Wrap the MockResponse in a MockHttpClient
        $mockClient = new MockHttpClient($mockResponse);

        $client = static::createClient();

        self::getContainer()->set(HttpClientInterface::class, $mockClient);

        $client->request('GET', '/api/stations');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $expected = [
            ['Station_id' => '001', 'Name' => 'Riga Central'],
            ['Station_id' => '002', 'Name' => 'Daugavpils'],
        ];

        $this->assertJsonStringEqualsJsonString(json_encode($expected), $client->getResponse()->getContent());
    }
}
