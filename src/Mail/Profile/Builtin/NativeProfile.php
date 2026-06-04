<?php

declare(strict_types=1);

namespace OmniMail\Mail\Profile\Builtin;

use OmniMail\Discovery\Attribute\MailProfile;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Profile\AbstractMailProfile;
use Override;

/**
 * PHP native transport profile.
 *
 * @since 0.1.0
 */
#[Service]
#[MailProfile(
    key: 'native',
    label: 'Native PHP',
    description: 'Use the PHP native mail configuration.',
    docsUrl: 'https://symfony.com/doc/current/mailer.html',
    useCases: ['transactional'],
)]
final class NativeProfile extends AbstractMailProfile
{
    #[Override]
    public function getSupportedSchemes(): array
    {
        return ['native'];
    }

    #[Override]
    public function getConfigurationFields(): array
    {
        return [];
    }

    /**
     * @since 0.1.0
     */
    #[Override]
    public function buildDsn(Connection $connection): ?string
    {
        return 'native://default';
    }
}
