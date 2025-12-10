<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Sylius\Component\Core\Model\ShipmentInterface;

interface MondialRelayQrCodeGeneratorInterface
{
    /**
     * Generate a QR code for the given shipment.
     *
     * @return array{success: bool, error?: string, qr_code_url?: string, tracking_number?: string}
     */
    public function generateQrCode(ShipmentInterface $shipment): array;

    /**
     * Get the URL to the generated QR code image.
     */
    public function getQrCodeUrl(ShipmentInterface $shipment): ?string;

    /**
     * Check if a QR code exists for the given shipment.
     */
    public function hasQrCode(ShipmentInterface $shipment): bool;

    /**
     * Delete the QR code for the given shipment.
     */
    public function deleteQrCode(ShipmentInterface $shipment): bool;
}
