<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClientInterface;
use Psr\Log\LoggerInterface;

final class MondialRelayApiV2Service
{
    public function __construct(
        private readonly MondialRelayApiClientInterface $apiClient,
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
            $baseUri = $sandbox
                ? 'https://api-sandbox.mondialrelay.com/v2/'
                : 'https://api.mondialrelay.com/v2/';

            // Test the connection by making a simple API call
            // For example, get relay points near a known location
            $response = $this->apiClient->get('/parcel-shops', [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $apiKey),
                    'X-API-Secret' => $apiSecret,
                    'X-Brand-ID' => $brandId,
                ],
                'query' => [
                    'country' => 'FR',
                    'zipCode' => '75001',
                    'limit' => 1,
                ],
            ]);

            if ($response['success'] ?? false) {
                return [
                    'success' => true,
                    'data' => [
                        'message' => 'Connection successful',
                        'sandbox' => $sandbox,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Unknown error',
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
