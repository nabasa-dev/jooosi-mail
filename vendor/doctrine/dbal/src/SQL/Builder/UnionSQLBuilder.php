<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\SQL\Builder;

use OmniMailDeps\Doctrine\DBAL\Exception;
use OmniMailDeps\Doctrine\DBAL\Query\UnionQuery;
interface UnionSQLBuilder
{
    /** @throws Exception */
    public function buildSQL(UnionQuery $query): string;
}
