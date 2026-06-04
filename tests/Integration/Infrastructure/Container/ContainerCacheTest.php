<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Infrastructure\Container;

use OmniMail\Bootstrap\Environment;
use OmniMail\Bootstrap\Paths;
use OmniMail\Infrastructure\Container\ContainerCache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WP_UnitTestCase;

/**
 * Covers compiled container cache validation.
 *
 * @since 0.1.0
 */
final class ContainerCacheTest extends WP_UnitTestCase
{
    /**
     * @since 0.1.0
     */
    private ?string $rootDir = null;

    /**
     * @since 0.1.0
     */
    public function tear_down(): void
    {
        if ($this->rootDir !== null) {
            $this->removeDirectory($this->rootDir);
            $this->rootDir = null;
        }

        parent::tear_down();
    }

    /**
     * @since 0.1.0
     */
    public function testInspectRejectsCacheFileWhenMetadataClassIsMissingFromCacheFile(): void
    {
        $cache = $this->createCache();
        $builder = new ContainerBuilder();
        $builder->register('omni_mail.cache_test_service', stdClass::class)->setPublic(true);
        $builder->compile();

        $cache->dump($builder);

        self::assertTrue($cache->inspect()['usable']);

        $staleClassName = 'OmniMailCachedContainerStale_' . str_replace('.', '_', uniqid('', true));
        $this->writeFile($this->rootDir . '/var/cache/container.php', sprintf("<?php\n\nclass %s\n{\n}\n", $staleClassName));

        $inspection = $cache->inspect();

        self::assertFalse($inspection['usable']);
        self::assertContains('cache_file_class_mismatch', $inspection['reasons']);

        try {
            $cache->load();
            self::fail('Expected the mismatched cache file to be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('does not contain the expected container class', $exception->getMessage());
        }

        self::assertFalse(class_exists($staleClassName, false));
    }

    /**
     * @since 0.1.0
     */
    private function createCache(): ContainerCache
    {
        $this->rootDir = sys_get_temp_dir() . '/omni-mail-container-cache-' . str_replace('.', '', uniqid('', true));

        $this->createDirectory($this->rootDir . '/src');
        $this->createDirectory($this->rootDir . '/var/cache');
        $this->writeFile($this->rootDir . '/composer.json', "{}\n");
        $this->writeFile($this->rootDir . '/omni-mail.php', "<?php\n");
        $this->writeFile($this->rootDir . '/src/Tracked.php', "<?php\n\ndeclare(strict_types=1);\n\nnamespace OmniMail\\Tests\\Fixture;\n\nfinal class ContainerCacheTracked\n{\n}\n");

        return new ContainerCache(
            new Paths(
                pluginFile: $this->rootDir . '/omni-mail.php',
                rootDir: $this->rootDir,
                srcDir: $this->rootDir . '/src',
                cacheDir: $this->rootDir . '/var/cache',
                documentationDir: $this->rootDir . '/documentation',
            ),
            new Environment(debug: false, name: 'production'),
        );
    }

    /**
     * @since 0.1.0
     */
    private function createDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            self::fail(sprintf('Unable to create test directory "%s".', $directory));
        }
    }

    /**
     * @since 0.1.0
     */
    private function writeFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            self::fail(sprintf('Unable to write test file "%s".', $path));
        }

        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($path, true);
        }
    }

    /**
     * @since 0.1.0
     */
    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());

                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
