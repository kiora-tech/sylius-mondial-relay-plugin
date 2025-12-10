<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\DTO;

/**
 * Data Transfer Object representing a Mondial Relay pickup point.
 *
 * Contains all information about a relay point returned by the API,
 * including location, address, opening hours, and available services.
 */
readonly class RelayPointDTO
{
    /**
     * @param string $relayPointId Mondial Relay unique identifier
     * @param string $name Relay point name (e.g., "TABAC LE CENTRAL")
     * @param string $street Street address
     * @param string $postalCode Postal code
     * @param string $city City name
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param float $latitude GPS latitude coordinate
     * @param float $longitude GPS longitude coordinate
     * @param int|null $distanceMeters Distance from search origin in meters
     * @param array<string, array<int, array{open: string, close: string}>> $openingHours Opening hours by day
     * @param array<string> $services Available services (e.g., ['parking', 'wheelchair_accessible'])
     * @param string|null $photoUrl Optional photo URL
     * @param string|null $informations Additional information text
     * @param bool $isActive Whether the relay point is currently active
     * @param array<array{date: string, reason: string}> $exceptionalClosures List of exceptional closures
     */
    public function __construct(
        public string $relayPointId,
        public string $name,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
        public float $latitude,
        public float $longitude,
        public ?int $distanceMeters = null,
        public array $openingHours = [],
        public array $services = [],
        public ?string $photoUrl = null,
        public ?string $informations = null,
        public bool $isActive = true,
        public array $exceptionalClosures = [],
    ) {
    }

    /**
     * Get full address as a single string.
     */
    public function getFullAddress(): string
    {
        return sprintf(
            '%s, %s %s, %s',
            $this->street,
            $this->postalCode,
            $this->city,
            $this->countryCode
        );
    }

    /**
     * Get distance in kilometers (rounded to 2 decimals).
     */
    public function getDistanceKm(): ?float
    {
        if ($this->distanceMeters === null) {
            return null;
        }

        return round($this->distanceMeters / 1000, 2);
    }

    /**
     * Get Google Maps URL for this relay point.
     */
    public function getGoogleMapsUrl(): string
    {
        return sprintf(
            'https://www.google.com/maps/search/?api=1&query=%f,%f',
            $this->latitude,
            $this->longitude
        );
    }

    /**
     * Check if the relay point has a specific service.
     *
     * @param string $service Service code (e.g., 'parking', 'wheelchair_accessible')
     */
    public function hasService(string $service): bool
    {
        return in_array($service, $this->services, true);
    }

    /**
     * Get opening hours for a specific day.
     *
     * @param string $day Day name in lowercase (e.g., 'monday', 'tuesday')
     *
     * @return array<int, array{open: string, close: string}> Array of time slots
     */
    public function getOpeningHoursForDay(string $day): array
    {
        return $this->openingHours[$day] ?? [];
    }

    /**
     * Check if the relay point is open on a specific day.
     *
     * @param string $day Day name in lowercase (e.g., 'monday', 'tuesday')
     */
    public function isOpenOnDay(string $day): bool
    {
        return !empty($this->getOpeningHoursForDay($day));
    }

    /**
     * Create DTO from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            relayPointId: (string) $data['id'],
            name: (string) $data['name'],
            street: (string) $data['address']['street'],
            postalCode: (string) $data['address']['postalCode'],
            city: (string) $data['address']['city'],
            countryCode: (string) $data['address']['countryCode'],
            latitude: (float) $data['coordinates']['latitude'],
            longitude: (float) $data['coordinates']['longitude'],
            distanceMeters: isset($data['distance']) ? (int) $data['distance'] : null,
            openingHours: $data['openingHours'] ?? [],
            services: $data['services'] ?? [],
            photoUrl: $data['photoUrl'] ?? null,
            informations: $data['informations'] ?? null,
            isActive: $data['isActive'] ?? true,
            exceptionalClosures: $data['exceptionalClosures'] ?? [],
        );
    }

    /**
     * Convert DTO to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'relayPointId' => $this->relayPointId,
            'name' => $this->name,
            'address' => [
                'street' => $this->street,
                'postalCode' => $this->postalCode,
                'city' => $this->city,
                'countryCode' => $this->countryCode,
            ],
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'distanceMeters' => $this->distanceMeters,
            'distanceKm' => $this->getDistanceKm(),
            'openingHours' => $this->openingHours,
            'services' => $this->services,
            'photoUrl' => $this->photoUrl,
            'informations' => $this->informations,
            'isActive' => $this->isActive,
            'exceptionalClosures' => $this->exceptionalClosures,
            'googleMapsUrl' => $this->getGoogleMapsUrl(),
        ];
    }
}
