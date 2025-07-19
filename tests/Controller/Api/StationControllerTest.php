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

    public function testStationDetailReturnsStationFromApi(): void
    {
        $mockData = [
            'result' => [
                'records' => [
                    ['Station_id' => 42, 'Name' => 'Test Station 42'],
                ],
            ],
        ];

        $mockResponse = new MockResponse(json_encode($mockData), [
            'http_code' => 200,
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $mockHttpClient = new MockHttpClient(function () use ($mockResponse): MockResponse {
            return $mockResponse;
        });

        $client = static::createClient(); // ✅ create client first

        // Override the HttpClient service AFTER createClient()
        self::getContainer()->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/stations/42', [], [], [
            'HTTP_Authorization' => 'Bearer your-secret-token-here',
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertEquals('42', $responseData['Station_id']);
        $this->assertEquals('Test Station 42', $responseData['Name']);
    }

}
