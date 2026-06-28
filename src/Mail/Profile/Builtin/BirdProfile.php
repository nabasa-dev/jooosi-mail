<?php

declare (strict_types=1);
namespace JooosiMail\Mail\Profile\Builtin;

use JooosiMail\Discovery\Attribute\MailProfile;
use JooosiMail\Discovery\Attribute\Service;
use JooosiMail\Mail\Connection\Connection;
use JooosiMail\Mail\Profile\AbstractMailProfile;
use Override;
/**
 * Bird transport profile.
 *
 * @link https://docs.bird.com/api/email-api/transmissions
 * @link https://docs.bird.com/api/email-api/smtp-api
 *
 * @since 0.1.0
 */
#[Service]
#[MailProfile(key: 'bird', label: 'Bird', description: 'Send mail through Bird using custom API or SMTP transports.', website: 'https://bird.com', docsUrl: 'https://docs.bird.com/api/email-api/transmissions', useCases: ['transactional', 'marketing'])]
final class BirdProfile extends AbstractMailProfile
{
    #[Override]
    public function supportsWebhooks(): bool
    {
        return \true;
    }
    #[Override]
    public function getWebhookEvents(): array
    {
        return ['injection', 'delivery', 'delay', 'bounce', 'spam_complaint', 'out_of_band', 'policy_rejection', 'generation_failure', 'generation_rejection', 'open', 'initial_open', 'click', 'initial_click', 'amp_click', 'amp_open'];
    }
    /**
     * @return list<string>
     */
    #[Override]
    public function getSupportedSchemes(): array
    {
        return ['bird+api', 'bird+smtp', 'bird+smtps'];
    }
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getConfigurationFields(): array
    {
        return ['scheme' => ['label' => 'Transport scheme', 'type' => 'choice', 'required' => \false, 'default' => 'bird+api', 'choices' => $this->getSupportedSchemes()], 'access_key' => ['label' => 'Bird access key', 'type' => 'password', 'required' => \true], 'workspace_id' => ['label' => 'Bird workspace ID', 'type' => 'text', 'required' => \true], 'region' => ['label' => 'Bird region', 'type' => 'choice', 'required' => \false, 'default' => 'eu', 'choices' => ['eu', 'us']]];
    }
    #[Override]
    public function buildDsn(Connection $connection): ?string
    {
        $defaults = $this->getConfigurationDefaults($connection);
        $scheme = $this->extractScalarString($defaults, 'scheme') ?? 'bird+api';
        $accessKey = $this->extractScalarString($defaults, 'access_key');
        $workspaceId = $this->extractScalarString($defaults, 'workspace_id');
        if (!in_array($scheme, $this->getSupportedSchemes(), \true) || $accessKey === null || $accessKey === '' || $workspaceId === null || $workspaceId === '') {
            return null;
        }
        $query = $this->buildQueryString(['workspace_id' => $workspaceId, 'region' => $this->extractScalarString($defaults, 'region')]);
        $dsn = $scheme . '://' . rawurlencode($accessKey) . '@default';
        return $query === '' ? $dsn : $dsn . '?' . $query;
    }
}
