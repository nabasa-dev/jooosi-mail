<?php

declare (strict_types=1);
namespace JooosiMail\Database\Migration;

/**
 * Immutable metadata for a discovered migration.
 *
 * @since 0.1.0
 */
final readonly class MigrationDefinition
{
    /**
     * @since 0.1.0
     */
    public function __construct(public string $version, public string $className, public string $description)
    {
    }
}
