<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Webhook\Adapter;

use JooosiMail\Mail\Connection\Connection;
use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;
use JooosiMail\Webhook\Adapter\AhaSendWebhookAdapter;
use JooosiMail\Webhook\Adapter\BirdWebhookAdapter;
use JooosiMail\Webhook\Adapter\BrevoWebhookAdapter;
use JooosiMail\Webhook\Adapter\GenericWebhookAdapter;
use JooosiMail\Webhook\Adapter\MailerSendWebhookAdapter;
use JooosiMail\Webhook\Adapter\MailgunWebhookAdapter;
use JooosiMail\Webhook\Adapter\MailjetWebhookAdapter;
use JooosiMail\Webhook\Adapter\MailomatWebhookAdapter;
use JooosiMail\Webhook\Adapter\MailtrapWebhookAdapter;
use JooosiMail\Webhook\Adapter\MandrillWebhookAdapter;
use JooosiMail\Webhook\Adapter\PostmarkWebhookAdapter;
use JooosiMail\Webhook\Adapter\ResendWebhookAdapter;
use JooosiMail\Webhook\Adapter\SendGridWebhookAdapter;
use JooosiMail\Webhook\Adapter\SendLayerWebhookAdapter;
use JooosiMail\Webhook\Adapter\Smtp2goWebhookAdapter;
use JooosiMail\Webhook\Adapter\SparkPostWebhookAdapter;
use JooosiMail\Webhook\Adapter\SweegoWebhookAdapter;
use JooosiMail\Webhook\Adapter\ToSendWebhookAdapter;
use JooosiMail\Webhook\Adapter\ZeptoMailWebhookAdapter;
use WP_REST_Request;

/**
 * Covers provider-specific webhook adapter parsing and verification.
 *
 * @since 0.1.0
 */
