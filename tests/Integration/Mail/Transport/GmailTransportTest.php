<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Mail\Transport;

use JooosiMail\Mail\Transport\Bridge\Gmail\GmailTokenManager;
use JooosiMail\Mail\Transport\Bridge\Gmail\Transport\GmailApiTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Covers the custom Gmail token and API transports.
 *
 * @since 0.1.0
 */
final class GmailTransportTest extends TestCase
{
    /**
     * @since 0.1.0
     */
    public function testGmailTokenManagerRetrievesAndCachesAccessToken(): void
    {
        $calls = 0;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$calls): ResponseInterface {
            ++$calls;

            self::assertSame('POST', $method);
            self::assertSame('https://oauth2.googleapis.com/token', $url);

            parse_str((string) ($options['body'] ?? ''), $body);

            self::assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $body['grant_type'] ?? null);
            self::assertIsString($body['assertion'] ?? null);

            $jwtParts = explode('.', (string) $body['assertion']);

            self::assertCount(3, $jwtParts);

            $header = json_decode($this->base64UrlDecode($jwtParts[0]), true);
            $claims = json_decode($this->base64UrlDecode($jwtParts[1]), true);

            self::assertSame('RS256', $header['alg'] ?? null);
            self::assertSame('service@example.iam.gserviceaccount.com', $claims['iss'] ?? null);
            self::assertSame('sender@example.com', $claims['sub'] ?? null);
            self::assertSame('https://www.googleapis.com/auth/gmail.send', $claims['scope'] ?? null);

            return new JsonMockResponse(['access_token' => 'gmail-access-token', 'expires_in' => 3600]);
        });

        $tokenManager = new GmailTokenManager(
            'service@example.iam.gserviceaccount.com',
            $this->generatePrivateKey(),
            'sender@example.com',
            $client,
        );

        self::assertSame('gmail-access-token', $tokenManager->getToken());
        self::assertSame('gmail-access-token', $tokenManager->getToken());
        self::assertSame(1, $calls);
    }

    /**
     * @since 0.1.0
     */
    public function testGmailApiTransportSendsRawMimePayload(): void
    {
        $tokenManager = new GmailTokenManager(
            'service@example.iam.gserviceaccount.com',
            $this->generatePrivateKey(),
            'sender@example.com',
            new MockHttpClient(static fn (): ResponseInterface => new JsonMockResponse(['access_token' => 'gmail-access-token', 'expires_in' => 3600])),
        );
        $apiClient = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertSame('https://gmail.googleapis.com/gmail/v1/users/sender%40example.com/messages/send', $url);
            self::assertStringContainsString('Bearer gmail-access-token', $options['normalized_headers']['authorization'][0] ?? '');

            $payload = json_decode((string) ($options['body'] ?? ''), true);

            self::assertIsArray($payload);
            self::assertIsString($payload['raw'] ?? null);

            $rawMessage = $this->base64UrlDecode($payload['raw']);

            self::assertStringContainsString('From: Sender <sender@example.com>', $rawMessage);
            self::assertStringContainsString('To: Recipient <recipient@example.com>', $rawMessage);
            self::assertStringContainsString('Subject: Hello Gmail', $rawMessage);
            self::assertStringContainsString('Hello from Gmail API.', $rawMessage);

            return new JsonMockResponse(['id' => 'gmail-message-1'], ['http_code' => 200]);
        });
        $transport = new GmailApiTransport($tokenManager, 'sender@example.com', $apiClient);
        $email = (new Email())
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->subject('Hello Gmail')
            ->text('Hello from Gmail API.');

        $sentMessage = $transport->send($email);

        self::assertSame('gmail-message-1', $sentMessage->getMessageId());
    }

    /**
     * @since 0.1.0
     */
    public function testGmailApiTransportThrowsForErrorResponses(): void
    {
        $tokenManager = new GmailTokenManager(
            'service@example.iam.gserviceaccount.com',
            $this->generatePrivateKey(),
            'sender@example.com',
            new MockHttpClient(static fn (): ResponseInterface => new JsonMockResponse(['access_token' => 'gmail-access-token', 'expires_in' => 3600])),
        );
        $transport = new GmailApiTransport(
            $tokenManager,
            'sender@example.com',
            new MockHttpClient(static fn (): ResponseInterface => new MockResponse('{"error":{"message":"Quota exceeded"}}', ['http_code' => 429])),
        );
        $email = (new Email())
            ->from(new Address('sender@example.com', 'Sender'))
            ->to(new Address('recipient@example.com', 'Recipient'))
            ->subject('Hello Gmail')
            ->text('Hello from Gmail API.');

        $this->expectException(HttpTransportException::class);
        $this->expectExceptionMessage('Unable to send email via Gmail API: Quota exceeded');

        $transport->send($email);
    }

    /**
     * @since 0.1.0
     */
    private function generatePrivateKey(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        self::assertNotFalse($key);

        $privateKey = '';

        self::assertTrue(openssl_pkey_export($key, $privateKey));

        return $privateKey;
    }

    /**
     * @since 0.1.0
     */
    private function base64UrlDecode(string $payload): string
    {
        $remainder = strlen($payload) % 4;

        if ($remainder !== 0) {
            $payload .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);

        self::assertIsString($decoded);

        return $decoded;
    }
}
