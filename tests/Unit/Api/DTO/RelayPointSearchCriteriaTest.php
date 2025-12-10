<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Api\DTO;

use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointSearchCriteria;
use PHPUnit\Framework\TestCase;

final class RelayPointSearchCriteriaTest extends TestCase
{
    public function testFromPostalCode(): void
    {
        $criteria = RelayPointSearchCriteria::fromPostalCode(
            postalCode: '75001',
            countryCode: 'FR',
            city: 'Paris',
            radius: 15,
            limit: 10
        );

        $this->assertEquals('75001', $criteria->postalCode);
        $this->assertEquals('FR', $criteria->countryCode);
        $this->assertEquals('Paris', $criteria->city);
        $this->assertEquals(15, $criteria->radius);
        $this->assertEquals(10, $criteria->limit);
        $this->assertTrue($criteria->hasPostalCode());
        $this->assertFalse($criteria->hasCoordinates());
    }

    public function testFromCoordinates(): void
    {
        $criteria = RelayPointSearchCriteria::fromCoordinates(
            latitude: 48.8566,
            longitude: 2.3522,
            countryCode: 'FR',
            radius: 25,
            limit: 15
        );

        $this->assertEquals(48.8566, $criteria->latitude);
        $this->assertEquals(2.3522, $criteria->longitude);
        $this->assertEquals('FR', $criteria->countryCode);
        $this->assertEquals(25, $criteria->radius);
        $this->assertEquals(15, $criteria->limit);
        $this->assertTrue($criteria->hasCoordinates());
        $this->assertFalse($criteria->hasPostalCode());
    }

    public function testConstructorWithDefaultValues(): void
    {
        $criteria = new RelayPointSearchCriteria(
            postalCode: '75001'
        );

        $this->assertEquals('FR', $criteria->countryCode);
        $this->assertEquals(20, $criteria->radius);
        $this->assertEquals(20, $criteria->limit);
        $this->assertNull($criteria->deliveryMode);
        $this->assertNull($criteria->weight);
    }

    public function testValidationThrowsExceptionWhenNoSearchCriteriaProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either postal code or GPS coordinates must be provided');

        new RelayPointSearchCriteria();
    }

    public function testValidationThrowsExceptionForInvalidLatitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid latitude: 91');

        new RelayPointSearchCriteria(
            latitude: 91.0,
            longitude: 2.3522
        );
    }

    public function testValidationThrowsExceptionForInvalidLatitudeTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid latitude: -91');

        new RelayPointSearchCriteria(
            latitude: -91.0,
            longitude: 2.3522
        );
    }

    public function testValidationThrowsExceptionForInvalidLongitude(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid longitude: 181');

        new RelayPointSearchCriteria(
            latitude: 48.8566,
            longitude: 181.0
        );
    }

    public function testValidationThrowsExceptionForInvalidLongitudeTooLow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid longitude: -181');

        new RelayPointSearchCriteria(
            latitude: 48.8566,
            longitude: -181.0
        );
    }

    public function testValidationThrowsExceptionForInvalidRadius(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid radius: 0 km. Must be between 1 and 100');

        new RelayPointSearchCriteria(
            postalCode: '75001',
            radius: 0
        );
    }

    public function testValidationThrowsExceptionForRadiusTooLarge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid radius: 101 km. Must be between 1 and 100');

        new RelayPointSearchCriteria(
            postalCode: '75001',
            radius: 101
        );
    }

    public function testValidationThrowsExceptionForInvalidLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid limit: 0. Must be between 1 and 50');

        new RelayPointSearchCriteria(
            postalCode: '75001',
            limit: 0
        );
    }

    public function testValidationThrowsExceptionForLimitTooLarge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid limit: 51. Must be between 1 and 50');

        new RelayPointSearchCriteria(
            postalCode: '75001',
            limit: 51
        );
    }

    public function testValidationThrowsExceptionForInvalidWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid weight: -100 grams. Must be positive');

        new RelayPointSearchCriteria(
            postalCode: '75001',
            weight: -100
        );
    }

    public function testValidCoordinatesAtBoundaries(): void
    {
        $criteria1 = new RelayPointSearchCriteria(
            latitude: 90.0,
            longitude: 180.0
        );

        $this->assertEquals(90.0, $criteria1->latitude);
        $this->assertEquals(180.0, $criteria1->longitude);

        $criteria2 = new RelayPointSearchCriteria(
            latitude: -90.0,
            longitude: -180.0
        );

        $this->assertEquals(-90.0, $criteria2->latitude);
        $this->assertEquals(-180.0, $criteria2->longitude);
    }

    public function testWithOptionalParameters(): void
    {
        $criteria = new RelayPointSearchCriteria(
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            radius: 10,
            limit: 5,
            deliveryMode: '24R',
            weight: 1000
        );

        $this->assertEquals('75001', $criteria->postalCode);
        $this->assertEquals('Paris', $criteria->city);
        $this->assertEquals('FR', $criteria->countryCode);
        $this->assertEquals(10, $criteria->radius);
        $this->assertEquals(5, $criteria->limit);
        $this->assertEquals('24R', $criteria->deliveryMode);
        $this->assertEquals(1000, $criteria->weight);
    }

    public function testHasPostalCodeReturnsFalseForEmptyString(): void
    {
        $criteria = new RelayPointSearchCriteria(
            postalCode: '',
            latitude: 48.8566,
            longitude: 2.3522
        );

        $this->assertFalse($criteria->hasPostalCode());
        $this->assertTrue($criteria->hasCoordinates());
    }

    public function testHasCoordinatesReturnsFalseWhenOnlyLatitudeProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RelayPointSearchCriteria(
            latitude: 48.8566
        );
    }

    public function testHasCoordinatesReturnsFalseWhenOnlyLongitudeProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RelayPointSearchCriteria(
            longitude: 2.3522
        );
    }

    public function testReadonlyProperty(): void
    {
        $criteria = RelayPointSearchCriteria::fromPostalCode('75001');

        $this->assertEquals('75001', $criteria->postalCode);

        // Verify that properties are readonly by checking class reflection
        $reflection = new \ReflectionClass($criteria);
        $this->assertTrue($reflection->isReadOnly());
    }
}
