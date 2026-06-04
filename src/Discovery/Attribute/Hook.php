<?php

declare(strict_types=1);

namespace OmniMail\Discovery\Attribute;

use Attribute;

/**
 * Declares a WordPress hook callback on a service method.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Hook
{
    public function __construct(
        public string $name,
        public string $kind = 'auto',
        public int $priority = 10,
        public int $acceptedArgs = 1,
    ) {
    }
}