final class WebhookAdapterTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testSendGridAdapterVerifiesAndParsesBatchEvents(): void
    {
        $adapter = $this->container()->get(SendGridWebhookAdapter::class);
        $keyPair = $this->generateSendGridKeyPair();
        $request = new WP_REST_Request('POST', '/sendgrid');
        $body = wp_json_encode([
            [
                'event' => 'delivered',
                'email' => 'first@example.com',
                'sg_message_id' => 'sg-message-1',
                'sg_event_id' => 'sg-event-1',
                'custom_args' => ['mail_log_id' => 41],
                'timestamp' => 1700000000,
            ],
            [
                'event' => 'spamreport',
                'email' => 'second@example.com',
                'smtp-id' => 'smtp-message-2',
                'sg_event_id' => 'sg-event-2',
                'metadata' => ['jooosi_mail_mail_log_id' => 42],
                'timestamp' => 1700000100,
            ],
        ]);

        self::assertIsString($body);

        $request->set_body($body);
        $request->set_header('x-twilio-email-event-webhook-timestamp', '1711756800');
        $request->set_header('x-twilio-email-event-webhook-signature', $this->signSendGridPayload($body, '1711756800', $keyPair['private']));

        $connection = new Connection(
            id: null,
            profileKey: 'sendgrid',
            name: 'SendGrid adapter test',
            secrets: ['webhook_secret' => $keyPair['public']],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(2, $events);
        self::assertSame('delivered', $events[0]['event_type']);
        self::assertSame(41, $events[0]['mail_log_id']);
        self::assertSame('sg-message-1', $events[0]['transport_message_id']);
        self::assertSame('sg-event-1', $events[0]['provider_event_id']);
        self::assertSame('spam_report', $events[1]['event_type']);
        self::assertSame(42, $events[1]['mail_log_id']);
        self::assertSame('smtp-message-2', $events[1]['transport_message_id']);
        self::assertSame('sg-event-2', $events[1]['provider_event_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testAhaSendAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(AhaSendWebhookAdapter::class);
        $secret = 'ahasend-secret';
        $body = wp_json_encode([
            'type' => 'message.transient_error',
            'timestamp' => '2026-03-30T00:00:00.123456789Z',
            'data' => [
                'id' => 'ahasend-message-1',
                'recipient' => 'recipient@example.com',
                'mail_log_id' => 49,
            ],
        ]);

        self::assertIsString($body);

        $request = new WP_REST_Request('POST', '/ahasend');
        $request->set_body($body);
        $request->set_header('webhook-id', 'ahasend-webhook-id');
        $request->set_header('webhook-timestamp', '1711756800');
        $request->set_header('webhook-signature', $this->signAhaSendPayload($body, 'ahasend-webhook-id', '1711756800', $secret));

        $connection = new Connection(
            id: null,
            profileKey: 'ahasend',
            name: 'AhaSend adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('deferred', $events[0]['event_type']);
        self::assertSame(49, $events[0]['mail_log_id']);
        self::assertSame('ahasend-message-1', $events[0]['transport_message_id']);
        self::assertSame('2026-03-30 00:00:00', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testSendLayerAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(SendLayerWebhookAdapter::class);
        $secret = 'sendlayer-secret';
        $request = new WP_REST_Request('POST', '/sendlayer');
        $request->set_body(wp_json_encode([
            'Signature' => [
                'Timestamp' => 1711756800,
                'Token' => 'sendlayer-token',
                'Signature' => hash_hmac('sha1', '1711756800sendlayer-token', $secret),
            ],
            'EventData' => [
                'Event' => 'clicked',
                'MessageID' => 'sendlayer-message-1',
                'To' => 'recipient@example.com',
                'IPAddress' => '10.30.126.18',
            ],
        ]));

        $connection = new Connection(
            id: null,
            profileKey: 'sendlayer',
            name: 'SendLayer adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('click', $events[0]['event_type']);
        self::assertSame('sendlayer-message-1', $events[0]['transport_message_id']);
        self::assertSame('2024-03-30 00:00:00', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testBrevoAdapterVerifiesAllowlistedRequestAndParsesPayload(): void
    {
        $adapter = $this->container()->get(BrevoWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/brevo');
        $request->set_body(wp_json_encode([
            'event' => 'complaint',
            'email' => 'brevo@example.com',
            'message-id' => 'brevo-message-1',
            'mail_log_id' => 51,
            'ts_event' => 1700000150,
        ]));

        $connection = new Connection(
            id: null,
            profileKey: 'brevo',
            name: 'Brevo adapter test',
            webhookEnabled: true,
        );

        $events = $this->withRemoteAddr('127.0.0.1', function () use ($adapter, $request, $connection): array {
            self::assertTrue($adapter->verify($request, $connection));

            return $adapter->parse($request, $connection);
        });

        self::assertCount(1, $events);
        self::assertSame('complaint', $events[0]['event_type']);
        self::assertSame(51, $events[0]['mail_log_id']);
        self::assertSame('brevo-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testPostmarkAdapterParsesSingleEventPayload(): void
    {
        $adapter = $this->container()->get(PostmarkWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/postmark');
        $request->set_body(wp_json_encode([
            'RecordType' => 'SubscriptionChange',
            'MessageID' => 'pm-message-1',
            'mail_log_id' => 55,
            'ChangedAt' => '2026-03-30T00:00:00Z',
        ]));

        $connection = new Connection(
            id: null,
            profileKey: 'postmark',
            name: 'Postmark adapter test',
            webhookEnabled: true,
        );

        $events = $this->withRemoteAddr('127.0.0.1', function () use ($adapter, $request, $connection): array {
            self::assertTrue($adapter->verify($request, $connection));

            return $adapter->parse($request, $connection);
        });

        self::assertCount(1, $events);
        self::assertSame('subscription_change', $events[0]['event_type']);
        self::assertSame(55, $events[0]['mail_log_id']);
        self::assertSame('pm-message-1', $events[0]['transport_message_id']);
        self::assertSame('2026-03-30 00:00:00', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testResendAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(ResendWebhookAdapter::class);
        $secret = 'resend-shared-secret';
        $body = wp_json_encode([
            'type' => 'email.delivered',
            'data' => [
                'email_id' => 're-message-1',
                'mail_log_id' => 77,
                'created_at' => '2026-03-30T00:00:00Z',
            ],
        ]);

        self::assertIsString($body);

        $request = new WP_REST_Request('POST', '/resend');
        $request->set_body($body);

        $svixId = 'msg_123';
        $svixTimestamp = '1711756800';
        $signature = base64_encode(hash_hmac('sha256', $svixId . '.' . $svixTimestamp . '.' . $body, $secret, true));

        $request->set_header('svix-id', $svixId);
        $request->set_header('svix-timestamp', $svixTimestamp);
        $request->set_header('svix-signature', 'v1=' . $signature);

        $connection = new Connection(
            id: null,
            profileKey: 'resend',
            name: 'Resend adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('email_delivered', $events[0]['event_type']);
        self::assertSame(77, $events[0]['mail_log_id']);
        self::assertSame('re-message-1', $events[0]['transport_message_id']);
        self::assertSame('2026-03-30 00:00:00', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testToSendAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(ToSendWebhookAdapter::class);
        $secret = 'tosend-shared-secret';
        $body = wp_json_encode([
            'type' => 'bounced',
            'data' => [
                'email' => 'recipient@example.com',
                'is_hard_bounce' => false,
                'mail_log_id' => 201,
                'message_id' => 'tosend-message-1',
                'timestamp' => '2026-04-18T10:29:58.000Z',
            ],
            'created_at' => '2026-04-18T10:30:00.000Z',
        ]);

        self::assertIsString($body);

        $request = new WP_REST_Request('POST', '/tosend');
        $request->set_body($body);
        $request->set_header('x-tosend-signature', 'sha256=' . hash_hmac('sha256', $body, $secret));
        $request->set_header('x-tosend-event', 'bounced');

        $connection = new Connection(
            id: null,
            profileKey: 'tosend',
            name: 'toSend adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('soft_bounce', $events[0]['event_type']);
        self::assertSame(201, $events[0]['mail_log_id']);
        self::assertSame('tosend-message-1', $events[0]['transport_message_id']);
        self::assertNull($events[0]['provider_event_id']);
        self::assertSame('2026-04-18 10:29:58', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailgunAdapterAcceptsParamFallbackWithSignatureData(): void
    {
        $adapter = $this->container()->get(MailgunWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/mailgun');
        $request->set_body('');
        $timestamp = '1700000200';
        $token = 'mailgun-token';
        $secret = 'mailgun-secret';

        $request->set_param('signature', [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => hash_hmac('sha256', $timestamp . $token, $secret),
        ]);
        $request->set_param('event-data', [
            'event' => 'complained',
            'message' => [
                'headers' => [
                    'message-id' => 'mailgun-message-1',
                ],
            ],
            'metadata' => [
                'mail_log_id' => 88,
            ],
            'timestamp' => 1700000200,
        ]);

        $connection = new Connection(
            id: null,
            profileKey: 'mailgun',
            name: 'Mailgun adapter edge test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('complained', $events[0]['event_type']);
        self::assertSame(88, $events[0]['mail_log_id']);
        self::assertSame('mailgun-message-1', $events[0]['transport_message_id']);
        self::assertSame('2023-11-14 22:16:40', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailgunAdapterRejectsMissingSignatureData(): void
    {
        $adapter = $this->container()->get(MailgunWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/mailgun');
        $request->set_body('');
        $request->set_param('event-data', [
            'event' => 'delivered',
            'id' => 'mailgun-message-2',
        ]);

        $connection = new Connection(
            id: null,
            profileKey: 'mailgun',
            name: 'Mailgun unsigned adapter test',
            secrets: ['webhook_secret' => 'mailgun-secret'],
            webhookEnabled: true,
        );

        self::assertFalse($adapter->verify($request, $connection));
    }

    /**
     * @since 0.1.0
     */
    public function testSmtp2goAdapterParsesJsonPayload(): void
    {
        $adapter = $this->container()->get(Smtp2goWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/smtp2go');
        $request->set_body(wp_json_encode([
            'event' => 'spam',
            'time' => 1700000900,
            'email_id' => 'smtp2go-message-1',
            'x_jooosi_mail_mail_log_id' => 161,
        ]));

        $connection = new Connection(
            id: null,
            profileKey: 'smtp2go',
            name: 'SMTP2GO adapter test',
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('spam_complaint', $events[0]['event_type']);
        self::assertSame(161, $events[0]['mail_log_id']);
        self::assertSame('smtp2go-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testSparkPostAdapterParsesWebhookBatch(): void
    {
        $adapter = $this->container()->get(SparkPostWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/sparkpost');
        $request->set_body(wp_json_encode([[
            'msys' => [
                'message_event' => [
                    'type' => 'spam_complaint',
                    'message_id' => 'sparkpost-message-1',
                    'timestamp' => 1700001000,
                    'rcpt_meta' => ['mail_log_id' => 166],
                ],
            ],
        ]]));

        $connection = new Connection(
            id: null,
            profileKey: 'sparkpost',
            name: 'SparkPost adapter test',
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('spam_complaint', $events[0]['event_type']);
        self::assertSame(166, $events[0]['mail_log_id']);
        self::assertSame('sparkpost-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testBirdAdapterParsesWebhookBatch(): void
    {
        $adapter = $this->container()->get(BirdWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/bird');
        $request->set_body(wp_json_encode([[
            'msys' => [
                'track_event' => [
                    'type' => 'initial_open',
                    'message_id' => 'bird-message-1',
                    'timestamp' => 1700001100,
                    'rcpt_meta' => ['mail_log_id' => 171],
                ],
            ],
        ]]));

        $connection = new Connection(
            id: null,
            profileKey: 'bird',
            name: 'Bird adapter test',
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('open', $events[0]['event_type']);
        self::assertSame(171, $events[0]['mail_log_id']);
        self::assertSame('bird-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailtrapAdapterParsesBatchEvents(): void
    {
        $adapter = $this->container()->get(MailtrapWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/mailtrap');
        $request->set_body(wp_json_encode([
            'events' => [
                [
                    'event' => 'delivery',
                    'message_id' => 'mailtrap-message-1',
                    'email' => 'first@example.com',
                    'timestamp' => 1700000300,
                    'custom_variables' => ['mail_log_id' => 99],
                ],
                [
                    'event' => 'soft bounce',
                    'message_id' => 'mailtrap-message-2',
                    'email' => 'second@example.com',
                    'timestamp' => 1700000400,
                    'custom_variables' => ['jooosi_mail_mail_log_id' => 100],
                ],
            ],
        ]));

        $connection = new Connection(
            id: null,
            profileKey: 'mailtrap',
            name: 'Mailtrap adapter test',
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(2, $events);
        self::assertSame('delivery', $events[0]['event_type']);
        self::assertSame(99, $events[0]['mail_log_id']);
        self::assertSame('mailtrap-message-1', $events[0]['transport_message_id']);
        self::assertSame('soft_bounce', $events[1]['event_type']);
        self::assertSame(100, $events[1]['mail_log_id']);
        self::assertSame('mailtrap-message-2', $events[1]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailerSendAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(MailerSendWebhookAdapter::class);
        $secret = 'mailersend-secret';
        $body = wp_json_encode([
            'type' => 'activity.delivered',
            'created_at' => '2026-03-30T00:00:00.000000Z',
            'data' => [
                'email' => [
                    'message' => ['id' => 'mailersend-message-1'],
                    'recipient' => ['email' => 'recipient@example.com'],
                    'variables' => ['mail_log_id' => 111],
                ],
            ],
        ]);

        self::assertIsString($body);

        $request = new WP_REST_Request('POST', '/mailersend');
        $request->set_body($body);
        $request->set_header('signature', hash_hmac('sha256', $body, $secret));

        $connection = new Connection(
            id: null,
            profileKey: 'mailersend',
            name: 'MailerSend adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('activity_delivered', $events[0]['event_type']);
        self::assertSame(111, $events[0]['mail_log_id']);
        self::assertSame('mailersend-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailomatAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(MailomatWebhookAdapter::class);
        $secret = 'mailomat-secret';
        $webhookId = '1d958822-0934-4c6a-abc8-5defec4baa64';
        $event = 'delivered';
        $timestamp = '1718004211';
        $body = wp_json_encode([
            'id' => $webhookId,
            'eventType' => $event,
            'occurredAt' => '2024-06-10T09:23:31+02:00',
            'messageUuid' => '1684f904-596e-4d9f-ba16-ead5c4d27957',
            'messageId' => 'mailomat-message-1@s.mailomat.swiss',
            'recipient' => 'recipient@example.com',
            'payload' => [
                'metadata' => [
                    'mail_log_id' => 191,
                ],
            ],
        ]);

        self::assertIsString($body);

        $request = new WP_REST_Request('POST', '/mailomat');
        $request->set_body($body);
        $request->set_header('x-mom-webhook-event', $event);
        $request->set_header('x-mom-webhook-id', $webhookId);
        $request->set_header('x-mom-webhook-timestamp', $timestamp);
        $request->set_header('x-mom-webhook-signature', $this->signMailomatPayload($webhookId, $event, $timestamp, $secret));

        $connection = new Connection(
            id: null,
            profileKey: 'mailomat',
            name: 'Mailomat adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('delivered', $events[0]['event_type']);
        self::assertSame(191, $events[0]['mail_log_id']);
        self::assertSame('mailomat-message-1@s.mailomat.swiss', $events[0]['transport_message_id']);
        self::assertSame('2024-06-10 07:23:31', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testMailjetAdapterParsesPayloadMetadata(): void
    {
        $adapter = $this->container()->get(MailjetWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/mailjet');
        $request->set_body(wp_json_encode([
            'event' => 'spam',
            'MessageID' => 'mailjet-message-1',
            'email' => 'recipient@example.com',
            'time' => 1700000500,
            'Payload' => wp_json_encode(['mail_log_id' => 121]),
        ]));

        $connection = new Connection(
            id: null,
            profileKey: 'mailjet',
            name: 'Mailjet adapter test',
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('spam', $events[0]['event_type']);
        self::assertSame(121, $events[0]['mail_log_id']);
        self::assertSame('mailjet-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testMandrillAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(MandrillWebhookAdapter::class);
        $secret = 'mandrill-secret';
        $request = new WP_REST_Request('POST', '/jooosi-mail/v1/webhook/1');
        $eventsPayload = wp_json_encode([
            [
                'event' => 'delivered',
                'msg' => [
                    '_id' => 'mandrill-message-1',
                    'ts' => 1700000600,
                    'email' => 'recipient@example.com',
                    'metadata' => ['mail_log_id' => 131],
                    'tags' => [],
                ],
            ],
        ]);

        self::assertIsString($eventsPayload);

        $request->set_param('mandrill_events', $eventsPayload);
        $request->set_header('x-mandrill-signature', $this->signMandrillPayload($request, $eventsPayload, $secret));

        $connection = new Connection(
            id: null,
            profileKey: 'mandrill',
            name: 'Mandrill adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('delivered', $events[0]['event_type']);
        self::assertSame(131, $events[0]['mail_log_id']);
        self::assertSame('mandrill-message-1', $events[0]['transport_message_id']);
    }

    /**
     * @since 0.1.0
     */
    public function testSweegoAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(SweegoWebhookAdapter::class);
        $rawSecret = 'sweego-secret';
        $encodedSecret = base64_encode($rawSecret);
        $body = wp_json_encode([
            'event_type' => 'hard_bounce',
            'timestamp' => '2026-03-30T00:00:00+00:00',
            'recipient' => 'recipient@example.com',
            'mail_log_id' => 141,
            'headers' => [
                'x-transaction-id' => 'sweego-message-1',
            ],
        ]);

        self::assertIsString($body);

        $request = new WP_REST_Request('POST', '/sweego');
        $request->set_body($body);
        $request->set_header('webhook-id', 'sweego-webhook-id');
        $request->set_header('webhook-timestamp', '1711756800');
        $request->set_header('webhook-signature', $this->signSweegoPayload($body, 'sweego-webhook-id', '1711756800', $rawSecret));

        $connection = new Connection(
            id: null,
            profileKey: 'sweego',
            name: 'Sweego adapter test',
            secrets: ['webhook_secret' => $encodedSecret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('hard_bounce', $events[0]['event_type']);
        self::assertSame(141, $events[0]['mail_log_id']);
        self::assertSame('sweego-message-1', $events[0]['transport_message_id']);
        self::assertSame('2026-03-30 00:00:00', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testZeptoMailAdapterVerifiesAndParsesSignedPayload(): void
    {
        $adapter = $this->container()->get(ZeptoMailWebhookAdapter::class);
        $secret = 'zeptomail-secret';
        $payload = [
            'event_name' => 'hard_bounce',
            'event_message' => [
                'request_id' => 'zepto-request-1',
                'email_info' => [
                    'email_reference' => 'zepto-reference-1',
                    'client_reference' => '151',
                    'processed_time' => '2026-03-30T00:00:00Z',
                ],
            ],
            'event_data' => [
                'details' => [
                    'time' => '2026-03-30T00:01:00Z',
                    'reason' => 'Mailbox unavailable',
                ],
            ],
        ];
        $payloadJson = wp_json_encode($payload);
        $timestamp = (string) round(microtime(true) * 1000);

        self::assertIsString($payloadJson);

        $request = new WP_REST_Request('POST', '/zeptomail');
        $request->set_body('data=' . rawurlencode($payloadJson));
        $request->set_header('producer-signature', $this->signZeptoMailPayload($payloadJson, $timestamp, $secret));

        $connection = new Connection(
            id: null,
            profileKey: 'zeptomail',
            name: 'ZeptoMail adapter test',
            secrets: ['webhook_secret' => $secret],
            webhookEnabled: true,
        );

        self::assertTrue($adapter->verify($request, $connection));

        $events = $adapter->parse($request, $connection);

        self::assertCount(1, $events);
        self::assertSame('hard_bounce', $events[0]['event_type']);
        self::assertSame(151, $events[0]['mail_log_id']);
        self::assertSame('zepto-reference-1', $events[0]['transport_message_id']);
        self::assertSame('zepto-request-1', $events[0]['provider_event_id']);
        self::assertSame('2026-03-30 00:01:00', $events[0]['occurred_at']);
    }

    /**
     * @since 0.1.0
     */
    public function testGenericAdapterFallsBackToRawRequestPayload(): void
    {
        $adapter = $this->container()->get(GenericWebhookAdapter::class);
        $request = new WP_REST_Request('POST', '/generic');
        $request->set_header('x-jooosi-mail-source', 'generic');
        $request->set_body('{"raw":true}');
        $request->set_param('event', 'provider callback');
        $request->set_param('message_id', 'generic-message-1');

        $events = $adapter->parse($request, new Connection(
            id: null,
            profileKey: 'unknown-provider',
            name: 'Generic adapter test',
        ));

        self::assertCount(1, $events);
        self::assertSame('provider callback', $events[0]['event_type']);
        self::assertSame('generic-message-1', $events[0]['transport_message_id']);
        self::assertSame('{"raw":true}', $events[0]['payload']['body']);
        self::assertSame('generic', $events[0]['payload']['headers']['x_jooosi_mail_source'][0] ?? null);
    }

    /**
     * @return array{private: string, public: string}
     *
     * @since 0.1.0
     */
    private function generateSendGridKeyPair(): array
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        self::assertNotFalse($keyPair);

        $privateKey = '';
        self::assertTrue(openssl_pkey_export($keyPair, $privateKey));

        $details = openssl_pkey_get_details($keyPair);

        self::assertIsArray($details);
        self::assertIsString($details['key'] ?? null);

        $publicKey = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $details['key']);

        self::assertIsString($publicKey);
        self::assertNotSame('', $publicKey);

        return [
            'private' => $privateKey,
            'public' => $publicKey,
        ];
    }

    /**
     * @since 0.1.0
     */
    private function signSendGridPayload(string $payload, string $timestamp, string $privateKey): string
    {
        $signature = '';

        self::assertTrue(openssl_sign($timestamp . $payload, $signature, $privateKey, OPENSSL_ALGO_SHA256));

        return base64_encode($signature);
    }

    /**
     * @since 0.1.0
     */
    private function signMandrillPayload(WP_REST_Request $request, string $eventsPayload, string $secret): string
    {
        $signedData = rest_url(ltrim($request->get_route(), '/')) . 'mandrill_events' . $eventsPayload;

        return base64_encode(hash_hmac('sha1', $signedData, $secret, true));
    }

    /**
     * @since 0.1.0
     */
    private function signAhaSendPayload(string $payload, string $webhookId, string $timestamp, string $secret): string
    {
        $hash = hash_hmac('sha256', $webhookId . '.' . $timestamp . '.' . $payload, $secret);

        return 'v1,' . base64_encode(pack('H*', $hash));
    }

    /**
     * @since 0.1.0
     */
    private function signMailomatPayload(string $webhookId, string $event, string $timestamp, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $webhookId . '.' . $event . '.' . $timestamp, $secret);
    }

    /**
     * @since 0.1.0
     */
    private function signZeptoMailPayload(string $payload, string $timestamp, string $secret): string
    {
        $signature = rawurlencode(base64_encode(hash_hmac('sha256', $payload, $secret, true)));

        return sprintf('ts=%s;s=%s;s-algorithm=HmacSHA256', $timestamp, $signature);
    }

    /**
     * @since 0.1.0
     */
    private function signSweegoPayload(string $payload, string $webhookId, string $timestamp, string $secret): string
    {
        return base64_encode(hash_hmac('sha256', $webhookId . '.' . $timestamp . '.' . $payload, $secret, true));
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @since 0.1.0
     */
    private function withRemoteAddr(string $remoteAddr, callable $callback): mixed
    {
        $originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = $remoteAddr;

        try {
            return $callback();
        } finally {
            if (is_string($originalRemoteAddr)) {
                $_SERVER['REMOTE_ADDR'] = $originalRemoteAddr;
            } else {
                unset($_SERVER['REMOTE_ADDR']);
            }
        }
    }
}
