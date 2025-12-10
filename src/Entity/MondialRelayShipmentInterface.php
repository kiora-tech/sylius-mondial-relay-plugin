<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Entity;

interface MondialRelayShipmentInterface
{
    public function getMondialRelayPickupPoint(): ?MondialRelayPickupPointInterface;

    public function setMondialRelayPickupPoint(?MondialRelayPickupPointInterface $pickupPoint): self;

    public function getMondialRelayTrackingNumber(): ?string;

    public function setMondialRelayTrackingNumber(?string $trackingNumber): self;

    public function getMondialRelayLabelUrl(): ?string;

    public function setMondialRelayLabelUrl(?string $labelUrl): self;
}
