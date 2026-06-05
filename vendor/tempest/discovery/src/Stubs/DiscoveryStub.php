<?php

declare (strict_types=1);
namespace OmniMailDeps\Tempest\Discovery\Stubs;

use OmniMailDeps\Tempest\Discovery\Discovery;
use OmniMailDeps\Tempest\Discovery\DiscoveryLocation;
use OmniMailDeps\Tempest\Discovery\IsDiscovery;
use OmniMailDeps\Tempest\Discovery\SkipDiscovery;
use OmniMailDeps\Tempest\Reflection\ClassReflector;
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
