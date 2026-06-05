<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Bridge\Sweego\Transport;

use OmniMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use OmniMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
/**
 * @author Mathieu Santostefano <msantostefano@proton.me>
 */
final class SweegoTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        return match ($dsn->getScheme()) {
            'sweego', 'sweego+smtp' => new SweegoSmtpTransport($dsn->getHost(), $dsn->getPort(), $this->getUser($dsn), $this->getPassword($dsn), $this->dispatcher, $this->logger),
            'sweego+api' => (new SweegoApiTransport($this->getUser($dsn), $this->client, $this->dispatcher, $this->logger))->setHost('default' === $dsn->getHost() ? null : $dsn->getHost())->setPort($dsn->getPort()),
            default => throw new UnsupportedSchemeException($dsn, 'sweego', $this->getSupportedSchemes()),
        };
    }
    protected function getSupportedSchemes(): array
    {
        return ['sweego', 'sweego+smtp', 'sweego+api'];
    }
}
