# Quick Start Guide - Kiora Sylius Mondial Relay Plugin

This guide will get you up and running with the Mondial Relay entities in your Sylius 2.1 application in under 10 minutes.

## Prerequisites

- Sylius 2.1+ installed
- PHP 8.2+
- MySQL 5.7.8+ or MariaDB 10.2.7+ (for JSON support)
- Composer
- Doctrine ORM configured

## Installation

### Step 1: Install the Plugin (2 minutes)

```bash
# If using as a standalone package
composer require kiora/sylius-mondial-relay-plugin

# If developing locally
# Add to composer.json:
{
    "repositories": [
        {
            "type": "path",
            "url": "../kiora-sylius-mondial-relay-plugin"
        }
    ],
    "require": {
        "kiora/sylius-mondial-relay-plugin": "dev-main"
    }
}

# Then run
composer install
```

### Step 2: Register the Plugin (1 minute)

```php
// config/bundles.php
return [
    // ... other bundles
    Kiora\SyliusMondialRelayPlugin\KioraSyliusMondialRelayPlugin::class => ['all' => true],
];
```

### Step 3: Extend Sylius Shipment Entity (2 minutes)

Create or modify your Shipment entity:

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

### Step 4: Configure Sylius Resource (1 minute)

```yaml
# config/packages/sylius_core.yaml
sylius_core:
    resources:
        shipment:
            classes:
                model: App\Entity\Shipping\Shipment
```

### Step 5: Generate and Run Migration (2 minutes)

```bash
# Generate migration
bin/console doctrine:migrations:diff

# Review the generated migration file
# Check: migrations/VersionYYYYMMDDHHMMSS.php

# Execute migration
bin/console doctrine:migrations:migrate -n

# Validate schema
bin/console doctrine:schema:validate
```

Expected output:
```
[OK] The mapping files are correct.
[OK] The database schema is in sync with the mapping files.
```

### Step 6: Clear Cache (1 minute)

```bash
bin/console cache:clear
```

## Quick Test

### Test 1: Create a Pickup Point

```php
<?php
// src/Service/QuickTest.php

use Doctrine\ORM\EntityManagerInterface;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

class QuickTest
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function createTestPickupPoint(): MondialRelayPickupPoint
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Test Relay Point')
            ->setStreet('15 rue de Rivoli')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8606111')
            ->setLongitude('2.3376720')
            ->setOpeningHours([
                'monday' => ['09:00-12:00', '14:00-19:00'],
                'tuesday' => ['09:00-12:00', '14:00-19:00'],
                'wednesday' => ['09:00-12:00', '14:00-19:00'],
                'thursday' => ['09:00-12:00', '14:00-19:00'],
                'friday' => ['09:00-12:00', '14:00-19:00'],
                'saturday' => ['09:00-12:00'],
                'sunday' => [],
            ]);

        $this->entityManager->persist($pickupPoint);
        $this->entityManager->flush();

        return $pickupPoint;
    }
}
```

### Test 2: Assign to Shipment

```php
<?php

use App\Entity\Shipping\Shipment;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

// Assuming you have a shipment and pickup point
$shipment = new Shipment();
$shipment->setMondialRelayPickupPoint($pickupPoint);
$shipment->setMondialRelayTrackingNumber('MR123456789FR');
$shipment->setMondialRelayLabelUrl('https://example.com/label.pdf');

$entityManager->persist($shipment);
$entityManager->flush();
```

### Test 3: Verify in Database

```bash
# Connect to your database
mysql -u root -p sylius

# Check pickup points table exists
SHOW TABLES LIKE 'kiora_mondial_relay_pickup_point';

# Check shipment columns were added
DESCRIBE sylius_shipment;

# Count pickup points
SELECT COUNT(*) FROM kiora_mondial_relay_pickup_point;

# View test data
SELECT * FROM kiora_mondial_relay_pickup_point LIMIT 1\G
```

## Console Commands

```bash
# View Doctrine mapping info
bin/console doctrine:mapping:info

# Check entity validation
bin/console debug:validator 'Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint'

# Check database schema
bin/console doctrine:schema:validate

# View entity metadata
bin/console doctrine:mapping:describe 'Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint'
```

## Troubleshooting

### Issue: Migration file not generated

**Solution:**
```bash
# Clear metadata cache
bin/console doctrine:cache:clear-metadata

# Try again
bin/console doctrine:migrations:diff
```

### Issue: Table already exists

**Check migration status:**
```bash
bin/console doctrine:migrations:status
```

**If needed, mark as executed:**
```bash
bin/console doctrine:migrations:version VERSION --add
```

### Issue: Validation errors

**Check validator configuration:**
```bash
bin/console debug:validator 'Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint'
```

### Issue: Foreign key constraint fails

**Ensure tables are created in correct order:**
1. First create `kiora_mondial_relay_pickup_point`
2. Then add columns to `sylius_shipment`
3. Finally add foreign key constraint

## Next Steps

### 1. Create a Repository (Optional but Recommended)

