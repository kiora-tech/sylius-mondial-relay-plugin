# Mondial Relay Plugin - Entities

This directory contains Doctrine ORM entities for the Kiora Sylius Mondial Relay Plugin.

## Entities Overview

### 1. MondialRelayPickupPoint

The main entity for storing Mondial Relay pickup points (relay points).

**Table:** `kiora_mondial_relay_pickup_point`

**Key Features:**
- Stores complete pickup point information from Mondial Relay API
- Indexed for efficient querying by relay point ID, postal code, country code, and creation date
- JSON storage for opening hours (flexible structure)
- Decimal precision for GPS coordinates (10,7)
- Automatic timestamp on creation

**Fields:**
```php
- id: int (auto-generated)
- relayPointId: string(10) - Unique Mondial Relay identifier
- name: string(100) - Relay point name
- street: string(255) - Street address
- postalCode: string(10) - Postal/ZIP code
- city: string(100) - City name
- countryCode: string(2) - ISO 3166-1 alpha-2 country code
- latitude: decimal(10,7) - GPS latitude
- longitude: decimal(10,7) - GPS longitude
- openingHours: json - Opening hours structure
- distanceMeters: int|null - Distance from searched location (optional)
- createdAt: DateTimeImmutable - Creation timestamp
```

**Indexes:**
- `idx_relay_point_id` on `relay_point_id` (unique)
- `idx_postal_code` on `postal_code`
- `idx_country_code` on `country_code`
- `idx_created_at` on `created_at`

### 2. MondialRelayShipmentTrait

A trait to extend Sylius `Shipment` entity with Mondial Relay specific fields.

**Usage in your Sylius application:**

```php
<?php
// src/Entity/Shipping/Shipment.php

declare(strict_types=1);

namespace App\Entity\Shipping;

use Doctrine\ORM\Mapping as ORM;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentInterface;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentTrait;
use Sylius\Component\Core\Model\Shipment as BaseShipment;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_shipment')]
class Shipment extends BaseShipment implements MondialRelayShipmentInterface
{
    use MondialRelayShipmentTrait;
}
```

**Added Fields:**
```php
- mondialRelayPickupPoint: MondialRelayPickupPoint|null - Selected relay point
- mondialRelayTrackingNumber: string(50)|null - Tracking number from Mondial Relay
- mondialRelayLabelUrl: text|null - URL to shipping label PDF
```

**Database Column:**
- `mondial_relay_pickup_point_id` (foreign key with SET NULL on delete)
- `mondial_relay_tracking_number`
- `mondial_relay_label_url`

### 3. AddressEmbeddable (Optional)

An embeddable Doctrine object for address data, useful if you need to reuse address structure.

**Usage example:**

```php
use Kiora\SyliusMondialRelayPlugin\Entity\AddressEmbeddable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CustomEntity
{
    #[ORM\Embedded(class: AddressEmbeddable::class, columnPrefix: 'shipping_address_')]
    private AddressEmbeddable $shippingAddress;

    #[ORM\Embedded(class: AddressEmbeddable::class, columnPrefix: 'billing_address_')]
    private AddressEmbeddable $billingAddress;
}
```

**Fields:**
```php
- street: string(255)|null
- streetAdditional: string(255)|null
- postalCode: string(10)|null
- city: string(100)|null
- countryCode: string(2)|null
- region: string(100)|null
- latitude: decimal(10,7)|null
- longitude: decimal(10,7)|null
```

**Helper Methods:**
- `getFullAddress(): string` - Returns formatted address string
- `isEmpty(): bool` - Check if address is empty
- `__toString(): string` - String representation

## Doctrine Migration

After implementing these entities in your Sylius application, generate and run migrations:

```bash
# Generate migration
bin/console doctrine:migrations:diff

# Review the generated migration file in migrations/

# Execute migration
bin/console doctrine:migrations:migrate
```

## PHP Version Requirements

These entities use PHP 8.2+ features:
- Constructor property promotion
- Union types
- Attributes for Doctrine mapping
- Strict types declaration

## Best Practices

### 1. Type Safety
All entities use strict types and proper type hints for maximum type safety.

### 2. Immutability
- `createdAt` uses `DateTimeImmutable` for immutability
- Coordinates stored as string to preserve precision (no float rounding)

### 3. Performance
- Proper indexes on frequently queried columns
- OneToOne relationship with SET NULL to avoid orphaned records

### 4. Validation
Consider adding Symfony validation constraints in your Shipment entity:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Valid]
private ?MondialRelayPickupPointInterface $mondialRelayPickupPoint = null;

#[Assert\Length(max: 50)]
private ?string $mondialRelayTrackingNumber = null;

#[Assert\Url]
private ?string $mondialRelayLabelUrl = null;
```

## Database Schema

### kiora_mondial_relay_pickup_point table

```sql
CREATE TABLE kiora_mondial_relay_pickup_point (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relay_point_id VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country_code VARCHAR(2) NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    opening_hours JSON NOT NULL,
    distance_meters INT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX idx_relay_point_id (relay_point_id),
    INDEX idx_postal_code (postal_code),
    INDEX idx_country_code (country_code),
    INDEX idx_created_at (created_at)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
```

### Shipment table additions

```sql
ALTER TABLE sylius_shipment
ADD COLUMN mondial_relay_pickup_point_id INT DEFAULT NULL,
ADD COLUMN mondial_relay_tracking_number VARCHAR(50) DEFAULT NULL,
ADD COLUMN mondial_relay_label_url TEXT DEFAULT NULL,
ADD CONSTRAINT FK_mondial_relay_pickup_point
    FOREIGN KEY (mondial_relay_pickup_point_id)
    REFERENCES kiora_mondial_relay_pickup_point (id)
    ON DELETE SET NULL;
```

## Testing

Example test for MondialRelayPickupPoint:

```php
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use PHPUnit\Framework\TestCase;

class MondialRelayPickupPointTest extends TestCase
{
    public function testCreatePickupPoint(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue de la Paix')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([
                'monday' => ['09:00-12:00', '14:00-19:00'],
            ])
            ->setDistanceMeters(500);

        $this->assertEquals('123456', $pickupPoint->getRelayPointId());
        $this->assertEquals('Paris', $pickupPoint->getCity());
        $this->assertInstanceOf(\DateTimeImmutable::class, $pickupPoint->getCreatedAt());
    }
}
```

## See Also

- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Sylius Shipment Documentation](https://docs.sylius.com/en/latest/book/orders/shipments.html)
- [Mondial Relay API Documentation](https://www.mondialrelay.fr/solutionspro/documentation-technique/)
