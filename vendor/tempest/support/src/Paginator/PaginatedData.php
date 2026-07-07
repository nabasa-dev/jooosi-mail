<?php

namespace JooosiMailDeps\Tempest\Support\Paginator;

use JsonSerializable;
/**
 * @template T
 */
final class PaginatedData implements JsonSerializable
{
    /**
     * @param array<T> $data
     * @param list<int> $pageRange
     */
    public function __construct(public array $data, public int $currentPage, public int $totalPages, public int $totalItems, public int $itemsPerPage, public int $offset, public int $limit, public bool $hasNext, public bool $hasPrevious, public ?int $nextPage, public ?int $previousPage, public array $pageRange)
    {
    }
    public function getCount(): int
    {
        return count($this->data);
    }
    public function getIsEmpty(): bool
    {
        return $this->getCount() === 0;
    }
    public function getIsNotEmpty(): bool
    {
        return !$this->getIsEmpty();
    }
    /**
     * @template U
     * @param callable(T): U $callback
     * @return PaginatedData<U>
     */
    public function map(callable $callback): self
    {
        return new self(data: array_map($callback, $this->data), currentPage: $this->currentPage, totalPages: $this->totalPages, totalItems: $this->totalItems, itemsPerPage: $this->itemsPerPage, offset: $this->offset, limit: $this->limit, hasNext: $this->hasNext, hasPrevious: $this->hasPrevious, nextPage: $this->nextPage, previousPage: $this->previousPage, pageRange: $this->pageRange);
    }
    /**
     * @return array{
     *     data: array<T>,
     *     pagination: array{
     *         current_page: int,
     *         total_pages: int,
     *         total_items: int,
     *         items_per_page: int,
     *         offset: int,
     *         limit: int,
     *         has_next: bool,
     *         has_previous: bool,
     *         next_page: ?int,
     *         previous_page: ?int,
     *         page_range: list<int>,
     *         count: int
     *     }
     * }
     */
    public function toArray(): array
    {
        return ['data' => $this->data, 'pagination' => ['current_page' => $this->currentPage, 'total_pages' => $this->totalPages, 'total_items' => $this->totalItems, 'items_per_page' => $this->itemsPerPage, 'offset' => $this->offset, 'limit' => $this->limit, 'has_next' => $this->hasNext, 'has_previous' => $this->hasPrevious, 'next_page' => $this->nextPage, 'previous_page' => $this->previousPage, 'page_range' => $this->pageRange, 'count' => $this->getCount()]];
    }
    /**
     * @return array{
     *     data: array<T>,
     *     pagination: array{
     *         current_page: int,
     *         total_pages: int,
     *         total_items: int,
     *         items_per_page: int,
     *         offset: int,
     *         limit: int,
     *         has_next: bool,
     *         has_previous: bool,
     *         next_page: ?int,
     *         previous_page: ?int,
     *         page_range: list<int>,
     *         count: int
     *     }
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    public function __get(string $name): mixed
    {
        if ($name === 'count') {
            return $this->getCount();
        }
        if ($name === 'isEmpty') {
            return $this->getIsEmpty();
        }
        if ($name === 'isNotEmpty') {
            return $this->getIsNotEmpty();
        }
        throw new \RuntimeException(sprintf('Undefined property: %s::$%s', self::class, $name));
    }
    public function __isset(string $name): bool
    {
        if ($name === 'count') {
            return $this->getCount() !== null;
        }
        if ($name === 'isEmpty') {
            return $this->getIsEmpty() !== null;
        }
        if ($name === 'isNotEmpty') {
            return $this->getIsNotEmpty() !== null;
        }
        return \false;
    }
}