```php
<?php
// src/Repository/MondialRelayPickupPointRepository.php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

class MondialRelayPickupPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MondialRelayPickupPoint::class);
    }

    public function findByPostalCode(string $postalCode): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.postalCode = :postalCode')
            ->setParameter('postalCode', $postalCode)
            ->orderBy('p.distanceMeters', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

Register as service:
```yaml
# config/services.yaml
services:
    App\Repository\MondialRelayPickupPointRepository:
        factory: ['@doctrine', 'getRepository']
        arguments:
            - Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint
        public: true
```

### 2. Create an API Endpoint

```php
<?php
// src/Controller/Api/MondialRelayController.php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\MondialRelayPickupPointRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mondial-relay', name: 'api_mondial_relay_')]
class MondialRelayController extends AbstractController
{
    public function __construct(
        private MondialRelayPickupPointRepository $repository
    ) {}

    #[Route('/pickup-points', name: 'pickup_points', methods: ['GET'])]
    public function getPickupPoints(Request $request): JsonResponse
    {
        $postalCode = $request->query->get('postal_code');

        if (!$postalCode) {
            return $this->json(['error' => 'postal_code required'], 400);
        }

        $pickupPoints = $this->repository->findByPostalCode($postalCode);

        return $this->json([
            'data' => array_map(fn($p) => [
                'id' => $p->getId(),
                'relay_point_id' => $p->getRelayPointId(),
                'name' => $p->getName(),
                'address' => [
                    'street' => $p->getStreet(),
                    'postal_code' => $p->getPostalCode(),
                    'city' => $p->getCity(),
                    'country_code' => $p->getCountryCode(),
                ],
                'coordinates' => [
                    'latitude' => (float) $p->getLatitude(),
                    'longitude' => (float) $p->getLongitude(),
                ],
                'distance_meters' => $p->getDistanceMeters(),
                'opening_hours' => $p->getOpeningHours(),
            ], $pickupPoints),
        ]);
    }
}
```

Test the API:
```bash
curl http://localhost:8000/api/mondial-relay/pickup-points?postal_code=75001
```

### 3. Add Test Data Fixtures

```php
<?php
// src/DataFixtures/MondialRelayFixture.php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

class MondialRelayFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $parisPoints = [
            ['123456', 'Relais Paris Centre', '15 rue de Rivoli', '75001', '48.8606111', '2.3376720'],
            ['234567', 'Relais Marais', '25 rue des Francs Bourgeois', '75004', '48.8572320', '2.3610160'],
            ['345678', 'Relais Montmartre', '18 rue des Abbesses', '75018', '48.8844600', '2.3388400'],
        ];

        foreach ($parisPoints as [$id, $name, $street, $postalCode, $lat, $lng]) {
            $pickupPoint = new MondialRelayPickupPoint();
            $pickupPoint
                ->setRelayPointId($id)
                ->setName($name)
                ->setStreet($street)
                ->setPostalCode($postalCode)
                ->setCity('Paris')
                ->setCountryCode('FR')
                ->setLatitude($lat)
                ->setLongitude($lng)
                ->setOpeningHours($this->getDefaultHours());

            $manager->persist($pickupPoint);
        }

        $manager->flush();
    }

    private function getDefaultHours(): array
    {
        return [
            'monday' => ['09:00-12:30', '14:00-19:00'],
            'tuesday' => ['09:00-12:30', '14:00-19:00'],
            'wednesday' => ['09:00-12:30', '14:00-19:00'],
            'thursday' => ['09:00-12:30', '14:00-19:00'],
            'friday' => ['09:00-12:30', '14:00-19:00'],
            'saturday' => ['09:00-12:30'],
            'sunday' => [],
        ];
    }
}
```

Load fixtures:
```bash
bin/console doctrine:fixtures:load --append
```

### 4. Write Tests

```bash
# Install test dependencies
composer require --dev phpunit/phpunit symfony/test-pack

# Create test
mkdir -p tests/Unit/Entity

# Copy example from docs/TESTING_GUIDE.md
# Then run:
bin/phpunit
```

## Documentation

All documentation is in the `docs/` directory:

- **INTEGRATION_GUIDE.md**: Complete integration guide with examples
- **MIGRATION_EXAMPLE.md**: Doctrine migration examples and SQL
- **TESTING_GUIDE.md**: Comprehensive testing guide
- **ENTITIES_SUMMARY.md**: Complete overview of entities
- **src/Entity/README.md**: Entity-specific documentation

## Useful Links

- [Sylius Documentation](https://docs.sylius.com/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/)
- [Symfony Validation](https://symfony.com/doc/current/validation.html)
- [Mondial Relay API Docs](https://www.mondialrelay.fr/solutionspro/documentation-technique/)

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review `docs/INTEGRATION_GUIDE.md` for detailed examples
3. Check your migration file matches `docs/MIGRATION_EXAMPLE.md`
4. Validate your entity configuration
5. Clear all caches

## Summary

You now have:

- ✅ Entities installed and configured
- ✅ Database schema created
- ✅ Validation working
- ✅ Shipment entity extended
- ✅ Ready to integrate with Mondial Relay API

**Total Time**: ~10 minutes

**Next**: Integrate with Mondial Relay API to fetch real pickup points, or start building your checkout flow.

---

**Plugin Version**: 1.0.0
**Compatible with**: Sylius 2.1+, PHP 8.2+
**Last Updated**: 2025-12-10
