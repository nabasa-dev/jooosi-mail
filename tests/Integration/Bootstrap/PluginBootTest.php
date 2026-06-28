<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Bootstrap;

use WP_Hook;
use WP_UnitTestCase;

/**
 * Verifies the plugin boots inside the WordPress test environment.
 *
 * @since 0.1.0
 */
final class PluginBootTest extends WP_UnitTestCase
{
    /**
     * @since 0.1.0
     */
    public function testMailInterceptionHookIsRegistered(): void
    {
        global $wp_filter;

        self::assertIsArray($wp_filter);
        self::assertArrayHasKey('pre_wp_mail', $wp_filter);
        self::assertInstanceOf(WP_Hook::class, $wp_filter['pre_wp_mail']);
        self::assertArrayHasKey(9999, $wp_filter['pre_wp_mail']->callbacks);
    }
}
