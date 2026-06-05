<?php

declare (strict_types=1);
namespace OmniMail\Discovery\Attribute;

use Attribute;
/**
 * Marks a custom Symfony mail transport factory.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class TransportFactory
{
}
