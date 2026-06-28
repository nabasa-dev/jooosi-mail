<?php

declare(strict_types=1);

namespace JooosiMail\Discovery\Runtime;

/**
 * Temporary in-memory state used while Tempest discovery runs.
 *
 * @since 0.1.0
 */
final class DiscoveryState
{
    /** @var array<class-string, class-string> */
    private static array $services = [];

    /** @var array<class-string, class-string> */
    private static array $controllers = [];

    /** @var array<class-string, class-string> */
    private static array $commands = [];

    /** @var array<class-string, class-string> */
    private static array $profiles = [];

    /** @var array<class-string, class-string> */
    private static array $messageHandlers = [];

    /** @var array<class-string, class-string> */
    private static array $transportFactories = [];

    /**
     * Reset previous discovery results.
     *
     * @since 0.1.0
     */
    public static function reset(): void
    {
        self::$services = [];
        self::$controllers = [];
        self::$commands = [];
        self::$profiles = [];
        self::$messageHandlers = [];
        self::$transportFactories = [];
    }

    /**
     * Record a discovered service.
     *
     * @since 0.1.0
     */
    public static function addService(string $class): void
    {
        self::$services[$class] = $class;
    }

    /**
     * Record a discovered controller.
     *
     * @since 0.1.0
     */
    public static function addController(string $class): void
    {
        self::$controllers[$class] = $class;
    }

    /**
     * Record a discovered command.
     *
     * @since 0.1.0
     */
    public static function addCommand(string $class): void
    {
        self::$commands[$class] = $class;
    }

    /**
     * Record a discovered mail profile.
     *
     * @since 0.1.0
     */
    public static function addProfile(string $class): void
    {
        self::$profiles[$class] = $class;
    }

    /**
     * Record a discovered message handler.
     *
     * @since 0.1.0
     */
    public static function addMessageHandler(string $class): void
    {
        self::$messageHandlers[$class] = $class;
    }

    /**
     * Record a discovered transport factory.
     *
     * @since 0.1.0
     */
    public static function addTransportFactory(string $class): void
    {
        self::$transportFactories[$class] = $class;
    }

    /**
     * Export the final manifest.
     *
     * @since 0.1.0
     */
    public static function export(): DiscoveryManifest
    {
        return new DiscoveryManifest(
            services: array_values(self::$services),
            controllers: array_values(self::$controllers),
            commands: array_values(self::$commands),
            profiles: array_values(self::$profiles),
            messageHandlers: array_values(self::$messageHandlers),
            transportFactories: array_values(self::$transportFactories),
        );
    }
}
