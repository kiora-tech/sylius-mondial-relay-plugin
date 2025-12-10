<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Tests\Unit\Api\DTO;

use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointCollection;
use Kiora\SyliusMondialRelayPlugin\Api\DTO\RelayPointDTO;
use PHPUnit\Framework\TestCase;

final class RelayPointCollectionTest extends TestCase
{
    private function createSampleRelayPoint(
        string $id,
        string $name = 'Test Point',
        int $distanceMeters = 500,
        array $services = []
    ): RelayPointDTO {
        return new RelayPointDTO(
            relayPointId: $id,
            name: $name,
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            distanceMeters: $distanceMeters,
            services: $services,
            isActive: true
        );
    }

    public function testEmptyCollection(): void
    {
        $collection = RelayPointCollection::empty();

        $this->assertInstanceOf(RelayPointCollection::class, $collection);
        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
        $this->assertEquals(0, $collection->totalCount);
        $this->assertNull($collection->first());
    }

    public function testIteration(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001'),
            $this->createSampleRelayPoint('FR002'),
            $this->createSampleRelayPoint('FR003'),
        ];

        $collection = new RelayPointCollection($relayPoints, 3);

        $this->assertCount(3, $collection);
        $this->assertFalse($collection->isEmpty());

        $iteratedIds = [];
        foreach ($collection as $relayPoint) {
            $iteratedIds[] = $relayPoint->relayPointId;
        }

