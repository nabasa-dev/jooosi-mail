<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

if (! file_exists($autoloadPath)) {
    fwrite(STDERR, "Composer dependencies are missing. Run composer install before executing the PHP test suite.\n");
    exit(1);
}

require_once $autoloadPath;

$testsDirectory = getenv('WP_TESTS_DIR');

if (! is_string($testsDirectory) || $testsDirectory === '') {
    $testsDirectory = $projectRoot . '/vendor/wp-phpunit/wp-phpunit';
}

$testsDirectory = rtrim($testsDirectory, '/\\');

if (! file_exists($testsDirectory . '/includes/bootstrap.php')) {
    fwrite(STDERR, "WordPress PHPUnit bootstrap was not found. Install dev dependencies or run the suite inside wp-env.\n");
    exit(1);
}

if (! defined('WP_TESTS_CONFIG_FILE_PATH')) {
    define('WP_TESTS_CONFIG_FILE_PATH', $projectRoot . '/tests/wp-config.php');
}

if (! defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $projectRoot . '/vendor/yoast/phpunit-polyfills');
}

require_once $testsDirectory . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function () use ($projectRoot): void {
    require $projectRoot . '/omni-mail.php';
});

require_once $testsDirectory . '/includes/bootstrap.php';
