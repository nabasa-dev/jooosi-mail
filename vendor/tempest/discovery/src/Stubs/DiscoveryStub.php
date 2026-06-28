<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Discovery\Stubs;

use JooosiMailDeps\Tempest\Discovery\Discovery;
use JooosiMailDeps\Tempest\Discovery\DiscoveryLocation;
use JooosiMailDeps\Tempest\Discovery\IsDiscovery;
use JooosiMailDeps\Tempest\Discovery\SkipDiscovery;
use JooosiMailDeps\Tempest\Reflection\ClassReflector;
#[SkipDiscovery]
final class DiscoveryStub implements Discovery
{
    use IsDiscovery;
    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if (!$class->implements('MyClass::class')) {
            return;
        }
        $this->discoveryItems->add($location, $class);
    }
    /**
     * @mago-expect lint:no-empty-loop
     */
    public function apply(): void
    {
        foreach ($this->discoveryItems as $class) {
            // Do something with the discovered class
        }
    }
}
