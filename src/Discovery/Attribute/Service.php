<?php

declare (strict_types=1);
namespace OmniMail\Discovery\Attribute;

use Attribute;
/**
 * Marks a class for container registration.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Service
{
    public function __construct(public bool $public = \true)
    {
    }
}
