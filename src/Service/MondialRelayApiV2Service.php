<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClient;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;

final class MondialRelayApiV2Service
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Test API connection with provided credentials.
     *
     * @return array{success: bool, error?: string, data?: array<string, mixed>}
     */
    public function testConnection(string $apiKey, string $apiSecret, string $brandId, bool $sandbox): array
    {
        try {
            // Create a temporary client with the provided credentials
            $testClient = new MondialRelayApiClient(
                apiKey: $apiKey,
                apiSecret: $apiSecret,
                sandbox: $sandbox,
                httpClient: HttpClient::create(),
                logger: $this->logger,
            );

            // Test the connection by making a simple API call to search for relay points
            $criteria = RelayPointSearchCriteria::fromPostalCode(
                postalCode: '75001',
                countryCode: 'FR',
                limit: 1,
            );

            $relayPoints = $testClient->findRelayPoints($criteria);

            return [
                'success' => true,
                'data' => [
                    'message' => 'Connection successful',
                    'sandbox' => $sandbox,
                    'relayPointsFound' => count($relayPoints),
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Mondial Relay API connection test failed', [
                'error' => $e->getMessage(),
                'sandbox' => $sandbox,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
