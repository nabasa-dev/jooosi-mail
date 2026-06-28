<?php

declare (strict_types=1);
namespace JooosiMail\Discovery\Discovery;

use JooosiMail\Discovery\Attribute\TransportFactory;
use JooosiMail\Discovery\Runtime\DiscoveryState;
use Override;
use JooosiMailDeps\Tempest\Discovery\Discovery;
use JooosiMailDeps\Tempest\Discovery\DiscoveryLocation;
use JooosiMailDeps\Tempest\Discovery\IsDiscovery;
use JooosiMailDeps\Tempest\Reflection\ClassReflector;
/**
 * Discovers custom mail transport factories.
 *
 * @since 0.1.0
 */
final class TransportFactoryDiscovery implements Discovery
{
    use IsDiscovery;
    /**
     * @since 0.1.0
     */
    #[Override]
    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if (!$class->isInstantiable()) {
            return;
        }
        if ($class->getAttribute(TransportFactory::class) instanceof TransportFactory) {
            $this->getItems()->add($location, $class->getName());
        }
    }
    /**
     * @since 0.1.0
     */
    #[Override]
    public function apply(): void
    {
        foreach ($this->getItems() as $class) {
            DiscoveryState::addTransportFactory($class);
        }
    }
}
