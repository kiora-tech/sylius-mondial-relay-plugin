<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Embeddable Doctrine for address data.
 *
 * This can be used as an alternative to storing address fields directly
 * in the main entity if you need to reuse address structure in multiple places.
 *
 * Usage:
 * ```php
 * #[ORM\Embedded(class: AddressEmbeddable::class, columnPrefix: 'address_')]
 * private AddressEmbeddable $address;
 * ```
 */
#[ORM\Embeddable]
class AddressEmbeddable
{
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $streetAdditional = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    public function __construct(
        ?string $street = null,
        ?string $postalCode = null,
        ?string $city = null,
        ?string $countryCode = null
    ) {
        $this->street = $street;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->countryCode = $countryCode;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getStreetAdditional(): ?string
    {
        return $this->streetAdditional;
    }

    public function setStreetAdditional(?string $streetAdditional): self
    {
        $this->streetAdditional = $streetAdditional;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->streetAdditional,
            $this->postalCode,
            $this->city,
            $this->countryCode,
        ]);

        return implode(', ', $parts);
    }

    public function isEmpty(): bool
    {
        return $this->street === null
            && $this->postalCode === null
            && $this->city === null
            && $this->countryCode === null;
    }

    public function __toString(): string
    {
        return $this->getFullAddress();
    }
}
