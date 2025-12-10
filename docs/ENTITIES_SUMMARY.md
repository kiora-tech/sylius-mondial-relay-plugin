# Entities Summary - Kiora Sylius Mondial Relay Plugin

## Overview

This document summarizes all entities created for the Mondial Relay Sylius plugin with their key features and usage.

## Created Files

### Core Entities

#### 1. `/src/Entity/MondialRelayPickupPointInterface.php`
- **Purpose**: Interface defining contract for pickup point entities
- **Key Methods**:
  - Getters/setters for all pickup point properties
  - GPS coordinates management
  - Opening hours handling

#### 2. `/src/Entity/MondialRelayPickupPoint.php`
- **Purpose**: Main entity for storing Mondial Relay pickup points
- **Database Table**: `kiora_mondial_relay_pickup_point`
- **Features**:
  - PHP 8.2+ attributes for Doctrine ORM
  - Symfony validation constraints
  - Custom GPS coordinates validation
  - JSON storage for opening hours
  - Auto-generated timestamps
  - 4 indexes for performance optimization
- **Validation**:
  - NotBlank constraints on required fields
  - Length constraints matching database schema
  - Country code validation (ISO 3166-1 alpha-2)
  - Custom coordinate validation (-90 to 90 for latitude, -180 to 180 for longitude)

#### 3. `/src/Entity/MondialRelayShipmentInterface.php`
- **Purpose**: Interface for extending Sylius Shipment with Mondial Relay features
- **Key Methods**:
  - `getMondialRelayPickupPoint()` / `setMondialRelayPickupPoint()`
  - `getMondialRelayTrackingNumber()` / `setMondialRelayTrackingNumber()`
  - `getMondialRelayLabelUrl()` / `setMondialRelayLabelUrl()`

#### 4. `/src/Entity/MondialRelayShipmentTrait.php`
- **Purpose**: Trait to extend Sylius Shipment entity
- **Features**:
  - OneToOne relationship with MondialRelayPickupPoint
  - SET NULL on delete (prevents orphaned records)
  - Tracking number storage (50 chars)
  - Label URL storage (TEXT type)
- **Usage**: Apply to your custom Shipment entity

#### 5. `/src/Entity/AddressEmbeddable.php`
- **Purpose**: Optional embeddable for address data
- **Features**:
  - Reusable address structure
  - GPS coordinates support
  - Helper methods (getFullAddress, isEmpty)
  - Can be embedded multiple times with different column prefixes

### Validators

#### 6. `/src/Validator/Constraints/ValidCoordinates.php`
- **Purpose**: Custom constraint for GPS coordinates validation
- **Usage**: PHP attribute on latitude/longitude properties
- **Features**:
  - Validates latitude range (-90 to 90)
  - Validates longitude range (-180 to 180)
  - Format validation (numeric strings)
  - Customizable error messages

#### 7. `/src/Validator/Constraints/ValidCoordinatesValidator.php`
- **Purpose**: Validator implementation for ValidCoordinates constraint
- **Features**:
  - Handles string and numeric values
  - Clear error messages
  - Type-safe validation

## Database Schema

### Table: kiora_mondial_relay_pickup_point

```sql
CREATE TABLE kiora_mondial_relay_pickup_point (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relay_point_id VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country_code VARCHAR(2) NOT NULL,
    latitude NUMERIC(10, 7) NOT NULL,
    longitude NUMERIC(10, 7) NOT NULL,
    opening_hours JSON NOT NULL,
    distance_meters INT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',

    INDEX idx_relay_point_id (relay_point_id),
    INDEX idx_postal_code (postal_code),
    INDEX idx_country_code (country_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Extended Sylius Shipment Table

```sql
ALTER TABLE sylius_shipment
ADD mondial_relay_pickup_point_id INT DEFAULT NULL,
ADD mondial_relay_tracking_number VARCHAR(50) DEFAULT NULL,
ADD mondial_relay_label_url LONGTEXT DEFAULT NULL,
ADD CONSTRAINT FK_mondial_relay_pickup_point
    FOREIGN KEY (mondial_relay_pickup_point_id)
    REFERENCES kiora_mondial_relay_pickup_point (id)
    ON DELETE SET NULL;

CREATE INDEX IDX_mondial_relay_pickup_point
ON sylius_shipment (mondial_relay_pickup_point_id);
```

## Key Features

### 1. Type Safety
- All entities use PHP 8.2+ strict types
- Proper type hints on all methods
- Nullable types where appropriate
- PHPDoc for array types

### 2. Validation
- Symfony validation constraints on all fields
- Custom GPS coordinates validator
- Country code validation (ISO standard)
- Length constraints matching database schema
- Required field validation

### 3. Performance
- Strategic indexes on frequently queried columns:
  - `relay_point_id` (unique searches)
  - `postal_code` (geographic searches)
  - `country_code` (filtering by country)
  - `created_at` (temporal queries)
- OneToOne relationship (better than ManyToOne for this use case)
- Foreign key with SET NULL (no orphaned records)

### 4. Flexibility
- JSON storage for opening hours (handles varying formats)
- Optional distance field (calculated during search)
- Embeddable address class for reusability
- Interface-based design (easy mocking for tests)

### 5. Immutability
- `DateTimeImmutable` for createdAt
- String storage for coordinates (no float precision loss)

## Integration Steps

### 1. Extend Sylius Shipment

```php
// src/Entity/Shipping/Shipment.php
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentInterface;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentTrait;

