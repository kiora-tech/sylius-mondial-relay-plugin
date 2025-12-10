<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Api\DTO;

use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointDTO;
use PHPUnit\Framework\TestCase;

final class RelayPointDTOTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '12:00'],
                ['open' => '14:00', 'close' => '19:00'],
            ],
        ];

        $services = ['parking', 'wheelchair_accessible'];
        $exceptionalClosures = [
            ['date' => '2024-12-25', 'reason' => 'Christmas'],
        ];

        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Relay Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distanceMeters: 500,
            openingHours: $openingHours,
            services: $services,
            photoUrl: 'https://example.com/photo.jpg',
            informations: 'Test information',
            isActive: true,
            exceptionalClosures: $exceptionalClosures
        );

        $this->assertEquals('FR123456', $dto->relayPointId);
        $this->assertEquals('Test Relay Point', $dto->name);
        $this->assertEquals('123 Test Street', $dto->street);
        $this->assertEquals('75001', $dto->postalCode);
        $this->assertEquals('Paris', $dto->city);
        $this->assertEquals('FR', $dto->countryCode);
        $this->assertEquals(48.8566, $dto->latitude);
        $this->assertEquals(2.3522, $dto->longitude);
        $this->assertEquals(500, $dto->distanceMeters);
        $this->assertEquals($openingHours, $dto->openingHours);
        $this->assertEquals($services, $dto->services);
        $this->assertEquals('https://example.com/photo.jpg', $dto->photoUrl);
        $this->assertEquals('Test information', $dto->informations);
        $this->assertTrue($dto->isActive);
        $this->assertEquals($exceptionalClosures, $dto->exceptionalClosures);
    }

    public function testGetFullAddress(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522
        );

        $expected = '123 Test Street, 75001 Paris, FR';
        $this->assertEquals($expected, $dto->getFullAddress());
    }

    public function testGetDistanceKm(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distanceMeters: 1500
        );

        $this->assertEquals(1.5, $dto->getDistanceKm());
    }

    public function testGetDistanceKmReturnsNullWhenDistanceIsNull(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distanceMeters: null
        );

        $this->assertNull($dto->getDistanceKm());
    }

    public function testGetDistanceKmRoundsToTwoDecimals(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distanceMeters: 1567
        );

        $this->assertEquals(1.57, $dto->getDistanceKm());
    }

    public function testGetGoogleMapsUrl(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522
        );

        $expected = 'https://www.google.com/maps/search/?api=1&query=48.856600,2.352200';
        $this->assertEquals($expected, $dto->getGoogleMapsUrl());
    }

    public function testHasService(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            services: ['parking', 'wheelchair_accessible']
        );

        $this->assertTrue($dto->hasService('parking'));
        $this->assertTrue($dto->hasService('wheelchair_accessible'));
        $this->assertFalse($dto->hasService('unknown_service'));
    }

    public function testGetOpeningHoursForDay(): void
    {
        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '12:00'],
                ['open' => '14:00', 'close' => '19:00'],
            ],
            'tuesday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'sunday' => [],
        ];

        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            openingHours: $openingHours
        );

        $mondayHours = $dto->getOpeningHoursForDay('monday');
        $this->assertCount(2, $mondayHours);
        $this->assertEquals('09:00', $mondayHours[0]['open']);
        $this->assertEquals('12:00', $mondayHours[0]['close']);

        $tuesdayHours = $dto->getOpeningHoursForDay('tuesday');
        $this->assertCount(1, $tuesdayHours);

        $sundayHours = $dto->getOpeningHoursForDay('sunday');
        $this->assertEmpty($sundayHours);

        $unknownDayHours = $dto->getOpeningHoursForDay('invalidday');
        $this->assertEmpty($unknownDayHours);
    }

    public function testIsOpenOnDay(): void
    {
        $openingHours = [
            'monday' => [
                ['open' => '09:00', 'close' => '19:00'],
            ],
            'sunday' => [],
        ];

        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            openingHours: $openingHours
        );

        $this->assertTrue($dto->isOpenOnDay('monday'));
        $this->assertFalse($dto->isOpenOnDay('sunday'));
        $this->assertFalse($dto->isOpenOnDay('wednesday'));
    }

    public function testFromApiResponse(): void
    {
        $apiData = [
            'id' => 'FR123456',
            'name' => 'Test Relay Point',
            'address' => [
                'street' => '123 Test Street',
                'postalCode' => '75001',
                'city' => 'Paris',
                'countryCode' => 'FR',
            ],
            'coordinates' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
            'distance' => 500,
            'openingHours' => [
                'monday' => [
                    ['open' => '09:00', 'close' => '19:00'],
                ],
            ],
            'services' => ['parking', 'wheelchair_accessible'],
            'photoUrl' => 'https://example.com/photo.jpg',
            'informations' => 'Test info',
            'isActive' => true,
            'exceptionalClosures' => [
                ['date' => '2024-12-25', 'reason' => 'Christmas'],
            ],
        ];

        $dto = RelayPointDTO::fromApiResponse($apiData);

        $this->assertEquals('FR123456', $dto->relayPointId);
        $this->assertEquals('Test Relay Point', $dto->name);
        $this->assertEquals('123 Test Street', $dto->street);
        $this->assertEquals('75001', $dto->postalCode);
        $this->assertEquals('Paris', $dto->city);
        $this->assertEquals('FR', $dto->countryCode);
        $this->assertEquals(48.8566, $dto->latitude);
        $this->assertEquals(2.3522, $dto->longitude);
        $this->assertEquals(500, $dto->distanceMeters);
        $this->assertTrue($dto->isActive);
    }

    public function testFromApiResponseWithMinimalData(): void
    {
        $apiData = [
            'id' => 'FR123456',
            'name' => 'Test Point',
            'address' => [
                'street' => '123 Test Street',
                'postalCode' => '75001',
                'city' => 'Paris',
                'countryCode' => 'FR',
            ],
            'coordinates' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
        ];

        $dto = RelayPointDTO::fromApiResponse($apiData);

        $this->assertEquals('FR123456', $dto->relayPointId);
        $this->assertNull($dto->distanceMeters);
        $this->assertEmpty($dto->openingHours);
        $this->assertEmpty($dto->services);
        $this->assertNull($dto->photoUrl);
        $this->assertNull($dto->informations);
        $this->assertTrue($dto->isActive); // Default value
        $this->assertEmpty($dto->exceptionalClosures);
    }

    public function testToArray(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distanceMeters: 1500,
            services: ['parking'],
            isActive: true
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('FR123456', $array['relayPointId']);
        $this->assertEquals('Test Point', $array['name']);
        $this->assertArrayHasKey('address', $array);
        $this->assertEquals('123 Test Street', $array['address']['street']);
        $this->assertEquals('75001', $array['address']['postalCode']);
        $this->assertArrayHasKey('coordinates', $array);
        $this->assertEquals(48.8566, $array['coordinates']['latitude']);
        $this->assertEquals(2.3522, $array['coordinates']['longitude']);
        $this->assertEquals(1500, $array['distanceMeters']);
        $this->assertEquals(1.5, $array['distanceKm']);
        $this->assertEquals(['parking'], $array['services']);
        $this->assertTrue($array['isActive']);
        $this->assertStringContainsString('google.com/maps', $array['googleMapsUrl']);
    }

    public function testReadonlyProperty(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522
        );

        // Verify that the class is readonly
        $reflection = new \ReflectionClass($dto);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testNegativeCoordinates(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'AU123456',
            name: 'Sydney Point',
            street: '123 Test Street',
            postalCode: '2000',
            city: 'Sydney',
            countryCode: 'AU',
            latitude: -33.8688,
            longitude: 151.2093
        );

        $this->assertEquals(-33.8688, $dto->latitude);
        $this->assertEquals(151.2093, $dto->longitude);

        $googleMapsUrl = $dto->getGoogleMapsUrl();
        $this->assertStringContainsString('-33.868800', $googleMapsUrl);
        $this->assertStringContainsString('151.209300', $googleMapsUrl);
    }

    public function testEmptyServicesArray(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            services: []
        );

        $this->assertFalse($dto->hasService('parking'));
        $this->assertEmpty($dto->services);
    }

    public function testInactiveRelayPoint(): void
    {
        $dto = new RelayPointDTO(
            relayPointId: 'FR123456',
            name: 'Test Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            isActive: false
        );

        $this->assertFalse($dto->isActive);
    }
}
