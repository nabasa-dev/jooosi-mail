<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Mail\Connection;

use JooosiMail\Mail\Connection\ConnectionDsnResolver;
use JooosiMail\Mail\Transport\TransportRegistry;
use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Covers built-in Symfony mailer profiles.
 *
 * @since 0.1.0
 */
final class BuiltinProfileTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testBuiltinProfilesAreListedWithExpectedSchemes(): void
    {
        $profiles = [];

        foreach ($this->connectionManager()->listProfiles() as $profile) {
            $profiles[(string) $profile['key']] = $profile;
        }

        self::assertSame('SMTP', $profiles['smtp']['label']);
        self::assertSame('Send mail through a remote SMTP server.', $profiles['smtp']['description']);
        self::assertSame(['smtp', 'smtps'], $profiles['smtp']['schemes']);
        self::assertFalse((bool) $profiles['smtp']['supports_webhooks']);
        self::assertSame('https://symfony.com/doc/current/mailer.html', $profiles['smtp']['metadata']['docsUrl']);
        self::assertSame(['transactional', 'marketing', 'self-hosted'], $profiles['smtp']['metadata']['useCases']);
        self::assertSame(['sendmail'], $profiles['sendmail']['schemes']);
        self::assertFalse((bool) $profiles['sendmail']['supports_webhooks']);
        self::assertSame(['native'], $profiles['native']['schemes']);
        self::assertFalse((bool) $profiles['native']['supports_webhooks']);
        self::assertSame(['null'], $profiles['null']['schemes']);
        self::assertSame('Null', $profiles['null']['label']);
        self::assertSame('Discard messages without delivering them.', $profiles['null']['description']);
        self::assertFalse((bool) $profiles['null']['supports_webhooks']);
        self::assertSame(['testing'], $profiles['null']['metadata']['useCases']);
    }

    /**
     * @since 0.1.0
     */
    public function testSmtpProfileBuildsResolvableTransportForSmtps(): void
    {
        $connection = $this->connectionManager()->create([
            'profile' => 'smtp',
            'name' => 'SMTP Relay',
            'scheme' => 'smtps',
            'host' => 'smtp.example.com',
            'port' => 465,
            'username' => 'mailer@example.com',
            'password' => 'smtp-password',
        ]);

        $dsn = $this->dsnResolver()->resolve($connection);

        self::assertSame('smtps://mailer%40example.com:smtp-password@smtp.example.com:465', $dsn);
        self::assertSame(EsmtpTransport::class, $this->transportRegistry()->create($dsn)::class);
    }

    /**
     * @since 0.1.0
     */
    public function testSendmailProfileBuildsResolvableTransportWithCustomCommand(): void
    {
        $connection = $this->connectionManager()->create([
            'profile' => 'sendmail',
            'name' => 'Local Sendmail',
            'command' => '/usr/sbin/sendmail -oi -t',
        ]);

        $dsn = $this->dsnResolver()->resolve($connection);

        self::assertSame('sendmail://default?command=%2Fusr%2Fsbin%2Fsendmail%20-oi%20-t', $dsn);
        self::assertSame(SendmailTransport::class, $this->transportRegistry()->create($dsn)::class);
    }

    /**
     * @since 0.1.0
     */
    public function testNativeAndNullProfilesBuildResolvableTransports(): void
    {
        $nativeConnection = $this->connectionManager()->create([
            'profile' => 'native',
            'name' => 'Native PHP Mail',
        ]);
        $nullConnection = $this->connectionManager()->create([
            'profile' => 'null',
            'name' => 'Null Sink',
        ]);

        $nativeDsn = $this->dsnResolver()->resolve($nativeConnection);
        $nullDsn = $this->dsnResolver()->resolve($nullConnection);

        self::assertSame('native://default', $nativeDsn);
        self::assertInstanceOf(TransportInterface::class, $this->transportRegistry()->create($nativeDsn));
        self::assertSame('null://null', $nullDsn);
        self::assertSame(NullTransport::class, $this->transportRegistry()->create($nullDsn)::class);
    }

    /**
     * @since 0.1.0
     */
    private function dsnResolver(): ConnectionDsnResolver
    {
        return $this->container()->get(ConnectionDsnResolver::class);
    }

    /**
     * @since 0.1.0
     */
    private function transportRegistry(): TransportRegistry
    {
        return $this->container()->get(TransportRegistry::class);
    }
}
