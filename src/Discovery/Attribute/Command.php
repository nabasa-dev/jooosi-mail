<?php

declare (strict_types=1);
namespace OmniMail\Discovery\Attribute;

use Attribute;
/**
 * Marks a service class or public method as a WP-CLI command.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class Command
{
    /**
     * @param list<string> $aliases
     *
     * @since 0.1.0
     */
    public function __construct(public ?string $name = null, public string $description = '', public array $aliases = [], public ?string $synopsis = null, public ?string $when = 'after_wp_load')
    {
    }
}
