<?php

declare(strict_types=1);

namespace OmniMail\Queue\Bus;

use OmniMail\Discovery\Attribute\MessageHandler;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Runtime\DiscoveryManifest;
use Override;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;

/**
 * Resolves handler services for Messenger messages.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class HandlerLocator implements HandlersLocatorInterface
{
    public function __construct(
        private DiscoveryManifest $manifest,
        private ContainerInterface $container,
    ) {
    }

    /**
     * @return iterable<int, HandlerDescriptor>
     *
     * @since 0.1.0
     */
    #[Override]
    public function getHandlers(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();

        foreach ($this->manifest->messageHandlers as $className) {
            $reflectionClass = new ReflectionClass($className);
            $attribute = array_first($reflectionClass->getAttributes(MessageHandler::class));

            if ($attribute === null) {
                continue;
            }

            /** @var MessageHandler $handler */
            $handler = $attribute->newInstance();

            if (! is_a($message, $handler->messageClass)) {
                continue;
            }

            yield new HandlerDescriptor($this->container->get($className));
        }
    }
}
