<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Filesystem\Filesystem;

final class MondialRelayQrCodeGenerator implements MondialRelayQrCodeGeneratorInterface
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $qrCodesDirectory,
        private readonly string $qrCodesPublicPath,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function generateQrCode(ShipmentInterface $shipment): array
    {
        try {
            // Check if shipment has Mondial Relay pickup point
            if (!$shipment instanceof MondialRelayShipmentInterface) {
                return [
                    'success' => false,
                    'error' => 'Shipment does not support Mondial Relay.',
                ];
            }

            $pickupPoint = $shipment->getMondialRelayPickupPoint();
            if (null === $pickupPoint) {
                return [
                    'success' => false,
                    'error' => 'Aucun point relais sélectionné pour cette expédition.',
                ];
            }

            // Build QR code data with pickup point information
            // Format: MR|RelayPointId|Name|PostalCode|City
            // This can be scanned by Mondial Relay depot staff
            $qrData = sprintf(
                "MR|%s|%s|%s|%s",
                $pickupPoint->getRelayPointId(),
                $this->sanitizeForQrCode($pickupPoint->getName()),
                $pickupPoint->getPostalCode(),
                $this->sanitizeForQrCode($pickupPoint->getCity())
            );

            // Create directory if it doesn't exist
            if (!$this->filesystem->exists($this->qrCodesDirectory)) {
                $this->filesystem->mkdir($this->qrCodesDirectory, 0755);
            }

            // Generate QR code
            $qrCodeFilename = sprintf('mr-%s.png', $shipment->getId());
            $qrCodePath = sprintf('%s/%s', $this->qrCodesDirectory, $qrCodeFilename);

            $builder = new Builder(
                data: $qrData,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 10,
            );

            $result = $builder->build();
            $result->saveToFile($qrCodePath);

            $qrCodeUrl = sprintf('%s/%s', $this->qrCodesPublicPath, $qrCodeFilename);

            // Save QR code URL to shipment
            $shipment->setMondialRelayLabelUrl($qrCodeUrl);
            $this->entityManager->persist($shipment);
            $this->entityManager->flush();

            $this->logger->info('QR code generated successfully', [
                'shipment_id' => $shipment->getId(),
                'relay_point_id' => $pickupPoint->getRelayPointId(),
                'qr_code_url' => $qrCodeUrl,
            ]);

            return [
                'success' => true,
                'qr_code_url' => $qrCodeUrl,
                'relay_point_id' => $pickupPoint->getRelayPointId(),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate QR code', [
                'shipment_id' => $shipment->getId(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sanitize string for QR code (remove special characters, limit length)
     */
    private function sanitizeForQrCode(string $value): string
    {
        // Remove accents and special characters
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        // Remove pipe character (used as separator)
        $value = str_replace('|', ' ', $value);
        // Limit length
        return substr(trim($value), 0, 50);
    }

    public function getQrCodeUrl(ShipmentInterface $shipment): ?string
    {
        // First check if URL is stored in shipment
        if ($shipment instanceof MondialRelayShipmentInterface) {
            $storedUrl = $shipment->getMondialRelayLabelUrl();
            if (null !== $storedUrl && '' !== $storedUrl) {
                return $storedUrl;
            }
        }

        // Fallback: check if file exists on disk
        $qrCodeFilename = sprintf('mr-%s.png', $shipment->getId());
        $qrCodePath = sprintf('%s/%s', $this->qrCodesDirectory, $qrCodeFilename);

        if ($this->filesystem->exists($qrCodePath)) {
            return sprintf('%s/%s', $this->qrCodesPublicPath, $qrCodeFilename);
        }

        return null;
    }

    public function hasQrCode(ShipmentInterface $shipment): bool
    {
        return null !== $this->getQrCodeUrl($shipment);
    }

    public function deleteQrCode(ShipmentInterface $shipment): bool
    {
        $qrCodeFilename = sprintf('mr-%s.png', $shipment->getId());
        $qrCodePath = sprintf('%s/%s', $this->qrCodesDirectory, $qrCodeFilename);

        $deleted = false;

        // Remove file from disk
        if ($this->filesystem->exists($qrCodePath)) {
            try {
                $this->filesystem->remove($qrCodePath);
                $deleted = true;
            } catch (\Exception $e) {
                $this->logger->error('Failed to delete QR code file', [
                    'shipment_id' => $shipment->getId(),
                    'qr_code_path' => $qrCodePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear URL from shipment
        if ($shipment instanceof MondialRelayShipmentInterface) {
            $shipment->setMondialRelayLabelUrl(null);
            $this->entityManager->persist($shipment);
            $this->entityManager->flush();
            $deleted = true;
        }

        return $deleted;
    }
}
