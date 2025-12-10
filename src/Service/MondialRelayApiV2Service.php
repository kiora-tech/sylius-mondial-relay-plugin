<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class MondialRelayApiV2Service
{
    private const API_URL_SANDBOX = 'https://connect-api-sandbox.mondialrelay.com/api/shipment';
    private const API_URL_PRODUCTION = 'https://connect-api.mondialrelay.com/api/shipment';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Test API connection with provided credentials.
     *
     * The Mondial Relay Connect API v2 uses HTTP Basic Authentication.
     * - apiKey: login email (e.g., BRANDID@business-api.mondialrelay.com)
     * - apiSecret: password
     *
     * @return array{success: bool, error?: string, data?: array<string, mixed>}
     */
    public function testConnection(string $apiKey, string $apiSecret, string $brandId, bool $sandbox): array
    {
        try {
            $httpClient = HttpClient::create();
            $url = $sandbox ? self::API_URL_SANDBOX : self::API_URL_PRODUCTION;

            // Build Basic Auth credentials
            $credentials = base64_encode($apiKey . ':' . $apiSecret);

            $this->logger->debug('Testing Mondial Relay API connection', [
                'url' => $url,
                'sandbox' => $sandbox,
                'apiKey' => $apiKey,
            ]);

            // Make a POST request with empty body to test authentication
            // A successful auth will return a validation error (10061), not an auth error (401/403)
            $response = $httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . $credentials,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => new \stdClass(), // Empty JSON object
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            $this->logger->debug('Mondial Relay API test response', [
                'statusCode' => $statusCode,
                'content' => $content,
            ]);

            // 401/403 means authentication failed
            if ($statusCode === 401 || $statusCode === 403) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed. Please check your API credentials.',
                ];
            }

            // Parse JSON response
            $data = json_decode($content, true);

            // Check for Mondial Relay error codes
            // Error 10061 = "Problème de formatage du XML" means auth succeeded but request is invalid (expected)
            // This confirms the credentials are valid
            if (isset($data['statusListField']) && is_array($data['statusListField'])) {
                foreach ($data['statusListField'] as $status) {
                    $code = $status['codeField'] ?? '';
                    // 10061 = Format error (expected with empty body, means auth worked)
                    // 10020 = Missing required field (also means auth worked)
                    if (in_array($code, ['10061', '10020', '10010'], true)) {
                        return [
                            'success' => true,
                            'data' => [
                                'message' => 'Connection successful',
                                'sandbox' => $sandbox,
                                'brandId' => $brandId,
                            ],
                        ];
                    }
                }

                // Other error codes may indicate auth issues
                $errorMessage = $data['statusListField'][0]['messageField'] ?? 'Unknown error';
                $errorCode = $data['statusListField'][0]['codeField'] ?? '';

                return [
                    'success' => false,
                    'error' => sprintf('[Code %s] %s', $errorCode, $errorMessage),
                ];
            }

            // If we got here with 200, connection is likely working
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'data' => [
                        'message' => 'Connection successful',
                        'sandbox' => $sandbox,
                        'brandId' => $brandId,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => sprintf('Unexpected response (HTTP %d)', $statusCode),
            ];
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Mondial Relay API connection test failed (transport error)', [
                'error' => $e->getMessage(),
                'sandbox' => $sandbox,
            ]);

            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
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
