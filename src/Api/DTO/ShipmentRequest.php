<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\DTO;

/**
 * Data Transfer Object for creating a Mondial Relay shipment.
 *
 * Contains all required information to create an expedition:
 * sender, recipient, relay point, package details.
 */
readonly class ShipmentRequest
{
    /**
     * @param string $orderReference Merchant's order reference
     * @param string $relayPointId Destination relay point identifier
     * @param string $countryCode Destination country code
     * @param string $recipientName Recipient full name
     * @param string $recipientEmail Recipient email address
     * @param string $recipientPhone Recipient phone number (international format)
     * @param string $recipientAddressLine1 Recipient address line 1
     * @param string|null $recipientAddressLine2 Optional recipient address line 2
     * @param string $recipientPostalCode Recipient postal code
     * @param string $recipientCity Recipient city
     * @param int $weightGrams Package weight in grams
     * @param string $deliveryMode Delivery mode code (e.g., '24R', 'DRI', 'LD1')
     * @param int|null $lengthCm Optional package length in cm
     * @param int|null $widthCm Optional package width in cm
     * @param int|null $heightCm Optional package height in cm
     * @param int|null $declaredValue Optional declared value in cents (for insurance)
     * @param string|null $instructions Optional delivery instructions
     * @param bool $collectionMode Whether this is a collection shipment (seller to relay point)
     * @param array<string, mixed> $customData Optional custom data for internal use
     */
    public function __construct(
        public string $orderReference,
        public string $relayPointId,
        public string $countryCode,
        public string $recipientName,
        public string $recipientEmail,
        public string $recipientPhone,
        public string $recipientAddressLine1,
        public ?string $recipientAddressLine2,
        public string $recipientPostalCode,
        public string $recipientCity,
        public int $weightGrams,
        public string $deliveryMode = '24R',
        public ?int $lengthCm = null,
        public ?int $widthCm = null,
        public ?int $heightCm = null,
        public ?int $declaredValue = null,
        public ?string $instructions = null,
        public bool $collectionMode = false,
        public array $customData = [],
    ) {
        $this->validate();
    }

    /**
     * Validate shipment request data.
     *
     * @throws \InvalidArgumentException When data is invalid
     */
    private function validate(): void
    {
        // Validate weight
        if ($this->weightGrams <= 0 || $this->weightGrams > 30000) {
            throw new \InvalidArgumentException(
                sprintf('Invalid weight: %d grams. Must be between 1 and 30000 (30kg).', $this->weightGrams)
            );
        }

        // Validate dimensions if provided
        if ($this->lengthCm !== null && ($this->lengthCm <= 0 || $this->lengthCm > 150)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid length: %d cm. Must be between 1 and 150.', $this->lengthCm)
            );
        }

        if ($this->widthCm !== null && ($this->widthCm <= 0 || $this->widthCm > 150)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid width: %d cm. Must be between 1 and 150.', $this->widthCm)
            );
        }

        if ($this->heightCm !== null && ($this->heightCm <= 0 || $this->heightCm > 150)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid height: %d cm. Must be between 1 and 150.', $this->heightCm)
            );
        }

        // Validate email
        if (!filter_var($this->recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid recipient email: %s', $this->recipientEmail)
            );
        }

        // Validate phone number (basic check)
        if (strlen($this->recipientPhone) < 10) {
            throw new \InvalidArgumentException(
                'Recipient phone number must be at least 10 characters.'
            );
        }

        // Validate order reference
        if (strlen($this->orderReference) > 35) {
            throw new \InvalidArgumentException(
                sprintf('Order reference too long: %d characters. Maximum is 35.', strlen($this->orderReference))
            );
        }
    }

    /**
     * Convert to array for API request.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'orderReference' => $this->orderReference,
            'relayPoint' => [
                'id' => $this->relayPointId,
                'countryCode' => $this->countryCode,
            ],
            'recipient' => [
                'name' => $this->recipientName,
                'email' => $this->recipientEmail,
                'phone' => $this->recipientPhone,
                'address' => [
                    'line1' => $this->recipientAddressLine1,
                    'line2' => $this->recipientAddressLine2,
                    'postalCode' => $this->recipientPostalCode,
                    'city' => $this->recipientCity,
                    'countryCode' => $this->countryCode,
                ],
            ],
            'package' => [
                'weight' => $this->weightGrams,
                'length' => $this->lengthCm,
                'width' => $this->widthCm,
                'height' => $this->heightCm,
            ],
            'deliveryMode' => $this->deliveryMode,
            'declaredValue' => $this->declaredValue,
            'instructions' => $this->instructions,
            'collectionMode' => $this->collectionMode,
            'customData' => $this->customData,
        ], fn($value) => $value !== null && $value !== [] && $value !== '');
    }
}
