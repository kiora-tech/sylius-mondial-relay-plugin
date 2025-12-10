# Entities Created - Kiora Sylius Mondial Relay Plugin

## Summary

This document lists all the entities and related files created for the Mondial Relay Sylius 2.1 plugin.

**Date Created**: 2025-12-10
**PHP Version**: 8.2+
**Doctrine ORM**: 2.14+ / 3.0+
**Symfony**: 6.4+ / 7.0+
**Sylius**: 2.1+

## Directory Structure

```
/var/www/kiora-sylius-mondial-relay-plugin/
├── docs/
│   ├── ENTITIES_SUMMARY.md           (11 KB) - Complete entities overview
│   ├── INTEGRATION_GUIDE.md          (18 KB) - Step-by-step integration guide
│   ├── MIGRATION_EXAMPLE.md          (9.5 KB) - Doctrine migration examples
│   └── TESTING_GUIDE.md              (24 KB) - Comprehensive testing guide
├── src/
│   ├── Entity/
│   │   ├── AddressEmbeddable.php              (4.1 KB) - Optional embeddable address
│   │   ├── MondialRelayPickupPoint.php        (5.8 KB) - Main pickup point entity
│   │   ├── MondialRelayPickupPointInterface.php (1.5 KB) - Pickup point interface
│   │   ├── MondialRelayShipmentInterface.php  (589 B)  - Shipment extension interface
│   │   ├── MondialRelayShipmentTrait.php      (2.0 KB) - Trait for Sylius Shipment
│   │   └── README.md                          (7.2 KB) - Entity documentation
│   └── Validator/
│       └── Constraints/
│           ├── ValidCoordinates.php           (1.5 KB) - GPS coordinates constraint
│           └── ValidCoordinatesValidator.php  (2.2 KB) - Validator implementation
└── ENTITIES_CREATED.md                         (this file)
```

## Files Created (11 files)

### Core Entities (5 files)

#### 1. MondialRelayPickupPoint.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Entity/MondialRelayPickupPoint.php`
**Size**: 5.8 KB
**Purpose**: Main entity for storing Mondial Relay pickup points

**Features**:
- Complete Doctrine ORM mapping with PHP 8.2 attributes
- Symfony validation constraints
- 4 database indexes for performance
- JSON storage for opening hours
- GPS coordinates with decimal precision (10,7)
- Auto-generated creation timestamp
- Custom `__toString()` method

**Database Table**: `kiora_mondial_relay_pickup_point`

**Key Fields**:
```php
- id: int (auto-increment)
- relayPointId: string(10) unique
- name: string(100)
- street: string(255)
- postalCode: string(10)
- city: string(100)
- countryCode: string(2)
- latitude: decimal(10,7)
- longitude: decimal(10,7)
- openingHours: json
- distanceMeters: int|null
- createdAt: DateTimeImmutable
```

---

#### 2. MondialRelayPickupPointInterface.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Entity/MondialRelayPickupPointInterface.php`
**Size**: 1.5 KB
**Purpose**: Interface defining the contract for pickup point entities

**Methods**: All getters and setters for MondialRelayPickupPoint properties

---

#### 3. MondialRelayShipmentTrait.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Entity/MondialRelayShipmentTrait.php`
**Size**: 2.0 KB
**Purpose**: Trait to extend Sylius Shipment entity with Mondial Relay features

**Added Properties**:
```php
- mondialRelayPickupPoint: MondialRelayPickupPoint|null
- mondialRelayTrackingNumber: string(50)|null
- mondialRelayLabelUrl: text|null
```

**Relationship**: OneToOne with MondialRelayPickupPoint, SET NULL on delete

**Usage**:
```php
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentTrait;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayShipmentInterface;

class Shipment extends BaseShipment implements MondialRelayShipmentInterface
{
    use MondialRelayShipmentTrait;
}
```

---

#### 4. MondialRelayShipmentInterface.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Entity/MondialRelayShipmentInterface.php`
**Size**: 589 bytes
**Purpose**: Interface for Mondial Relay shipment extensions

**Methods**:
- `getMondialRelayPickupPoint()` / `setMondialRelayPickupPoint()`
- `getMondialRelayTrackingNumber()` / `setMondialRelayTrackingNumber()`
- `getMondialRelayLabelUrl()` / `setMondialRelayLabelUrl()`

---

#### 5. AddressEmbeddable.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Entity/AddressEmbeddable.php`
**Size**: 4.1 KB
**Purpose**: Optional embeddable Doctrine object for reusable address structure

**Features**:
- Can be embedded multiple times with different column prefixes
- Includes GPS coordinates
- Helper methods: `getFullAddress()`, `isEmpty()`, `__toString()`

