<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Discovery;

use JooosiMailDeps\Tempest\Support\Filesystem;
use JooosiMailDeps\Tempest\Support\Namespace\Psr4Namespace;
final class DiscoveryLocation
{
    public readonly string $path;
    public function getKey(): string
    {
        return hash('xxh64', $this->path);
    }
    public function __construct(public readonly string $namespace, string $path, public array $ignore = [])
    {
        $this->path = Filesystem\normalize_path(rtrim($path, '\/'));
    }
    public static function fromNamespace(Psr4Namespace $namespace): self
    {
        return new self($namespace->namespace, $namespace->path);
    }
    public function isTempest(): bool
    {
        return str_starts_with($this->namespace, 'Tempest');
    }
    public function isVendor(): bool
    {
        return str_contains($this->path, '/vendor/') || str_contains($this->path, '\vendor\\') || $this->isTempest();
    }
    public function isIgnored(string $path): bool
    {
        $found = \false;
        foreach ($this->ignore as $ignore) {
            if (str_starts_with($path, $ignore)) {
                $found = \true;
                break;
            }
        }
        return $found;
    }
    public function toClassName(string $path): string
    {
        // Try to create a PSR-compliant class name from the path
        return str_replace([$this->path, '/', '\\\\', '.php'], [$this->namespace, '\\', '\\', ''], $path);
    }
    public function __get(string $name): mixed
    {
        if ($name === 'key') {
            return $this->getKey();
        }
        throw new \RuntimeException(sprintf('Undefined property: %s::$%s', self::class, $name));
    }
    public function __isset(string $name): bool
    {
        if ($name === 'key') {
            return $this->getKey() !== null;
        }
        return \false;
    }
}
