# Integration Guide - Mondial Relay Plugin Entities

This guide explains how to integrate the Mondial Relay entities into your Sylius 2.1 application.

## Step 1: Extend Sylius Shipment Entity

Create or modify your custom Shipment entity to implement the Mondial Relay interface and use the trait.

### File: `src/Entity/Shipping/Shipment.php`

```php
<?php

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

    // Add any additional custom fields or methods here
}
```

## Step 2: Register Your Custom Entity

Update your Sylius resource configuration to use your custom Shipment entity.

### File: `config/packages/sylius_core.yaml`

```yaml
sylius_core:
    resources:
        shipment:
            classes:
                model: App\Entity\Shipping\Shipment
```

## Step 3: Generate and Run Migration

```bash
# Generate the migration
bin/console doctrine:migrations:diff

# Review the generated migration file
# migrations/VersionYYYYMMDDHHMMSS.php

# Execute the migration
bin/console doctrine:migrations:migrate -n

# Verify schema
bin/console doctrine:schema:validate
```

## Step 4: Using Entities in Your Code

### Example 1: Create and Save a Pickup Point

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

class PickupPointManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createFromApiData(array $apiData): MondialRelayPickupPoint
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId($apiData['Num'])
            ->setName($apiData['LgAdr1'])
            ->setStreet($apiData['LgAdr3'])
            ->setPostalCode($apiData['CP'])
            ->setCity($apiData['Ville'])
            ->setCountryCode($apiData['Pays'])
            ->setLatitude($apiData['Latitude'])
            ->setLongitude($apiData['Longitude'])
            ->setOpeningHours($this->parseOpeningHours($apiData))
            ->setDistanceMeters((int) $apiData['Distance']);

        $this->entityManager->persist($pickupPoint);
        $this->entityManager->flush();

        return $pickupPoint;
    }

    private function parseOpeningHours(array $apiData): array
    {
        // Parse Mondial Relay opening hours format
        return [
            'monday' => $this->parseDay($apiData['Horaires_Lundi'] ?? ''),
            'tuesday' => $this->parseDay($apiData['Horaires_Mardi'] ?? ''),
            'wednesday' => $this->parseDay($apiData['Horaires_Mercredi'] ?? ''),
            'thursday' => $this->parseDay($apiData['Horaires_Jeudi'] ?? ''),
            'friday' => $this->parseDay($apiData['Horaires_Vendredi'] ?? ''),
            'saturday' => $this->parseDay($apiData['Horaires_Samedi'] ?? ''),
            'sunday' => $this->parseDay($apiData['Horaires_Dimanche'] ?? ''),
        ];
    }

    private function parseDay(string $hours): array
    {
        // Format: "09001200 14001900" -> ["09:00-12:00", "14:00-19:00"]
        if (empty($hours)) {
            return [];
        }

        $slots = [];
        $parts = explode(' ', $hours);

        foreach ($parts as $part) {
            if (strlen($part) === 8) {
                $start = substr($part, 0, 2) . ':' . substr($part, 2, 2);
                $end = substr($part, 4, 2) . ':' . substr($part, 6, 2);
                $slots[] = $start . '-' . $end;
            }
        }

        return $slots;
    }
}
```

### Example 2: Assign Pickup Point to Shipment

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Shipping\Shipment;
use Doctrine\ORM\EntityManagerInterface;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

class ShipmentManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function assignPickupPoint(
        Shipment $shipment,
        MondialRelayPickupPoint $pickupPoint
    ): void {
        $shipment->setMondialRelayPickupPoint($pickupPoint);
        $this->entityManager->flush();
    }

    public function updateTrackingInfo(
        Shipment $shipment,
        string $trackingNumber,
        string $labelUrl
    ): void {
        $shipment
            ->setMondialRelayTrackingNumber($trackingNumber)
            ->setMondialRelayLabelUrl($labelUrl);

        $this->entityManager->flush();
    }
}
```

### Example 3: Repository for Finding Pickup Points

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

/**
 * @extends ServiceEntityRepository<MondialRelayPickupPoint>
 */
class MondialRelayPickupPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MondialRelayPickupPoint::class);
    }

    /**
     * Find pickup points by postal code and country.
     *
     * @return MondialRelayPickupPoint[]
     */
    public function findByPostalCodeAndCountry(
        string $postalCode,
        string $countryCode,
        int $limit = 20
    ): array {
        return $this->createQueryBuilder('p')
            ->where('p.postalCode = :postalCode')
            ->andWhere('p.countryCode = :countryCode')
            ->setParameter('postalCode', $postalCode)
            ->setParameter('countryCode', $countryCode)
            ->orderBy('p.distanceMeters', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find nearest pickup points by coordinates.
     *
     * @return MondialRelayPickupPoint[]
     */
    public function findNearestByCoordinates(
        float $latitude,
        float $longitude,
        float $radiusKm = 10.0,
        int $limit = 20
    ): array {
        // Haversine formula for distance calculation
        $sql = <<<SQL
            SELECT p.*,
                (6371 * acos(
                    cos(radians(:latitude)) *
                    cos(radians(p.latitude)) *
                    cos(radians(p.longitude) - radians(:longitude)) +
                    sin(radians(:latitude)) *
                    sin(radians(p.latitude))
                )) AS distance
            FROM kiora_mondial_relay_pickup_point p
            HAVING distance < :radius
            ORDER BY distance ASC
            LIMIT :limit
        SQL;

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radiusKm,
            'limit' => $limit,
        ]);

        $data = $result->fetchAllAssociative();

        // Hydrate entities
        return array_map(
            fn(array $row) => $this->find($row['id']),
            $data
        );
    }

    /**
     * Find by relay point ID.
     */
    public function findByRelayPointId(string $relayPointId): ?MondialRelayPickupPoint
    {
        return $this->createQueryBuilder('p')
            ->where('p.relayPointId = :relayPointId')
            ->setParameter('relayPointId', $relayPointId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search pickup points.
     *
     * @return MondialRelayPickupPoint[]
     */
    public function search(
        ?string $city = null,
        ?string $postalCode = null,
        ?string $countryCode = null
    ): array {
        $qb = $this->createQueryBuilder('p');

        if ($city !== null) {
            $qb->andWhere('p.city LIKE :city')
               ->setParameter('city', '%' . $city . '%');
        }

        if ($postalCode !== null) {
            $qb->andWhere('p.postalCode = :postalCode')
               ->setParameter('postalCode', $postalCode);
        }

        if ($countryCode !== null) {
            $qb->andWhere('p.countryCode = :countryCode')
               ->setParameter('countryCode', $countryCode);
        }

        return $qb->orderBy('p.city', 'ASC')
                  ->addOrderBy('p.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}
```

### Example 4: Register Repository as Service

```yaml
# config/services.yaml
services:
    App\Repository\MondialRelayPickupPointRepository:
        factory: ['@doctrine', 'getRepository']
        arguments:
            - Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint
        public: true
```

### Example 5: Using in a Controller

```php
<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use App\Entity\Shipping\Shipment;
use App\Repository\MondialRelayPickupPointRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mondial-relay', name: 'app_shop_mondial_relay_')]
class MondialRelayController extends AbstractController
{
    public function __construct(
        private MondialRelayPickupPointRepository $pickupPointRepository
    ) {
    }

    #[Route('/pickup-points', name: 'pickup_points', methods: ['GET'])]
    public function listPickupPoints(Request $request): JsonResponse
    {
        $postalCode = $request->query->get('postal_code');
        $countryCode = $request->query->get('country_code', 'FR');

        if (!$postalCode) {
            return $this->json(['error' => 'postal_code is required'], Response::HTTP_BAD_REQUEST);
        }

        $pickupPoints = $this->pickupPointRepository->findByPostalCodeAndCountry(
            $postalCode,
            $countryCode
        );

        return $this->json([
            'pickup_points' => array_map(
                fn($point) => [
                    'id' => $point->getId(),
                    'relay_point_id' => $point->getRelayPointId(),
                    'name' => $point->getName(),
                    'address' => [
                        'street' => $point->getStreet(),
                        'postal_code' => $point->getPostalCode(),
                        'city' => $point->getCity(),
                        'country_code' => $point->getCountryCode(),
                    ],
                    'coordinates' => [
                        'latitude' => (float) $point->getLatitude(),
                        'longitude' => (float) $point->getLongitude(),
                    ],
                    'distance_meters' => $point->getDistanceMeters(),
                    'opening_hours' => $point->getOpeningHours(),
                ],
                $pickupPoints
            ),
        ]);
    }

    #[Route('/nearest', name: 'nearest', methods: ['GET'])]
    public function findNearest(Request $request): JsonResponse
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');

        if (!$latitude || !$longitude) {
            return $this->json(
                ['error' => 'latitude and longitude are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $pickupPoints = $this->pickupPointRepository->findNearestByCoordinates(
            (float) $latitude,
            (float) $longitude,
            radiusKm: 10.0,
            limit: 10
        );

        return $this->json([
            'pickup_points' => array_map(
                fn($point) => [
                    'id' => $point->getId(),
                    'relay_point_id' => $point->getRelayPointId(),
                    'name' => $point->getName(),
                    'city' => $point->getCity(),
                    'distance_meters' => $point->getDistanceMeters(),
                ],
                $pickupPoints
            ),
        ]);
    }
}
```

## Step 5: Testing

### Unit Test Example

```php
<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use PHPUnit\Framework\TestCase;

class MondialRelayPickupPointTest extends TestCase
{
    public function testPickupPointCreation(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        self::assertInstanceOf(\DateTimeImmutable::class, $pickupPoint->getCreatedAt());
        self::assertNull($pickupPoint->getId());
        self::assertNull($pickupPoint->getRelayPointId());
    }

    public function testSettersAndGetters(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Test Relay')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setDistanceMeters(500);

        self::assertSame('123456', $pickupPoint->getRelayPointId());
        self::assertSame('Test Relay', $pickupPoint->getName());
        self::assertSame('123 rue Test', $pickupPoint->getStreet());
        self::assertSame('75001', $pickupPoint->getPostalCode());
        self::assertSame('Paris', $pickupPoint->getCity());
        self::assertSame('FR', $pickupPoint->getCountryCode());
        self::assertSame('48.8566140', $pickupPoint->getLatitude());
        self::assertSame('2.3522219', $pickupPoint->getLongitude());
        self::assertSame(500, $pickupPoint->getDistanceMeters());
    }

    public function testOpeningHours(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $openingHours = [
            'monday' => ['09:00-12:00', '14:00-19:00'],
            'tuesday' => ['09:00-12:00', '14:00-19:00'],
        ];

        $pickupPoint->setOpeningHours($openingHours);

        self::assertSame($openingHours, $pickupPoint->getOpeningHours());
    }

    public function testToString(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Test Relay')
            ->setPostalCode('75001')
            ->setCity('Paris');

        $expected = '123456 - Test Relay 75001 Paris';
        self::assertSame($expected, (string) $pickupPoint);
    }
}
```

### Functional Test Example

```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Shipping\Shipment;
use App\Repository\MondialRelayPickupPointRepository;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MondialRelayPickupPointRepositoryTest extends KernelTestCase
{
    private MondialRelayPickupPointRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()
            ->get(MondialRelayPickupPointRepository::class);
    }

    public function testFindByPostalCodeAndCountry(): void
    {
        $pickupPoints = $this->repository->findByPostalCodeAndCountry('75001', 'FR');

        self::assertIsArray($pickupPoints);

        foreach ($pickupPoints as $point) {
            self::assertInstanceOf(MondialRelayPickupPoint::class, $point);
            self::assertSame('75001', $point->getPostalCode());
            self::assertSame('FR', $point->getCountryCode());
        }
    }

    public function testFindByRelayPointId(): void
    {
        $pickupPoint = $this->repository->findByRelayPointId('123456');

        if ($pickupPoint !== null) {
            self::assertInstanceOf(MondialRelayPickupPoint::class, $pickupPoint);
            self::assertSame('123456', $pickupPoint->getRelayPointId());
        } else {
            self::markTestSkipped('No pickup point with ID 123456 found');
        }
    }
}
```

## Troubleshooting

### Issue: Entity not recognized

**Solution:** Clear cache and regenerate proxies
```bash
bin/console cache:clear
bin/console doctrine:cache:clear-metadata
```

### Issue: Migration fails with "Table already exists"

**Solution:** Check if migration was already applied
```bash
bin/console doctrine:migrations:status
```

### Issue: Foreign key constraint fails

**Solution:** Ensure proper order in migration (create pickup_point table before adding foreign key)

## Next Steps

1. Implement API client for fetching pickup points from Mondial Relay
2. Create Sylius admin forms for managing pickup points
3. Add frontend widget for selecting pickup points during checkout
4. Implement label generation service
5. Add event listeners for order state changes

## See Also

- [Sylius Documentation](https://docs.sylius.com/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Mondial Relay API Documentation](https://www.mondialrelay.fr/solutionspro/documentation-technique/)
