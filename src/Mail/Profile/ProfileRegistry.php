<?php

declare (strict_types=1);
namespace OmniMail\Mail\Profile;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Runtime\DiscoveryManifest;
use OmniMailDeps\Psr\Container\ContainerInterface;
/**
 * Runtime registry for discovered mail profiles.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class ProfileRegistry
{
    public function __construct(private DiscoveryManifest $manifest, private ContainerInterface $container, private \OmniMail\Mail\Profile\ProfileMetadataResolver $profileMetadataResolver)
    {
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
            if ($profile instanceof \OmniMail\Mail\Profile\MailProfileInterface) {
                $profiles[] = $profile;
            }
        }
        return $profiles;
    }
    /**
     * @since 0.1.0
     */
    public function get(string $key): ?\OmniMail\Mail\Profile\MailProfileInterface
    {
        return array_find($this->all(), fn(\OmniMail\Mail\Profile\MailProfileInterface $profile): bool => $this->profileMetadataResolver->getKey($profile) === $key);
    }
}
