<?php

namespace JooosiMail\Tests\Rector\Rule\DowngradePropertyHookRector\Fixture;

final class Cache
{
    public bool $enabled {
        get => $this->valid && $this->strategy->isEnabled();
    }

    public bool $valid {
        get => $this->strategy->isValid();
    }

    public function __construct(private Strategy $strategy)
    {
    }

    public function restore(): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        return [];
    }
}

final class Strategy
{
    public function isEnabled(): bool
    {
        return true;
    }

    public function isValid(): bool
    {
        return true;
    }
}

interface PropertyAttribute
{
    public PropertyReflector $property { set; get; }
}

final class PropertyReflector
{
}

?>
-----
<?php

namespace JooosiMail\Tests\Rector\Rule\DowngradePropertyHookRector\Fixture;

final class Cache
{
    public function getEnabled(): bool
    {
        return $this->getValid() && $this->strategy->isEnabled();
    }

    public function getValid(): bool
    {
        return $this->strategy->isValid();
    }

    public function __construct(private Strategy $strategy)
    {
    }

    public function restore(): ?array
    {
        if (! $this->getEnabled()) {
            return null;
        }

        return [];
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
        return false;
    }
}

final class Strategy
{
    public function isEnabled(): bool
    {
        return true;
    }

    public function isValid(): bool
    {
        return true;
    }
}

interface PropertyAttribute
{
}

final class PropertyReflector
{
}

?>
