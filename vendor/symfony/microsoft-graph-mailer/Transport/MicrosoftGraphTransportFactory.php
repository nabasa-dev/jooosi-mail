<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport;

use JooosiMailDeps\Symfony\Component\Mailer\Bridge\MicrosoftGraph\TokenManager;
use JooosiMailDeps\Symfony\Component\Mailer\Exception\IncompleteDsnException;
use JooosiMailDeps\Symfony\Component\Mailer\Exception\InvalidArgumentException;
use JooosiMailDeps\Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\Dsn;
use JooosiMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
class MicrosoftGraphTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if ('microsoftgraph+api' !== $dsn->getScheme()) {
            throw new UnsupportedSchemeException($dsn, 'microsoft graph api', $this->getSupportedSchemes());
        }
        if (null === $tenantId = $dsn->getOption('tenantId')) {
            throw new IncompleteDsnException('Transport "microsoftgraph+api" requires the "tenant" option.');
        }
        $graphEndpoint = $dsn->getHost();
        $authEndpoint = $dsn->getOption('authEndpoint');
        if ('default' === $graphEndpoint) {
            $graphEndpoint = 'graph.microsoft.com';
            if (null === $authEndpoint) {
                $authEndpoint = 'login.microsoftonline.com';
            }
        }
        if (null === $authEndpoint) {
            throw new IncompleteDsnException('Transport "microsoftgraph+api" requires the "authEndpoint" option when not using the default graph endpoint.');
        }
        if (preg_match('#^https?://#', $authEndpoint)) {
            throw new InvalidArgumentException('Auth endpoint needs to be provided without "http(s)://".');
        }
        if (preg_match('#^https?://#', $graphEndpoint)) {
            throw new InvalidArgumentException('Graph endpoint needs to be provided without "http(s)://".');
        }
        $tokenManager = new TokenManager($graphEndpoint, $authEndpoint, $tenantId, $this->getUser($dsn), $this->getPassword($dsn), $this->client);
        return new MicrosoftGraphApiTransport($graphEndpoint, $tokenManager, $dsn->getBooleanOption('noSave'), $this->client, $this->dispatcher, $this->logger);
    }
    protected function getSupportedSchemes(): array
    {
        return ['microsoftgraph+api'];
    }
}
