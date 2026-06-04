<?php

declare(strict_types=1);

namespace OmniMail\Mail\Profile\Builtin;

use OmniMail\Discovery\Attribute\MailProfile;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Profile\AbstractMailProfile;
use Override;

/**
 * Postmark transport profile.
 *
 * @since 0.1.0
 */
#[Service]
#[MailProfile(
    key: 'postmark',
    label: 'Postmark',
    description: 'Send mail through Postmark using the Symfony bridge API or SMTP transport.',
    website: 'https://postmarkapp.com',
    docsUrl: 'https://postmarkapp.com/developer',
    useCases: ['transactional'],
)]
final class PostmarkProfile extends AbstractMailProfile
{
    #[Override]
    public function getWebhookEvents(): array
    {
        return ['delivered', 'bounce', 'spam_complaint', 'open', 'click', 'subscription_change'];
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
            'scheme' => ['label' => 'Transport scheme', 'type' => 'choice', 'required' => false, 'default' => 'postmark+api', 'choices' => $this->getSupportedSchemes()],
            'api_key' => ['label' => 'Postmark server token', 'type' => 'password', 'required' => true],
        ];
    }

    #[Override]
    public function supportsWebhooks(): bool
    {
        return true;
    }

    #[Override]
    public function getSupportedSchemes(): array
    {
        return ['postmark+api', 'postmark+smtp'];
    }

    #[Override]
    public function buildDsn(Connection $connection): ?string
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'postmark+api';

        if (! in_array($scheme, $this->getSupportedSchemes(), true)) {
            return null;
        }

        $apiKey = $this->extractScalarString($defaults, 'api_key');

        if ($apiKey === null || $apiKey === '') {
            return null;
        }

        return $scheme . '://' . rawurlencode($apiKey) . '@default';
    }
}