class Shipment extends BaseShipment implements MondialRelayShipmentInterface
{
    use MondialRelayShipmentTrait;
}
```

### 2. Configure Sylius Resource

```yaml
# config/packages/sylius_core.yaml
sylius_core:
    resources:
        shipment:
            classes:
                model: App\Entity\Shipping\Shipment
```

### 3. Generate Migration

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

## Usage Examples

### Create Pickup Point

```php
$pickupPoint = new MondialRelayPickupPoint();
$pickupPoint
    ->setRelayPointId('123456')
    ->setName('Relais Test')
    ->setStreet('123 rue de Test')
    ->setPostalCode('75001')
    ->setCity('Paris')
    ->setCountryCode('FR')
    ->setLatitude('48.8566140')
    ->setLongitude('2.3522219')
    ->setOpeningHours([
        'monday' => ['09:00-12:00', '14:00-19:00'],
    ]);

$entityManager->persist($pickupPoint);
$entityManager->flush();
```

### Assign to Shipment

```php
$shipment->setMondialRelayPickupPoint($pickupPoint);
$shipment->setMondialRelayTrackingNumber('MR123456789FR');
$shipment->setMondialRelayLabelUrl('https://example.com/label.pdf');
$entityManager->flush();
```

### Validate Entity

```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

$errors = $validator->validate($pickupPoint);

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo $error->getPropertyPath() . ': ' . $error->getMessage() . "\n";
    }
}
```

## Testing

### Unit Test Example

```php
class MondialRelayPickupPointTest extends TestCase
{
    public function testValidCoordinates(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219');

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $errors = $validator->validate($pickupPoint);

        $this->assertCount(0, $errors);
    }
}
```

## Best Practices

1. **Always validate** entities before persisting
2. **Use repositories** for complex queries
3. **Index strategically** based on query patterns
4. **Handle null values** properly (especially for optional pickupPoint on Shipment)
5. **Use transactions** when creating related entities
6. **Clear entity manager** periodically in batch operations
7. **Use query builders** instead of DQL for dynamic queries

## Performance Considerations

- **Pickup Point Lookups**: Indexed by relay_point_id (unique) for O(1) lookups
- **Geographic Searches**: Indexed by postal_code and country_code
- **Temporal Queries**: Indexed by created_at for recent pickup points
- **Foreign Key**: SET NULL prevents cascade deletes affecting shipments
- **JSON Field**: Efficient storage for variable opening hours structure

## Potential Enhancements

1. **Add SoftDelete**: Use Gedmo Doctrine Extensions for soft deletes
2. **Add Timestampable**: Auto-update updatedAt field on changes
3. **Add Translatable**: Support for multilingual names/addresses
4. **Add Sluggable**: URL-friendly identifiers for pickup points
5. **Add Versioning**: Track changes to pickup point data
6. **Add Geospatial Index**: Use MySQL spatial extensions for distance queries
7. **Add Caching**: Redis cache for frequently accessed pickup points

## Documentation Files

1. `README.md` - Entity documentation with usage examples
2. `MIGRATION_EXAMPLE.md` - Complete migration guide with SQL examples
3. `INTEGRATION_GUIDE.md` - Step-by-step integration into Sylius app
4. `ENTITIES_SUMMARY.md` - This file

## Compatibility

- **PHP**: 8.2+
- **Symfony**: 6.4+ / 7.0+
- **Doctrine ORM**: 2.14+ / 3.0+
- **Sylius**: 2.1+
- **MySQL**: 5.7.8+ or MariaDB 10.2.7+ (for JSON support)
- **PostgreSQL**: 9.4+ (with JSONB support)

## File Sizes

```
Entity/MondialRelayPickupPoint.php       ~6.8 KB
Entity/MondialRelayPickupPointInterface.php  ~1.5 KB
Entity/MondialRelayShipmentTrait.php     ~2.0 KB
Entity/MondialRelayShipmentInterface.php ~0.6 KB
Entity/AddressEmbeddable.php             ~4.1 KB
Validator/Constraints/ValidCoordinates.php        ~1.2 KB
Validator/Constraints/ValidCoordinatesValidator.php ~2.1 KB
```

## Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "doctrine/orm": "^2.14|^3.0",
        "symfony/validator": "^6.4|^7.0",
        "sylius/sylius": "^2.1"
    }
}
```

## Next Steps

1. ✅ Create entities with Doctrine attributes
2. ✅ Add Symfony validation constraints
3. ✅ Create custom GPS validator
4. ✅ Document usage and integration
5. ⏳ Create repository with complex queries
6. ⏳ Add Sylius admin grid configuration
7. ⏳ Create API endpoints for pickup point search
8. ⏳ Add frontend widget for pickup point selection
9. ⏳ Implement label generation service
10. ⏳ Write comprehensive tests

## Support

For questions or issues related to these entities:
1. Check the integration guide in `docs/INTEGRATION_GUIDE.md`
2. Review migration examples in `docs/MIGRATION_EXAMPLE.md`
3. Consult entity README in `src/Entity/README.md`
4. Review Sylius documentation: https://docs.sylius.com/
5. Check Doctrine ORM docs: https://www.doctrine-project.org/

## License

This plugin follows the same license as the main Kiora Sylius Mondial Relay Plugin project.
