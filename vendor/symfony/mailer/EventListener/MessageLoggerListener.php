<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\EventListener;

use OmniMailDeps\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use OmniMailDeps\Symfony\Component\Mailer\Event\MessageEvent;
use OmniMailDeps\Symfony\Component\Mailer\Event\MessageEvents;
use OmniMailDeps\Symfony\Contracts\Service\ResetInterface;
/**
 * Logs Messages.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MessageLoggerListener implements EventSubscriberInterface, ResetInterface
{
    private MessageEvents $events;
    public function __construct()
    {
        $this->events = new MessageEvents();
    }
    public function reset(): void
    {
        $this->events = new MessageEvents();
    }
    public function onMessage(MessageEvent $event): void
    {
        $this->events->add($event);
    }
    public function getEvents(): MessageEvents
    {
        return $this->events;
    }
    public static function getSubscribedEvents(): array
    {
        return [MessageEvent::class => ['onMessage', -255]];
    }
}
