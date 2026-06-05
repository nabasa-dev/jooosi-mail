<?php

declare (strict_types=1);
namespace OmniMail\Mail\ValueObject;

/**
 * Immutable attachment metadata.
 *
 * @since 0.1.0
 */
final readonly class MailAttachment
{
    public function __construct(public string $path, public ?string $name = null, public ?string $contentType = null)
    {
    }
    /**
     * @since 0.1.0
     */
    public static function fromArray(array $data): self
    {
        return new self(path: (string) ($data['path'] ?? ''), name: isset($data['name']) ? (string) $data['name'] : null, contentType: isset($data['contentType']) ? (string) $data['contentType'] : null);
    }
    /**
     * @return array{path: string, name: string|null, contentType: string|null}
     *
     * @since 0.1.0
     */
    public function toArray(): array
    {
        return ['path' => $this->path, 'name' => $this->name, 'contentType' => $this->contentType];
    }
}
