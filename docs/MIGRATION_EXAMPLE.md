# Example Doctrine Migration for Mondial Relay Plugin

This document provides example SQL migrations for integrating the Mondial Relay entities into your Sylius application.

## Migration Class Example

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Mondial Relay pickup point table and extend Sylius shipment';
    }

    public function up(Schema $schema): void
    {
        // Create pickup point table
        $this->addSql('
            CREATE TABLE kiora_mondial_relay_pickup_point (
                id INT AUTO_INCREMENT NOT NULL,
                relay_point_id VARCHAR(10) NOT NULL,
                name VARCHAR(100) NOT NULL,
                street VARCHAR(255) NOT NULL,
                postal_code VARCHAR(10) NOT NULL,
                city VARCHAR(100) NOT NULL,
                country_code VARCHAR(2) NOT NULL,
                latitude NUMERIC(10, 7) NOT NULL,
                longitude NUMERIC(10, 7) NOT NULL,
                opening_hours JSON NOT NULL,
                distance_meters INT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_relay_point_id (relay_point_id),
                INDEX idx_relay_point_id (relay_point_id),
                INDEX idx_postal_code (postal_code),
                INDEX idx_country_code (country_code),
                INDEX idx_created_at (created_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Extend Sylius shipment table
        $this->addSql('
            ALTER TABLE sylius_shipment
            ADD mondial_relay_pickup_point_id INT DEFAULT NULL,
            ADD mondial_relay_tracking_number VARCHAR(50) DEFAULT NULL,
            ADD mondial_relay_label_url LONGTEXT DEFAULT NULL
        ');

        // Add foreign key constraint
        $this->addSql('
            ALTER TABLE sylius_shipment
            ADD CONSTRAINT FK_mondial_relay_pickup_point
            FOREIGN KEY (mondial_relay_pickup_point_id)
            REFERENCES kiora_mondial_relay_pickup_point (id)
            ON DELETE SET NULL
        ');

        // Add index on foreign key
        $this->addSql('
            CREATE INDEX IDX_mondial_relay_pickup_point
            ON sylius_shipment (mondial_relay_pickup_point_id)
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key and index
        $this->addSql('
            ALTER TABLE sylius_shipment
            DROP FOREIGN KEY FK_mondial_relay_pickup_point
        ');

        $this->addSql('
            DROP INDEX IDX_mondial_relay_pickup_point
            ON sylius_shipment
        ');

        // Remove columns from shipment
        $this->addSql('
            ALTER TABLE sylius_shipment
            DROP mondial_relay_pickup_point_id,
            DROP mondial_relay_tracking_number,
            DROP mondial_relay_label_url
        ');

        // Drop pickup point table
        $this->addSql('DROP TABLE kiora_mondial_relay_pickup_point');
    }
}
```

## Manual SQL Migration (if needed)

### Up Migration

```sql
-- Create Mondial Relay pickup point table
CREATE TABLE kiora_mondial_relay_pickup_point (
    id INT AUTO_INCREMENT NOT NULL,
    relay_point_id VARCHAR(10) NOT NULL,
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
    UNIQUE INDEX UNIQ_relay_point_id (relay_point_id),
    INDEX idx_relay_point_id (relay_point_id),
    INDEX idx_postal_code (postal_code),
    INDEX idx_country_code (country_code),
    INDEX idx_created_at (created_at),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Extend Sylius shipment table
ALTER TABLE sylius_shipment
ADD COLUMN mondial_relay_pickup_point_id INT DEFAULT NULL,
ADD COLUMN mondial_relay_tracking_number VARCHAR(50) DEFAULT NULL,
ADD COLUMN mondial_relay_label_url LONGTEXT DEFAULT NULL;

-- Add foreign key constraint
ALTER TABLE sylius_shipment
ADD CONSTRAINT FK_mondial_relay_pickup_point
FOREIGN KEY (mondial_relay_pickup_point_id)
REFERENCES kiora_mondial_relay_pickup_point (id)
ON DELETE SET NULL;

-- Add index on foreign key
CREATE INDEX IDX_mondial_relay_pickup_point
ON sylius_shipment (mondial_relay_pickup_point_id);
```

### Down Migration

```sql
-- Drop foreign key and index
ALTER TABLE sylius_shipment
DROP FOREIGN KEY FK_mondial_relay_pickup_point;

DROP INDEX IDX_mondial_relay_pickup_point
ON sylius_shipment;

-- Remove columns from shipment
ALTER TABLE sylius_shipment
DROP COLUMN mondial_relay_pickup_point_id,
DROP COLUMN mondial_relay_tracking_number,
DROP COLUMN mondial_relay_label_url;

-- Drop pickup point table
DROP TABLE kiora_mondial_relay_pickup_point;
```

## PostgreSQL Version (if using PostgreSQL)

### Up Migration

```sql
-- Create Mondial Relay pickup point table
CREATE TABLE kiora_mondial_relay_pickup_point (
    id SERIAL NOT NULL,
    relay_point_id VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    street VARCHAR(255) NOT NULL,
    postal_code VARCHAR(10) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country_code VARCHAR(2) NOT NULL,
    latitude NUMERIC(10, 7) NOT NULL,
    longitude NUMERIC(10, 7) NOT NULL,
    opening_hours JSONB NOT NULL,
    distance_meters INT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_relay_point_id ON kiora_mondial_relay_pickup_point (relay_point_id);
CREATE INDEX idx_relay_point_id ON kiora_mondial_relay_pickup_point (relay_point_id);
CREATE INDEX idx_postal_code ON kiora_mondial_relay_pickup_point (postal_code);
CREATE INDEX idx_country_code ON kiora_mondial_relay_pickup_point (country_code);
CREATE INDEX idx_created_at ON kiora_mondial_relay_pickup_point (created_at);

COMMENT ON COLUMN kiora_mondial_relay_pickup_point.created_at IS '(DC2Type:datetime_immutable)';

-- Extend Sylius shipment table
ALTER TABLE sylius_shipment
ADD COLUMN mondial_relay_pickup_point_id INT DEFAULT NULL,
ADD COLUMN mondial_relay_tracking_number VARCHAR(50) DEFAULT NULL,
ADD COLUMN mondial_relay_label_url TEXT DEFAULT NULL;

-- Add foreign key constraint
ALTER TABLE sylius_shipment
ADD CONSTRAINT FK_mondial_relay_pickup_point
FOREIGN KEY (mondial_relay_pickup_point_id)
REFERENCES kiora_mondial_relay_pickup_point (id)
ON DELETE SET NULL;

-- Add index on foreign key
CREATE INDEX IDX_mondial_relay_pickup_point
ON sylius_shipment (mondial_relay_pickup_point_id);
```

## Testing the Migration

After running the migration:

```bash
# Generate the migration
bin/console doctrine:migrations:diff

# Check the migration status
bin/console doctrine:migrations:status

# Execute the migration
bin/console doctrine:migrations:migrate --no-interaction

# Verify tables exist
bin/console doctrine:schema:validate
```

## Verification Queries

```sql
-- Check if pickup point table exists
SHOW CREATE TABLE kiora_mondial_relay_pickup_point;

-- Check shipment table structure
DESCRIBE sylius_shipment;

-- Verify indexes
SHOW INDEXES FROM kiora_mondial_relay_pickup_point;
SHOW INDEXES FROM sylius_shipment;

-- Test data insertion
INSERT INTO kiora_mondial_relay_pickup_point
(relay_point_id, name, street, postal_code, city, country_code, latitude, longitude, opening_hours, created_at)
VALUES
('123456', 'Test Relay', '123 rue Test', '75001', 'Paris', 'FR', 48.8566140, 2.3522219, '{}', NOW());

-- Verify insertion
SELECT * FROM kiora_mondial_relay_pickup_point;
```

## Common Issues and Solutions

### Issue 1: JSON column type not supported

**Error:** `Unknown column type "json" requested`

**Solution:**
- MySQL: Upgrade to MySQL 5.7.8+ or MariaDB 10.2.7+
- For older versions, change JSON to TEXT in migration

### Issue 2: Decimal precision

**Error:** `Numeric value out of range`

**Solution:**
- Verify latitude/longitude values are within range: -180 to 180
- Check DECIMAL(10,7) is sufficient for your precision needs

### Issue 3: Foreign key constraint fails

**Error:** `Cannot add foreign key constraint`

**Solution:**
- Ensure pickup point table is created before adding foreign key
- Verify table engines are compatible (both InnoDB)
- Check character sets match (both utf8mb4)

## Rollback Procedure

If you need to rollback:

```bash
# Rollback one migration
bin/console doctrine:migrations:execute --down Version20251210000000

# Or rollback to specific version
bin/console doctrine:migrations:migrate prev
```

## Performance Considerations

1. **Indexes:** The migration creates indexes on frequently queried columns
2. **Foreign Key:** SET NULL on delete prevents orphaned records
3. **JSON Storage:** Efficient for variable opening hours structure
4. **Decimal Precision:** NUMERIC(10,7) provides adequate GPS precision (±11mm)

## Next Steps

1. Run the migration in development environment
2. Test entity operations (CRUD)
3. Verify foreign key relationships
4. Load test with sample relay point data
5. Review query performance with EXPLAIN
6. Deploy to staging environment
7. Backup production database before production deployment
