<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\DTO;

/**
 * Data Transfer Object for relay point search criteria.
 *
 * Encapsulates all parameters needed to search for Mondial Relay pickup points.
 * Uses readonly properties for immutability and promoted constructors for conciseness.
 */
readonly class RelayPointSearchCriteria
{
    private const DEFAULT_RADIUS_KM = 20;
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 50;

    /**
     * @param string|null $postalCode Postal code for search (required if coordinates not provided)
     * @param string|null $city City name for search (optional, helps refine results)
     * @param string $countryCode ISO 3166-1 alpha-2 country code (e.g., 'FR', 'BE', 'ES')
     * @param float|null $latitude Latitude coordinate for GPS-based search
     * @param float|null $longitude Longitude coordinate for GPS-based search
     * @param int $radius Search radius in kilometers (default: 20km)
     * @param int $limit Maximum number of results to return (default: 20, max: 50)
     * @param string|null $deliveryMode Delivery mode code (e.g., '24R', 'DRI', null for all)
     * @param int|null $weight Package weight in grams (for filtering compatible points)
     */
    public function __construct(
        public ?string $postalCode = null,
        public ?string $city = null,
        public string $countryCode = 'FR',
        public ?float $latitude = null,
        public ?float $longitude = null,
        public int $radius = self::DEFAULT_RADIUS_KM,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $deliveryMode = null,
        public ?int $weight = null,
    ) {
        $this->validate();
    }

    /**
     * Check if GPS coordinates are provided.
     */
    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Check if postal code search is provided.
     */
    public function hasPostalCode(): bool
    {
        return $this->postalCode !== null && $this->postalCode !== '';
    }

    /**
     * Validate search criteria.
     *
     * @throws \InvalidArgumentException When criteria are invalid
     */
    private function validate(): void
    {
        if (!$this->hasCoordinates() && !$this->hasPostalCode()) {
            throw new \InvalidArgumentException(
                'Either postal code or GPS coordinates must be provided for relay point search.'
            );
        }

        if ($this->hasCoordinates()) {
            if ($this->latitude < -90 || $this->latitude > 90) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid latitude: %f. Must be between -90 and 90.', $this->latitude)
                );
            }

            if ($this->longitude < -180 || $this->longitude > 180) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid longitude: %f. Must be between -180 and 180.', $this->longitude)
                );
            }
        }

        if ($this->radius <= 0 || $this->radius > 100) {
            throw new \InvalidArgumentException(
                sprintf('Invalid radius: %d km. Must be between 1 and 100.', $this->radius)
            );
        }

        if ($this->limit <= 0 || $this->limit > self::MAX_LIMIT) {
            throw new \InvalidArgumentException(
                sprintf('Invalid limit: %d. Must be between 1 and %d.', $this->limit, self::MAX_LIMIT)
            );
        }

        if ($this->weight !== null && $this->weight <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Invalid weight: %d grams. Must be positive.', $this->weight)
            );
        }
    }

    /**
     * Create criteria from postal code search.
     *
     * @param string $postalCode Postal code
     * @param string $countryCode ISO country code
     * @param string|null $city Optional city name
     * @param int $radius Search radius in km
     * @param int $limit Maximum results
     */
    public static function fromPostalCode(
        string $postalCode,
        string $countryCode = 'FR',
        ?string $city = null,
        int $radius = self::DEFAULT_RADIUS_KM,
        int $limit = self::DEFAULT_LIMIT
    ): self {
        return new self(
            postalCode: $postalCode,
            city: $city,
            countryCode: $countryCode,
            radius: $radius,
            limit: $limit
        );
    }

    /**
     * Create criteria from GPS coordinates.
     *
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $countryCode ISO country code
     * @param int $radius Search radius in km
     * @param int $limit Maximum results
     */
    public static function fromCoordinates(
        float $latitude,
        float $longitude,
        string $countryCode = 'FR',
        int $radius = self::DEFAULT_RADIUS_KM,
        int $limit = self::DEFAULT_LIMIT
    ): self {
        return new self(
            latitude: $latitude,
            longitude: $longitude,
            countryCode: $countryCode,
            radius: $radius,
            limit: $limit
        );
    }

    /**
     * Return a new instance with a different radius.
     *
     * @param int $radius Search radius in kilometers
     */
    public function withRadius(int $radius): self
    {
        return new self(
            postalCode: $this->postalCode,
            city: $this->city,
            countryCode: $this->countryCode,
            latitude: $this->latitude,
            longitude: $this->longitude,
            radius: $radius,
            limit: $this->limit,
            deliveryMode: $this->deliveryMode,
            weight: $this->weight,
        );
    }

    /**
     * Return a new instance with a different limit.
     *
     * @param int $limit Maximum number of results
     */
    public function withLimit(int $limit): self
    {
        return new self(
            postalCode: $this->postalCode,
            city: $this->city,
            countryCode: $this->countryCode,
            latitude: $this->latitude,
            longitude: $this->longitude,
            radius: $this->radius,
            limit: min($limit, self::MAX_LIMIT),
            deliveryMode: $this->deliveryMode,
            weight: $this->weight,
        );
    }

    /**
     * Return a new instance with a delivery mode.
     *
     * @param string|null $deliveryMode Delivery mode code
     */
    public function withDeliveryMode(?string $deliveryMode): self
    {
        return new self(
            postalCode: $this->postalCode,
            city: $this->city,
            countryCode: $this->countryCode,
            latitude: $this->latitude,
            longitude: $this->longitude,
            radius: $this->radius,
            limit: $this->limit,
            deliveryMode: $deliveryMode,
            weight: $this->weight,
        );
    }

    /**
     * Return a new instance with a weight.
     *
     * @param int|null $weight Package weight in grams
     */
    public function withWeight(?int $weight): self
    {
        return new self(
            postalCode: $this->postalCode,
            city: $this->city,
            countryCode: $this->countryCode,
            latitude: $this->latitude,
            longitude: $this->longitude,
            radius: $this->radius,
            limit: $this->limit,
            deliveryMode: $this->deliveryMode,
            weight: $weight,
        );
    }
}
