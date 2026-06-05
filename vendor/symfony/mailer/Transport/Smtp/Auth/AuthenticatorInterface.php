<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\Auth;

use OmniMailDeps\Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
/**
 * An Authentication mechanism.
 *
 * @author Chris Corbyn
 */
interface AuthenticatorInterface
{
    /**
     * Tries to authenticate the user.
     *
     * @throws TransportExceptionInterface
     */
    public function authenticate(EsmtpTransport $client): void;
    /**
     * Gets the name of the AUTH mechanism this Authenticator handles.
     */
    public function getAuthKeyword(): string;
}
