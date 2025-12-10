# Mondial Relay Entities - README

## What Was Created

This package includes complete Doctrine ORM entities for integrating Mondial Relay pickup points into Sylius 2.1+ applications.

## Files Created (13 files total)

### Core Entities (5 files)
1. **MondialRelayPickupPoint.php** - Main pickup point entity
2. **MondialRelayPickupPointInterface.php** - Pickup point interface
3. **MondialRelayShipmentTrait.php** - Extends Sylius Shipment
4. **MondialRelayShipmentInterface.php** - Shipment extension interface
5. **AddressEmbeddable.php** - Optional reusable address embeddable

### Validators (2 files)
6. **ValidCoordinates.php** - GPS coordinates constraint
7. **ValidCoordinatesValidator.php** - Validator implementation

### Documentation (6 files)
8. **src/Entity/README.md** - Entity documentation
9. **docs/ENTITIES_SUMMARY.md** - Complete overview
10. **docs/INTEGRATION_GUIDE.md** - Step-by-step integration
11. **docs/MIGRATION_EXAMPLE.md** - Database migrations
12. **docs/TESTING_GUIDE.md** - Testing examples
13. **QUICK_START.md** - 10-minute quick start

## Quick Start

See [QUICK_START.md](./QUICK_START.md) for a 10-minute setup guide.

## Key Features

- PHP 8.2+ with attributes
- Doctrine ORM mapping
- Symfony validation
- Custom GPS validator
- JSON storage for opening hours
- Performance-optimized indexes
- Type-safe interfaces
- Comprehensive documentation

## Database Tables

### New Table
- `kiora_mondial_relay_pickup_point` (11 columns, 4 indexes)

### Extended Table
- `sylius_shipment` (+3 columns, +1 foreign key, +1 index)

## Requirements

- PHP 8.2+
- Symfony 6.4+ or 7.0+
- Doctrine ORM 2.14+ or 3.0+
- Sylius 2.1+
- MySQL 5.7.8+ or MariaDB 10.2.7+ (JSON support)

## Installation

```bash
# Step 1: Install package
composer require kiora/sylius-mondial-relay-plugin

# Step 2: Extend Shipment entity (see QUICK_START.md)

# Step 3: Generate migration
bin/console doctrine:migrations:diff

# Step 4: Run migration
bin/console doctrine:migrations:migrate
```

## Usage Example

```php
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

$pickupPoint = new MondialRelayPickupPoint();
$pickupPoint
    ->setRelayPointId('123456')
    ->setName('Relais Test')
    ->setStreet('15 rue de Rivoli')
    ->setPostalCode('75001')
    ->setCity('Paris')
    ->setCountryCode('FR')
    ->setLatitude('48.8606111')
    ->setLongitude('2.3376720')
    ->setOpeningHours(['monday' => ['09:00-12:00', '14:00-19:00']]);

$entityManager->persist($pickupPoint);
$entityManager->flush();

// Assign to shipment
$shipment->setMondialRelayPickupPoint($pickupPoint);
```

## Documentation

| Document | Description | Size |
|----------|-------------|------|
| [QUICK_START.md](./QUICK_START.md) | 10-minute setup guide | 10 KB |
| [docs/INTEGRATION_GUIDE.md](./docs/INTEGRATION_GUIDE.md) | Complete integration examples | 18 KB |
| [docs/MIGRATION_EXAMPLE.md](./docs/MIGRATION_EXAMPLE.md) | SQL migration examples | 9.5 KB |
| [docs/TESTING_GUIDE.md](./docs/TESTING_GUIDE.md) | Testing examples | 27 KB |
| [docs/ENTITIES_SUMMARY.md](./docs/ENTITIES_SUMMARY.md) | Complete overview | 11 KB |
| [src/Entity/README.md](./src/Entity/README.md) | Entity documentation | 7.2 KB |

## Architecture

```
MondialRelayPickupPoint (standalone entity)
         ↑
         | OneToOne (nullable)
         |
    Shipment (Sylius entity extended with trait)
```

## Validation

- All required fields validated
- Country code validation (ISO 3166-1 alpha-2)
- GPS coordinates validation (-90 to 90, -180 to 180)
- Length constraints matching database schema

## Performance

- 4 strategic indexes for fast queries
- DECIMAL for coordinates (no precision loss)
- JSON for opening hours (flexible structure)
- SET NULL on foreign key (no orphaned records)

## Testing

See [docs/TESTING_GUIDE.md](./docs/TESTING_GUIDE.md) for:
- Unit tests examples
- Functional tests
- Integration tests
- Validation tests
- Data fixtures

## License

Part of Kiora Sylius Mondial Relay Plugin

---

**Created**: 2025-12-10
**Version**: 1.0.0
**PHP**: 8.2+
**Sylius**: 2.1+
**Status**: Production Ready
