<?php

declare(strict_types=1);

namespace OmniMail\Mail\Profile\Builtin;

use OmniMail\Discovery\Attribute\MailProfile;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Profile\AbstractMailProfile;
use Override;

/**
 * Mailjet transport profile.
 *
 * @since 0.1.0
 */
#[Service]
#[MailProfile(
    key: 'mailjet',
    label: 'Mailjet',
    description: 'Send mail through Mailjet using the Symfony bridge API or SMTP transport.',
    website: 'https://www.mailjet.com',
    docsUrl: 'https://dev.mailjet.com',
    useCases: ['transactional', 'marketing'],
)]
final class MailjetProfile extends AbstractMailProfile
{
    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getSupportedSchemes(): array
    {
        return ['mailjet+api', 'mailjet+smtp'];
    }

    #[Override]
    public function supportsWebhooks(): bool
    {
        return true;
    }

    #[Override]
    public function getWebhookEvents(): array
    {
        return ['bounce', 'sent', 'blocked', 'click', 'open', 'spam', 'unsub'];
    }

    /**
     * @return array<string, mixed>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getConfigurationFields(): array
    {
        return [
            'scheme' => ['label' => 'Transport scheme', 'type' => 'choice', 'required' => false, 'default' => 'mailjet+api', 'choices' => $this->getSupportedSchemes()],
            'access_key' => ['label' => 'Mailjet access key', 'type' => 'password', 'required' => true],
            'secret_key' => ['label' => 'Mailjet secret key', 'type' => 'password', 'required' => true],
            'sandbox' => ['label' => 'Mailjet sandbox mode', 'type' => 'choice', 'required' => false, 'default' => 'false', 'choices' => ['false', 'true'], 'visible_when' => [$this->conditionIn('scheme', 'mailjet+api')]],
        ];
    }

    #[Override]
    public function validateConfiguration(Connection $connection): void
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'mailjet+api';

        match ($scheme) {
            'mailjet+api', 'mailjet+smtp' => $this->assertRequiredConfigurationValues($defaults, $this->profileKey(), $scheme, ['access_key', 'secret_key']),
            default => null,
        };
    }

    #[Override]
    public function buildDsn(Connection $connection): ?string
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'mailjet+api';

        if (! in_array($scheme, $this->getSupportedSchemes(), true)) {
            return null;
        }

        $accessKey = is_string($defaults['access_key'] ?? null) ? (string) $defaults['access_key'] : null;
        $secretKey = is_string($defaults['secret_key'] ?? null) ? (string) $defaults['secret_key'] : null;

        if ($accessKey === null || $accessKey === '' || $secretKey === null || $secretKey === '') {
            return null;
        }

        $dsn = $scheme . '://' . rawurlencode($accessKey) . ':' . rawurlencode($secretKey) . '@default';

        if ($scheme !== 'mailjet+api') {
            return $dsn;
        }

        $sandbox = filter_var($defaults['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $query = $sandbox === true ? 'sandbox=true' : '';

        return $query === '' ? $dsn : $dsn . '?' . $query;
    }
}
