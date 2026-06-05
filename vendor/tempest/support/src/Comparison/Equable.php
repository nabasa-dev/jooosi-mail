<?php

declare (strict_types=1);
namespace OmniMailDeps\Tempest\Support\Comparison;

/**
 * @template T
 */
interface Equable
{
    /**
     * @param T $other
     */
    public function equals(mixed $other): bool;
}
