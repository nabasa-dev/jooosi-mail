<?php

declare(strict_types=1);

namespace JooosiMail\Discovery\Attribute;

use Attribute;

/**
 * Marks a class as a discovered mail profile.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MailProfile
{
    /**
     * @param list<string>                            $useCases
     * @param array<string, scalar|list<scalar>|null> $extra
     */
    public function __construct(
        public string $key,
        public ?string $label = null,
        public ?string $description = null,
        public ?string $website = null,
        public ?string $docsUrl = null,
        public array $useCases = [],
        public array $extra = [],
    ) {
    }
}
