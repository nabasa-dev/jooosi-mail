<?php

declare(strict_types=1);

namespace JooosiMail\Discovery\Discovery;

use JooosiMail\Discovery\Attribute\Controller;
use JooosiMail\Discovery\Runtime\DiscoveryState;
use Override;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

/**
 * Discovers REST controllers.
 *
 * @since 0.1.0
 */
final class ControllerDiscovery implements Discovery
{
    use IsDiscovery;

    /**
     * @since 0.1.0
     */
    #[Override]
    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if (! $class->isInstantiable()) {
            return;
        }

        if ($class->getAttribute(Controller::class) instanceof Controller) {
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
            DiscoveryState::addController($class);
        }
    }
}
