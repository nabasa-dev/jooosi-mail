<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Mail\Transport;

use JooosiMail\Mail\Transport\Bridge\ToSend\Transport\ToSendApiTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Covers the custom toSend REST API transport.
 *
 * @since 0.1.0
 */
final class ToSendTransportTest extends TestCase
{
    /**
     * @since 0.1.0
     */
    public function testToSendApiTransportSendsRestPayload(): void
    {
        $transport = new ToSendApiTransport('tosend-key-123', new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertSame('https://api.tosend.com/v2/emails', $url);
            self::assertStringContainsString('Bearer tosend-key-123', $options['normalized_headers']['authorization'][0] ?? '');

            $payload = json_decode((string) ($options['body'] ?? ''), true);

            self::assertIsArray($payload);
            self::assertSame([
                'email' => 'sender@example.com',
                'name' => 'Sender',
            ], $payload['from'] ?? null);
            self::assertSame([[
                'email' => 'recipient@example.com',
                'name' => 'Recipient',
            ]], $payload['to'] ?? null);
            self::assertSame([[
                'email' => 'cc@example.com',
            ]], $payload['cc'] ?? null);
            self::assertSame([[
                'email' => 'bcc@example.com',
            ]], $payload['bcc'] ?? null);
            self::assertSame([
                'email' => 'reply@example.com',
                'name' => 'Reply Desk',
            ], $payload['reply_to'] ?? null);
            self::assertSame('Hello toSend', $payload['subject'] ?? null);
            self::assertSame('Hello from toSend.', $payload['text'] ?? null);
            self::assertSame('<p>Hello from toSend.</p>', $payload['html'] ?? null);
            self::assertSame('<original@example.com>', $payload['headers']['In-Reply-To'] ?? null);
            self::assertSame('campaign-123', $payload['headers']['X-Campaign-ID'] ?? null);
            self::assertArrayNotHasKey('From', $payload['headers'] ?? []);
            self::assertArrayNotHasKey('Subject', $payload['headers'] ?? []);
            self::assertCount(1, $payload['attachments'] ?? []);
            self::assertSame('report.txt', $payload['attachments'][0]['name'] ?? null);
            self::assertSame('text/plain', $payload['attachments'][0]['type'] ?? null);

            return new JsonMockResponse([
                'message_id' => 'tosend-message-1',
            ], ['http_code' => 200]);
        }));
        $email = (new Email())
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo(new Address('reply@example.com', 'Reply Desk'))
            ->subject('Hello toSend')
            ->text('Hello from toSend.')
            ->html('<p>Hello from toSend.</p>')
            ->attach('report body', 'report.txt', 'text/plain');

        $email->getHeaders()->addTextHeader('In-Reply-To', '<original@example.com>');
        $email->getHeaders()->addTextHeader('X-Campaign-ID', 'campaign-123');

        $sentMessage = $transport->send($email);

        self::assertSame('tosend-message-1', $sentMessage->getMessageId());
    }

    /**
     * @since 0.1.0
     */
    public function testToSendApiTransportThrowsForApiErrors(): void
    {
        $transport = new ToSendApiTransport('tosend-key-123', new MockHttpClient(static fn (): ResponseInterface => new JsonMockResponse([
            'status_code' => 422,
            'error_type' => 'validation_error',
            'message' => 'Subject and either html or text content are required.',
            'errors' => [
                'content' => [
                    'required' => 'Either html or text content is required.',
                ],
            ],
        ], ['http_code' => 422])));
        $email = (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Broken toSend email')
            ->text('Broken toSend email body.');

        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('Unable to send email via toSend: Subject and either html or text content are required.');

        $transport->send($email);
    }
}
