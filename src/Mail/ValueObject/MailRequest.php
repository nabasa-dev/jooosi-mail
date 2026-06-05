<?php

declare (strict_types=1);
namespace OmniMail\Mail\ValueObject;

/**
 * Stable normalized email payload used across sync and async flows.
 *
 * @since 0.1.0
 */
final readonly class MailRequest
{
    /**
     * @param list<MailAddress> $from
     * @param list<MailAddress> $to
     * @param list<MailAddress> $cc
     * @param list<MailAddress> $bcc
     * @param list<MailAddress> $replyTo
     * @param list<MailAttachment> $attachments
     * @param array<string, string> $headers
     * @param array<string, mixed>  $metadata
     */
    public function __construct(public array $from, public array $to, public array $cc, public array $bcc, public array $replyTo, public string $subject, public ?string $textBody, public ?string $htmlBody, public array $attachments, public array $headers, public ?\OmniMail\Mail\ValueObject\MailAddress $envelopeSender = null, public string $source = 'wp_mail', public array $metadata = [])
    {
    }
    /**
     * @since 0.1.0
     */
    public static function fromArray(array $data): self
    {
        return new self(from: array_map(static fn(array $item): \OmniMail\Mail\ValueObject\MailAddress => \OmniMail\Mail\ValueObject\MailAddress::fromArray($item), $data['from'] ?? []), to: array_map(static fn(array $item): \OmniMail\Mail\ValueObject\MailAddress => \OmniMail\Mail\ValueObject\MailAddress::fromArray($item), $data['to'] ?? []), cc: array_map(static fn(array $item): \OmniMail\Mail\ValueObject\MailAddress => \OmniMail\Mail\ValueObject\MailAddress::fromArray($item), $data['cc'] ?? []), bcc: array_map(static fn(array $item): \OmniMail\Mail\ValueObject\MailAddress => \OmniMail\Mail\ValueObject\MailAddress::fromArray($item), $data['bcc'] ?? []), replyTo: array_map(static fn(array $item): \OmniMail\Mail\ValueObject\MailAddress => \OmniMail\Mail\ValueObject\MailAddress::fromArray($item), $data['replyTo'] ?? []), subject: (string) ($data['subject'] ?? ''), textBody: isset($data['textBody']) ? (string) $data['textBody'] : null, htmlBody: isset($data['htmlBody']) ? (string) $data['htmlBody'] : null, attachments: array_map(static fn(array $item): \OmniMail\Mail\ValueObject\MailAttachment => \OmniMail\Mail\ValueObject\MailAttachment::fromArray($item), $data['attachments'] ?? []), headers: $data['headers'] ?? [], envelopeSender: is_array($data['envelopeSender'] ?? null) ? \OmniMail\Mail\ValueObject\MailAddress::fromArray($data['envelopeSender']) : null, source: (string) ($data['source'] ?? 'wp_mail'), metadata: $data['metadata'] ?? []);
    }
    /**
     * @return array<string, mixed>
     *
     * @since 0.1.0
     */
    public function toArray(): array
    {
        return ['from' => array_map(static fn(\OmniMail\Mail\ValueObject\MailAddress $item): array => $item->toArray(), $this->from), 'to' => array_map(static fn(\OmniMail\Mail\ValueObject\MailAddress $item): array => $item->toArray(), $this->to), 'cc' => array_map(static fn(\OmniMail\Mail\ValueObject\MailAddress $item): array => $item->toArray(), $this->cc), 'bcc' => array_map(static fn(\OmniMail\Mail\ValueObject\MailAddress $item): array => $item->toArray(), $this->bcc), 'replyTo' => array_map(static fn(\OmniMail\Mail\ValueObject\MailAddress $item): array => $item->toArray(), $this->replyTo), 'subject' => $this->subject, 'textBody' => $this->textBody, 'htmlBody' => $this->htmlBody, 'attachments' => array_map(static fn(\OmniMail\Mail\ValueObject\MailAttachment $item): array => $item->toArray(), $this->attachments), 'headers' => $this->headers, 'envelopeSender' => $this->envelopeSender?->toArray(), 'source' => $this->source, 'metadata' => $this->metadata];
    }
}
