<?php

declare (strict_types=1);
namespace OmniMail\Discovery\Discovery;

use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMail\Discovery\Runtime\DiscoveryState;
use Override;
use OmniMailDeps\Tempest\Discovery\Discovery;
use OmniMailDeps\Tempest\Discovery\DiscoveryLocation;
use OmniMailDeps\Tempest\Discovery\IsDiscovery;
use OmniMailDeps\Tempest\Reflection\ClassReflector;
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
