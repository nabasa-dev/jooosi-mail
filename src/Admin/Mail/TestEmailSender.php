<?php

declare(strict_types=1);

namespace OmniMail\Admin\Mail;

use OmniMail\Discovery\Attribute\Service;
use OmniMail\Mail\ValueObject\MailRequest;

/**
 * Sends generated Omni Mail diagnostic test messages.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class TestEmailSender
{
    /**
     * @since 0.1.0
     */
    public function __construct(
        private TestEmailTemplateRenderer $templateRenderer,
    ) {
    }

    /**
     * Send a generated diagnostic test email through `wp_mail()`.
     *
     * @since 0.1.0
     */
    public function send(string $to, ?string $subject = null, ?int $connectionId = null): bool
    {
        $subject = sanitize_text_field((string) ($subject ?? ''));

        if ($subject === '') {
            $subject = __('Omni Mail test', 'omni-mail');
        }

        $bodies = $this->templateRenderer->render(
            recipientSummary: $to,
            connectionLabel: __('Not routed by Omni Mail', 'omni-mail'),
            connectionProfile: __('WordPress native wp_mail()', 'omni-mail'),
            deliveryMode: __('Native wp_mail', 'omni-mail'),
            routingStrategy: __('None', 'omni-mail'),
            mailLogLabel: __('Not logged by Omni Mail', 'omni-mail'),
            mailLogUrl: admin_url('admin.php?page=omni-mail#/logs/mail'),
        );
        $markTestMailRequest = static fn (MailRequest $mailRequest): MailRequest => new MailRequest(
            from: $mailRequest->from,
            to: $mailRequest->to,
            cc: $mailRequest->cc,
            bcc: $mailRequest->bcc,
            replyTo: $mailRequest->replyTo,
            subject: $mailRequest->subject,
            textBody: $mailRequest->textBody,
            htmlBody: $mailRequest->htmlBody,
            attachments: $mailRequest->attachments,
            headers: $mailRequest->headers,
            envelopeSender: $mailRequest->envelopeSender,
            source: 'admin_test_email',
            metadata: array_merge($mailRequest->metadata, [TestEmailDeliveryTemplateListener::METADATA_KEY => true]),
        );
        $setAltBody = static function (object $phpmailer) use ($bodies): void {
            if (property_exists($phpmailer, 'AltBody')) {
                $phpmailer->AltBody = $bodies['textBody'];
            }
        };

        add_filter('f!omni-mail/mail:normalize.request', $markTestMailRequest, 10, 1);
        add_action('phpmailer_init', $setAltBody);

        try {
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $connectionId = max(0, (int) ($connectionId ?? 0));

            if ($connectionId > 0) {
                $headers[] = sprintf('X-Omni-Mail-Connection-Id: %d', $connectionId);
            }

            return wp_mail($to, $subject, $bodies['htmlBody'], $headers);
        } finally {
            remove_filter('f!omni-mail/mail:normalize.request', $markTestMailRequest, 10);
            remove_action('phpmailer_init', $setAltBody);
        }
    }
}
