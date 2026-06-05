<?php

declare (strict_types=1);
namespace OmniMail\Database\Migration;

use OmniMail\Discovery\Attribute\Service;
use RuntimeException;
/**
 * Runs Omni Mail database migrations during activation.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class MigrationRunner
{
    public function __construct(private \OmniMail\Database\Migration\MigrationManager $migrationManager)
    {
    }
    /**
     * @since 0.1.0
     */
    public function run(): void
    {
        $result = $this->migrationManager->run();
        if (isset($result['failed'])) {
            throw new RuntimeException($result['message']);
        }
    }
}
