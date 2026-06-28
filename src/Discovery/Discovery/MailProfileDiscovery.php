<?php

declare(strict_types=1);

namespace JooosiMail\Discovery\Discovery;

use JooosiMail\Discovery\Attribute\MailProfile;
use JooosiMail\Discovery\Runtime\DiscoveryState;
use Override;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

/**
 * Discovers mail profiles.
 *
 * @since 0.1.0
 */
final class MailProfileDiscovery implements Discovery
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

        if ($class->getAttribute(MailProfile::class) instanceof MailProfile) {
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
            DiscoveryState::addProfile($class);
        }
    }
}
