<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
use function sprintf;
final class ColumnDoesNotExist extends LogicException implements SchemaException
{
    public static function new(string $columnName, string $table): self
    {
        return new self(sprintf('There is no column with name "%s" on table "%s".', $columnName, $table));
    }
}
