<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Filesystem\Filesystem;

final class MondialRelayQrCodeGenerator implements MondialRelayQrCodeGeneratorInterface
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $qrCodesDirectory,
        private readonly string $qrCodesPublicPath,
    ) {
        $this->filesystem = new Filesystem();
    }

    public function generateQrCode(ShipmentInterface $shipment): array
    {
        try {
            // Get tracking number
            $trackingNumber = $shipment->getTracking();
            if (null === $trackingNumber || '' === $trackingNumber) {
                return [
                    'success' => false,
                    'error' => 'Shipment has no tracking number. Please generate a label first.',
                ];
            }

            // Create directory if it doesn't exist
            if (!$this->filesystem->exists($this->qrCodesDirectory)) {
                $this->filesystem->mkdir($this->qrCodesDirectory, 0755);
            }

            // Generate QR code
            $qrCodePath = sprintf('%s/%s.png', $this->qrCodesDirectory, $shipment->getId());

            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($trackingNumber)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build()
            ;

            $result->saveToFile($qrCodePath);

            $qrCodeUrl = sprintf('%s/%s.png', $this->qrCodesPublicPath, $shipment->getId());

            return [
                'success' => true,
                'qr_code_url' => $qrCodeUrl,
                'tracking_number' => $trackingNumber,
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

    public function getQrCodeUrl(ShipmentInterface $shipment): ?string
    {
        $qrCodePath = sprintf('%s/%s.png', $this->qrCodesDirectory, $shipment->getId());

        if ($this->filesystem->exists($qrCodePath)) {
            return sprintf('%s/%s.png', $this->qrCodesPublicPath, $shipment->getId());
        }

        return null;
    }

    public function hasQrCode(ShipmentInterface $shipment): bool
    {
        return null !== $this->getQrCodeUrl($shipment);
    }

    public function deleteQrCode(ShipmentInterface $shipment): bool
    {
        $qrCodePath = sprintf('%s/%s.png', $this->qrCodesDirectory, $shipment->getId());

        if (!$this->filesystem->exists($qrCodePath)) {
            return false;
        }

        try {
            $this->filesystem->remove($qrCodePath);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete QR code', [
                'shipment_id' => $shipment->getId(),
                'qr_code_path' => $qrCodePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
