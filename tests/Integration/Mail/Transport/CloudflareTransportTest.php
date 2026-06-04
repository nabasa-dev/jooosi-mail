<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Mail\Transport;

use OmniMail\Mail\Transport\Bridge\Cloudflare\Transport\CloudflareApiTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Covers the custom Cloudflare Email Service transport.
 *
 * @since 0.1.0
 */
final class CloudflareTransportTest extends TestCase
{
    /**
     * @since 0.1.0
     */
    public function testCloudflareApiTransportSendsRestPayload(): void
    {
        $transport = new CloudflareApiTransport('account-123', 'token-123', new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertSame('https://api.cloudflare.com/client/v4/accounts/account-123/email/sending/send', $url);
            self::assertStringContainsString('Bearer token-123', $options['normalized_headers']['authorization'][0] ?? '');

            $payload = json_decode((string) ($options['body'] ?? ''), true);

            self::assertIsArray($payload);
            self::assertSame([
                'address' => 'sender@example.com',
                'name' => 'Sender',
            ], $payload['from'] ?? null);
            self::assertSame(['recipient@example.com'], $payload['to'] ?? null);
            self::assertSame(['cc@example.com'], $payload['cc'] ?? null);
            self::assertSame(['bcc@example.com'], $payload['bcc'] ?? null);
            self::assertSame([
                'address' => 'reply@example.com',
                'name' => 'Reply Desk',
            ], $payload['reply_to'] ?? null);
            self::assertSame('Hello Cloudflare', $payload['subject'] ?? null);
            self::assertSame('Hello from Cloudflare.', $payload['text'] ?? null);
            self::assertSame('<p>Hello from Cloudflare.</p>', $payload['html'] ?? null);
            self::assertSame('<original@example.com>', $payload['headers']['In-Reply-To'] ?? null);
            self::assertSame('campaign-123', $payload['headers']['X-Campaign-ID'] ?? null);
            self::assertArrayNotHasKey('From', $payload['headers'] ?? []);
            self::assertArrayNotHasKey('Subject', $payload['headers'] ?? []);
            self::assertCount(1, $payload['attachments'] ?? []);
            self::assertSame('report.txt', $payload['attachments'][0]['filename'] ?? null);
            self::assertSame('text/plain', $payload['attachments'][0]['type'] ?? null);
            self::assertSame('attachment', $payload['attachments'][0]['disposition'] ?? null);

            return new JsonMockResponse([
                'success' => true,
                'errors' => [],
                'messages' => [],
                'result' => [
                    'delivered' => ['recipient@example.com'],
                    'permanent_bounces' => [],
                    'queued' => [],
                ],
            ], ['http_code' => 200]);
        }));
        $email = (new Email())
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo(new Address('reply@example.com', 'Reply Desk'))
            ->subject('Hello Cloudflare')
            ->text('Hello from Cloudflare.')
            ->html('<p>Hello from Cloudflare.</p>')
            ->attach('report body', 'report.txt', 'text/plain');

        $email->getHeaders()->addTextHeader('In-Reply-To', '<original@example.com>');
        $email->getHeaders()->addTextHeader('X-Campaign-ID', 'campaign-123');

        $sentMessage = $transport->send($email);

        self::assertNotSame('', $sentMessage->getMessageId());
    }

    /**
     * @since 0.1.0
     */
    public function testCloudflareApiTransportThrowsForApiErrors(): void
    {
        $transport = new CloudflareApiTransport('account-123', 'token-123', new MockHttpClient(static fn (): ResponseInterface => new JsonMockResponse([
            'success' => false,
            'errors' => [
                ['code' => 10001, 'message' => 'email.sending.error.invalid_request_schema'],
            ],
            'messages' => [],
            'result' => null,
        ], ['http_code' => 400])));
        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Broken Cloudflare email')
            ->text('Broken payload.');

        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('Unable to send email via Cloudflare Email Service: email.sending.error.invalid_request_schema');

        $transport->send($email);
    }
}