**Fields**:
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

**Usage**:
```php
#[ORM\Embedded(class: AddressEmbeddable::class, columnPrefix: 'shipping_')]
private AddressEmbeddable $shippingAddress;
```

---

### Validators (2 files)

#### 6. ValidCoordinates.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Validator/Constraints/ValidCoordinates.php`
**Size**: 1.5 KB
**Purpose**: Custom Symfony validation constraint for GPS coordinates

**Features**:
- Validates latitude range (-90 to 90)
- Validates longitude range (-180 to 180)
- Format validation (numeric strings)
- Customizable error messages
- PHP 8.2 attribute support

**Usage**:
```php
#[ValidCoordinates(type: 'latitude')]
private ?string $latitude = null;

#[ValidCoordinates(type: 'longitude')]
private ?string $longitude = null;
```

---

#### 7. ValidCoordinatesValidator.php
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Validator/Constraints/ValidCoordinatesValidator.php`
**Size**: 2.2 KB
**Purpose**: Validator implementation for ValidCoordinates constraint

**Features**:
- Handles string and numeric values
- Type-safe validation
- Clear violation messages
- Null/empty value handling

---

### Documentation (4 files + 1 README)

#### 8. Entity/README.md
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/src/Entity/README.md`
**Size**: 7.2 KB
**Content**:
- Complete entity documentation
- Database schema details
- Usage examples
- Best practices
- Troubleshooting guide

---

#### 9. docs/ENTITIES_SUMMARY.md
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/docs/ENTITIES_SUMMARY.md`
**Size**: 11 KB
**Content**:
- Overview of all entities
- Database schema with SQL
- Key features and design decisions
- Performance considerations
- Potential enhancements
- Compatibility matrix

---

#### 10. docs/INTEGRATION_GUIDE.md
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/docs/INTEGRATION_GUIDE.md`
**Size**: 18 KB
**Content**:
- Step-by-step integration instructions
- Complete code examples:
  - Extending Sylius Shipment entity
  - Creating pickup point manager service
  - Repository implementation with complex queries
  - Controller examples
  - Service registration
- Unit and functional test examples
- Troubleshooting common issues

---

#### 11. docs/MIGRATION_EXAMPLE.md
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/docs/MIGRATION_EXAMPLE.md`
**Size**: 9.5 KB
**Content**:
- Complete Doctrine migration class example
- Manual SQL for MySQL/MariaDB
- PostgreSQL version
- Up and down migrations
- Verification queries
- Common issues and solutions
- Rollback procedures

---

#### 12. docs/TESTING_GUIDE.md
**Location**: `/var/www/kiora-sylius-mondial-relay-plugin/docs/TESTING_GUIDE.md`
**Size**: 24 KB
**Content**:
- Comprehensive testing examples:
  - Unit tests for all entities
  - Validation tests
  - Repository functional tests
  - Integration tests
  - Data fixtures
- PHPUnit configuration
- GitHub Actions CI example
- Testing best practices
- Coverage goals

---

## Technical Specifications

### PHP 8.2+ Features Used
- Attributes for Doctrine ORM mapping
- Attributes for Symfony validation
- Constructor property promotion (in AddressEmbeddable)
- Union types where appropriate
- Strict types declaration
- Named arguments support

### Doctrine ORM Features
- PHP 8 attributes mapping
- JSON column type
- DECIMAL precision for GPS coordinates
- DateTimeImmutable for timestamps
- OneToOne relationships
- Embeddables
- Indexes for performance
- Foreign key constraints

### Symfony Features
- Validation constraints
- Custom validators
- Fluent interface pattern
- PSR-12 coding standards

### Database Support
- **MySQL**: 5.7.8+ (JSON support)
- **MariaDB**: 10.2.7+ (JSON support)
- **PostgreSQL**: 9.4+ (JSONB support)

## Database Schema Overview

### New Table: kiora_mondial_relay_pickup_point
```
Columns: 11
Indexes: 4 (relay_point_id, postal_code, country_code, created_at)
Primary Key: id (auto-increment)
Unique Key: relay_point_id
JSON Field: opening_hours
DECIMAL Fields: latitude, longitude (precision 10, scale 7)
```

### Extended Table: sylius_shipment
```
New Columns: 3
  - mondial_relay_pickup_point_id (INT, nullable)
  - mondial_relay_tracking_number (VARCHAR 50, nullable)
  - mondial_relay_label_url (TEXT, nullable)
