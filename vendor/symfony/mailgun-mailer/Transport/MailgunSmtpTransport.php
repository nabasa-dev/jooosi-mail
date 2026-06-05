<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Bridge\Mailgun\Transport;

use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use OmniMailDeps\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
/**
 * @author Kevin Verschaeve
 */
class MailgunSmtpTransport extends EsmtpTransport
{
    use MailgunHeadersTrait;
    public function __construct(
        string $username,
        #[\SensitiveParameter]
        string $password,
        ?string $region = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    )
    {
        parent::__construct('us' !== ($region ?: 'us') ? \sprintf('smtp.%s.mailgun.org', $region) : 'smtp.mailgun.org', 587, \false, $dispatcher, $logger);
        $this->setUsername($username);
        $this->setPassword($password);
    }
}
