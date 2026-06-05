<?php

declare (strict_types=1);
namespace OmniMail\Infrastructure\Container;

use OmniMailDeps\Doctrine\DBAL\Connection;
use OmniMail\Bootstrap\Environment;
use OmniMail\Bootstrap\LifecycleManager;
use OmniMail\Bootstrap\Paths;
use OmniMail\Discovery\Discovery\CommandDiscovery;
use OmniMail\Discovery\Discovery\ControllerDiscovery;
use OmniMail\Discovery\Discovery\MailProfileDiscovery;
use OmniMail\Discovery\Discovery\MessageHandlerDiscovery;
use OmniMail\Discovery\Discovery\ServiceDiscovery;
use OmniMail\Discovery\Discovery\TransportFactoryDiscovery;
use OmniMail\Discovery\Runtime\DiscoveryManifest;
use OmniMail\Discovery\Runtime\DiscoveryState;
use OmniMail\Discovery\Runtime\NullDiscoveryContainer;
use OmniMail\Infrastructure\Database\DatabaseConnectionFactory;
use OmniMail\Infrastructure\Database\TableNameResolver;
use OmniMail\Infrastructure\Event\EventPublisherInterface;
use OmniMail\Infrastructure\Security\SecretCipher;
use OmniMail\Infrastructure\WordPress\CommandRegistrar;
use OmniMail\Infrastructure\WordPress\HookRegistrar;
use OmniMail\Infrastructure\WordPress\OptionStore;
use OmniMail\Infrastructure\WordPress\RestRouteRegistrar;
use OmniMail\Infrastructure\WordPress\WordPressEventPublisher;
use OmniMail\Mail\Routing\ConnectionHealthPenaltyProviderInterface;
use OmniMail\Queue\Bus\MessageBusFactory;
use OmniMail\Webhook\Event\WebhookHealthPenaltyProvider;
use OmniMailDeps\Psr\Container\ContainerInterface;
use OmniMailDeps\Psr\EventDispatcher\EventDispatcherInterface;
use OmniMailDeps\Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;
use OmniMailDeps\Symfony\Component\DependencyInjection\ContainerBuilder;
use OmniMailDeps\Symfony\Component\DependencyInjection\Definition;
use OmniMailDeps\Symfony\Component\DependencyInjection\Reference;
use OmniMailDeps\Symfony\Component\EventDispatcher\EventDispatcher;
use OmniMailDeps\Symfony\Component\Messenger\MessageBusInterface;
use OmniMailDeps\Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use OmniMailDeps\Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use OmniMailDeps\Symfony\Contracts\HttpClient\HttpClientInterface;
use OmniMailDeps\Symfony\Component\HttpClient\HttpClient;
use OmniMailDeps\Psr\Log\NullLogger;
use OmniMailDeps\Tempest\Discovery\BootDiscovery;
use OmniMailDeps\Tempest\Discovery\DiscoveryCache;
use OmniMailDeps\Tempest\Discovery\DiscoveryCacheStrategy;
use OmniMailDeps\Tempest\Discovery\DiscoveryConfig;
use OmniMailDeps\Tempest\Discovery\DiscoveryLocation;
/**
 * Builds the Omni Mail Symfony container.
 *
 * @since 0.1.0
 */
