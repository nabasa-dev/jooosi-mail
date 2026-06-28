<?php

declare(strict_types=1);

namespace JooosiMail\Mail\Connection;

/**
 * Persisted mail connection settings.
 *
 * The optional `dsn` property stores a raw transport override. Canonical profile
 * configuration lives in `settings` and `secrets`, and profiles rebuild the
 * effective DSN lazily when delivery starts.
 *
 * @since 0.1.0
 */
final readonly class Connection
{
    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $secrets
     */
    public function __construct(
        public ?int $id,
        public string $profileKey,
        public string $name,
        public ?string $dsn = null,
        public array $settings = [],
        public array $secrets = [],
        public bool $enabled = true,
        public bool $default = false,
        public int $priority = 10,
        public int $weight = 1,
        public bool $webhookEnabled = false,
    ) {
    }

    /**
     * @since 0.1.0
     */
    public function hasDsnOverride(): bool
    {
        return $this->dsn !== null && $this->dsn !== '';
    }

    /**
     * @return array<string, mixed>
     *
     * @since 0.1.0
     */
    public function getProfileSettings(): array
    {
        $settings = $this->settings['profile'] ?? null;

        return is_array($settings) ? $settings : [];
    }

    /**
     * @return array<string, mixed>
     *
     * @since 0.1.0
     */
    public function getProfileSecrets(): array
    {
        $secrets = $this->secrets['profile'] ?? null;

        return is_array($secrets) ? $secrets : [];
    }

    /**
     * @since 0.1.0
     */
    public function getWebhookSecret(): ?string
    {
        $secret = $this->secrets['webhook_secret'] ?? null;

        if (! is_string($secret)) {
            return null;
        }

        $secret = trim($secret);

        return $secret !== '' ? $secret : null;
    }

    /**
     * @since 0.1.0
     */
    public function hasWebhookSecret(): bool
    {
        return $this->getWebhookSecret() !== null;
    }
}
