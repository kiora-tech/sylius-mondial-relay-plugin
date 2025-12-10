# Testing Guide - Mondial Relay Entities

This guide provides comprehensive examples for testing the Mondial Relay entities in your Sylius application.

## Table of Contents
1. [Unit Tests](#unit-tests)
2. [Functional Tests](#functional-tests)
3. [Integration Tests](#integration-tests)
4. [Validation Tests](#validation-tests)
5. [Repository Tests](#repository-tests)
6. [Test Data Fixtures](#test-data-fixtures)

## Setup

### Install Testing Dependencies

```bash
composer require --dev phpunit/phpunit
composer require --dev symfony/test-pack
composer require --dev doctrine/doctrine-fixtures-bundle
```

### Configure PHPUnit

```xml
<!-- phpunit.xml.dist -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <php>
        <env name="KERNEL_CLASS" value="App\Kernel"/>
        <env name="APP_ENV" value="test"/>
        <env name="DATABASE_URL" value="mysql://root:root@127.0.0.1:3306/sylius_test"/>
    </php>
</phpunit>
```

## Unit Tests

### Test 1: MondialRelayPickupPoint Entity

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use PHPUnit\Framework\TestCase;

class MondialRelayPickupPointTest extends TestCase
{
    public function testConstruct(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $this->assertNull($pickupPoint->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $pickupPoint->getCreatedAt());
        $this->assertEqualsWithDelta(
            new \DateTimeImmutable(),
            $pickupPoint->getCreatedAt(),
            1 // 1 second delta
        );
    }

    public function testSettersAndGetters(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $createdAt = new \DateTimeImmutable('2025-01-01 12:00:00');

        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue de la Paix')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setDistanceMeters(1500)
            ->setCreatedAt($createdAt);

        $this->assertSame('123456', $pickupPoint->getRelayPointId());
        $this->assertSame('Relais Test', $pickupPoint->getName());
        $this->assertSame('123 rue de la Paix', $pickupPoint->getStreet());
        $this->assertSame('75001', $pickupPoint->getPostalCode());
        $this->assertSame('Paris', $pickupPoint->getCity());
        $this->assertSame('FR', $pickupPoint->getCountryCode());
        $this->assertSame('48.8566140', $pickupPoint->getLatitude());
        $this->assertSame('2.3522219', $pickupPoint->getLongitude());
        $this->assertSame(1500, $pickupPoint->getDistanceMeters());
        $this->assertSame($createdAt, $pickupPoint->getCreatedAt());
    }

    public function testOpeningHours(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $openingHours = [
            'monday' => ['09:00-12:00', '14:00-19:00'],
            'tuesday' => ['09:00-12:00', '14:00-19:00'],
            'wednesday' => ['09:00-12:00', '14:00-19:00'],
            'thursday' => ['09:00-12:00', '14:00-19:00'],
            'friday' => ['09:00-12:00', '14:00-19:00'],
            'saturday' => ['09:00-12:00'],
            'sunday' => [],
        ];

        $pickupPoint->setOpeningHours($openingHours);

        $this->assertSame($openingHours, $pickupPoint->getOpeningHours());
        $this->assertIsArray($pickupPoint->getOpeningHours());
        $this->assertCount(7, $pickupPoint->getOpeningHours());
    }

    public function testToString(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setPostalCode('75001')
            ->setCity('Paris');

        $expected = '123456 - Relais Test 75001 Paris';
        $this->assertSame($expected, (string) $pickupPoint);
    }

    public function testToStringWithEmptyValues(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $this->assertSame(' -   ', (string) $pickupPoint);
    }

    public function testFluentInterface(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $result = $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Test');

        $this->assertSame($pickupPoint, $result);
    }

    /**
     * @dataProvider coordinatesProvider
     */
    public function testCoordinates(string $latitude, string $longitude): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setLatitude($latitude)
            ->setLongitude($longitude);

        $this->assertSame($latitude, $pickupPoint->getLatitude());
        $this->assertSame($longitude, $pickupPoint->getLongitude());
    }

    public static function coordinatesProvider(): array
    {
        return [
            'Paris coordinates' => ['48.8566140', '2.3522219'],
            'London coordinates' => ['51.5074000', '-0.1278000'],
            'New York coordinates' => ['40.7128000', '-74.0060000'],
            'Tokyo coordinates' => ['35.6762000', '139.6503000'],
            'Sydney coordinates' => ['-33.8688000', '151.2093000'],
        ];
    }
}
```

### Test 2: AddressEmbeddable

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use Kiora\SyliusMondialRelayPlugin\Entity\AddressEmbeddable;
use PHPUnit\Framework\TestCase;

class AddressEmbeddableTest extends TestCase
{
    public function testConstructor(): void
    {
        $address = new AddressEmbeddable(
            '123 rue Test',
            '75001',
            'Paris',
            'FR'
        );

        $this->assertSame('123 rue Test', $address->getStreet());
        $this->assertSame('75001', $address->getPostalCode());
        $this->assertSame('Paris', $address->getCity());
        $this->assertSame('FR', $address->getCountryCode());
    }

    public function testGetFullAddress(): void
    {
        $address = new AddressEmbeddable(
            '123 rue de la Paix',
            '75001',
            'Paris',
            'FR'
        );

        $expected = '123 rue de la Paix, 75001, Paris, FR';
        $this->assertSame($expected, $address->getFullAddress());
    }

    public function testGetFullAddressWithAdditional(): void
    {
        $address = new AddressEmbeddable(
            '123 rue de la Paix',
            '75001',
            'Paris',
            'FR'
        );
        $address->setStreetAdditional('Appartement 5');

        $expected = '123 rue de la Paix, Appartement 5, 75001, Paris, FR';
        $this->assertSame($expected, $address->getFullAddress());
    }

    public function testIsEmpty(): void
    {
        $address = new AddressEmbeddable();
        $this->assertTrue($address->isEmpty());

        $address->setStreet('123 rue Test');
        $this->assertFalse($address->isEmpty());
    }

    public function testToString(): void
    {
        $address = new AddressEmbeddable(
            '123 rue Test',
            '75001',
            'Paris',
            'FR'
        );

        $this->assertSame($address->getFullAddress(), (string) $address);
    }
}
```

## Validation Tests

### Test 3: ValidCoordinates Validator

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use Kiora\SyliusMondialRelayPlugin\Validator\Constraints\ValidCoordinates;
use Kiora\SyliusMondialRelayPlugin\Validator\Constraints\ValidCoordinatesValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidCoordinatesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidCoordinatesValidator
    {
        return new ValidCoordinatesValidator();
    }

    /**
     * @dataProvider validLatitudesProvider
     */
    public function testValidLatitude($value): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validLongitudesProvider
     */
    public function testValidLongitude($value): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider invalidLatitudesProvider
     */
    public function testInvalidLatitude($value): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->invalidLatitudeMessage)
            ->setParameter('{{ value }}', (string) $value)
            ->assertRaised();
    }

    /**
     * @dataProvider invalidLongitudesProvider
     */
    public function testInvalidLongitude($value): void
    {
        $constraint = new ValidCoordinates(type: 'longitude');

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->invalidLongitudeMessage)
            ->setParameter('{{ value }}', (string) $value)
            ->assertRaised();
    }

    public function testNullIsValid(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid(): void
    {
        $constraint = new ValidCoordinates(type: 'latitude');

        $this->validator->validate('', $constraint);

        $this->assertNoViolation();
    }

    public static function validLatitudesProvider(): array
    {
        return [
            ['0'],
            ['0.0'],
            ['48.8566140'],
            ['90'],
            ['-90'],
            ['89.999999'],
            ['-89.999999'],
            [0],
            [0.0],
            [48.8566140],
            [90],
            [-90],
        ];
    }

    public static function validLongitudesProvider(): array
    {
        return [
            ['0'],
            ['0.0'],
            ['2.3522219'],
            ['180'],
            ['-180'],
            ['179.999999'],
            ['-179.999999'],
            [0],
            [0.0],
            [2.3522219],
            [180],
            [-180],
        ];
    }

    public static function invalidLatitudesProvider(): array
    {
        return [
            ['90.1'],
            ['-90.1'],
            ['100'],
            ['-100'],
            [90.1],
            [-90.1],
            [100],
            [-100],
        ];
    }

    public static function invalidLongitudesProvider(): array
    {
        return [
            ['180.1'],
            ['-180.1'],
            ['200'],
            ['-200'],
            [180.1],
            [-180.1],
            [200],
            [-200],
        ];
    }
}
```

### Test 4: Entity Validation

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validation;

use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MondialRelayPickupPointValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidPickupPoint(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([]);

        $errors = $this->validator->validate($pickupPoint);

        $this->assertCount(0, $errors);
    }

    public function testMissingRelayPointId(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setName('Relais Test')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([]);

        $errors = $this->validator->validate($pickupPoint);

        $this->assertGreaterThan(0, count($errors));
        $this->assertStringContainsString('relayPointId', (string) $errors);
    }

    public function testInvalidCountryCode(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('INVALID')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([]);

        $errors = $this->validator->validate($pickupPoint);

        $this->assertGreaterThan(0, count($errors));
        $this->assertStringContainsString('countryCode', (string) $errors);
    }

    public function testInvalidCoordinates(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('999') // Invalid latitude
            ->setLongitude('2.3522219')
            ->setOpeningHours([]);

        $errors = $this->validator->validate($pickupPoint);

        $this->assertGreaterThan(0, count($errors));
    }
}
```

## Functional Tests

### Test 5: Repository Tests

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Repository\MondialRelayPickupPointRepository;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class MondialRelayPickupPointRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private MondialRelayPickupPointRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();
        $this->repository = self::getContainer()
            ->get(MondialRelayPickupPointRepository::class);

        // Clean database
        $this->entityManager->createQuery('DELETE FROM ' . MondialRelayPickupPoint::class)
            ->execute();
    }

    public function testFindByPostalCodeAndCountry(): void
    {
        // Create test data
        $pickupPoint1 = $this->createPickupPoint('123456', '75001', 'FR', 100);
        $pickupPoint2 = $this->createPickupPoint('234567', '75001', 'FR', 200);
        $pickupPoint3 = $this->createPickupPoint('345678', '75002', 'FR', 300);

        $this->entityManager->persist($pickupPoint1);
        $this->entityManager->persist($pickupPoint2);
        $this->entityManager->persist($pickupPoint3);
        $this->entityManager->flush();

        // Test
        $results = $this->repository->findByPostalCodeAndCountry('75001', 'FR');

        $this->assertCount(2, $results);
        $this->assertSame('123456', $results[0]->getRelayPointId());
        $this->assertSame('234567', $results[1]->getRelayPointId());
    }

    public function testFindByRelayPointId(): void
    {
        $pickupPoint = $this->createPickupPoint('123456', '75001', 'FR');
        $this->entityManager->persist($pickupPoint);
        $this->entityManager->flush();

        $result = $this->repository->findByRelayPointId('123456');

        $this->assertNotNull($result);
        $this->assertSame('123456', $result->getRelayPointId());
    }

    public function testFindByRelayPointIdNotFound(): void
    {
        $result = $this->repository->findByRelayPointId('999999');

        $this->assertNull($result);
    }

    public function testSearch(): void
    {
        $pickupPoint1 = $this->createPickupPoint('123456', '75001', 'FR', city: 'Paris');
        $pickupPoint2 = $this->createPickupPoint('234567', '75002', 'FR', city: 'Paris');
        $pickupPoint3 = $this->createPickupPoint('345678', '69001', 'FR', city: 'Lyon');

        $this->entityManager->persist($pickupPoint1);
        $this->entityManager->persist($pickupPoint2);
        $this->entityManager->persist($pickupPoint3);
        $this->entityManager->flush();

        $results = $this->repository->search(city: 'Paris');

        $this->assertCount(2, $results);
    }

    private function createPickupPoint(
        string $relayPointId,
        string $postalCode,
        string $countryCode,
        ?int $distance = null,
        string $city = 'Test City'
    ): MondialRelayPickupPoint {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId($relayPointId)
            ->setName('Relais ' . $relayPointId)
            ->setStreet('123 rue Test')
            ->setPostalCode($postalCode)
            ->setCity($city)
            ->setCountryCode($countryCode)
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([])
            ->setDistanceMeters($distance);

        return $pickupPoint;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

## Integration Tests

### Test 6: Shipment Integration

```php
<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Shipping\Shipment;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ShipmentIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testAssignPickupPointToShipment(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([]);

        $this->entityManager->persist($pickupPoint);

        $shipment = new Shipment();
        $shipment->setMondialRelayPickupPoint($pickupPoint);
        $shipment->setMondialRelayTrackingNumber('MR123456789FR');
        $shipment->setMondialRelayLabelUrl('https://example.com/label.pdf');

        $this->entityManager->persist($shipment);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Retrieve from database
        $retrievedShipment = $this->entityManager
            ->getRepository(Shipment::class)
            ->find($shipment->getId());

        $this->assertNotNull($retrievedShipment);
        $this->assertNotNull($retrievedShipment->getMondialRelayPickupPoint());
        $this->assertSame('123456', $retrievedShipment->getMondialRelayPickupPoint()->getRelayPointId());
        $this->assertSame('MR123456789FR', $retrievedShipment->getMondialRelayTrackingNumber());
        $this->assertSame('https://example.com/label.pdf', $retrievedShipment->getMondialRelayLabelUrl());
    }

    public function testDeletePickupPointSetsNullOnShipment(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint
            ->setRelayPointId('123456')
            ->setName('Relais Test')
            ->setStreet('123 rue Test')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566140')
            ->setLongitude('2.3522219')
            ->setOpeningHours([]);

        $this->entityManager->persist($pickupPoint);

        $shipment = new Shipment();
        $shipment->setMondialRelayPickupPoint($pickupPoint);

        $this->entityManager->persist($shipment);
        $this->entityManager->flush();

        $shipmentId = $shipment->getId();

        // Delete pickup point
        $this->entityManager->remove($pickupPoint);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Verify shipment still exists but pickup point is null
        $retrievedShipment = $this->entityManager
            ->getRepository(Shipment::class)
            ->find($shipmentId);

        $this->assertNotNull($retrievedShipment);
        $this->assertNull($retrievedShipment->getMondialRelayPickupPoint());
    }
}
```

## Test Data Fixtures

### Fixture Example

```php
<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;

class MondialRelayPickupPointFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $pickupPoints = [
            [
                'id' => '123456',
                'name' => 'Relais Paris Centre',
                'street' => '15 rue de Rivoli',
                'postalCode' => '75001',
                'city' => 'Paris',
                'countryCode' => 'FR',
                'latitude' => '48.8606111',
                'longitude' => '2.3376720',
                'distance' => 500,
            ],
            [
                'id' => '234567',
                'name' => 'Relais Paris Nord',
                'street' => '45 avenue de Flandre',
                'postalCode' => '75019',
                'city' => 'Paris',
                'countryCode' => 'FR',
                'latitude' => '48.8847090',
                'longitude' => '2.3695540',
                'distance' => 1200,
            ],
            [
                'id' => '345678',
                'name' => 'Relais Lyon Part-Dieu',
                'street' => '10 rue de la Part-Dieu',
                'postalCode' => '69003',
                'city' => 'Lyon',
                'countryCode' => 'FR',
                'latitude' => '45.7602410',
                'longitude' => '4.8561790',
                'distance' => 800,
            ],
        ];

        foreach ($pickupPoints as $data) {
            $pickupPoint = new MondialRelayPickupPoint();
            $pickupPoint
                ->setRelayPointId($data['id'])
                ->setName($data['name'])
                ->setStreet($data['street'])
                ->setPostalCode($data['postalCode'])
                ->setCity($data['city'])
                ->setCountryCode($data['countryCode'])
                ->setLatitude($data['latitude'])
                ->setLongitude($data['longitude'])
                ->setDistanceMeters($data['distance'])
                ->setOpeningHours($this->getDefaultOpeningHours());

            $manager->persist($pickupPoint);
        }

        $manager->flush();
    }

    private function getDefaultOpeningHours(): array
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

## Running Tests

```bash
# Run all tests
bin/phpunit

# Run specific test class
bin/phpunit tests/Unit/Entity/MondialRelayPickupPointTest.php

# Run with coverage
bin/phpunit --coverage-html coverage/

# Run with testdox (readable output)
bin/phpunit --testdox

# Load fixtures
bin/console doctrine:fixtures:load --no-interaction
```

## Continuous Integration

### GitHub Actions Example

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: sylius_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run tests
        run: bin/phpunit --coverage-text

      - name: Run static analysis
        run: vendor/bin/phpstan analyse
```

## Best Practices

1. **Use data providers** for testing multiple scenarios
2. **Mock external dependencies** (API calls, etc.)
3. **Test edge cases** (null values, empty arrays, boundary conditions)
4. **Use fixtures** for consistent test data
5. **Test validation** separately from persistence
6. **Clean database** between tests
7. **Use transactions** for faster tests when possible
8. **Test relationships** (cascade operations, orphan removal)
9. **Verify constraints** (unique, foreign keys)
10. **Test custom validators** thoroughly

## Coverage Goals

- **Unit Tests**: 100% code coverage
- **Integration Tests**: All entity relationships
- **Validation Tests**: All constraints
- **Repository Tests**: All custom queries

## See Also

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [Doctrine Testing](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/testing.html)
