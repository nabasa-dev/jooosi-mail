<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Discovery;

use JooosiMailDeps\Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use JooosiMailDeps\Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use JooosiMailDeps\Tempest\Support\Filesystem;
use Throwable;
use function JooosiMailDeps\Tempest\internal_storage_path;
final class DiscoveryCache
{
    public function getEnabled(): bool
    {
        return $this->getValid() && $this->strategy->isEnabled();
    }
    public function getValid(): bool
    {
        return $this->strategy->isValid();
    }
    public function __construct(public readonly DiscoveryCacheStrategy $strategy, private ?CacheItemPoolInterface $pool = null)
    {
        $this->pool = $pool ?? new PhpFilesAdapter(directory: $this->getCachePath());
    }
    public function withStrategy(DiscoveryCacheStrategy $strategy): self
    {
        return new self(strategy: $strategy, pool: $this->pool);
    }
    /**
     * @return array<class-string<\Tempest\Discovery\Discovery>, DiscoveryItems>
     */
    public function restore(DiscoveryLocation $location): ?array
    {
        if (!$this->getEnabled()) {
            return null;
        }
        return $this->pool->getItem($location->key)->get();
    }
    /**
     * @param Discovery[] $discoveries
     */
    public function store(DiscoveryLocation $location, array $discoveries): void
    {
        $cachedForLocation = [];
        foreach ($discoveries as $discovery) {
            $items = $discovery->getItems();
            if ($this->strategy === DiscoveryCacheStrategy::PARTIAL) {
                $items = $items->onlyVendor();
            }
            $cachedForLocation[$discovery::class] = $items->getForLocation($location);
        }
        $item = $this->pool->getItem($location->key)->set($cachedForLocation);
        $saved = $this->pool->save($item);
        if (!$saved) {
            throw new CouldNotStoreDiscoveryCache($location);
        }
    }
    public function clear(): void
    {
        if (!$this->pool->clear()) {
            throw new RuntimeException('Could not clear discovery cache.');
        }
        $this->storeStrategy(DiscoveryCacheStrategy::INVALID);
    }
    public function storeStrategy(DiscoveryCacheStrategy $strategy): void
    {
        $path = self::getCurrentDiscoverStrategyCachePath();
        Filesystem\create_directory_for_file($path);
        Filesystem\write_file($path, $strategy->value);
    }
    public static function getCurrentDiscoverStrategyCachePath(): string
    {
        try {
            return internal_storage_path('current_discovery_strategy');
        } catch (Throwable) {
            return __DIR__ . '/current_discovery_strategy';
        }
    }
    private function getCachePath(): string
    {
        try {
            return internal_storage_path('cache/discovery');
        } catch (Throwable) {
            return __DIR__ . '/../.tempest/cache';
        }
    }
    public function __get(string $name): mixed
    {
        if ($name === 'enabled') {
            return $this->getEnabled();
        }
        if ($name === 'valid') {
            return $this->getValid();
        }
        throw new \RuntimeException(sprintf('Undefined property: %s::$%s', self::class, $name));
    }
    public function __isset(string $name): bool
    {
        if ($name === 'enabled') {
            return $this->getEnabled() !== null;
        }
        if ($name === 'valid') {
            return $this->getValid() !== null;
        }
        return \false;
    }
}
