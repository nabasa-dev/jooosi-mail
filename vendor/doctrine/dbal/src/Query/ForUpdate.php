<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Query;

use OmniMailDeps\Doctrine\DBAL\Query\ForUpdate\ConflictResolutionMode;
/** @internal */
final readonly class ForUpdate
{
    public function __construct(private ConflictResolutionMode $conflictResolutionMode)
    {
    }
    public function getConflictResolutionMode(): ConflictResolutionMode
    {
        return $this->conflictResolutionMode;
    }
}
