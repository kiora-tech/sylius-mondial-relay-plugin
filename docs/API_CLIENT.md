# Mondial Relay API Client

## Overview

The Mondial Relay API Client provides a robust, type-safe interface to interact with the Mondial Relay REST API v2. Built with PHP 8.2+ features including readonly classes, promoted constructor properties, and strict typing.

## Features

- **Type-Safe DTOs**: All requests and responses use immutable readonly DTOs
- **Automatic Authentication**: HMAC-SHA256 request signing with API key/secret
- **Retry Logic**: Exponential backoff retry for temporary failures (configurable)
- **Error Handling**: Comprehensive exception hierarchy with translated error messages
- **Logging**: Optional PSR-3 logger integration for debugging
- **Timeout Control**: Configurable request timeouts (default 30s)
- **Sandbox Support**: Easy switching between production and sandbox environments

## Installation

The client is automatically registered as a service when you install the plugin:

```bash
composer require kiora/sylius-mondial-relay-plugin
```

## Configuration

Add your Mondial Relay credentials to your `.env` file:

```bash
# Mondial Relay API Credentials
MONDIAL_RELAY_API_KEY=your_api_key_here
MONDIAL_RELAY_API_SECRET=your_api_secret_here
MONDIAL_RELAY_SANDBOX=true  # Set to false for production

# Optional configuration (with defaults)
MONDIAL_RELAY_HTTP_TIMEOUT=30
MONDIAL_RELAY_CACHE_TTL=14400
```

## Usage Examples

### Basic Usage

```php
<?php

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClientInterface;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;

class MyService
{
    public function __construct(
        private readonly MondialRelayApiClientInterface $apiClient
    ) {}

    public function searchNearbyRelayPoints(): void
    {
        try {
            // Search by postal code
            $criteria = RelayPointSearchCriteria::fromPostalCode(
                postalCode: '75002',
                countryCode: 'FR',
                radius: 10,
                limit: 20
            );

            $collection = $this->apiClient->findRelayPoints($criteria);

            foreach ($collection as $relayPoint) {
                echo sprintf(
                    "%s - %s (%s km)\n",
                    $relayPoint->name,
                    $relayPoint->getFullAddress(),
                    $relayPoint->getDistanceKm()
                );
            }
        } catch (MondialRelayApiException $e) {
            // Handle error
            echo "Error: " . $e->getMessage();
        }
    }
}
```

### Search by GPS Coordinates

```php
// Search by coordinates
$criteria = RelayPointSearchCriteria::fromCoordinates(
    latitude: 48.8566,
    longitude: 2.3522,
    countryCode: 'FR',
    radius: 15
);

$collection = $this->apiClient->findRelayPoints($criteria);
```

### Get Specific Relay Point

```php
$relayPoint = $this->apiClient->getRelayPoint(
    relayPointId: '012345',
    countryCode: 'FR'
);

if ($relayPoint !== null) {
    echo $relayPoint->name;
    echo $relayPoint->getGoogleMapsUrl();

    // Check if open on Monday
    if ($relayPoint->isOpenOnDay('monday')) {
        $hours = $relayPoint->getOpeningHoursForDay('monday');
        // ...
    }
}
```

### Create Shipment

```php
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentRequest;

$request = new ShipmentRequest(
    orderReference: 'ORDER-12345',
    relayPointId: '012345',
    countryCode: 'FR',
    recipientName: 'Jean Dupont',
    recipientEmail: 'jean@example.com',
    recipientPhone: '+33612345678',
    recipientAddressLine1: '10 rue de la Paix',
    recipientAddressLine2: null,
    recipientPostalCode: '75002',
    recipientCity: 'Paris',
    weightGrams: 1500,
    deliveryMode: '24R',
    lengthCm: 30,
    widthCm: 20,
    heightCm: 10
);

$response = $this->apiClient->createShipment($request);

echo "Expedition number: " . $response->expeditionNumber;
echo "Tracking URL: " . $response->trackingUrl;
echo "Label URL: " . $response->labelUrl;
```

### Download Shipping Label

```php
$label = $this->apiClient->getLabel('EXP123456789');

// Save to file
$label->saveToFile('/path/to/labels/' . $label->getSuggestedFilename());

// Or get as base64 for display
$dataUri = $label->getDataUri();

echo "Label size: " . $label->getHumanReadableSize();
```

## Error Handling

The client provides a comprehensive exception hierarchy:

```php
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayAuthenticationException;

try {
    $collection = $this->apiClient->findRelayPoints($criteria);
} catch (MondialRelayAuthenticationException $e) {
    // Invalid API credentials
    $this->logger->critical('Invalid Mondial Relay credentials');
} catch (MondialRelayApiException $e) {
    // Check error type
    if ($e->isTemporary()) {
        // Retry later
        $this->logger->warning('Temporary API error: ' . $e->getMessage());
    } elseif ($e->isConfigurationError()) {
        // Configuration issue
        $this->logger->error('Configuration error: ' . $e->getMessage());
    } elseif ($e->isValidationError()) {
        // Invalid request data
        $this->logger->error('Validation error: ' . $e->getMessage());
    }

    // Get Mondial Relay error code
    $errorCode = $e->getMondialRelayErrorCode();

    // Get additional context
    $context = $e->getContext();
}
```

## Error Codes