        $this->assertEquals(['FR001', 'FR002', 'FR003'], $iteratedIds);
    }

    public function testFilterByDistance(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001', 'Point 1', 300),
            $this->createSampleRelayPoint('FR002', 'Point 2', 800),
            $this->createSampleRelayPoint('FR003', 'Point 3', 1500),
            $this->createSampleRelayPoint('FR004', 'Point 4', 500),
        ];

        $collection = new RelayPointCollection($relayPoints, 4);
        $filtered = $collection->filterByMaxDistance(600);

        $this->assertCount(2, $filtered);
        $this->assertEquals('FR001', $filtered->first()->relayPointId);
    }

    public function testFilterByService(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001', 'Point 1', 300, ['parking']),
            $this->createSampleRelayPoint('FR002', 'Point 2', 500, ['parking', 'wheelchair_accessible']),
            $this->createSampleRelayPoint('FR003', 'Point 3', 700, ['wheelchair_accessible']),
            $this->createSampleRelayPoint('FR004', 'Point 4', 900, []),
        ];

        $collection = new RelayPointCollection($relayPoints, 4);

        $withParking = $collection->filterByService('parking');
        $this->assertCount(2, $withParking);

        $withWheelchair = $collection->filterByService('wheelchair_accessible');
        $this->assertCount(2, $withWheelchair);

        $withUnknown = $collection->filterByService('unknown_service');
        $this->assertCount(0, $withUnknown);
    }

    public function testFindById(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001'),
            $this->createSampleRelayPoint('FR002'),
            $this->createSampleRelayPoint('FR003'),
        ];

        $collection = new RelayPointCollection($relayPoints, 3);

        $found = $collection->findById('FR002');
        $this->assertNotNull($found);
        $this->assertEquals('FR002', $found->relayPointId);

        $notFound = $collection->findById('FR999');
        $this->assertNull($notFound);
    }

    public function testGet(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001'),
            $this->createSampleRelayPoint('FR002'),
            $this->createSampleRelayPoint('FR003'),
        ];

        $collection = new RelayPointCollection($relayPoints, 3);

        $this->assertEquals('FR001', $collection->get(0)->relayPointId);
        $this->assertEquals('FR002', $collection->get(1)->relayPointId);
        $this->assertEquals('FR003', $collection->get(2)->relayPointId);
        $this->assertNull($collection->get(3));
        $this->assertNull($collection->get(-1));
    }

    public function testFirst(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001'),
            $this->createSampleRelayPoint('FR002'),
        ];

        $collection = new RelayPointCollection($relayPoints, 2);

        $first = $collection->first();
        $this->assertNotNull($first);
        $this->assertEquals('FR001', $first->relayPointId);
    }

    public function testAll(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001'),
            $this->createSampleRelayPoint('FR002'),
        ];

        $collection = new RelayPointCollection($relayPoints, 2);

        $all = $collection->all();
        $this->assertIsArray($all);
        $this->assertCount(2, $all);
        $this->assertInstanceOf(RelayPointDTO::class, $all[0]);
    }

    public function testMap(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001', 'Point 1'),
            $this->createSampleRelayPoint('FR002', 'Point 2'),
            $this->createSampleRelayPoint('FR003', 'Point 3'),
        ];

        $collection = new RelayPointCollection($relayPoints, 3);

        $ids = $collection->map(fn(RelayPointDTO $rp) => $rp->relayPointId);
        $this->assertEquals(['FR001', 'FR002', 'FR003'], $ids);

        $names = $collection->map(fn(RelayPointDTO $rp) => $rp->name);
        $this->assertEquals(['Point 1', 'Point 2', 'Point 3'], $names);
    }

    public function testToArray(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001', 'Point 1', 300),
            $this->createSampleRelayPoint('FR002', 'Point 2', 500),
        ];

        $collection = new RelayPointCollection($relayPoints, 2);

        $array = $collection->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertIsArray($array[0]);
        $this->assertEquals('FR001', $array[0]['relayPointId']);
        $this->assertEquals('Point 1', $array[0]['name']);
    }

    public function testFilterActive(): void
    {
        $activePoint = new RelayPointDTO(
            relayPointId: 'FR001',
            name: 'Active Point',
            street: '123 Test Street',
            postalCode: '75001',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            isActive: true
        );

        $inactivePoint = new RelayPointDTO(
            relayPointId: 'FR002',
            name: 'Inactive Point',
            street: '456 Test Street',
            postalCode: '75002',
            city: 'Paris',
            countryCode: 'FR',
            latitude: 48.8566,
            longitude: 2.3522,
            isActive: false
        );

        $collection = new RelayPointCollection([$activePoint, $inactivePoint], 2);

        $activeOnly = $collection->filterActive();
        $this->assertCount(1, $activeOnly);
        $this->assertEquals('FR001', $activeOnly->first()->relayPointId);
    }

    public function testFromApiResponse(): void
    {
        $apiResponse = [
            'relayPoints' => [
                [
                    'id' => 'FR001',
                    'name' => 'Test Point 1',
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
                    'distance' => 300,
                    'services' => ['parking'],
                    'isActive' => true,
                ],
                [
                    'id' => 'FR002',
                    'name' => 'Test Point 2',
                    'address' => [
                        'street' => '456 Test Avenue',
                        'postalCode' => '75002',
                        'city' => 'Paris',
                        'countryCode' => 'FR',
                    ],
                    'coordinates' => [
                        'latitude' => 48.8700,
                        'longitude' => 2.3500,
                    ],
                    'distance' => 500,
                    'services' => ['wheelchair_accessible'],
                    'isActive' => true,
                ],
            ],
            'totalCount' => 2,
        ];

        $collection = RelayPointCollection::fromApiResponse($apiResponse);

        $this->assertCount(2, $collection);
        $this->assertEquals(2, $collection->totalCount);
        $this->assertEquals('FR001', $collection->first()->relayPointId);
        $this->assertEquals('Test Point 1', $collection->first()->name);
    }

    public function testFromApiResponseWithCustomKeys(): void
    {
        $apiResponse = [
            'data' => [
                [
                    'id' => 'FR001',
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
                    'isActive' => true,
                ],
            ],
            'total' => 1,
        ];

        $collection = RelayPointCollection::fromApiResponse($apiResponse, 'data', 'total');

        $this->assertCount(1, $collection);
        $this->assertEquals(1, $collection->totalCount);
    }

    public function testFromApiResponseWithEmptyArray(): void
    {
        $apiResponse = [
            'relayPoints' => [],
            'totalCount' => 0,
        ];

        $collection = RelayPointCollection::fromApiResponse($apiResponse);

        $this->assertCount(0, $collection);
        $this->assertEquals(0, $collection->totalCount);
        $this->assertTrue($collection->isEmpty());
    }

    public function testFilterPreservesTotalCount(): void
    {
        $relayPoints = [
            $this->createSampleRelayPoint('FR001', 'Point 1', 300),
            $this->createSampleRelayPoint('FR002', 'Point 2', 800),
            $this->createSampleRelayPoint('FR003', 'Point 3', 1500),
        ];

        $collection = new RelayPointCollection($relayPoints, 10);
        $filtered = $collection->filterByMaxDistance(600);

        // TotalCount should remain the same (represents total available on server)
        $this->assertEquals(10, $filtered->totalCount);
        // But count should reflect filtered results
        $this->assertCount(1, $filtered);
    }

    public function testReadonlyProperty(): void
    {
        $collection = new RelayPointCollection([], 5);

        $this->assertEquals(5, $collection->totalCount);

        // Verify that the class uses readonly
        $reflection = new \ReflectionClass($collection);
        $this->assertTrue($reflection->isReadOnly());
    }
}
