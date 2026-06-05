<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Transport;

use OmniMailDeps\Symfony\Component\Mailer\Envelope;
use OmniMailDeps\Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use OmniMailDeps\Symfony\Component\Mailer\SentMessage;
use OmniMailDeps\Symfony\Component\Mime\RawMessage;
/**
 * Interface for all mailer transports.
 *
 * When sending emails, you should prefer MailerInterface implementations
 * as they allow asynchronous sending.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface TransportInterface extends \Stringable
{
    /**
     * @throws TransportExceptionInterface
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage;
}
