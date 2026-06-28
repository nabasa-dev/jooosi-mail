<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\Bridge\AhaSend\Transport;

use JooosiMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
/**
 * @author Farhad Hedayatifard <farhad@ahasend.com>
 */
final class AhaSendTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $transport = null;
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);
        if ('ahasend+api' === $scheme) {
            $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
            $port = $dsn->getPort();
            $transport = (new AhaSendApiTransport($user, $this->client, $this->dispatcher, $this->logger))->setHost($host)->setPort($port);
        }
        if ('ahasend+smtp' === $scheme || 'ahasend' === $scheme) {
            $password = $this->getPassword($dsn);
            $transport = new AhaSendSmtpTransport($user, $password, $this->dispatcher, $this->logger);
        }
        if (null === $transport) {
            throw new UnsupportedSchemeException($dsn, 'ahasend', $this->getSupportedSchemes());
        }
        return $transport;
    }
    protected function getSupportedSchemes(): array
    {
        return ['ahasend', 'ahasend+api', 'ahasend+smtp'];
    }
}
