<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\DTO;

/**
 * Data Transfer Object for Mondial Relay shipment creation response.
 *
 * Contains the expedition number, tracking information, and label URL
 * returned after successfully creating a shipment.
 */
readonly class ShipmentResponse
{
    /**
     * @param string $expeditionNumber Mondial Relay expedition number (unique identifier)
     * @param string $trackingUrl Public tracking URL for the shipment
     * @param string $labelUrl URL to download the shipping label PDF
     * @param string|null $qrCode Optional QR code data for label-free deposit
     * @param \DateTimeInterface $createdAt Shipment creation timestamp
     * @param array<string, mixed> $metadata Additional metadata from API response
     */
    public function __construct(
        public string $expeditionNumber,
        public string $trackingUrl,
        public string $labelUrl,
        public ?string $qrCode = null,
        public ?\DateTimeInterface $createdAt = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Check if QR code is available.
     */
    public function hasQrCode(): bool
    {
        return $this->qrCode !== null && $this->qrCode !== '';
    }

    /**
     * Get a short version of the expedition number for display.
     *
     * @param int $length Number of characters to keep (default: 8)
     */
    public function getShortExpeditionNumber(int $length = 8): string
    {
        if (strlen($this->expeditionNumber) <= $length) {
            return $this->expeditionNumber;
        }

        return substr($this->expeditionNumber, 0, $length) . '...';
    }

    /**
     * Create response from API response array.
     *
     * @param array<string, mixed> $data Raw API response data
     */
    public static function fromApiResponse(array $data): self
    {
        $createdAt = null;
        if (isset($data['createdAt'])) {
            $createdAt = is_string($data['createdAt'])
                ? new \DateTimeImmutable($data['createdAt'])
                : $data['createdAt'];
        }

        return new self(
            expeditionNumber: (string) $data['expeditionNumber'],
            trackingUrl: (string) $data['trackingUrl'],
            labelUrl: (string) $data['labelUrl'],
            qrCode: $data['qrCode'] ?? null,
            createdAt: $createdAt ?? new \DateTimeImmutable(),
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'expeditionNumber' => $this->expeditionNumber,
            'trackingUrl' => $this->trackingUrl,
            'labelUrl' => $this->labelUrl,
            'qrCode' => $this->qrCode,
            'createdAt' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
        ];
    }
}
