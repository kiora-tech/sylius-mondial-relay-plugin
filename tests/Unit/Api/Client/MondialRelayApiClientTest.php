<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Api\Client;

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClient;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\LabelResponse;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointCollection;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentRequest;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentResponse;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayAuthenticationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MondialRelayApiClientTest extends TestCase
{
    private const API_KEY = 'test_api_key_12345678';
    private const API_SECRET = 'test_secret_12345678';

    public function testConstructorThrowsExceptionWithEmptyApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key and secret are required.');

        new MondialRelayApiClient('', self::API_SECRET);
    }

    public function testConstructorThrowsExceptionWithEmptyApiSecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API key and secret are required.');

        new MondialRelayApiClient(self::API_KEY, '');
    }

    public function testFindRelayPointsSuccess(): void
    {
        $responseData = [
            'relayPoints' => [
                [
                    'id' => 'FR123456',
                    'name' => 'Test Relay Point',
                    'address' => [
                        'street' => '123 Test Street',
                        'postalCode' => '75001',
                        'city' => 'Paris',
                        'countryCode' => 'FR',
                    ],
                    'coordinates' => [
                        'latitude' => 48.8566,
                        'longitude' => 2.3522,
                    ],
                    'distance' => 500,
                    'isActive' => true,
                ],
            ],
            'totalCount' => 1,
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');
        $collection = $client->findRelayPoints($criteria);

        $this->assertInstanceOf(RelayPointCollection::class, $collection);
        $this->assertCount(1, $collection);
        $this->assertEquals(1, $collection->totalCount);

        $relayPoint = $collection->first();
        $this->assertNotNull($relayPoint);
        $this->assertEquals('FR123456', $relayPoint->relayPointId);
        $this->assertEquals('Test Relay Point', $relayPoint->name);
    }

    public function testFindRelayPointsWithInvalidCredentials(): void
    {
        $mockResponse = new MockResponse('', [
            'http_code' => 401,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');

        $this->expectException(MondialRelayAuthenticationException::class);
        $client->findRelayPoints($criteria);
    }

    public function testGetRelayPointSuccess(): void
    {
        $responseData = [
            'id' => 'FR123456',
            'name' => 'Test Relay Point',
            'address' => [
                'street' => '123 Test Street',
                'postalCode' => '75001',
                'city' => 'Paris',
                'countryCode' => 'FR',
            ],
            'coordinates' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
            'openingHours' => [
                'monday' => [
                    ['open' => '09:00', 'close' => '12:00'],
                    ['open' => '14:00', 'close' => '19:00'],
                ],
            ],
            'services' => ['parking', 'wheelchair_accessible'],
            'isActive' => true,
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 200,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $relayPoint = $client->getRelayPoint('FR123456', 'FR');

        $this->assertNotNull($relayPoint);
        $this->assertEquals('FR123456', $relayPoint->relayPointId);
        $this->assertEquals('Test Relay Point', $relayPoint->name);
        $this->assertEquals('75001', $relayPoint->postalCode);
        $this->assertTrue($relayPoint->hasService('parking'));
        $this->assertTrue($relayPoint->hasService('wheelchair_accessible'));
    }

    public function testGetRelayPointNotFound(): void
    {
        $mockResponse = new MockResponse('', [
            'http_code' => 404,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient(function (string $method, string $url) use ($mockResponse): ResponseInterface {
            return $mockResponse;
        });

        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $relayPoint = $client->getRelayPoint('INVALID', 'FR');

        $this->assertNull($relayPoint);
    }

    public function testCreateShipmentSuccess(): void
    {
        $responseData = [
            'expeditionNumber' => 'EXP123456789',
            'trackingUrl' => 'https://www.mondialrelay.com/suivi-colis/?numeroExpedition=EXP123456789',
            'labelUrl' => 'https://api.mondialrelay.com/v2/shipments/EXP123456789/label',
            'status' => 'created',
        ];

        $mockResponse = new MockResponse(json_encode($responseData), [
            'http_code' => 201,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $shipmentRequest = new ShipmentRequest(
            orderReference: 'ORDER123',
            senderName: 'Test Sender',
            senderAddress: '123 Sender St',
            senderPostalCode: '75001',
            senderCity: 'Paris',
            senderCountryCode: 'FR',
            recipientName: 'Test Recipient',
            recipientAddress: '456 Recipient Ave',
            recipientPostalCode: '75002',
            recipientCity: 'Paris',
            recipientCountryCode: 'FR',
            weight: 1000,
            relayPointId: 'FR123456',
            deliveryMode: '24R'
        );

        $response = $client->createShipment($shipmentRequest);

        $this->assertInstanceOf(ShipmentResponse::class, $response);
        $this->assertEquals('EXP123456789', $response->expeditionNumber);
    }

    public function testRetryOnTemporaryError(): void
    {
        $callCount = 0;
        $mockHttpClient = new MockHttpClient(function () use (&$callCount): ResponseInterface {
            ++$callCount;

            // First two attempts fail with transport error
            if ($callCount <= 2) {
                throw new TransportException('Network error');
            }

            // Third attempt succeeds
            return new MockResponse(json_encode([
                'relayPoints' => [],
                'totalCount' => 0,
            ]), [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        });

        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $mockHttpClient,
            logger: new NullLogger(),
            enableRetry: true
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');
        $collection = $client->findRelayPoints($criteria);

        $this->assertEquals(3, $callCount);
        $this->assertInstanceOf(RelayPointCollection::class, $collection);
    }

    public function testRetryExhausted(): void
    {
        $callCount = 0;
        $mockHttpClient = new MockHttpClient(function () use (&$callCount): ResponseInterface {
            ++$callCount;
            throw new TransportException('Network error');
        });

        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $mockHttpClient,
            logger: new NullLogger(),
            enableRetry: true
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');

        $this->expectException(MondialRelayApiException::class);
        $this->expectExceptionMessage('Service temporairement indisponible');

        try {
            $client->findRelayPoints($criteria);
        } finally {
            $this->assertEquals(3, $callCount);
        }
    }

    public function testGetLabelSuccess(): void
    {
        $pdfContent = '%PDF-1.4 fake pdf content';
        $mockResponse = new MockResponse($pdfContent, [
            'http_code' => 200,
            'response_headers' => [
                'content-type' => 'application/pdf',
                'content-disposition' => 'attachment; filename=label_format_A4.pdf',
            ],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $labelResponse = $client->getLabel('EXP123456789');

        $this->assertInstanceOf(LabelResponse::class, $labelResponse);
        $this->assertEquals('EXP123456789', $labelResponse->expeditionNumber);
        $this->assertEquals($pdfContent, $labelResponse->content);
        $this->assertEquals('application/pdf', $labelResponse->contentType);
    }

    public function testApiErrorCodeInResponse(): void
    {
        $errorResponse = [
            'errorCode' => 80,
            'errorMessage' => 'Point relais non trouvé',
            'details' => 'Le point relais demandé n\'existe pas',
        ];

        $mockResponse = new MockResponse(json_encode($errorResponse), [
            'http_code' => 400,
            'response_headers' => ['content-type' => 'application/json'],
        ]);

        $httpClient = new MockHttpClient($mockResponse);
        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $httpClient,
            logger: new NullLogger()
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');

        $this->expectException(MondialRelayApiException::class);

        try {
            $client->findRelayPoints($criteria);
        } catch (MondialRelayApiException $e) {
            $this->assertEquals(80, $e->getMondialRelayErrorCode());
            $this->assertStringContainsString('Point relais non trouvé', $e->getMessage());
            throw $e;
        }
    }

    public function testSandboxModeUsesCorrectUrl(): void
    {
        $requestedUrl = null;

        $mockHttpClient = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): ResponseInterface {
            $requestedUrl = $url;

            return new MockResponse(json_encode([
                'relayPoints' => [],
                'totalCount' => 0,
            ]), [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        });

        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $mockHttpClient,
            logger: new NullLogger()
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');
        $client->findRelayPoints($criteria);

        $this->assertStringContainsString('api-sandbox.mondialrelay.com', $requestedUrl);
    }

    public function testProductionModeUsesCorrectUrl(): void
    {
        $requestedUrl = null;

        $mockHttpClient = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl): ResponseInterface {
            $requestedUrl = $url;

            return new MockResponse(json_encode([
                'relayPoints' => [],
                'totalCount' => 0,
            ]), [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        });

        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: false,
            httpClient: $mockHttpClient,
            logger: new NullLogger()
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');
        $client->findRelayPoints($criteria);

        $this->assertStringContainsString('api.mondialrelay.com', $requestedUrl);
        $this->assertStringNotContainsString('sandbox', $requestedUrl);
    }

    public function testAuthenticationHeadersAreSent(): void
    {
        $requestHeaders = null;

        $mockHttpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requestHeaders): ResponseInterface {
            $requestHeaders = $options['headers'] ?? [];

            return new MockResponse(json_encode([
                'relayPoints' => [],
                'totalCount' => 0,
            ]), [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        });

        $client = new MondialRelayApiClient(
            self::API_KEY,
            self::API_SECRET,
            sandbox: true,
            httpClient: $mockHttpClient,
            logger: new NullLogger()
        );

        $criteria = RelayPointSearchCriteria::fromPostalCode('75001', 'FR');
        $client->findRelayPoints($criteria);

        $this->assertIsArray($requestHeaders);
        $this->assertArrayHasKey('Authorization', $requestHeaders);
        $this->assertArrayHasKey('X-MR-Signature', $requestHeaders);
        $this->assertArrayHasKey('X-MR-Timestamp', $requestHeaders);
        $this->assertStringContainsString('Bearer ' . self::API_KEY, $requestHeaders['Authorization']);
    }
}
