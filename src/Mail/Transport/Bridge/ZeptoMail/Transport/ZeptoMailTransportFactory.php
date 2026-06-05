<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\ZeptoMail\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * ZeptoMail custom transport factory.
 *
 * @link https://www.zoho.com/zeptomail/help/api/email-sending.html
 * @link https://www.zoho.com/zeptomail/help/smtp-home.html
 *
 * @since 0.1.0
 */
#[Service]
#[TransportFactory]
final class ZeptoMailTransportFactory extends AbstractTransportFactory
{
    /**
     * @since 0.1.0
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $password = $dsn->getPassword();
        $host = $dsn->getHost() === 'default' ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return match ($scheme) {
            'zeptomail+api' => (new ZeptoMailApiTransport(
                $user,
                $this->client,
                $this->dispatcher,
                $this->logger,
            ))->setHost($host)->setPort($port),
            'zeptomail', 'zeptomail+smtp' => new ZeptoMailSmtpTransport(
                $password === null ? 'emailapikey' : $user,
                $password ?? $user,
                $port,
                $this->dispatcher,
                $this->logger,
            ),
            'zeptomail+smtps' => new ZeptoMailSmtpTransport(
                $password === null ? 'emailapikey' : $user,
                $password ?? $user,
                $port ?? 465,
                $this->dispatcher,
                $this->logger,
            ),
            default => 
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new UnsupportedSchemeException($dsn, 'zeptomail', $this->getSupportedSchemes()),
        };
    }

    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    protected function getSupportedSchemes(): array
    {
        return ['zeptomail', 'zeptomail+api', 'zeptomail+smtp', 'zeptomail+smtps'];
    }
}
