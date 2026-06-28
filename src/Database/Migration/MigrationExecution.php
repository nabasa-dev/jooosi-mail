<?php

declare(strict_types=1);

namespace JooosiMail\Database\Migration;

/**
 * Execution history row for a migration.
 *
 * @since 0.1.0
 */
final readonly class MigrationExecution
{
    /**
     * @since 0.1.0
     */
    public function __construct(
        public string $version,
        public string $className,
        public string $description,
        public string $executedAt,
        public int $executionTimeMs,
    ) {
    }
}
