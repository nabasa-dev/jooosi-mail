<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\DependencyInjection\Loader\Configurator;

use JooosiMailDeps\Psr\Clock\ClockInterface as PsrClockInterface;
use JooosiMailDeps\Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use JooosiMailDeps\Symfony\Component\Clock\Clock;
use JooosiMailDeps\Symfony\Component\Clock\ClockInterface;
use JooosiMailDeps\Symfony\Component\Config\Loader\LoaderInterface;
use JooosiMailDeps\Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use JooosiMailDeps\Symfony\Component\Config\ResourceCheckerConfigCacheFactory;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Config\ContainerParametersResourceChecker;
use JooosiMailDeps\Symfony\Component\DependencyInjection\EnvVarProcessor;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Kernel\FileLocator;
use JooosiMailDeps\Symfony\Component\DependencyInjection\Kernel\KernelInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ReverseContainer;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ServicesResetter;
use JooosiMailDeps\Symfony\Component\DependencyInjection\ServicesResetterInterface;
use JooosiMailDeps\Symfony\Component\EventDispatcher\EventDispatcher;
use JooosiMailDeps\Symfony\Component\EventDispatcher\EventDispatcherInterface;
use JooosiMailDeps\Symfony\Component\Filesystem\Filesystem;
use JooosiMailDeps\Symfony\Contracts\EventDispatcher\EventDispatcherInterface as ContractsEventDispatcherInterface;
return static function (ContainerConfigurator $container) {
    $container->services()->set('kernel')->synthetic()->public()->alias(KernelInterface::class, 'kernel')->set('parameter_bag', ContainerBag::class)->args([service('service_container')])->alias(ContainerBagInterface::class, 'parameter_bag')->alias(ParameterBagInterface::class, 'parameter_bag')->set('event_dispatcher', EventDispatcher::class)->public()->tag('container.hot_path')->tag('event_dispatcher.dispatcher', ['name' => 'event_dispatcher'])->alias(EventDispatcherInterface::class, 'event_dispatcher')->alias(ContractsEventDispatcherInterface::class, 'event_dispatcher')->alias(PsrEventDispatcherInterface::class, 'event_dispatcher')->set('filesystem', Filesystem::class)->alias(Filesystem::class, 'filesystem')->set('file_locator', FileLocator::class)->args([service('kernel')])->alias(FileLocator::class, 'file_locator')->set('config_cache_factory', ResourceCheckerConfigCacheFactory::class)->args([tagged_iterator('config_cache.resource_checker')])->set('dependency_injection.config.container_parameters_resource_checker', ContainerParametersResourceChecker::class)->args([service('service_container')])->tag('config_cache.resource_checker', ['priority' => -980])->set('config.resource.self_checking_resource_checker', SelfCheckingResourceChecker::class)->tag('config_cache.resource_checker', ['priority' => -990])->set('reverse_container', ReverseContainer::class)->args([service('service_container'), service_locator([])])->alias(ReverseContainer::class, 'reverse_container')->set('services_resetter', ServicesResetter::class)->public()->alias(ServicesResetterInterface::class, 'services_resetter')->set('container.env_var_processor', EnvVarProcessor::class)->args([service('service_container'), tagged_iterator('container.env_var_loader')])->tag('container.env_var_processor')->tag('kernel.reset', ['method' => 'reset'])->set('clock', Clock::class)->alias(ClockInterface::class, 'clock')->alias(PsrClockInterface::class, 'clock')->set(LoaderInterface::class)->abstract()->tag('container.excluded');
};