| Code | Type | Message FR | Retryable |
|------|------|------------|-----------|
| 0 | Info | Mode sandbox actif | No |
| 1 | Critical | Identifiants API invalides | No |
| 2 | Validation | Code postal non desservi | No |
| 3 | Temporary | Service temporairement indisponible | Yes |
| 9 | Validation | Poids hors limites (max 30kg) | No |
| 20 | Validation | Point relais inactif | No |
| 80 | Validation | Point relais introuvable | No |
| 81 | Temporary | Point relais saturé | Yes |

## Advanced Features

### Custom HTTP Client

```php
use Symfony\Component\HttpClient\HttpClient;
use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClient;

$httpClient = HttpClient::create([
    'timeout' => 60,
    'max_redirects' => 0,
]);

$apiClient = new MondialRelayApiClient(
    apiKey: $_ENV['MONDIAL_RELAY_API_KEY'],
    apiSecret: $_ENV['MONDIAL_RELAY_API_SECRET'],
    sandbox: true,
    httpClient: $httpClient,
    logger: $logger,
    timeout: 60.0,
    enableRetry: true
);
```

### Disable Retries

```php
$apiClient = new MondialRelayApiClient(
    apiKey: $_ENV['MONDIAL_RELAY_API_KEY'],
    apiSecret: $_ENV['MONDIAL_RELAY_API_SECRET'],
    sandbox: false,
    enableRetry: false  // Disable automatic retries
);
```

## Working with Collections

```php
$collection = $this->apiClient->findRelayPoints($criteria);

// Basic operations
echo "Found: " . $collection->count() . " relay points\n";
echo "Total available: " . $collection->totalCount . "\n";

// Get first result
$first = $collection->first();

// Find by ID
$specific = $collection->findById('012345');

// Filter by service
$withParking = $collection->filterByService('parking');
$wheelchairAccessible = $collection->filterByService('wheelchair_accessible');

// Filter by distance
$nearbyOnly = $collection->filterByMaxDistance(1000); // Within 1km

// Filter active only
$activeOnly = $collection->filterActive();

// Chain filters
$filtered = $collection
    ->filterActive()
    ->filterByMaxDistance(2000)
    ->filterByService('parking');

// Map to array
$names = $collection->map(fn($rp) => $rp->name);

// Convert to array
$array = $collection->toArray();
```

## Performance Considerations

### Caching

The API client itself does not implement caching, but you can easily add it:

```php
use Symfony\Contracts\Cache\CacheInterface;

class CachedMondialRelayService
{
    public function __construct(
        private readonly MondialRelayApiClientInterface $apiClient,
        private readonly CacheInterface $cache
    ) {}

    public function findRelayPoints(RelayPointSearchCriteria $criteria): RelayPointCollection
    {
        $cacheKey = 'mr_' . md5(serialize($criteria));

        return $this->cache->get($cacheKey, function() use ($criteria) {
            return $this->apiClient->findRelayPoints($criteria);
        }, ttl: 3600); // Cache for 1 hour
    }
}
```

### Request Optimization

- Use GPS coordinates when available (faster than postal code lookup)
- Limit results to what you actually need (default: 20, max: 50)
- Set an appropriate search radius (smaller = faster)

## Testing

Mock the interface in your tests:

```php
use PHPUnit\Framework\TestCase;
use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClientInterface;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointCollection;

class MyServiceTest extends TestCase
{
    public function testSearchRelayPoints(): void
    {
        $apiClient = $this->createMock(MondialRelayApiClientInterface::class);
        $apiClient->expects($this->once())
            ->method('findRelayPoints')
            ->willReturn(RelayPointCollection::empty());

        $service = new MyService($apiClient);
        // ... test your service
    }
}
```

## Troubleshooting

### Enable Debug Logging

Configure Monolog to log Mondial Relay API calls:

```yaml
# config/packages/dev/monolog.yaml
monolog:
    channels: ['mondial_relay']
    handlers:
        mondial_relay:
            type: stream
            path: '%kernel.logs_dir%/mondial_relay_%kernel.environment%.log'
            level: debug
            channels: ['mondial_relay']
```

### Common Issues

**Authentication Failed (401/403)**
- Check your API key and secret in `.env`
- Verify your account is active on Mondial Relay portal
- Ensure you're using the correct environment (sandbox vs production)

**Timeout Errors**
- Increase the timeout: `MONDIAL_RELAY_HTTP_TIMEOUT=60`
- Check your network connectivity
- Verify Mondial Relay API status

**Empty Results**
- Verify the postal code/coordinates are correct
- Try increasing the search radius
- Check if the area is covered by Mondial Relay

## API Reference

### DTOs

- `RelayPointSearchCriteria`: Search parameters for relay points
- `RelayPointDTO`: Relay point information
- `RelayPointCollection`: Iterable collection of relay points
- `ShipmentRequest`: Shipment creation parameters
- `ShipmentResponse`: Created shipment details
- `LabelResponse`: Shipping label PDF content

### Exceptions

- `MondialRelayApiException`: Base exception for all API errors
- `MondialRelayAuthenticationException`: Authentication failures

## Links

- [Mondial Relay API Documentation](https://www.mondialrelay.fr/api-documentation)
- [Plugin Repository](https://github.com/kiora-tech/sylius-mondial-relay-plugin)
- [Issue Tracker](https://github.com/kiora-tech/sylius-mondial-relay-plugin/issues)
