<?php

declare(strict_types=1);

namespace OmniMail\Discovery\Attribute;

use Attribute;

/**
 * Marks a class as a REST controller.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Controller
{
    public function __construct(
        public string $namespace = 'omni-mail/v1',
        public string $prefix = '',
    ) {
    }
}
