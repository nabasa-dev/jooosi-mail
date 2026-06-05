<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Query;

/** @internal */
final readonly class Union
{
    public function __construct(public string|QueryBuilder $query, public ?UnionType $type = null)
    {
    }
}
