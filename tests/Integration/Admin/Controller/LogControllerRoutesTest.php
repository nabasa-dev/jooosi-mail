<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Admin\Controller;

use OmniMail\Tests\Integration\Support\OmniMailIntegrationTestCase;

/**
 * Verifies admin log routes remain registered after controller splitting.
 *
 * @since 0.1.0
 */
final class LogControllerRoutesTest extends OmniMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testAdminLogRoutesRemainRegistered(): void
    {
        global $wp_rest_server;

        $wp_rest_server = null;
        $server = rest_get_server();

        do_action('rest_api_init');

        $routes = $server->get_routes();

        self::assertArrayHasKey('/omni-mail/v1/admin/logs', $routes);
        self::assertArrayHasKey('/omni-mail/v1/admin/logs/mail', $routes);
        self::assertArrayHasKey('/omni-mail/v1/admin/logs/mail/(?P<mail_log_id>\d+)', $routes);
        self::assertArrayHasKey('/omni-mail/v1/admin/logs/mail/test', $routes);
        self::assertArrayHasKey('/omni-mail/v1/admin/logs/queue', $routes);
        self::assertArrayHasKey('/omni-mail/v1/admin/logs/webhooks', $routes);
    }
}
