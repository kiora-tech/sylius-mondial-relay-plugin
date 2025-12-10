<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\Client;

use Kiora\SyliusMondialRelayPlugin\Api\DTO\LabelResponse;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointCollection;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointDTO;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentRequest;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentResponse;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayAuthenticationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * HTTP client for Mondial Relay REST API v2.
 *
 * Implements authentication, request signing, automatic retries,
 * and comprehensive error handling for the Mondial Relay API.
 */
final class MondialRelayApiClient implements MondialRelayApiClientInterface
{
    // Note: Mondial Relay REST API v2 uses the same URL for both production and sandbox.
    // The sandbox mode is determined by the API credentials used (test credentials like TTMRSDBX).
    private const API_BASE_URL_PRODUCTION = 'https://api.mondialrelay.com/v2';
    private const API_BASE_URL_SANDBOX = 'https://api.mondialrelay.com/v2';
    private const DEFAULT_TIMEOUT = 30.0;
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MS = 1000; // Initial delay, increases exponentially

    /**
     * @param string $apiKey Mondial Relay API key
     * @param string $apiSecret Mondial Relay API secret for request signing
     * @param bool $sandbox Whether to use sandbox environment
     * @param HttpClientInterface|null $httpClient Optional custom HTTP client
     * @param LoggerInterface|null $logger Optional logger for debugging
     * @param float $timeout Request timeout in seconds
     * @param bool $enableRetry Whether to enable automatic retries
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly bool $sandbox = false,
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly float $timeout = self::DEFAULT_TIMEOUT,
        private readonly bool $enableRetry = true,
    ) {
        if ($this->apiKey === '' || $this->apiSecret === '') {
            throw new \InvalidArgumentException('API key and secret are required.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findRelayPoints(RelayPointSearchCriteria $criteria): RelayPointCollection
    {
        $endpoint = '/relay-points/search';
        $body = $this->buildSearchRequestBody($criteria);

        try {
            $response = $this->post($endpoint, $body);
            $data = $response->toArray();

            return RelayPointCollection::fromApiResponse($data);
        } catch (ExceptionInterface $e) {
            $this->getLogger()->error('Failed to search relay points', [
                'criteria' => $body,
                'error' => $e->getMessage(),
            ]);

            throw new MondialRelayApiException(
                mondialRelayErrorCode: 3,
                customMessage: 'Échec de la recherche des points relais.',
                context: ['criteria' => $body],
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRelayPoint(string $relayPointId, string $countryCode): ?RelayPointDTO
    {
        $endpoint = sprintf('/relay-points/%s/%s', $countryCode, $relayPointId);

        try {
            $response = $this->get($endpoint);
            $data = $response->toArray();

            return RelayPointDTO::fromApiResponse($data);
        } catch (ExceptionInterface $e) {
            $this->getLogger()->warning('Relay point not found or API error', [
                'relayPointId' => $relayPointId,
                'countryCode' => $countryCode,
                'error' => $e->getMessage(),
            ]);

            // Return null if relay point not found (404), throw for other errors
            if ($e instanceof \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface) {
                $statusCode = method_exists($e, 'getResponse') ? $e->getResponse()->getStatusCode() : 0;
                if ($statusCode === 404) {
                    return null;
                }
            }

            throw new MondialRelayApiException(
                mondialRelayErrorCode: 80,
                context: ['relayPointId' => $relayPointId, 'countryCode' => $countryCode],
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createShipment(ShipmentRequest $request): ShipmentResponse
    {
        $endpoint = '/shipments';
        $body = $request->toArray();

        try {
            $response = $this->post($endpoint, $body);
            $data = $response->toArray();

            return ShipmentResponse::fromApiResponse($data);
        } catch (ExceptionInterface $e) {
            $this->getLogger()->error('Failed to create shipment', [
                'orderReference' => $request->orderReference,
                'error' => $e->getMessage(),
            ]);

            throw new MondialRelayApiException(
                mondialRelayErrorCode: 3,
                customMessage: 'Échec de la création de l\'expédition.',
                context: ['orderReference' => $request->orderReference],
                previous: $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(string $expeditionNumber): LabelResponse
    {
        $endpoint = sprintf('/shipments/%s/label', $expeditionNumber);

        try {
            $response = $this->get($endpoint);
            $content = $response->getContent();

            // Extract content type and format from headers
            $contentType = $response->getHeaders()['content-type'][0] ?? 'application/pdf';
            $format = $this->extractLabelFormat($response);

            return LabelResponse::fromApiResponse(
                content: $content,
                expeditionNumber: $expeditionNumber,
                contentType: $contentType,
                format: $format
            );
        } catch (ExceptionInterface $e) {
            $this->getLogger()->error('Failed to retrieve label', [
                'expeditionNumber' => $expeditionNumber,
                'error' => $e->getMessage(),
            ]);

            throw new MondialRelayApiException(
                mondialRelayErrorCode: 3,
                customMessage: 'Échec de la récupération de l\'étiquette.',
                context: ['expeditionNumber' => $expeditionNumber],
                previous: $e
            );
        }
    }

    /**
     * Perform GET request with authentication and retry logic.
     *
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $queryParams Optional query parameters
     *
     * @throws MondialRelayApiException
     */
    private function get(string $endpoint, array $queryParams = []): ResponseInterface
    {
        return $this->request('GET', $endpoint, null, $queryParams);
    }

