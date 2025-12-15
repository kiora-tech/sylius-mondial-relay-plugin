# Mondial Relay API Clients

## Overview

This plugin uses **two separate APIs** to interact with Mondial Relay:

| API | Client Class | Purpose |
|-----|--------------|---------|
| **SOAP API v1** | `MondialRelaySoapClient` | Relay point search (WSI4_PointRelais_Recherche) |
| **REST API v2 (Connect)** | `MondialRelayApiClient` | Shipment creation, label generation |

> **Why two APIs?** The Mondial Relay REST API v2 (Connect) does not provide relay point search endpoints. This functionality is only available through the legacy SOAP API v1.

## Features

- **Type-Safe DTOs**: All requests and responses use immutable readonly DTOs
- **SOAP Client**: MD5 signature-based authentication for relay point search
- **REST Client**: Basic Auth authentication for shipments
- **Retry Logic**: Exponential backoff retry for temporary failures
- **Error Handling**: Comprehensive exception hierarchy with translated error messages
- **Logging**: Optional PSR-3 logger integration for debugging

## Installation

```bash
composer require kiora/sylius-mondial-relay-plugin
```

## Configuration

All API credentials are configured via the **Admin Interface**:

**Admin > Configuration > Mondial Relay**

Configure:
- **REST API v2**: API Key, API Secret, Brand ID (for shipments/labels)
- **SOAP API v1**: Enseigne code, Private Key (for relay point search)
- **Sandbox Mode**: Toggle test/production environment

Configuration is stored in `config/mondial_relay.json`.

---

## SOAP Client (Relay Point Search)

Use `MondialRelaySoapClient` for searching and retrieving relay points.

### Basic Search

```php
<?php

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelaySoapClient;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;

class MyService
{
    public function __construct(
        private readonly MondialRelaySoapClient $soapClient
    ) {}

    public function searchNearbyRelayPoints(): void
    {
        try {
            // Search by postal code
            $criteria = RelayPointSearchCriteria::fromPostalCode(
                postalCode: '75002',
                countryCode: 'FR',
                city: 'Paris',
                radius: 10,
                limit: 20
            );

            $collection = $this->soapClient->findRelayPoints($criteria);

            foreach ($collection as $relayPoint) {
                echo sprintf(
                    "%s - %s (%s km)\n",
                    $relayPoint->name,
                    $relayPoint->getFullAddress(),
                    $relayPoint->getDistanceKm()
                );
            }
        } catch (MondialRelayApiException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
```

### Search by GPS Coordinates

```php
$criteria = RelayPointSearchCriteria::fromCoordinates(
    latitude: 48.8566,
    longitude: 2.3522,
    countryCode: 'FR',
    radius: 15
);

$collection = $this->soapClient->findRelayPoints($criteria);
```

### Get Specific Relay Point

```php
$relayPoint = $this->soapClient->getRelayPoint(
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

---

## REST Client (Shipments & Labels)

Use `MondialRelayApiClient` for creating shipments and downloading labels.

### Create Shipment

```php
<?php

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClient;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentRequest;

class ShipmentService
{
    public function __construct(
        private readonly MondialRelayApiClient $apiClient
    ) {}

    public function createShipment(): void
    {
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
    }
}
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

---

## Error Handling

Both clients use the same exception hierarchy:

```php
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayAuthenticationException;

try {
    $collection = $this->soapClient->findRelayPoints($criteria);
} catch (MondialRelayAuthenticationException $e) {
    // Invalid API credentials
    $this->logger->critical('Invalid Mondial Relay credentials');
} catch (MondialRelayApiException $e) {
    if ($e->isTemporary()) {
        // Retry later
        $this->logger->warning('Temporary API error: ' . $e->getMessage());
    } elseif ($e->isConfigurationError()) {
        $this->logger->error('Configuration error: ' . $e->getMessage());
    } elseif ($e->isValidationError()) {
        $this->logger->error('Validation error: ' . $e->getMessage());
    }

    // Get Mondial Relay error code
    $errorCode = $e->getMondialRelayErrorCode();
}
```

### Error Codes

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

---

## Working with Collections

```php
$collection = $this->soapClient->findRelayPoints($criteria);

// Basic operations
echo "Found: " . $collection->count() . " relay points\n";
echo "Total available: " . $collection->totalCount . "\n";

// Get first result
$first = $collection->first();

// Find by ID
$specific = $collection->findById('012345');

// Filter by distance
$nearbyOnly = $collection->filterByMaxDistance(1000); // Within 1km

// Filter active only
$activeOnly = $collection->filterActive();

// Chain filters
$filtered = $collection
    ->filterActive()
    ->filterByMaxDistance(2000);

// Map to array
$names = $collection->map(fn($rp) => $rp->name);

// Convert to array (for JSON API)
$array = $collection->toArray();
```

---

## Debugging

### Enable Logging

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

**Authentication Failed**
- Check credentials in Admin > Configuration > Mondial Relay
- Verify your account is active on Mondial Relay portal
- Ensure sandbox mode matches your credentials type

**SOAP Errors**
- Ensure PHP SOAP extension is installed: `php -m | grep soap`
- Check firewall allows HTTPS connections to `api.mondialrelay.com`

**Empty Results**
- Verify the postal code/coordinates are correct
- Try increasing the search radius
- Check if the area is covered by Mondial Relay

---

## DTOs Reference

| DTO | Purpose |
|-----|---------|
| `RelayPointSearchCriteria` | Search parameters for relay points |
| `RelayPointDTO` | Relay point information |
| `RelayPointCollection` | Iterable collection of relay points |
| `ShipmentRequest` | Shipment creation parameters |
| `ShipmentResponse` | Created shipment details |
| `LabelResponse` | Shipping label PDF content |

## Links

- [Mondial Relay API Documentation](https://www.mondialrelay.fr/api-documentation)
- [Plugin Repository](https://github.com/kiora-tech/sylius-mondial-relay-plugin)
- [Issue Tracker](https://github.com/kiora-tech/sylius-mondial-relay-plugin/issues)
