<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait to be applied on Sylius\Component\Core\Model\Shipment.
 *
 * Usage:
 * ```php
 * use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentInterface;
 * use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentTrait;
 *
 * class Shipment extends BaseShipment implements MondialRelayShipmentInterface
 * {
 *     use MondialRelayShipmentTrait;
 * }
 * ```
 */
trait MondialRelayShipmentTrait
{
    #[ORM\OneToOne(targetEntity: MondialRelayPickupPoint::class)]
    #[ORM\JoinColumn(name: 'mondial_relay_pickup_point_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?MondialRelayPickupPointInterface $mondialRelayPickupPoint = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $mondialRelayTrackingNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mondialRelayLabelUrl = null;

    public function getMondialRelayPickupPoint(): ?MondialRelayPickupPointInterface
    {
        return $this->mondialRelayPickupPoint;
    }

    public function setMondialRelayPickupPoint(?MondialRelayPickupPointInterface $pickupPoint): self
    {
        $this->mondialRelayPickupPoint = $pickupPoint;

        return $this;
    }

    public function getMondialRelayTrackingNumber(): ?string
    {
        return $this->mondialRelayTrackingNumber;
    }

    public function setMondialRelayTrackingNumber(?string $trackingNumber): self
    {
        $this->mondialRelayTrackingNumber = $trackingNumber;

        return $this;
    }

    public function getMondialRelayLabelUrl(): ?string
    {
        return $this->mondialRelayLabelUrl;
    }

    public function setMondialRelayLabelUrl(?string $labelUrl): self
    {
        $this->mondialRelayLabelUrl = $labelUrl;

        return $this;
    }
}
