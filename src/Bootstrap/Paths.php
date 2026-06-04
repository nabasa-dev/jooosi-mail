<?php

declare(strict_types=1);

namespace OmniMail\Bootstrap;

/**
 * Runtime filesystem paths for Omni Mail.
 *
 * @since 0.1.0
 */
final readonly class Paths
{
    public function __construct(
        public string $pluginFile,
        public string $rootDir,
        public string $srcDir,
        public string $cacheDir,
        public string $documentationDir,
    ) {
    }

    /**
     * Build runtime paths from the main plugin file.
     *
     * @since 0.1.0
     */
    public static function fromPluginFile(string $pluginFile): self
    {
        $rootDir = dirname($pluginFile);

        return new self(
            pluginFile: $pluginFile,
            rootDir: $rootDir,
            srcDir: $rootDir . '/src',
            cacheDir: $rootDir . '/var/cache',
            documentationDir: $rootDir . '/documentation',
        );
    }
}
