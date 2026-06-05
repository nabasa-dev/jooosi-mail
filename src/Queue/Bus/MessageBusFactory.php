<?php

declare (strict_types=1);
namespace OmniMail\Queue\Bus;

use OmniMail\Discovery\Attribute\Service;
use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Symfony\Component\Messenger\MessageBus;
use OmniMailDeps\Symfony\Component\Messenger\MessageBusInterface;
use OmniMailDeps\Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use OmniMailDeps\Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
/**
 * Builds the Omni Mail Messenger bus.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MessageBusFactory
{
    public function __construct(private \OmniMail\Queue\Bus\MessageRouter $messageRouter, private \OmniMail\Queue\Bus\HandlerLocator $handlerLocator, private EventDispatcherInterface $eventDispatcher)
    {
    }
    /**
     * @since 0.1.0
     */
    public function create(): MessageBusInterface
    {
        return new MessageBus([new SendMessageMiddleware($this->messageRouter, $this->eventDispatcher, \true), new HandleMessageMiddleware($this->handlerLocator, \false)]);
    }
}
