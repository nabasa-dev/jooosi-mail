<?php

declare(strict_types=1);

namespace OmniMail\Database\Migration;

use OmniMail\Bootstrap\Paths;
use OmniMail\Discovery\Attribute\Service;
use RuntimeException;

/**
 * Discovers versioned schema migrations from the dedicated migrations directory.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MigrationRegistry
{
    private const string MIGRATIONS_DIRECTORY = '/Database/Migration/Versions';

    public function __construct(
        private Paths $paths,
    ) {
    }

    /**
     * @return list<MigrationDefinition>
     *
     * @since 0.1.0
     */
    public function all(): array
    {
        $definitions = [];
        $knownVersions = [];

        foreach ($this->classNames() as $className) {
            $migration = $this->newInstance($className);
            $version = $migration->getVersion();

            if ($version === '') {
                throw new RuntimeException(sprintf('The Omni Mail migration class "%s" returned an empty version.', $className));
            }

            if (isset($knownVersions[$version])) {
                throw new RuntimeException(sprintf('The Omni Mail migration version "%s" is registered more than once.', $version));
            }

            $knownVersions[$version] = true;
            $definitions[] = new MigrationDefinition(
                version: $version,
                className: $className,
                description: $migration->getDescription(),
            );
        }

        usort(
            $definitions,
            static fn (MigrationDefinition $left, MigrationDefinition $right): int => strcmp($left->version, $right->version),
        );

        return $definitions;
    }

    /**
     * @since 0.1.0
     */
    public function directory(): string
    {
        return $this->paths->srcDir . self::MIGRATIONS_DIRECTORY;
    }

    /**
     * @param array<string> $executedVersions
     *
     * @return list<MigrationDefinition>
     *
     * @since 0.1.0
     */
    public function pending(array $executedVersions): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (MigrationDefinition $definition): bool => ! in_array($definition->version, $executedVersions, true),
        ));
    }

    /**
     * @param array<string> $executedVersions
     *
     * @return list<MigrationDefinition>
     *
     * @since 0.1.0
     */
    public function executed(array $executedVersions): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (MigrationDefinition $definition): bool => in_array($definition->version, $executedVersions, true),
        ));
    }

    /**
     * @since 0.1.0
     */
    public function find(string $identifier): ?MigrationDefinition
    {
        foreach ($this->all() as $definition) {
            if ($definition->version === $identifier || $definition->className === $identifier) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @since 0.1.0
     */
    public function createInstance(MigrationDefinition|string $migration): MigrationInterface
    {
        if ($migration instanceof MigrationDefinition) {
            return $this->newInstance($migration->className);
        }

        $definition = $this->find($migration);
        if (! $definition instanceof MigrationDefinition) {
            throw new RuntimeException(sprintf('The Omni Mail migration "%s" could not be found.', $migration));
        }

        return $this->newInstance($definition->className);
    }

    /**
     * @return list<class-string<MigrationInterface>>
     *
     * @since 0.1.0
     */
    public function classNames(): array
    {
        $files = glob($this->directory() . '/Version*.php');
        if (! is_array($files)) {
            return [];
        }

        sort($files, SORT_STRING);

        return array_map(
            static fn (string $file): string => sprintf('%s\\%s', self::migrationsNamespace(), pathinfo($file, PATHINFO_FILENAME)),
            $files,
        );
    }

    /**
     * @since 0.1.0
     */
    private static function migrationsNamespace(): string
    {
        return __NAMESPACE__ . '\\Versions';
    }

    /**
     * @param class-string<MigrationInterface> $className
     *
     * @since 0.1.0
     */
    private function newInstance(string $className): MigrationInterface
    {
        if (! class_exists($className)) {
            throw new RuntimeException(sprintf('The Omni Mail migration class "%s" could not be autoloaded.', $className));
        }

        $migration = new $className();
        if (! $migration instanceof MigrationInterface) {
            throw new RuntimeException(sprintf('The Omni Mail migration class "%s" must implement %s.', $className, MigrationInterface::class));
        }

        return $migration;
    }
}
