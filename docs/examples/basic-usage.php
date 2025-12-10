<?php

declare(strict_types=1);

/**
 * Basic usage examples for Mondial Relay API Client
 *
 * This file demonstrates common use cases for the Mondial Relay API client.
 * Copy and adapt these examples to your own services.
 */

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClient;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\ShipmentRequest;
use Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;

// ============================================================================
// Example 1: Initialize the API client
// ============================================================================

function createApiClient(LoggerInterface $logger): MondialRelayApiClient
{
    return new MondialRelayApiClient(
        apiKey: $_ENV['MONDIAL_RELAY_API_KEY'],
        apiSecret: $_ENV['MONDIAL_RELAY_API_SECRET'],
        sandbox: true, // Use sandbox for testing
        httpClient: HttpClient::create(),
        logger: $logger,
        timeout: 30.0,
        enableRetry: true
    );
}

// ============================================================================
// Example 2: Search relay points by postal code
// ============================================================================

function searchByPostalCode(MondialRelayApiClient $client): void
{
    try {
        // Create search criteria
        $criteria = RelayPointSearchCriteria::fromPostalCode(
            postalCode: '75002',
            countryCode: 'FR',
            city: 'Paris',
            radius: 10, // 10km radius
            limit: 10   // Return top 10 results
        );

        // Execute search
        $collection = $client->findRelayPoints($criteria);

        // Display results
        echo "Found {$collection->count()} relay points:\n\n";

        foreach ($collection as $relayPoint) {
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "ID: {$relayPoint->relayPointId}\n";
            echo "Name: {$relayPoint->name}\n";
            echo "Address: {$relayPoint->getFullAddress()}\n";
            echo "Distance: {$relayPoint->getDistanceKm()} km\n";
            echo "Active: " . ($relayPoint->isActive ? 'Yes' : 'No') . "\n";
            echo "Google Maps: {$relayPoint->getGoogleMapsUrl()}\n";

            // Display opening hours
            if ($relayPoint->isOpenOnDay('monday')) {
                echo "Monday hours: ";
                $hours = $relayPoint->getOpeningHoursForDay('monday');
                foreach ($hours as $slot) {
                    echo "{$slot['open']}-{$slot['close']} ";
                }
                echo "\n";
            }

            // Display services
            if (!empty($relayPoint->services)) {
                echo "Services: " . implode(', ', $relayPoint->services) . "\n";
            }

            echo "\n";
        }
    } catch (MondialRelayApiException $e) {
        echo "Error: {$e->getMessage()}\n";
        echo "Mondial Relay error code: {$e->getMondialRelayErrorCode()}\n";
    }
}

// ============================================================================
// Example 3: Search relay points by GPS coordinates
// ============================================================================