Foreign Key: mondial_relay_pickup_point_id -> pickup_point.id (SET NULL on delete)
New Index: 1 (mondial_relay_pickup_point_id)
```

## Validation Rules

### MondialRelayPickupPoint
- `relayPointId`: NotBlank, Length(1-10)
- `name`: NotBlank, Length(max: 100)
- `street`: NotBlank, Length(max: 255)
- `postalCode`: NotBlank, Length(max: 10)
- `city`: NotBlank, Length(max: 100)
- `countryCode`: NotBlank, Country, Length(exactly: 2)
- `latitude`: NotBlank, ValidCoordinates(type: 'latitude')
- `longitude`: NotBlank, ValidCoordinates(type: 'longitude')
- `openingHours`: JSON array (no constraint)
- `distanceMeters`: Integer, nullable
- `createdAt`: DateTimeImmutable, auto-set

## Performance Optimizations

1. **Strategic Indexes**:
   - `relay_point_id` (unique) - O(1) lookups
   - `postal_code` - Geographic searches
   - `country_code` - Country filtering
   - `created_at` - Temporal queries

2. **Relationship Design**:
   - OneToOne (not ManyToOne) - Better for this use case
   - SET NULL on delete - No orphaned records

3. **Data Types**:
   - DECIMAL for coordinates - No float precision loss
   - JSON for opening hours - Flexible structure
   - INT for distance - Efficient storage

## Migration Commands

```bash
# Generate migration
bin/console doctrine:migrations:diff

# Execute migration
bin/console doctrine:migrations:migrate -n

# Validate schema
bin/console doctrine:schema:validate

# Rollback
bin/console doctrine:migrations:migrate prev
```

## Testing Commands

```bash
# Run all tests
bin/phpunit

# Run with coverage
bin/phpunit --coverage-html coverage/

# Load fixtures
bin/console doctrine:fixtures:load --no-interaction

# Validate entities
bin/console doctrine:mapping:info
```

## Integration Checklist

- [ ] Create custom Shipment entity extending BaseShipment
- [ ] Add MondialRelayShipmentTrait to Shipment
- [ ] Implement MondialRelayShipmentInterface on Shipment
- [ ] Configure Sylius resource (config/packages/sylius_core.yaml)
- [ ] Generate Doctrine migration
- [ ] Review and execute migration
- [ ] Validate database schema
- [ ] Create repository for MondialRelayPickupPoint
- [ ] Write unit tests
- [ ] Write functional tests
- [ ] Add data fixtures
- [ ] Test integration with Sylius checkout
- [ ] Document custom implementations

## Dependencies Required

```json
{
    "require": {
        "php": "^8.2",
        "doctrine/orm": "^2.14|^3.0",
        "doctrine/dbal": "^3.0",
        "symfony/validator": "^6.4|^7.0",
        "symfony/orm-pack": "^2.0",
        "sylius/sylius": "^2.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "symfony/test-pack": "^1.0",
        "doctrine/doctrine-fixtures-bundle": "^3.0"
    }
}
```

## Next Steps

1. ✅ Entities created with Doctrine attributes
2. ✅ Validation constraints added
3. ✅ Custom GPS validator implemented
4. ✅ Complete documentation written
5. ⏳ Create repository with complex queries
6. ⏳ Implement API client for Mondial Relay
7. ⏳ Create Sylius admin configuration
8. ⏳ Build frontend pickup point selector widget
9. ⏳ Implement label generation service
10. ⏳ Write comprehensive test suite

## Key Advantages

### 1. Type Safety
- Strict types everywhere
- Proper type hints
- Interface-based design

### 2. Flexibility
- Embeddable address for reuse
- JSON for variable data
- Optional fields where appropriate

### 3. Performance
- Strategic indexes
- Efficient data types
- Optimized relationships

### 4. Maintainability
- Clean separation of concerns
- Comprehensive documentation
- Interface-based contracts

### 5. Testability
- Mockable interfaces
- Clear dependencies
- Isolated entities

## Support Resources

1. **Entity Documentation**: `src/Entity/README.md`
2. **Integration Guide**: `docs/INTEGRATION_GUIDE.md`
3. **Migration Examples**: `docs/MIGRATION_EXAMPLE.md`
4. **Testing Guide**: `docs/TESTING_GUIDE.md`
5. **Summary**: `docs/ENTITIES_SUMMARY.md`

## License

Part of Kiora Sylius Mondial Relay Plugin - Follow main project license

---

**Total Files Created**: 12 files
**Total Code**: ~25 KB (PHP)
**Total Documentation**: ~62 KB (Markdown)
**Lines of Code**: ~800+ lines
**Test Examples**: 6 complete test classes

**Created By**: Claude Code (Anthropic)
**Date**: 2025-12-10
**Version**: 1.0.0
