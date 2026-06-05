<?php

namespace OmniMailDeps\Tempest\Support;

use Attribute;
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Priority
{
    public const int EXCEPTION_HANDLING = -100;
    public const int FRAMEWORK = -1;
    public const int HIGHEST = 0;
    public const int HIGH = 10;
    public const int NORMAL = 100;
    public const int LOW = 1000;
    public const int LOWEST = 10000;
    public function __construct(public int $priority = self::NORMAL)
    {
    }
}
