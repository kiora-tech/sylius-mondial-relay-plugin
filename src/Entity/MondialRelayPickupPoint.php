<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Kiora\SyliusMondialRelayPlugin\Validator\Constraints\ValidCoordinates;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'kiora_mondial_relay_pickup_point')]
#[ORM\Index(columns: ['relay_point_id'], name: 'idx_relay_point_id')]
#[ORM\Index(columns: ['postal_code'], name: 'idx_postal_code')]
#[ORM\Index(columns: ['country_code'], name: 'idx_country_code')]
#[ORM\Index(columns: ['created_at'], name: 'idx_created_at')]
class MondialRelayPickupPoint implements MondialRelayPickupPointInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Relay point ID cannot be blank.')]
    #[Assert\Length(
        min: 1,
        max: 10,
        minMessage: 'Relay point ID must be at least {{ limit }} characters.',
        maxMessage: 'Relay point ID cannot be longer than {{ limit }} characters.'
    )]
    private ?string $relayPointId = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Name cannot be blank.')]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Street cannot be blank.')]
    #[Assert\Length(max: 255)]
    private ?string $street = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    #[Assert\NotBlank(message: 'Postal code cannot be blank.')]
    #[Assert\Length(max: 10)]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'City cannot be blank.')]
    #[Assert\Length(max: 100)]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 2)]
    #[Assert\NotBlank(message: 'Country code cannot be blank.')]
    #[Assert\Country(message: 'The country code "{{ value }}" is not valid.')]
    #[Assert\Length(exactly: 2)]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    #[Assert\NotBlank(message: 'Latitude cannot be blank.')]
    #[ValidCoordinates(type: 'latitude')]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    #[Assert\NotBlank(message: 'Longitude cannot be blank.')]
    #[ValidCoordinates(type: 'longitude')]
    private ?string $longitude = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: false)]
    private ?array $openingHours = [];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $distanceMeters = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRelayPointId(): ?string
    {
        return $this->relayPointId;
    }

    public function setRelayPointId(string $relayPointId): self
    {
        $this->relayPointId = $relayPointId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getOpeningHours(): ?array
    {
        return $this->openingHours;
    }

    public function setOpeningHours(array $openingHours): self
    {
        $this->openingHours = $openingHours;

        return $this;
    }

    public function getDistanceMeters(): ?int
    {
        return $this->distanceMeters;
    }

    public function setDistanceMeters(?int $distanceMeters): self
    {
        $this->distanceMeters = $distanceMeters;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s %s %s',
            $this->relayPointId ?? '',
            $this->name ?? '',
            $this->postalCode ?? '',
            $this->city ?? ''
        );
    }
}