    /**
     * Perform POST request with authentication and retry logic.
     *
     * @param string $endpoint API endpoint path
     * @param array<string, mixed>|null $body Request body
     *
     * @throws MondialRelayApiException
     */
    private function post(string $endpoint, ?array $body = null): ResponseInterface
    {
        return $this->request('POST', $endpoint, $body);
    }

    /**
     * Perform HTTP request with full error handling and retry logic.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint path
     * @param array<string, mixed>|null $body Request body
     * @param array<string, mixed> $queryParams Query parameters
     *
     * @throws MondialRelayApiException
     */
    private function request(
        string $method,
        string $endpoint,
        ?array $body = null,
        array $queryParams = []
    ): ResponseInterface {
        $url = $this->buildUrl($endpoint, $queryParams);
        $headers = $this->buildHeaders($method, $endpoint, $body);

        $options = [
            'headers' => $headers,
            'timeout' => $this->timeout,
        ];

        if ($body !== null) {
            $options['json'] = $body;
        }

        $attempt = 0;
        $lastException = null;

        while ($attempt < ($this->enableRetry ? self::MAX_RETRY_ATTEMPTS : 1)) {
            ++$attempt;

            try {
                $this->getLogger()->debug('Mondial Relay API request', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempt,
                ]);

                $response = $this->getHttpClient()->request($method, $url, $options);

                // Trigger actual HTTP call and check for errors
                $statusCode = $response->getStatusCode();

                $this->getLogger()->debug('Mondial Relay API response', [
                    'statusCode' => $statusCode,
                    'attempt' => $attempt,
                ]);

                // Check for API-level errors in response
                $this->checkApiErrors($response);

                return $response;
            } catch (TransportExceptionInterface $e) {
                $lastException = $e;

                $this->getLogger()->warning('Mondial Relay API transport error', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                // Retry on transport errors if enabled
                if ($this->enableRetry && $attempt < self::MAX_RETRY_ATTEMPTS) {
                    $this->sleep($attempt);
                    continue;
                }

                throw new MondialRelayApiException(
                    mondialRelayErrorCode: 3,
                    customMessage: 'Erreur de connexion à l\'API Mondial Relay.',
                    context: ['method' => $method, 'endpoint' => $endpoint],
                    previous: $e
                );
            } catch (ExceptionInterface $e) {
                // Don't retry on client errors (4xx) except 429 (rate limit)
                if ($e instanceof \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface) {
                    $this->handleClientError($e);
                }

                throw $e;
            }
        }

        // If we exhausted all retries
        throw new MondialRelayApiException(
            mondialRelayErrorCode: 3,
            customMessage: 'Service temporairement indisponible après plusieurs tentatives.',
            context: ['attempts' => $attempt, 'method' => $method, 'endpoint' => $endpoint],
            previous: $lastException
        );
    }

