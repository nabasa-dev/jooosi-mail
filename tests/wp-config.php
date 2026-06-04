<?php

declare(strict_types=1);

$environment = static function (string ...$names): ?string {
    foreach ($names as $name) {
        $value = getenv($name);

        if ($value !== false) {
            return $value;
        }
    }

    return null;
};

$isWpEnv = $environment('WP_TESTS_DIR') !== null;
$wordpressDirectory = $environment('WP_TESTS_WORDPRESS_DIR') ?? dirname(__DIR__, 4);

define('ABSPATH', rtrim($wordpressDirectory, '/\\') . '/');
define('WP_DEFAULT_THEME', 'default');
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', false);

// The WordPress test suite drops all tables for this prefix during install/reset.
$table_prefix = $environment('WP_PHPUNIT__TABLE_PREFIX') ?? 'omni_tests_';

define('DB_NAME', $environment('WP_DB_NAME', 'WORDPRESS_DB_NAME') ?? 'wordpress');
define('DB_USER', $environment('WP_DB_USER', 'WORDPRESS_DB_USER') ?? 'root');
define('DB_PASSWORD', $environment('WP_DB_PASS', 'WP_DB_PASSWORD', 'WORDPRESS_DB_PASSWORD') ?? '');
define('DB_HOST', $environment('WP_DB_HOST', 'WORDPRESS_DB_HOST') ?? ($isWpEnv ? 'mysql' : 'localhost'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('AUTH_KEY', 'omni-mail-tests-auth-key');
define('SECURE_AUTH_KEY', 'omni-mail-tests-secure-auth-key');
define('LOGGED_IN_KEY', 'omni-mail-tests-logged-in-key');
define('NONCE_KEY', 'omni-mail-tests-nonce-key');
define('AUTH_SALT', 'omni-mail-tests-auth-salt');
define('SECURE_AUTH_SALT', 'omni-mail-tests-secure-auth-salt');
define('LOGGED_IN_SALT', 'omni-mail-tests-logged-in-salt');
define('NONCE_SALT', 'omni-mail-tests-nonce-salt');

define('WP_TESTS_DOMAIN', 'omni-mail.test');
define('WP_TESTS_EMAIL', 'admin@omni-mail.test');
define('WP_TESTS_TITLE', 'Omni Mail Test Site');
define('WP_PHP_BINARY', PHP_BINARY);
define('WPLANG', '');
