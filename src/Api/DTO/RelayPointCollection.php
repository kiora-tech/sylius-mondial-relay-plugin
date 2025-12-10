<?php

declare(strict_types=1);

namespace Kiora\SyliusMondialRelayPlugin\Api\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Collection of RelayPointDTO objects.
 *
 * Provides an iterable collection with useful methods for working with
 * multiple relay points (filtering, sorting, pagination).
 *
 * @implements IteratorAggregate<int, RelayPointDTO>
 */
readonly class RelayPointCollection implements IteratorAggregate, Countable
{
    /**
     * @param array<RelayPointDTO> $relayPoints Array of relay point DTOs
     * @param int $totalCount Total number of results (for pagination)
     */
    public function __construct(
        private array $relayPoints = [],
        public int $totalCount = 0,
    ) {
    }

    /**
     * Get iterator for the collection.
     *
     * @return Traversable<int, RelayPointDTO>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->relayPoints);
    }

    /**
     * Count the number of relay points in the collection.
     */
    public function count(): int
    {
        return count($this->relayPoints);
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->relayPoints);
    }

    /**
     * Get all relay points as an array.
     *
     * @return array<RelayPointDTO>
     */
    public function all(): array
    {
        return $this->relayPoints;
    }

    /**
     * Get the first relay point in the collection.
     */
    public function first(): ?RelayPointDTO
    {
        return $this->relayPoints[0] ?? null;
    }

    /**
     * Get relay point by index.
     *
     * @param int $index Zero-based index
     */
    public function get(int $index): ?RelayPointDTO
    {
        return $this->relayPoints[$index] ?? null;
    }

    /**
     * Find a relay point by its ID.
     *
     * @param string $relayPointId Mondial Relay point identifier
     */
    public function findById(string $relayPointId): ?RelayPointDTO
    {
        foreach ($this->relayPoints as $relayPoint) {
            if ($relayPoint->relayPointId === $relayPointId) {
                return $relayPoint;
            }
        }

        return null;
    }

    /**
     * Filter relay points by a callback.
     *
     * @param callable(RelayPointDTO): bool $callback Filter function
     */
    public function filter(callable $callback): self
    {
        return new self(
            relayPoints: array_values(array_filter($this->relayPoints, $callback)),
            totalCount: $this->totalCount
        );
    }

    /**
     * Filter relay points by service availability.
     *
     * @param string $service Service code (e.g., 'parking', 'wheelchair_accessible')
     */
    public function filterByService(string $service): self
    {
        return $this->filter(fn(RelayPointDTO $rp) => $rp->hasService($service));
    }

    /**
     * Filter relay points by maximum distance.
     *
     * @param int $maxDistanceMeters Maximum distance in meters
     */
    public function filterByMaxDistance(int $maxDistanceMeters): self
    {
        return $this->filter(
            fn(RelayPointDTO $rp) => $rp->distanceMeters !== null && $rp->distanceMeters <= $maxDistanceMeters
        );
    }

    /**
     * Filter relay points that are currently active.
     */
    public function filterActive(): self
    {
        return $this->filter(fn(RelayPointDTO $rp) => $rp->isActive);
    }

    /**
     * Map relay points to an array using a callback.
     *
     * @template T
     * @param callable(RelayPointDTO): T $callback Mapping function
     *
     * @return array<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->relayPoints);
    }

    /**
     * Convert all relay points to array representation.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->map(fn(RelayPointDTO $rp) => $rp->toArray());
    }

    /**
     * Create collection from API response.
     *
     * @param array<string, mixed> $response API response containing relay points
     * @param string $itemsKey Key containing the array of relay points (default: 'relayPoints')
     * @param string $totalKey Key containing the total count (default: 'totalCount')
     */
    public static function fromApiResponse(
        array $response,
        string $itemsKey = 'relayPoints',
        string $totalKey = 'totalCount'
    ): self {
        $items = $response[$itemsKey] ?? [];
        $relayPoints = array_map(
            fn(array $item) => RelayPointDTO::fromApiResponse($item),
            $items
        );

        return new self(
            relayPoints: $relayPoints,
            totalCount: $response[$totalKey] ?? count($relayPoints)
        );
    }

    /**
     * Create an empty collection.
     */
    public static function empty(): self
    {
        return new self([], 0);
    }
}
