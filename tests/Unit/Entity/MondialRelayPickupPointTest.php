<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Entity;

use Kiora\SyliusMondialRelayPlugin\Entity\MondialRelayPickupPoint;
use PHPUnit\Framework\TestCase;

final class MondialRelayPickupPointTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $this->assertInstanceOf(\DateTimeImmutable::class, $pickupPoint->getCreatedAt());
        $this->assertEqualsWithDelta(
            new \DateTimeImmutable(),
            $pickupPoint->getCreatedAt(),
            2 // Allow 2 seconds difference
        );
    }

    public function testGettersAndSetters(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        // Test relayPointId
        $pickupPoint->setRelayPointId('FR123456');
        $this->assertEquals('FR123456', $pickupPoint->getRelayPointId());

        // Test name
        $pickupPoint->setName('Test Relay Point');
        $this->assertEquals('Test Relay Point', $pickupPoint->getName());

        // Test street
        $pickupPoint->setStreet('123 Test Street');
        $this->assertEquals('123 Test Street', $pickupPoint->getStreet());

        // Test postalCode
        $pickupPoint->setPostalCode('75001');
        $this->assertEquals('75001', $pickupPoint->getPostalCode());

        // Test city
        $pickupPoint->setCity('Paris');
        $this->assertEquals('Paris', $pickupPoint->getCity());

        // Test countryCode
        $pickupPoint->setCountryCode('FR');
        $this->assertEquals('FR', $pickupPoint->getCountryCode());

        // Test latitude
        $pickupPoint->setLatitude('48.8566');
        $this->assertEquals('48.8566', $pickupPoint->getLatitude());

        // Test longitude
        $pickupPoint->setLongitude('2.3522');
        $this->assertEquals('2.3522', $pickupPoint->getLongitude());

        // Test distanceMeters
        $pickupPoint->setDistanceMeters(500);
        $this->assertEquals(500, $pickupPoint->getDistanceMeters());

        // Test createdAt
        $now = new \DateTimeImmutable();
        $pickupPoint->setCreatedAt($now);
        $this->assertEquals($now, $pickupPoint->getCreatedAt());
    }

    public function testOpeningHoursJsonHandling(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '12:00'],
                ['open' => '14:00', 'close' => '19:00'],
            ],
            'tuesday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'wednesday' => [],
        ];

        $pickupPoint->setOpeningHours($openingHours);
        $retrieved = $pickupPoint->getOpeningHours();

        $this->assertIsArray($retrieved);
        $this->assertArrayHasKey('monday', $retrieved);
        $this->assertArrayHasKey('tuesday', $retrieved);
        $this->assertCount(2, $retrieved['monday']);
        $this->assertEquals('09:00', $retrieved['monday'][0]['open']);
        $this->assertEquals('12:00', $retrieved['monday'][0]['close']);
    }

    public function testOpeningHoursDefaultsToEmptyArray(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $openingHours = $pickupPoint->getOpeningHours();

        $this->assertIsArray($openingHours);
        $this->assertEmpty($openingHours);
    }

    public function testToString(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();
        $pickupPoint->setRelayPointId('FR123456');
        $pickupPoint->setName('Test Relay Point');
        $pickupPoint->setPostalCode('75001');
        $pickupPoint->setCity('Paris');

        $expected = 'FR123456 - Test Relay Point 75001 Paris';
        $this->assertEquals($expected, (string) $pickupPoint);
    }

    public function testToStringWithNullValues(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $expected = ' -   ';
        $this->assertEquals($expected, (string) $pickupPoint);
    }

    public function testIdIsNullByDefault(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $this->assertNull($pickupPoint->getId());
    }

    public function testDistanceMetersCanBeNull(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $this->assertNull($pickupPoint->getDistanceMeters());

        $pickupPoint->setDistanceMeters(null);
        $this->assertNull($pickupPoint->getDistanceMeters());
    }

    public function testFluentSetters(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $result = $pickupPoint
            ->setRelayPointId('FR123456')
            ->setName('Test Point')
            ->setStreet('123 Street')
            ->setPostalCode('75001')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566')
            ->setLongitude('2.3522')
            ->setDistanceMeters(500)
            ->setOpeningHours(['monday' => []])
            ->setCreatedAt(new \DateTimeImmutable());

        $this->assertInstanceOf(MondialRelayPickupPoint::class, $result);
        $this->assertEquals('FR123456', $pickupPoint->getRelayPointId());
        $this->assertEquals('Test Point', $pickupPoint->getName());
    }

    public function testCompletePickupPointData(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '12:00'],
                ['open' => '14:00', 'close' => '19:00'],
            ],
            'tuesday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'wednesday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'thursday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'friday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'saturday' => [
                ['open' => '09:00', 'close' => '12:00'],
            ],
            'sunday' => [],
        ];

        $createdAt = new \DateTimeImmutable('2024-01-15 10:00:00');

        $pickupPoint
            ->setRelayPointId('FR123456')
            ->setName('Tabac Le Central')
            ->setStreet('123 Avenue de la République')
            ->setPostalCode('75011')
            ->setCity('Paris')
            ->setCountryCode('FR')
            ->setLatitude('48.8566')
            ->setLongitude('2.3522')
            ->setDistanceMeters(750)
            ->setOpeningHours($openingHours)
            ->setCreatedAt($createdAt);

        $this->assertEquals('FR123456', $pickupPoint->getRelayPointId());
        $this->assertEquals('Tabac Le Central', $pickupPoint->getName());
        $this->assertEquals('123 Avenue de la République', $pickupPoint->getStreet());
        $this->assertEquals('75011', $pickupPoint->getPostalCode());
        $this->assertEquals('Paris', $pickupPoint->getCity());
        $this->assertEquals('FR', $pickupPoint->getCountryCode());
        $this->assertEquals('48.8566', $pickupPoint->getLatitude());
        $this->assertEquals('2.3522', $pickupPoint->getLongitude());
        $this->assertEquals(750, $pickupPoint->getDistanceMeters());
        $this->assertEquals($openingHours, $pickupPoint->getOpeningHours());
        $this->assertEquals($createdAt, $pickupPoint->getCreatedAt());

        $expectedString = 'FR123456 - Tabac Le Central 75011 Paris';
        $this->assertEquals($expectedString, (string) $pickupPoint);
    }

    public function testCoordinatesStoredAsDecimalStrings(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        // Test with high precision coordinates
        $pickupPoint->setLatitude('48.8566140');
        $pickupPoint->setLongitude('2.3522219');

        $this->assertIsString($pickupPoint->getLatitude());
        $this->assertIsString($pickupPoint->getLongitude());
        $this->assertEquals('48.8566140', $pickupPoint->getLatitude());
        $this->assertEquals('2.3522219', $pickupPoint->getLongitude());
    }

    public function testNegativeCoordinates(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $pickupPoint->setLatitude('-33.8688');
        $pickupPoint->setLongitude('-151.2093');

        $this->assertEquals('-33.8688', $pickupPoint->getLatitude());
        $this->assertEquals('-151.2093', $pickupPoint->getLongitude());
    }

    public function testEmptyOpeningHoursForSpecificDays(): void
    {
        $pickupPoint = new MondialRelayPickupPoint();

        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'sunday' => [], // Closed on Sunday
        ];

        $pickupPoint->setOpeningHours($openingHours);
        $retrieved = $pickupPoint->getOpeningHours();

        $this->assertNotEmpty($retrieved['monday']);
        $this->assertEmpty($retrieved['sunday']);
    }
}
