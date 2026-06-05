<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\SQL\Builder;

use OmniMailDeps\Doctrine\DBAL\Exception;
use OmniMailDeps\Doctrine\DBAL\Query\SelectQuery;
interface SelectSQLBuilder
{
    /** @throws Exception */
    public function buildSQL(SelectQuery $query): string;
}
