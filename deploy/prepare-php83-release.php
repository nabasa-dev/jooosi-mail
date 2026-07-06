<?php

declare(strict_types=1);

/**
 * Prepares the copied release tree after syntax downgrade.
 *
 * @since 1.0.7
 */

if ($argc !== 2) {
    fwrite(STDERR, "Usage: php deploy/prepare-php83-release.php <release-directory>\n");
    exit(1);
}

$releaseDirectory = rtrim((string) $argv[1], '/');

if (! is_dir($releaseDirectory)) {
    fwrite(STDERR, sprintf("Release directory '%s' does not exist.\n", $releaseDirectory));
    exit(1);
}

updateComposerJson($releaseDirectory . '/composer.json');
updateInstalledJson($releaseDirectory . '/vendor/composer/installed.json');
updatePlatformCheck($releaseDirectory . '/vendor/composer/platform_check.php');
replaceInFile($releaseDirectory . '/jooosi-mail.php', '/Requires PHP:\s*8\.5/', 'Requires PHP:        8.3');
replaceInFile($releaseDirectory . '/readme.txt', '/Requires PHP:\s*8\.5/', 'Requires PHP: 8.3');

/**
 * Updates release-only Composer metadata.
 *
 * @since 1.0.7
 */
function updateComposerJson(string $path): void
{
    if (! is_file($path)) {
        throw new RuntimeException(sprintf('Composer file "%s" does not exist.', $path));
    }

    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Unable to read composer file "%s".', $path));
    }

    $composer = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($composer)) {
        throw new RuntimeException(sprintf('Composer file "%s" did not decode to an array.', $path));
    }

    $composer['require']['php'] = '>=8.3';
    $composer['extra']['wordpress-plugin']['requires-php'] = '8.3';
    $composer['config']['platform']['php'] = '8.3.0';
    $composer['config']['platform-check'] = false;

    $encoded = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents($path, $encoded . "\n");
}

/**
 * Updates Composer's installed package metadata in the release copy.
 *
 * @since 1.0.7
 */
function updateInstalledJson(string $path): void
{
    if (! is_file($path)) {
        return;
    }

    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Unable to read installed metadata "%s".', $path));
    }

    $installed = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    if (! is_array($installed)) {
        throw new RuntimeException(sprintf('Installed metadata "%s" did not decode to an array.', $path));
    }

    if (isset($installed['packages']) && is_array($installed['packages'])) {
        foreach ($installed['packages'] as &$package) {
            normalizePackagePhpRequirement($package);
        }
        unset($package);
    } else {
        foreach ($installed as &$package) {
            normalizePackagePhpRequirement($package);
        }
        unset($package);
    }

    $encoded = json_encode($installed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents($path, $encoded . "\n");
}

/**
 * Normalizes one package's PHP requirement when the release code was downgraded.
 *
 * @param mixed $package
 *
 * @since 1.0.7
 */
function normalizePackagePhpRequirement(mixed &$package): void
{
    if (! is_array($package)) {
        return;
    }

    if (! isset($package['require']['php']) || ! is_string($package['require']['php'])) {
        return;
    }

    if (! preg_match('/8\.[45]/', $package['require']['php'])) {
        return;
    }

    $package['require']['php'] = '>=8.3';
}

/**
 * Updates the temporary platform check generated before release metadata is patched.
 *
 * @since 1.0.7
 */
function updatePlatformCheck(string $path): void
{
    if (! is_file($path)) {
        return;
    }

    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Unable to read platform check "%s".', $path));
    }

    $contents = str_replace('PHP_VERSION_ID >= 80500', 'PHP_VERSION_ID >= 80300', $contents);
    $contents = str_replace('>= 8.5.0', '>= 8.3.0', $contents);
    $contents = str_replace('>= 8.4.0', '>= 8.3.0', $contents);
    $contents = str_replace('>= 8.4.1', '>= 8.3.0', $contents);

    file_put_contents($path, $contents);
}

/**
 * Replaces release metadata in a file.
 *
 * @since 1.0.7
 */
function replaceInFile(string $path, string $pattern, string $replacement): void
{
    if (! is_file($path)) {
        throw new RuntimeException(sprintf('File "%s" does not exist.', $path));
    }

    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Unable to read file "%s".', $path));
    }

    $updatedContents = preg_replace($pattern, $replacement, $contents, count: $count);

    if (! is_string($updatedContents) || $count !== 1) {
        throw new RuntimeException(sprintf('Unable to update release metadata in "%s".', $path));
    }

    file_put_contents($path, $updatedContents);
}
