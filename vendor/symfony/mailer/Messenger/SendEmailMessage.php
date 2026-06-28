<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\Messenger;

use JooosiMailDeps\Symfony\Component\Mailer\Envelope;
use JooosiMailDeps\Symfony\Component\Mime\RawMessage;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SendEmailMessage
{
    public function __construct(private RawMessage $message, private ?Envelope $envelope = null)
    {
    }
    public function getMessage(): RawMessage
    {
        return $this->message;
    }
    public function getEnvelope(): ?Envelope
    {
        return $this->envelope;
    }
}