    /**
     * Check API response for Mondial Relay specific errors.
     *
     * @throws MondialRelayApiException
     * @throws MondialRelayAuthenticationException
     */
    private function checkApiErrors(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        // Authentication errors
        if ($statusCode === 401 || $statusCode === 403) {
            throw new MondialRelayAuthenticationException(
                context: ['statusCode' => $statusCode]
            );
        }

        // Check for Mondial Relay error codes in response body
        if ($statusCode >= 400) {
            try {
                $data = $response->toArray(false);

                if (isset($data['errorCode'])) {
                    $errorCode = (int) $data['errorCode'];
                    $errorMessage = $data['errorMessage'] ?? null;

                    throw new MondialRelayApiException(
                        mondialRelayErrorCode: $errorCode,
                        customMessage: $errorMessage,
                        context: $data
                    );
                }
            } catch (\JsonException) {
                // If response is not JSON, let it be handled as HTTP error
            }
        }
    }

    /**
     * Handle HTTP client errors (4xx status codes).
     *
     * @throws MondialRelayApiException
     */
    private function handleClientError(\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e): void
    {
        $statusCode = method_exists($e, 'getResponse') ? $e->getResponse()->getStatusCode() : 0;

        $errorCodeMap = [
            400 => 2, // Bad request - validation error
            404 => 80, // Not found
            429 => 3, // Rate limit - temporary error
        ];

        $errorCode = $errorCodeMap[$statusCode] ?? 3;

        throw new MondialRelayApiException(
            mondialRelayErrorCode: $errorCode,
            context: ['httpStatus' => $statusCode],
            previous: $e
        );
    }

    /**
     * Build full API URL with query parameters.
     *
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $queryParams Query parameters
     */
    private function buildUrl(string $endpoint, array $queryParams = []): string
    {
        $baseUrl = $this->sandbox ? self::API_BASE_URL_SANDBOX : self::API_BASE_URL_PRODUCTION;
        $url = $baseUrl . $endpoint;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Build request headers including authentication signature.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint path
     * @param array<string, mixed>|null $body Request body
     *
     * @return array<string, string>
     */
    private function buildHeaders(string $method, string $endpoint, ?array $body): array
    {
        $timestamp = (string) time();
        $signature = $this->generateSignature($method, $endpoint, $body, $timestamp);

        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'X-MR-Signature' => $signature,
            'X-MR-Timestamp' => $timestamp,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Kiora-Sylius-MondialRelay-Plugin/1.0',
        ];
    }

    /**
     * Generate HMAC signature for request authentication.
     *
     * Signature algorithm: HMAC-SHA256(secret, method + endpoint + timestamp + body_json)
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint path
     * @param array<string, mixed>|null $body Request body
     * @param string $timestamp Unix timestamp
     */
    private function generateSignature(string $method, string $endpoint, ?array $body, string $timestamp): string
    {
        $bodyString = $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $payload = $method . $endpoint . $timestamp . $bodyString;

        return hash_hmac('sha256', $payload, $this->apiSecret);
    }

    /**
     * Build request body for relay point search.
     *
     * @return array<string, mixed>
     */
    private function buildSearchRequestBody(RelayPointSearchCriteria $criteria): array
    {
        $body = [
            'countryCode' => $criteria->countryCode,
            'radius' => $criteria->radius,
            'limit' => $criteria->limit,
        ];

        if ($criteria->hasCoordinates()) {
            $body['latitude'] = $criteria->latitude;
            $body['longitude'] = $criteria->longitude;
        } elseif ($criteria->hasPostalCode()) {
            $body['postalCode'] = $criteria->postalCode;
            if ($criteria->city !== null) {
                $body['city'] = $criteria->city;
            }
        }

        if ($criteria->deliveryMode !== null) {
            $body['deliveryMode'] = $criteria->deliveryMode;
        }

        if ($criteria->weight !== null) {
            $body['weight'] = $criteria->weight;
        }

        return $body;
    }

    /**
     * Extract label format from response headers.
     */
    private function extractLabelFormat(ResponseInterface $response): string
    {
        $disposition = $response->getHeaders()['content-disposition'][0] ?? '';

        if (preg_match('/format[=_]([A-Z0-9x]+)/i', $disposition, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'A4'; // Default format
    }

    /**
     * Sleep with exponential backoff between retries.
     *
     * @param int $attempt Current attempt number (1-indexed)
     */
    private function sleep(int $attempt): void
    {
        $delayMs = self::RETRY_DELAY_MS * (2 ** ($attempt - 1)); // Exponential backoff
        usleep($delayMs * 1000);
    }

    /**
     * Get HTTP client instance.
     */
    private function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient ?? HttpClient::create();
    }

    /**
     * Get logger instance.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }
}
