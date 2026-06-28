<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\DependencyInjection\Kernel;

use JooosiMailDeps\PHPUnit\Framework\TestCase;
use JooosiMailDeps\Psr\Clock\ClockInterface as PsrClockInterface;
use JooosiMailDeps\Psr\Container\ContainerInterface as PsrContainerInterface;
use JooosiMailDeps\Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use JooosiMailDeps\Psr\Log\LoggerAwareInterface;
use JooosiMailDeps\Symfony\Component\Clock\ClockInterface;
use JooosiMailDeps\Symfony\Component\Config\ResourceCheckerInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ChildDefinition;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Compiler\AddBehaviorDescribingTagsPass;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Compiler\PassConfig;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Compiler\ResettableServicePass;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ContainerBuilder;
use JooosiMailDeps\Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Exception\LogicException;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Reference;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ServiceLocator;
use JooosiMailDeps\Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use JooosiMailDeps\Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use JooosiMailDeps\Symfony\Component\EventDispatcher\EventDispatcherInterface;
use JooosiMailDeps\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use JooosiMailDeps\Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;
use JooosiMailDeps\Symfony\Contracts\Service\ResetInterface;
use JooosiMailDeps\Symfony\Contracts\Service\ServiceSubscriberInterface;
/**
 * Provides core DI infrastructure services (event dispatcher, filesystem, clock, etc.).
 */
class ServicesBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return $this->path ??= __DIR__;
    }
    public function build(ContainerBuilder $container): void
    {
        if (class_exists(RegisterListenersPass::class)) {
            $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
        }
        $container->addCompilerPass(new AddBehaviorDescribingTagsPass(['container.do_not_inline', 'container.service_locator', 'container.service_subscriber', 'kernel.event_subscriber', 'kernel.event_listener', 'kernel.reset']));
        $container->addCompilerPass(new ResettableServicePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -32);
    }
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('Resources/config/services.php');
        $container->registerAliasForArgument('parameter_bag', PsrContainerInterface::class);
        $container->registerForAutoconfiguration(ResourceCheckerInterface::class)->addTag('config_cache.resource_checker');
        $container->registerForAutoconfiguration(EnvVarLoaderInterface::class)->addTag('container.env_var_loader');
        $container->registerForAutoconfiguration(EnvVarProcessorInterface::class)->addTag('container.env_var_processor');
        $container->registerForAutoconfiguration(ServiceLocator::class)->addTag('container.service_locator');
        $container->registerForAutoconfiguration(EventDispatcherInterface::class)->addTag('event_dispatcher.dispatcher');
        $container->registerForAutoconfiguration(ResetInterface::class)->addTag('kernel.reset', ['method' => 'reset']);
        $container->registerForAutoconfiguration(ServiceSubscriberInterface::class)->addTag('container.service_subscriber');
        $container->registerForAutoconfiguration(EventSubscriberInterface::class)->addTag('kernel.event_subscriber');
        $container->registerForAutoconfiguration(LoggerAwareInterface::class)->addMethodCall('setLogger', [new Reference('logger', $container::IGNORE_ON_INVALID_REFERENCE)]);
        $container->registerForAutoconfiguration(CompilerPassInterface::class)->addTag('container.excluded', ['source' => 'because it\'s a compiler pass']);
        $container->registerForAutoconfiguration(TestCase::class)->addTag('container.excluded', ['source' => 'because it\'s a test case']);
        $container->registerForAutoconfiguration(\UnitEnum::class)->addTag('container.excluded', ['source' => 'because it\'s an enum']);
        $container->registerAttributeForAutoconfiguration(\Attribute::class, static function (ChildDefinition $definition) {
            $definition->addTag('container.excluded', ['source' => 'because it\'s a PHP attribute']);
        });
        $container->registerAttributeForAutoconfiguration(AsEventListener::class, static function (ChildDefinition $definition, AsEventListener $attribute, \ReflectionClass|\ReflectionMethod $reflector) {
            $tagAttributes = get_object_vars($attribute);
            if ($reflector instanceof \ReflectionMethod) {
                if (isset($tagAttributes['method'])) {
                    throw new LogicException(\sprintf('AsEventListener attribute cannot declare a method on "%s::%s()".', $reflector->class, $reflector->name));
                }
                $tagAttributes['method'] = $reflector->getName();
            }
            $definition->addTag('kernel.event_listener', $tagAttributes);
        });
        if (!$container::willBeAvailable('symfony/event-dispatcher', EventDispatcherInterface::class, ['symfony/dependency-injection'])) {
            $container->removeDefinition('event_dispatcher');
            $container->removeAlias(EventDispatcherInterface::class);
            $container->removeAlias(ContractsEventDispatcherInterface::class);
            $container->removeAlias(PsrEventDispatcherInterface::class);
        }
        if (!interface_exists(ClockInterface::class)) {
            $container->removeDefinition('clock');
        }
        if (!$container::willBeAvailable('symfony/clock', ClockInterface::class, ['symfony/dependency-injection'])) {
            $container->removeAlias(ClockInterface::class);
            $container->removeAlias(PsrClockInterface::class);
        }
        if (!$container->getParameter('kernel.debug')) {
            $container->getDefinition('config_cache_factory')->setArguments([]);
        }
    }
}
