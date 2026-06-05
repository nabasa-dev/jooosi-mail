<?php

declare (strict_types=1);
namespace OmniMail\Mail\Transport;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Discovery\Runtime\DiscoveryManifest;
use OmniMailDeps\Psr\Container\ContainerInterface;
use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use OmniMailDeps\Symfony\Component\Mailer\Transport;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use OmniMailDeps\Symfony\Component\Mailer\Transport\TransportInterface;
use OmniMailDeps\Symfony\Contracts\HttpClient\HttpClientInterface;
/**
 * Builds Symfony mail transports, including discovered custom factories.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class TransportRegistry
{
    public function __construct(private DiscoveryManifest $manifest, private ContainerInterface $container, private ?EventDispatcherInterface $eventDispatcher = null, private ?HttpClientInterface $httpClient = null, private ?LoggerInterface $logger = null)
    {
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
