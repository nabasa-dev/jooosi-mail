<?php

declare(strict_types=1);

$wpClasses = json_decode(file_get_contents('deploy/php-scoper-wordpress-excludes-master/generated/exclude-wordpress-classes.json'));
$wpFunctions = json_decode(file_get_contents('deploy/php-scoper-wordpress-excludes-master/generated/exclude-wordpress-functions.json'));
$wpConstants = json_decode(file_get_contents('deploy/php-scoper-wordpress-excludes-master/generated/exclude-wordpress-constants.json'));

/**
 * @return list<string>
 */
function collectFiles(string $directory, callable $filter): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $fileInfo) {
        if (! $fileInfo instanceof SplFileInfo || ! $fileInfo->isFile()) {
            continue;
        }

        if ($filter($fileInfo)) {
            $files[] = $fileInfo->getPathname();
        }
    }

    return $files;
}

$polyfillBootstraps = [];
$polyfillStubs = [];

foreach (glob(dirname(__DIR__) . '/vendor/symfony/polyfill-*', GLOB_ONLYDIR) ?: [] as $polyfillDirectory) {
    $polyfillBootstraps = [
        ...$polyfillBootstraps,
        ...collectFiles(
            $polyfillDirectory,
            static fn (SplFileInfo $fileInfo): bool => str_starts_with($fileInfo->getFilename(), 'bootstrap')
                && $fileInfo->getExtension() === 'php',
        ),
    ];

    $polyfillStubs = [
        ...$polyfillStubs,
        ...collectFiles(
            $polyfillDirectory . '/Resources/stubs',
            static fn (SplFileInfo $fileInfo): bool => $fileInfo->getExtension() === 'php',
        ),
    ];
}

$actionSchedulerFiles = collectFiles(
    dirname(__DIR__) . '/vendor/woocommerce/action-scheduler',
    static fn (SplFileInfo $fileInfo): bool => $fileInfo->getExtension() === 'php',
);

return [
    'prefix' => 'JooosiMailDeps',

    'exclude-files' => [
        ...$polyfillBootstraps,
        ...$polyfillStubs,
        ...$actionSchedulerFiles,
    ],

    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if (str_ends_with($filePath, 'symfony/cache/Traits/ValueWrapper.php')) {
                $contents = preg_replace('/^class\s+[^\s\{]+/m', 'class ©', $contents) ?? $contents;
            }

            if (str_ends_with($filePath, 'symfony/cache/CacheItem.php')) {
                $contents = preg_replace(
                    '/private const VALUE_WRAPPER = "\\xA9";/i',
                    'private const VALUE_WRAPPER = \'' . $prefix . '\\©\';',
                    $contents,
                ) ?? $contents;
            }

            if (str_contains($filePath, 'symfony/dependency-injection/')) {
                $contents = preg_replace(
                    '/^use Symfony\\\\Component\\\\DependencyInjection\\\\ParameterBag\\\\/m',
                    'use ' . $prefix . '\\Symfony\\Component\\DependencyInjection\\ParameterBag' . '\\',
                    $contents,
                ) ?? $contents;

                $contents = preg_replace(
                    '/(?<!' . preg_quote($prefix, '/') . ')\\\\Symfony\\\\Component\\\\DependencyInjection\\\\ParameterBag\\\\/',
                    '\\\\' . $prefix . '\\Symfony\\Component\\DependencyInjection\\ParameterBag' . '\\',
                    $contents,
                ) ?? $contents;
            }

            if (str_contains($filePath, 'symfony/dependency-injection/ParameterBag/')) {
                $contents = preg_replace(
                    '/^namespace Symfony\\\\Component\\\\DependencyInjection\\\\ParameterBag;$/m',
                    'namespace ' . $prefix . '\\Symfony\\Component\\DependencyInjection\\ParameterBag;',
                    $contents,
                ) ?? $contents;
            }

            return $contents;
        },
    ],

    'exclude-namespaces' => [
        'JooosiMail',
        'WP_CLI',
        'Symfony\Polyfill',
    ],

    'exclude-classes' => array_merge(
        $wpClasses,
        [
            'WP_CLI',
            'WP_CLI_Command',
            'ActionScheduler',
            '/^ActionScheduler_[\p{L}\d_]+$/',
        ],
    ),

    'exclude-functions' => array_merge(
        $wpFunctions,
        [
            '/^as_[\p{L}\d_]+$/',
            'action_scheduler_register_3_dot_9_dot_3',
        ],
    ),

    'exclude-constants' => array_merge(
        $wpConstants,
        [
            '/^JOOOSI_MAIL_[\p{L}\d_]+$/',
            '/^ACTION_SCHEDULER_[\p{L}\d_]+$/',
            '/^SYMFONY_[\p{L}\d_]+$/',
            'WP_CONTENT_DIR',
            'WP_CONTENT_URL',
            'ABSPATH',
            'WPINC',
            'WP_DEBUG',
            'WP_DEBUG_DISPLAY',
            'WPMU_PLUGIN_DIR',
            'WP_PLUGIN_DIR',
            'WP_PLUGIN_URL',
            'WPMU_PLUGIN_URL',
            'DB_CHARSET',
            'MINUTE_IN_SECONDS',
            'HOUR_IN_SECONDS',
            'DAY_IN_SECONDS',
            'MONTH_IN_SECONDS',
        ],
    ),

    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => [],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [
        '/^JOOOSI_MAIL_[\p{L}\d_]+$/',
    ],
];
