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

/**
 * Interface for Mondial Relay REST API v2 client.
 *
 * This interface abstracts all interactions with the Mondial Relay API,
 * providing methods for relay point search, shipment creation, and label generation.
 */
interface MondialRelayApiClientInterface
{
    /**
     * Search for relay points based on criteria.
     *
     * @param RelayPointSearchCriteria $criteria Search criteria (postal code, city, coordinates, etc.)
     *
     * @return RelayPointCollection Collection of relay points sorted by distance
     *
     * @throws MondialRelayApiException When API request fails
     */
    public function findRelayPoints(RelayPointSearchCriteria $criteria): RelayPointCollection;

    /**
     * Get detailed information about a specific relay point.
     *
     * @param string $relayPointId Mondial Relay point identifier
     * @param string $countryCode ISO 3166-1 alpha-2 country code (e.g., 'FR', 'BE')
     *
     * @return RelayPointDTO|null Relay point details or null if not found
     *
     * @throws MondialRelayApiException When API request fails
     */
    public function getRelayPoint(string $relayPointId, string $countryCode): ?RelayPointDTO;

    /**
     * Create a new shipment with Mondial Relay.
     *
     * @param ShipmentRequest $request Shipment creation request with sender/recipient details
     *
     * @return ShipmentResponse Shipment details including expedition number and label URL
     *
     * @throws MondialRelayApiException When API request fails or shipment creation is rejected
     */
    public function createShipment(ShipmentRequest $request): ShipmentResponse;

    /**
     * Get the shipping label PDF for an expedition.
     *
     * @param string $expeditionNumber Mondial Relay expedition number
     *
     * @return LabelResponse Label PDF content and metadata
     *
     * @throws MondialRelayApiException When API request fails or label is not available
     */
    public function getLabel(string $expeditionNumber): LabelResponse;
}
