<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Entity;

interface MondialRelayPickupPointInterface
{
    public function getId(): ?int;

    public function getRelayPointId(): ?string;

    public function setRelayPointId(string $relayPointId): self;

    public function getName(): ?string;

    public function setName(string $name): self;

    public function getStreet(): ?string;

    public function setStreet(string $street): self;

    public function getPostalCode(): ?string;

    public function setPostalCode(string $postalCode): self;

    public function getCity(): ?string;

    public function setCity(string $city): self;

    public function getCountryCode(): ?string;

    public function setCountryCode(string $countryCode): self;

    public function getLatitude(): ?string;

    public function setLatitude(string $latitude): self;

    public function getLongitude(): ?string;

    public function setLongitude(string $longitude): self;

    /**
     * @return array<string, mixed>|null
     */
    public function getOpeningHours(): ?array;

    /**
     * @param array<string, mixed> $openingHours
     */
    public function setOpeningHours(array $openingHours): self;

    public function getDistanceMeters(): ?int;

    public function setDistanceMeters(?int $distanceMeters): self;

    public function getCreatedAt(): ?\DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): self;
}
