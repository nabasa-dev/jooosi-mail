<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport\Bridge\ZohoMail\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Attribute\TransportFactory;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Zoho Mail custom transport factory.
 *
 * @link https://www.zoho.com/mail/help/zoho-smtp.html
 *
 * @since 0.1.0
 */
#[Service]
#[TransportFactory]
final class ZohoMailTransportFactory extends AbstractTransportFactory
{
    /**
     * @since 0.1.0
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $username = $this->getUser($dsn);
        $password = $this->getPassword($dsn);
        $port = $dsn->getPort();
        $accountType = $dsn->getOption('account_type');

        return match ($scheme) {
            'zohomail', 'zohomail+smtp' => new ZohoMailSmtpTransport(
                $username,
                $password,
                $accountType,
                $port,
                $this->dispatcher,
                $this->logger,
            ),
            'zohomail+smtps' => new ZohoMailSmtpTransport(
                $username,
                $password,
                $accountType,
                $port ?? 465,
                $this->dispatcher,
                $this->logger,
            ),
            default => 
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new UnsupportedSchemeException($dsn, 'zohomail', $this->getSupportedSchemes()),
        };
    }

    /**
     * @return list<string>
     *
     * @since 0.1.0
     */
    protected function getSupportedSchemes(): array
    {
        return ['zohomail', 'zohomail+smtp', 'zohomail+smtps'];
    }
}