function searchByCoordinates(MondialRelayApiClient $client): void
{
    try {
        // Paris coordinates
        $criteria = RelayPointSearchCriteria::fromCoordinates(
            latitude: 48.8566,
            longitude: 2.3522,
            countryCode: 'FR',
            radius: 5,
            limit: 20
        );

        $collection = $client->findRelayPoints($criteria);

        echo "Found {$collection->count()} relay points near Paris center\n";

        // Filter results
        $withParking = $collection->filterByService('parking');
        echo "With parking: {$withParking->count()}\n";

        $nearbyOnly = $collection->filterByMaxDistance(1000); // Within 1km
        echo "Within 1km: {$nearbyOnly->count()}\n";

        // Get the closest one
        $closest = $collection->first();
        if ($closest !== null) {
            echo "\nClosest relay point:\n";
            echo "{$closest->name} at {$closest->getDistanceKm()} km\n";
        }
    } catch (MondialRelayApiException $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}

// ============================================================================
// Example 4: Get specific relay point details
// ============================================================================

function getRelayPointDetails(MondialRelayApiClient $client, string $relayPointId): void
{
    try {
        $relayPoint = $client->getRelayPoint($relayPointId, 'FR');

        if ($relayPoint === null) {
            echo "Relay point not found\n";
            return;
        }

        echo "Relay Point: {$relayPoint->name}\n";
        echo "Address: {$relayPoint->getFullAddress()}\n";
        echo "Coordinates: {$relayPoint->latitude}, {$relayPoint->longitude}\n";

        // Display full opening hours
        echo "\nOpening hours:\n";
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            echo ucfirst($day) . ": ";
            if ($relayPoint->isOpenOnDay($day)) {
                $hours = $relayPoint->getOpeningHoursForDay($day);
                foreach ($hours as $slot) {
                    echo "{$slot['open']}-{$slot['close']} ";
                }
            } else {
                echo "Closed";
            }
            echo "\n";
        }

        // Display exceptional closures
        if (!empty($relayPoint->exceptionalClosures)) {
            echo "\nExceptional closures:\n";
            foreach ($relayPoint->exceptionalClosures as $closure) {
                echo "- {$closure['date']}: {$closure['reason']}\n";
            }
        }
    } catch (MondialRelayApiException $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}

// ============================================================================
// Example 5: Create a shipment
// ============================================================================

function createShipment(MondialRelayApiClient $client): void
{
    try {
        // Build shipment request
        $request = new ShipmentRequest(
            orderReference: 'ORDER-' . uniqid(),
            relayPointId: '012345',
            countryCode: 'FR',
            recipientName: 'Jean Dupont',
            recipientEmail: 'jean.dupont@example.com',
            recipientPhone: '+33612345678',
            recipientAddressLine1: '10 rue de la Paix',
            recipientAddressLine2: 'Appartement 5',
            recipientPostalCode: '75002',
            recipientCity: 'Paris',
            weightGrams: 1500,
            deliveryMode: '24R', // 24h relay point
            lengthCm: 30,
            widthCm: 20,
            heightCm: 10,
            declaredValue: 5000, // 50.00 EUR in cents
            instructions: 'Fragile - Handle with care'
        );

        // Create shipment
        $response = $client->createShipment($request);

        echo "Shipment created successfully!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Expedition number: {$response->expeditionNumber}\n";
        echo "Tracking URL: {$response->trackingUrl}\n";
        echo "Label URL: {$response->labelUrl}\n";

        if ($response->hasQrCode()) {
            echo "QR Code available: Yes\n";
        }

        echo "Created at: {$response->createdAt?->format('Y-m-d H:i:s')}\n";
    } catch (MondialRelayApiException $e) {
        echo "Failed to create shipment: {$e->getMessage()}\n";

        if ($e->isValidationError()) {
            echo "Please check your shipment data.\n";
        }
    }
}

// ============================================================================
// Example 6: Download shipping label
// ============================================================================

function downloadLabel(MondialRelayApiClient $client, string $expeditionNumber): void
{
    try {
        $label = $client->getLabel($expeditionNumber);

        echo "Label downloaded successfully\n";
        echo "Size: {$label->getHumanReadableSize()}\n";
        echo "Format: {$label->format}\n";

        // Save to file
        $filename = $label->getSuggestedFilename('order_12345');
        $filepath = '/tmp/labels/' . $filename;

        if ($label->saveToFile($filepath)) {
            echo "Label saved to: {$filepath}\n";
        } else {
            echo "Failed to save label to file\n";
        }

        // Or get as data URI for display in browser
        // echo $label->getDataUri();

        // Or get as base64
        // echo $label->getBase64Content();
    } catch (MondialRelayApiException $e) {
        echo "Failed to download label: {$e->getMessage()}\n";
    }
}

// ============================================================================
// Example 7: Advanced error handling
// ============================================================================

function advancedErrorHandling(MondialRelayApiClient $client): void
{
    try {
        $criteria = RelayPointSearchCriteria::fromPostalCode('75002', 'FR');
        $collection = $client->findRelayPoints($criteria);
        // ... process results
    } catch (\Kiora\SyliusMondialRelayPlugin\Api\Exception\MondialRelayAuthenticationException $e) {
        // Authentication failed - critical error
        error_log('CRITICAL: Invalid Mondial Relay API credentials');
        // Send alert to admin
        // Disable feature temporarily
    } catch (MondialRelayApiException $e) {
        // Check error type
        if ($e->isTemporary()) {
            // Temporary error - retry later
            error_log("Temporary error: {$e->getMessage()}");
            // Queue for retry
        } elseif ($e->isConfigurationError()) {
            // Configuration issue
            error_log("Configuration error: {$e->getMessage()}");
            // Alert admin
        } elseif ($e->isValidationError()) {
            // Invalid request data
            error_log("Validation error: {$e->getMessage()}");
            // Show user-friendly error
        } else {
            // Unknown error
            error_log("Unknown error: {$e->getMessage()}");
        }

        // Log context for debugging
        error_log('Error context: ' . json_encode($e->getContext()));
    }
}

// ============================================================================
// Example 8: Working with collections
// ============================================================================

function collectionOperations(MondialRelayApiClient $client): void
{
    try {
        $criteria = RelayPointSearchCriteria::fromPostalCode('75002', 'FR', limit: 50);
        $collection = $client->findRelayPoints($criteria);

        // Basic operations
        echo "Total results: {$collection->count()}\n";
        echo "Total available: {$collection->totalCount}\n";

        // Check if empty
        if ($collection->isEmpty()) {
            echo "No relay points found\n";
            return;
        }

        // Get first
        $first = $collection->first();
        echo "Closest: {$first?->name}\n";

        // Find by ID
        $specific = $collection->findById('012345');
        if ($specific !== null) {
            echo "Found specific point: {$specific->name}\n";
        }

        // Filter by criteria
        $withParking = $collection->filterByService('parking');
        $wheelchairAccessible = $collection->filterByService('wheelchair_accessible');
        $nearbyOnly = $collection->filterByMaxDistance(2000); // Within 2km
        $activeOnly = $collection->filterActive();

        // Chain filters
        $filtered = $collection
            ->filterActive()
            ->filterByMaxDistance(1500)
            ->filterByService('parking');

        echo "After filtering: {$filtered->count()} points\n";

        // Map to extract data
        $names = $collection->map(fn($rp) => $rp->name);
        echo "Names: " . implode(', ', array_slice($names, 0, 5)) . "...\n";

        // Convert to array for JSON API
        $array = $collection->toArray();
        // echo json_encode($array, JSON_PRETTY_PRINT);
    } catch (MondialRelayApiException $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}

// ============================================================================
// Run examples (uncomment the ones you want to test)
// ============================================================================

// $logger = new YourLogger();
// $client = createApiClient($logger);

// searchByPostalCode($client);
// searchByCoordinates($client);
// getRelayPointDetails($client, '012345');
// createShipment($client);
// downloadLabel($client, 'EXP123456789');
// advancedErrorHandling($client);
// collectionOperations($client);
