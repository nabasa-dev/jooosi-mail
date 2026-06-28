<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Admin\Controller;

use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;
use WP_REST_Request;

/**
 * Verifies log endpoints accept array query parameters.
 *
 * @since 0.1.0
 */
final class LogControllerArrayQueryTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testMailLogEndpointFiltersByArrayStatuses(): void
    {
        $this->authenticateAdmin();
        $this->registerRoutes();

        $this->db()->insert($this->tableNameResolver()->resolve('mail_logs'), [
            'source' => 'test',
            'subject' => 'Sent mail',
            'recipients_json' => wp_json_encode([['address' => 'sent@example.com']]),
            'payload_json' => wp_json_encode(['from' => [['address' => 'from@example.com']]]),
            'plan_json' => wp_json_encode([]),
            'status' => 'sent',
            'created_at' => '2026-04-08 10:00:00',
            'updated_at' => '2026-04-08 10:00:00',
        ]);
        $this->db()->insert($this->tableNameResolver()->resolve('mail_logs'), [
            'source' => 'test',
            'subject' => 'Failed mail',
            'recipients_json' => wp_json_encode([['address' => 'failed@example.com']]),
            'payload_json' => wp_json_encode(['from' => [['address' => 'from@example.com']]]),
            'plan_json' => wp_json_encode([]),
            'status' => 'failed',
            'created_at' => '2026-04-08 11:00:00',
            'updated_at' => '2026-04-08 11:00:00',
        ]);

        $request = new WP_REST_Request('GET', '/jooosi-mail/v1/admin/logs/mail');
        $request->set_query_params([
            'statuses' => ['failed'],
        ]);

        $response = rest_do_request($request);
        $data = $response->get_data();

        self::assertSame(200, $response->get_status());
        self::assertCount(1, $data['items']);
        self::assertSame('failed', $data['items'][0]['status']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailLogDetailEndpointNormalizesLegacyHtmlPayloads(): void
    {
        $this->authenticateAdmin();
        $this->registerRoutes();

        $html = '<!doctype html><html><body><p>Hello <strong>world</strong></p></body></html>';

        $this->db()->insert($this->tableNameResolver()->resolve('mail_logs'), [
            'source' => 'test',
            'subject' => 'Legacy HTML payload',
            'recipients_json' => wp_json_encode([['address' => 'recipient@example.com']]),
            'payload_json' => wp_json_encode([
                'from' => [['address' => 'from@example.com']],
                'textBody' => $html,
                'htmlBody' => null,
                'headers' => [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ],
            ]),
            'plan_json' => wp_json_encode([]),
            'status' => 'sent',
            'created_at' => '2026-04-08 10:00:00',
            'updated_at' => '2026-04-08 10:00:00',
        ]);

        $mailLogId = (int) $this->db()->lastInsertId();

        $request = new WP_REST_Request('GET', sprintf('/jooosi-mail/v1/admin/logs/mail/%d', $mailLogId));
        $response = rest_do_request($request);
        $data = $response->get_data();

        self::assertSame(200, $response->get_status());
        self::assertSame($html, $data['item']['htmlBody']);
        self::assertNull($data['item']['textBody']);
    }

    /**
     * @since 0.1.0
     */
    public function testQueueLogEndpointFiltersByArrayStatuses(): void
    {
        $this->authenticateAdmin();
        $this->registerRoutes();

        $this->db()->insert($this->tableNameResolver()->resolve('queue_messages'), [
            'body' => '{}',
            'headers_json' => wp_json_encode([]),
            'status' => 'pending',
            'priority' => 10,
            'available_at' => '2026-04-08 10:00:00',
            'attempt_count' => 0,
            'max_attempts' => 3,
            'created_at' => '2026-04-08 10:00:00',
            'updated_at' => '2026-04-08 10:00:00',
        ]);
        $this->db()->insert($this->tableNameResolver()->resolve('queue_messages'), [
            'body' => '{}',
            'headers_json' => wp_json_encode([]),
            'status' => 'failed',
            'priority' => 10,
            'available_at' => '2026-04-08 11:00:00',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'created_at' => '2026-04-08 11:00:00',
            'updated_at' => '2026-04-08 11:00:00',
        ]);

        $request = new WP_REST_Request('GET', '/jooosi-mail/v1/admin/logs/queue');
        $request->set_query_params([
            'statuses' => ['failed'],
        ]);

        $response = rest_do_request($request);
        $data = $response->get_data();

        self::assertSame(200, $response->get_status());
        self::assertCount(1, $data['items']);
        self::assertSame('failed', $data['items'][0]['status']);
    }

    /**
     * @since 0.1.0
     */
    public function testWebhookLogEndpointFiltersByArrayEventTypes(): void
    {
        $this->authenticateAdmin();
        $this->registerRoutes();

        $this->db()->insert($this->tableNameResolver()->resolve('webhook_events'), [
            'event_type' => 'delivered',
            'payload_json' => wp_json_encode([]),
            'created_at' => '2026-04-08 10:00:00',
        ]);
        $this->db()->insert($this->tableNameResolver()->resolve('webhook_events'), [
            'event_type' => 'bounced',
            'payload_json' => wp_json_encode([]),
            'created_at' => '2026-04-08 11:00:00',
        ]);

        $request = new WP_REST_Request('GET', '/jooosi-mail/v1/admin/logs/webhooks');
        $request->set_query_params([
            'eventTypes' => ['bounced'],
        ]);

        $response = rest_do_request($request);
        $data = $response->get_data();

        self::assertSame(200, $response->get_status());
        self::assertCount(1, $data['items']);
        self::assertSame('bounced', $data['items'][0]['eventType']);
    }

    /**
     * @since 0.1.0
     */
    private function authenticateAdmin(): void
    {
        wp_set_current_user($this->factory()->user->create([
            'role' => 'administrator',
        ]));
    }

    /**
     * @since 0.1.0
     */
    private function registerRoutes(): void
    {
        global $wp_rest_server;

        $wp_rest_server = null;
        rest_get_server();
        do_action('rest_api_init');
    }
}
