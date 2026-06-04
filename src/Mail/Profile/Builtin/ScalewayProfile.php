<?php

declare(strict_types=1);

namespace OmniMail\Mail\Profile\Builtin;

use OmniMail\Discovery\Attribute\MailProfile;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Connection\Connection;
use OmniMail\Mail\Profile\AbstractMailProfile;
use Override;

/**
 * Scaleway transport profile.
 *
 * @since 0.1.0
 */
#[Service]
#[MailProfile(
    key: 'scaleway',
    label: 'Scaleway',
    description: 'Send mail through Scaleway using the Symfony bridge API or SMTP transport.',
    website: 'https://www.scaleway.com/en/transactional-email-tem/',
    docsUrl: 'https://developers.scaleway.com/en/products/transactional_email/api/',
    useCases: ['transactional'],
)]
final class ScalewayProfile extends AbstractMailProfile
{
    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getSupportedSchemes(): array
    {
        return ['scaleway+api', 'scaleway+smtp'];
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
            'scheme' => ['label' => 'Transport scheme', 'type' => 'choice', 'required' => false, 'default' => 'scaleway+api', 'choices' => $this->getSupportedSchemes()],
            'project_id' => ['label' => 'Scaleway project ID', 'type' => 'text', 'required' => true],
            'api_key' => ['label' => 'Scaleway API key', 'type' => 'password', 'required' => true],
            'region' => ['label' => 'Scaleway region', 'type' => 'text', 'required' => false, 'visible_when' => [$this->conditionIn('scheme', 'scaleway+api')]],
        ];
    }

    #[Override]
    public function buildDsn(Connection $connection): ?string
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'scaleway+api';

        if (! in_array($scheme, $this->getSupportedSchemes(), true)) {
            return null;
        }

        $projectId = $this->extractScalarString($defaults, 'project_id');
        $apiKey = $this->extractScalarString($defaults, 'api_key');

        if ($projectId === null || $projectId === '' || $apiKey === null || $apiKey === '') {
            return null;
        }

        $dsn = $scheme . '://' . rawurlencode($projectId) . ':' . rawurlencode($apiKey) . '@default';

        if ($scheme !== 'scaleway+api') {
            return $dsn;
        }

        $query = $this->buildQueryString([
            'region' => $this->extractScalarString($defaults, 'region'),
        ]);

        return $query === '' ? $dsn : $dsn . '?' . $query;
    }
}
