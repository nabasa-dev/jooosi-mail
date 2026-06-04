<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Mail\WordPress;

use OmniMail\Mail\WordPress\WpMailPayloadNormalizer;
use WP_UnitTestCase;

/**
 * Covers WordPress mail payload normalization with a real WordPress runtime.
 *
 * @since 0.1.0
 */
final class WpMailPayloadNormalizerTest extends WP_UnitTestCase
{
    /**
     * @since 0.1.0
     */
    public function testNormalizeUsesWordPressDefaultsWhenFromHeaderIsMissing(): void
    {
        $previousAdminEmail = get_option('admin_email');
        $previousBlogName = get_option('blogname');

        update_option('admin_email', 'admin@example.test');
        update_option('blogname', 'Omni Mail Tests');

        try {
            $normalizer = new WpMailPayloadNormalizer();
            $request = $normalizer->normalize([
                'to' => 'recipient@example.test',
                'subject' => 'Hello',
                'message' => 'Plain text body',
            ]);

            self::assertCount(1, $request->from);
            self::assertSame('admin@example.test', $request->from[0]->address);
            self::assertSame('Omni Mail Tests', $request->from[0]->name);
            self::assertCount(1, $request->to);
            self::assertSame('recipient@example.test', $request->to[0]->address);
            self::assertSame('Plain text body', $request->textBody);
            self::assertNull($request->htmlBody);
            self::assertSame('wp_mail', $request->source);
        } finally {
            update_option('admin_email', $previousAdminEmail);
            update_option('blogname', $previousBlogName);
        }
    }

    /**
     * @since 0.1.0
     */
    public function testNormalizeParsesHtmlHeadersAddressesAndAttachments(): void
    {
        $normalizer = new WpMailPayloadNormalizer();
        $message = '<p>Hello <strong>World</strong></p>';

        $request = $normalizer->normalize([
            'to' => [
                '"Primary Recipient" <primary@example.test>',
                'secondary@example.test',
            ],
            'subject' => 'HTML message',
            'message' => $message,
            'headers' => [
                'From: "Sender Name" <sender@example.test>',
                'Cc: cc-one@example.test, "Copy Two" <cc-two@example.test>',
                'Bcc: hidden@example.test',
                'Reply-To: reply@example.test',
                'Content-Type: text/html',
                'X-Omni-Mail: enabled',
            ],
            'attachments' => [
                '/tmp/report.pdf',
                '',
                100,
                '/tmp/archive.zip',
            ],
        ]);

        self::assertCount(1, $request->from);
        self::assertSame('sender@example.test', $request->from[0]->address);
        self::assertSame('Sender Name', $request->from[0]->name);
        self::assertCount(2, $request->to);
        self::assertSame('primary@example.test', $request->to[0]->address);
        self::assertSame('Primary Recipient', $request->to[0]->name);
        self::assertSame('secondary@example.test', $request->to[1]->address);
        self::assertCount(2, $request->cc);
        self::assertSame('cc-one@example.test', $request->cc[0]->address);
        self::assertSame('cc-two@example.test', $request->cc[1]->address);
        self::assertSame('Copy Two', $request->cc[1]->name);
        self::assertCount(1, $request->bcc);
        self::assertSame('hidden@example.test', $request->bcc[0]->address);
        self::assertCount(1, $request->replyTo);
        self::assertSame('reply@example.test', $request->replyTo[0]->address);
        self::assertNull($request->textBody);
        self::assertSame($message, $request->htmlBody);
        self::assertCount(2, $request->attachments);
        self::assertSame('/tmp/report.pdf', $request->attachments[0]->path);
        self::assertSame('/tmp/archive.zip', $request->attachments[1]->path);
        self::assertSame('text/html', $request->headers['Content-Type']);
        self::assertSame('enabled', $request->headers['X-Omni-Mail']);
    }

    /**
     * @since 0.1.0
     */
    public function testNormalizeTreatsHtmlContentTypeWithCharsetAsHtml(): void
    {
        $normalizer = new WpMailPayloadNormalizer();
        $message = '<p>Hello <strong>Charset</strong></p>';

        $request = $normalizer->normalize([
            'to' => 'recipient@example.test',
            'subject' => 'HTML message with charset',
            'message' => $message,
            'headers' => [
                'Content-Type: text/html; charset=UTF-8',
            ],
        ]);

        self::assertNull($request->textBody);
        self::assertSame($message, $request->htmlBody);
        self::assertSame('text/html; charset=UTF-8', $request->headers['Content-Type']);
    }
}
