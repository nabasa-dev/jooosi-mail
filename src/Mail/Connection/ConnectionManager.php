<?php

declare (strict_types=1);
namespace OmniMail\Mail\Connection;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\Profile\MailProfileInterface;
use OmniMail\Mail\Profile\ProfileMetadataResolver;
use OmniMail\Mail\Profile\ProfileRegistry;
/**
 * Coordinates mail connection configuration persistence.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class ConnectionManager
{
    public function __construct(private \OmniMail\Mail\Connection\ConnectionRepository $connectionRepository, private ProfileRegistry $profileRegistry, private ProfileMetadataResolver $profileMetadataResolver, private \OmniMail\Mail\Connection\ConnectionInputResolver $connectionInputResolver, private \OmniMail\Mail\Connection\ConnectionConfigurationValidator $connectionConfigurationValidator)
    {
    }
    /**
     * @return list<array<string, mixed>>
     *
     * @since 0.1.0
     */
    public function listProfiles(): array
    {
        return array_map(function (MailProfileInterface $profile): array {
            $payload = ['key' => $this->profileMetadataResolver->getKey($profile), 'label' => $this->profileMetadataResolver->getLabel($profile), 'description' => $this->profileMetadataResolver->getDescription($profile), 'schemes' => $profile->getSupportedSchemes(), 'supports_webhooks' => $profile->supportsWebhooks(), 'configuration_fields' => $profile->getConfigurationFields()];
            $metadata = $this->profileMetadataResolver->resolve($profile);
            if ($metadata !== []) {
                $payload['metadata'] = $metadata;
            }
            return $payload;
        }, $this->profileRegistry->all());
    }
    /**
     * @param array<string, mixed> $input
     *
     * @since 0.1.0
     */
    public function create(array $input): \OmniMail\Mail\Connection\Connection
    {
        $profile = $this->resolveProfile($input['profile'] ?? null);
        $connection = $this->resolveConnection(null, $profile, $input);
        $connectionId = $this->persist($connection);
        return $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException('The created connection could not be reloaded.');
    }
    /**
     * @param array<string, mixed> $input
     *
     * @since 0.1.0
     */
    public function update(int $connectionId, array $input): \OmniMail\Mail\Connection\Connection
    {
        $existingConnection = $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d was not found.', $connectionId));
        $profileKey = $input['profile'] ?? $existingConnection->profileKey;
        $profile = $this->resolveProfile($profileKey);
        $connection = $this->resolveConnection($existingConnection, $profile, $input);
        $this->persist($connection);
        return $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d could not be reloaded.', $connectionId));
    }
    /**
     * @since 0.1.0
     */
    public function setDefault(int $connectionId): \OmniMail\Mail\Connection\Connection
    {
        $connection = $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d was not found.', $connectionId));
        $updatedConnection = new \OmniMail\Mail\Connection\Connection(id: $connection->id, profileKey: $connection->profileKey, name: $connection->name, dsn: $connection->dsn, settings: $connection->settings, secrets: $connection->secrets, enabled: $connection->enabled, default: \true, priority: $connection->priority, weight: $connection->weight, webhookEnabled: $connection->webhookEnabled);
        $this->persist($updatedConnection);
        return $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d could not be reloaded.', $connectionId));
    }
    /**
     * @since 0.1.0
     */
    public function setEnabled(int $connectionId, bool $enabled): \OmniMail\Mail\Connection\Connection
    {
        $connection = $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d was not found.', $connectionId));
        $updatedConnection = new \OmniMail\Mail\Connection\Connection(id: $connection->id, profileKey: $connection->profileKey, name: $connection->name, dsn: $connection->dsn, settings: $connection->settings, secrets: $connection->secrets, enabled: $enabled, default: $enabled ? $connection->default : \false, priority: $connection->priority, weight: $connection->weight, webhookEnabled: $connection->webhookEnabled);
        $this->persist($updatedConnection);
        return $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d could not be reloaded.', $connectionId));
    }
    /**
     * @since 0.1.0
     */
    public function delete(int $connectionId): void
    {
        $connection = $this->connectionRepository->find($connectionId) ?? throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Connection %d was not found.', $connectionId));
        $this->connectionRepository->delete($connectionId);
        $this->assignFallbackDefaultIfNeeded();
    }
    /**
     * @param array<string, mixed> $input
     *
     * @since 0.1.0
     */
    private function resolveConnection(?\OmniMail\Mail\Connection\Connection $existingConnection, MailProfileInterface $profile, array $input): \OmniMail\Mail\Connection\Connection
    {
        $connection = $this->connectionInputResolver->resolve($existingConnection, $profile, $input);
        $this->connectionConfigurationValidator->validate($profile, $connection);
        return $connection;
    }
    /**
     * @since 0.1.0
     */
    private function persist(\OmniMail\Mail\Connection\Connection $connection): int
    {
        if ($connection->default) {
            $this->connectionRepository->clearDefault($connection->id);
        }
        $connectionId = $this->connectionRepository->save($connection);
        $this->assignFallbackDefaultIfNeeded();
        return $connectionId;
    }
    /**
     * @since 0.1.0
     */
    private function assignFallbackDefaultIfNeeded(): void
    {
        if ($this->connectionRepository->findDefault() instanceof \OmniMail\Mail\Connection\Connection) {
            return;
        }
        $activeConnections = $this->connectionRepository->findActive();
        $nextDefault = array_first($activeConnections);
        if (!$nextDefault instanceof \OmniMail\Mail\Connection\Connection || $nextDefault->id === null) {
            return;
        }
        $this->connectionRepository->clearDefault($nextDefault->id);
        $this->connectionRepository->save(new \OmniMail\Mail\Connection\Connection(id: $nextDefault->id, profileKey: $nextDefault->profileKey, name: $nextDefault->name, dsn: $nextDefault->dsn, settings: $nextDefault->settings, secrets: $nextDefault->secrets, enabled: $nextDefault->enabled, default: \true, priority: $nextDefault->priority, weight: $nextDefault->weight, webhookEnabled: $nextDefault->webhookEnabled));
    }
    /**
     * @since 0.1.0
     */
    private function resolveProfile(mixed $profileKey): MailProfileInterface
    {
        if (!is_scalar($profileKey) || (string) $profileKey === '') {
            throw new \OmniMail\Mail\Connection\ConnectionConfigurationException('A connection profile key is required.');
        }
        $profile = $this->profileRegistry->get((string) $profileKey);
        if (!$profile instanceof MailProfileInterface) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new \OmniMail\Mail\Connection\ConnectionConfigurationException(sprintf('Profile "%s" is not registered.', (string) $profileKey));
        }
        return $profile;
    }
}
