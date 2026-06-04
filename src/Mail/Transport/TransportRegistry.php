<?php

declare(strict_types=1);

namespace OmniMail\Mail\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Runtime\DiscoveryManifest;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Builds Symfony mail transports, including discovered custom factories.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class TransportRegistry
{
    public function __construct(
        private DiscoveryManifest $manifest,
        private ContainerInterface $container,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private ?HttpClientInterface $httpClient = null,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @since 0.1.0
     */
    public function create(string $dsn): TransportInterface
    {
        return (new Transport($this->getFactories()))->fromString($dsn);
    }

    /**
     * @return list<TransportFactoryInterface>
     *
     * @since 0.1.0
     */
    private function getFactories(): array
    {
        $factories = iterator_to_array(Transport::getDefaultFactories($this->eventDispatcher, $this->httpClient, $this->logger));

        foreach ($this->manifest->transportFactories as $className) {
            $factory = $this->container->get($className);

            if ($factory instanceof TransportFactoryInterface) {
                $factories[] = $factory;
            }
        }

        return $factories;
    }
}
