<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Kiora\SyliusMondialRelayPlugin\Api\Client\MondialRelayApiClientInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Filesystem\Filesystem;

final class MondialRelayLabelGenerator implements MondialRelayLabelGeneratorInterface
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly MondialRelayApiClientInterface $apiClient,
        private readonly LoggerInterface $logger,
        private readonly string $labelsDirectory,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function generateLabel(ShipmentInterface $shipment): array
    {
        try {
            $order = $shipment->getOrder();
            if (null === $order) {
                return [
                    'success' => false,
                    'error' => 'Shipment has no associated order',
                ];
            }

            // Check if relay point is assigned
            // TODO: Get relay point from shipment (implement getRelayPointId() method)
            // For now, return a placeholder error
            return [
                'success' => false,
                'error' => 'Label generation not yet implemented. Please implement the API call to generate labels.',
            ];

            // TODO: Implement actual label generation
            // 1. Call Mondial Relay API to create shipment
            // 2. Download label PDF
            // 3. Save to filesystem
            // 4. Update shipment with tracking number
            // 5. Return success with label path
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate Mondial Relay label', [
                'shipment_id' => $shipment->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getLabelPath(ShipmentInterface $shipment): ?string
    {
        $labelPath = sprintf('%s/%s.pdf', $this->labelsDirectory, $shipment->getId());

        if ($this->filesystem->exists($labelPath)) {
            return $labelPath;
        }

        return null;
    }

    public function hasLabel(ShipmentInterface $shipment): bool
    {
        return null !== $this->getLabelPath($shipment);
    }

    public function deleteLabel(ShipmentInterface $shipment): bool
    {
        $labelPath = $this->getLabelPath($shipment);

        if (null === $labelPath) {
            return false;
        }

        try {
            $this->filesystem->remove($labelPath);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete Mondial Relay label', [
                'shipment_id' => $shipment->getId(),
                'label_path' => $labelPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
