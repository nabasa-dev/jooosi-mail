<?php

declare(strict_types=1);

namespace JooosiMail\Mail\Profile;

use JooosiMail\Discovery\Attribute\Service;
use JooosiMail\Discovery\Runtime\DiscoveryManifest;
use Psr\Container\ContainerInterface;

/**
 * Runtime registry for discovered mail profiles.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class ProfileRegistry
{
    public function __construct(
        private DiscoveryManifest $manifest,
        private ContainerInterface $container,
        private ProfileMetadataResolver $profileMetadataResolver,
    ) {
    }

    /**
     * @return list<MailProfileInterface>
     *
     * @since 0.1.0
     */
    public function all(): array
    {
        $profiles = [];

        foreach ($this->manifest->profiles as $className) {
            $profile = $this->container->get($className);

            if ($profile instanceof MailProfileInterface) {
                $profiles[] = $profile;
            }
        }

        return $profiles;
    }

    /**
     * @since 0.1.0
     */
    public function get(string $key): ?MailProfileInterface
    {
        return array_find(
            $this->all(),
            fn (MailProfileInterface $profile): bool => $this->profileMetadataResolver->getKey($profile) === $key,
        );
    }
}
