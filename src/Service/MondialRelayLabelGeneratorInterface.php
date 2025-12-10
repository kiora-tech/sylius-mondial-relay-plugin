<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Sylius\Component\Core\Model\ShipmentInterface;

interface MondialRelayLabelGeneratorInterface
{
    /**
     * Generate a shipping label for the given shipment.
     *
     * @return array{success: bool, error?: string, label_path?: string, tracking_number?: string}
     */
    public function generateLabel(ShipmentInterface $shipment): array;

    /**
     * Get the path to the generated label PDF.
     */
    public function getLabelPath(ShipmentInterface $shipment): ?string;

    /**
     * Check if a label exists for the given shipment.
     */
    public function hasLabel(ShipmentInterface $shipment): bool;

    /**
     * Delete the label for the given shipment.
     */
    public function deleteLabel(ShipmentInterface $shipment): bool;
}
