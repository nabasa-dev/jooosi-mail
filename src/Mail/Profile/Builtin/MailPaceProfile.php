<?php

declare (strict_types=1);
namespace OmniMail\Mail\Profile\Builtin;

use OmniMail\Discovery\Attribute\MailProfile;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Profile\AbstractMailProfile;
use Override;
/**
 * MailPace transport profile.
 *
 * @since 0.1.0
 */
#[Service]
#[MailProfile(key: 'mailpace', label: 'MailPace', description: 'Send mail through MailPace using the Symfony bridge API or SMTP transport.', website: 'https://mailpace.com', docsUrl: 'https://docs.mailpace.com', useCases: ['transactional'])]
final class MailPaceProfile extends AbstractMailProfile
{
    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getSupportedSchemes(): array
    {
        return ['mailpace+api', 'mailpace+smtp'];
    }
    /**
     * @return array<string, mixed>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getConfigurationFields(): array
    {
        return ['scheme' => ['label' => 'Transport scheme', 'type' => 'choice', 'required' => \false, 'default' => 'mailpace+api', 'choices' => $this->getSupportedSchemes()], 'api_token' => ['label' => 'MailPace API token', 'type' => 'password', 'required' => \true]];
    }
    #[Override]
    public function validateConfiguration(Connection $connection): void
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'mailpace+api';
        match ($scheme) {
            'mailpace+api', 'mailpace+smtp' => $this->assertRequiredConfigurationValues($defaults, $this->profileKey(), $scheme, ['api_token']),
            default => null,
        };
    }
    #[Override]
    public function buildDsn(Connection $connection): ?string
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'mailpace+api';
        $apiToken = is_string($defaults['api_token'] ?? null) ? trim((string) $defaults['api_token']) : null;
        if (!in_array($scheme, $this->getSupportedSchemes(), \true) || $apiToken === null || $apiToken === '') {
            return null;
        }
        return $scheme . '://' . rawurlencode($apiToken) . '@default';
    }
}
