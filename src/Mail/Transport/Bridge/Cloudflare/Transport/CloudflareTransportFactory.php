<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport\Bridge\Cloudflare\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
/**
 * Cloudflare Email Service custom transport factory.
 *
 * @since 0.1.0
 */
#[Service]
#[TransportFactory]
final class CloudflareTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();
        return match ($dsn->getScheme()) {
            'cloudflare+api' => (new \OmniMail\Mail\Transport\Bridge\Cloudflare\Transport\CloudflareApiTransport($this->getUser($dsn), $this->getPassword($dsn), $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port),
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            default => throw new UnsupportedSchemeException($dsn, 'cloudflare', $this->getSupportedSchemes()),
        };
    }
    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    protected function getSupportedSchemes(): array
    {
        return ['cloudflare+api'];
    }
}