final readonly class ContainerFactory
{
    public function __construct(private Paths $paths, private Environment $environment)
    {
    }
    /**
     * Build or load the compiled container.
     *
     * @since 0.1.0
     */
    public function build(): ContainerInterface
    {
        $cache = new \OmniMail\Infrastructure\Container\ContainerCache($this->paths, $this->environment);
        $inspection = $cache->inspect();
        if ($inspection['usable']) {
            try {
                return $cache->load();
            } catch (Throwable) {
                $cache->clear();
            }
        }
        $manifest = $this->discover();
        $builder = new ContainerBuilder();
        $this->registerCoreServices($builder, $manifest);
        $this->registerDiscoveredServices($builder, $manifest);
        $builder->compile();
        $cache->dump($builder);
        try {
            return $cache->load();
        } catch (Throwable) {
            $cache->clear();
            // Keep the current request alive when the dumped PHP file is stale or not reloadable.
            return $builder;
        }
    }
    /**
     * @since 0.1.0
     */
    private function discover(): DiscoveryManifest
    {
        DiscoveryState::reset();
        $config = new DiscoveryConfig([new DiscoveryLocation('OmniMail', $this->paths->srcDir)]);
        $config->classes = [ServiceDiscovery::class, ControllerDiscovery::class, CommandDiscovery::class, MailProfileDiscovery::class, TransportFactoryDiscovery::class, MessageHandlerDiscovery::class];
        $bootDiscovery = new BootDiscovery(new NullDiscoveryContainer(), $config, new DiscoveryCache(DiscoveryCacheStrategy::NONE));
        $bootDiscovery($config->classes, $config->locations);
        return DiscoveryState::export();
    }
    /**
     * @since 0.1.0
     */
    private function registerCoreServices(ContainerBuilder $builder, DiscoveryManifest $manifest): void
    {
        $builder->register(Paths::class, Paths::class)->setPublic(\true)->setFactory([Paths::class, 'fromPluginFile'])->addArgument($this->paths->pluginFile);
        $builder->register(Environment::class, Environment::class)->setPublic(\true)->setFactory([Environment::class, 'fromWordPress']);
        $builder->register(DiscoveryManifest::class, DiscoveryManifest::class)->setPublic(\true)->setFactory([DiscoveryManifest::class, 'fromArray'])->addArgument($manifest->toArray());
        $builder->register(EventDispatcher::class, EventDispatcher::class)->setPublic(\true);
        $builder->setAlias(EventDispatcherInterface::class, EventDispatcher::class)->setPublic(\true);
        $builder->setAlias(ContainerInterface::class, 'service_container')->setPublic(\true);
        $builder->register(NullLogger::class, NullLogger::class)->setPublic(\true);
        $builder->setAlias(LoggerInterface::class, NullLogger::class)->setPublic(\true);
        $builder->register('omni_mail.http_client', HttpClientInterface::class)->setPublic(\true)->setFactory([HttpClient::class, 'create']);
        $builder->setAlias(HttpClientInterface::class, 'omni_mail.http_client')->setPublic(\true);
        $builder->register(DatabaseConnectionFactory::class, DatabaseConnectionFactory::class)->setPublic(\true);
        $builder->register('omni_mail.database_connection', Connection::class)->setPublic(\true)->setFactory([new Reference(DatabaseConnectionFactory::class), 'create']);
        $builder->setAlias(Connection::class, 'omni_mail.database_connection')->setPublic(\true);
        $builder->register(TableNameResolver::class, TableNameResolver::class)->setPublic(\true);
        $builder->register(\OmniMail\Infrastructure\Container\ContainerCache::class, \OmniMail\Infrastructure\Container\ContainerCache::class)->setPublic(\true)->setAutowired(\true);
        $builder->register(SecretCipher::class, SecretCipher::class)->setPublic(\true);
        $builder->register(OptionStore::class, OptionStore::class)->setPublic(\true)->setAutowired(\true);
        $builder->register(WordPressEventPublisher::class, WordPressEventPublisher::class)->setPublic(\true);
        $builder->setAlias(EventPublisherInterface::class, WordPressEventPublisher::class)->setPublic(\true);
        $builder->setAlias(ConnectionHealthPenaltyProviderInterface::class, WebhookHealthPenaltyProvider::class)->setPublic(\true);
        $builder->register(PhpSerializer::class, PhpSerializer::class)->setPublic(\true);
        $builder->setAlias(SerializerInterface::class, PhpSerializer::class)->setPublic(\true);
        $builder->register('omni_mail.message_bus', MessageBusInterface::class)->setPublic(\true)->setFactory([new Reference(MessageBusFactory::class), 'create']);
        $builder->setAlias(MessageBusInterface::class, 'omni_mail.message_bus')->setPublic(\true);
        $builder->register(HookRegistrar::class, HookRegistrar::class)->setPublic(\true)->setAutowired(\true);
        $builder->register(RestRouteRegistrar::class, RestRouteRegistrar::class)->setPublic(\true)->setAutowired(\true);
        $builder->register(CommandRegistrar::class, CommandRegistrar::class)->setPublic(\true)->setAutowired(\true);
        $builder->register(LifecycleManager::class, LifecycleManager::class)->setPublic(\true)->setAutowired(\true);
    }
    /**
     * @since 0.1.0
     */
    private function registerDiscoveredServices(ContainerBuilder $builder, DiscoveryManifest $manifest): void
    {
        foreach ($manifest->allClasses() as $className) {
            $definition = new Definition($className);
            $definition->setAutowired(\true);
            $definition->setPublic(\true);
            $reflectionClass = new ReflectionClass($className);
            if ($reflectionClass->isAbstract()) {
                continue;
            }
            $builder->setDefinition($className, $definition);
        }
    }
}
