<?php

namespace JooosiMailDeps\Tempest\Support\Paginator;

use JooosiMailDeps\Tempest\Support\Paginator\Exceptions\ArgumentWasInvalid;
final class Paginator
{
    public function __construct(public int $totalItems, public int $itemsPerPage = 20, public int $currentPage = 1, public int $maxLinks = 10)
    {
        if ($this->totalItems < 0) {
            throw new ArgumentWasInvalid('Total items cannot be negative');
        }
        if ($this->itemsPerPage <= 0) {
            throw new ArgumentWasInvalid('Items per page must be positive');
        }
        if ($this->currentPage <= 0) {
            throw new ArgumentWasInvalid('Current page must be positive');
        }
        if ($this->maxLinks <= 0) {
            throw new ArgumentWasInvalid('Max links must be positive');
        }
        $this->currentPage = min(max(1, $this->currentPage), $this->getTotalPages());
    }
    public function getTotalPages(): int
    {
        return max(1, (int) ceil($this->totalItems / $this->itemsPerPage));
    }
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }
    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }
    public function getHasNext(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }
    public function getHasPrevious(): bool
    {
        return $this->currentPage > 1;
    }
    public function getNextPage(): ?int
    {
        return $this->getHasNext() ? $this->currentPage + 1 : null;
    }
    public function getPreviousPage(): ?int
    {
        return $this->getHasPrevious() ? $this->currentPage - 1 : null;
    }
    public function getFirstPage(): ?int
    {
        return $this->getTotalPages() > 0 ? 1 : null;
    }
    public function getLastPage(): ?int
    {
        return $this->getTotalPages() > 0 ? $this->getTotalPages() : null;
    }
    public function getPageRange(): array
    {
        return $this->calculatePageRange();
    }
    public function withPage(int $page): self
    {
        return new self(totalItems: $this->totalItems, itemsPerPage: $this->itemsPerPage, currentPage: $page, maxLinks: $this->maxLinks);
    }
    public function withItemsPerPage(int $itemsPerPage): self
    {
        return new self(totalItems: $this->totalItems, itemsPerPage: $itemsPerPage, currentPage: $this->currentPage, maxLinks: $this->maxLinks);
    }
    /**
     * Creates paginated data with the provided items.
     *
     * @template T
     * @param array<T> $data
     * @return PaginatedData<T>
     */
    public function paginate(array $data): PaginatedData
    {
        return new PaginatedData(data: $data, currentPage: $this->currentPage, totalPages: $this->getTotalPages(), totalItems: $this->totalItems, itemsPerPage: $this->itemsPerPage, offset: $this->getOffset(), limit: $this->getLimit(), hasNext: $this->getHasNext(), hasPrevious: $this->getHasPrevious(), nextPage: $this->getNextPage(), previousPage: $this->getPreviousPage(), pageRange: $this->getPageRange());
    }
    /**
     * Creates paginated data from a callable that fetches data.
     *
     * @template T
     * @param callable(int $limit, int $offset): array<T> $callback
     * @return PaginatedData<T>
     */
    public function paginateWith(callable $callback): PaginatedData
    {
        return $this->paginate($callback($this->getLimit(), $this->getOffset()));
    }
    /** @return list<int> */
    private function calculatePageRange(): array
    {
        if ($this->getTotalPages() <= $this->maxLinks) {
            return range(1, $this->getTotalPages());
        }
        $half = (int) floor($this->maxLinks / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->getTotalPages(), $start + $this->maxLinks - 1);
        if ($end - $start + 1 < $this->maxLinks) {
            $start = max(1, $end - $this->maxLinks + 1);
        }
        return range($start, $end);
    }
    public function __get(string $name): mixed
    {
        if ($name === 'totalPages') {
            return $this->getTotalPages();
        }
        if ($name === 'offset') {
            return $this->getOffset();
        }
        if ($name === 'limit') {
            return $this->getLimit();
        }
        if ($name === 'hasNext') {
            return $this->getHasNext();
        }
        if ($name === 'hasPrevious') {
            return $this->getHasPrevious();
        }
        if ($name === 'nextPage') {
            return $this->getNextPage();
        }
        if ($name === 'previousPage') {
            return $this->getPreviousPage();
        }
        if ($name === 'firstPage') {
            return $this->getFirstPage();
        }
        if ($name === 'lastPage') {
            return $this->getLastPage();
        }
        if ($name === 'pageRange') {
            return $this->getPageRange();
        }
        throw new \RuntimeException(sprintf('Undefined property: %s::$%s', self::class, $name));
    }
    public function __isset(string $name): bool
    {
        if ($name === 'totalPages') {
            return $this->getTotalPages() !== null;
        }
        if ($name === 'offset') {
            return $this->getOffset() !== null;
        }
        if ($name === 'limit') {
            return $this->getLimit() !== null;
        }
        if ($name === 'hasNext') {
            return $this->getHasNext() !== null;
        }
        if ($name === 'hasPrevious') {
            return $this->getHasPrevious() !== null;
        }
        if ($name === 'nextPage') {
            return $this->getNextPage() !== null;
        }
        if ($name === 'previousPage') {
            return $this->getPreviousPage() !== null;
        }
        if ($name === 'firstPage') {
            return $this->getFirstPage() !== null;
        }
        if ($name === 'lastPage') {
            return $this->getLastPage() !== null;
        }
        if ($name === 'pageRange') {
            return $this->getPageRange() !== null;
        }
        return \false;
    }
}
