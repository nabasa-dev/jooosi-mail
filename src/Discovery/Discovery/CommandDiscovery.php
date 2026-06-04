<?php

declare(strict_types=1);

namespace OmniMail\Discovery\Discovery;

use OmniMail\Discovery\Attribute\Command;
use OmniMail\Discovery\Runtime\DiscoveryState;
use Override;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

/**
 * Discovers WP-CLI commands.
 *
 * @since 0.1.0
 */
final class CommandDiscovery implements Discovery
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

        if ($this->hasCommandAttribute($class)) {
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
            DiscoveryState::addCommand($class);
        }
    }

    /**
     * @since 0.1.0
     */
    private function hasCommandAttribute(ClassReflector $class): bool
    {
        if ($class->getAttribute(Command::class) instanceof Command) {
            return true;
        }

        foreach ($class->getPublicMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class->getName()) {
                continue;
            }

            if ($method->getAttribute(Command::class) instanceof Command) {
                return true;
            }
        }

        return false;
    }
}
