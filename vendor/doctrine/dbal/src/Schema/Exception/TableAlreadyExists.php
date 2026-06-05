<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
use function sprintf;
final class TableAlreadyExists extends LogicException implements SchemaException
{
    public static function new(string $tableName): self
    {
        return new self(sprintf('The table with name "%s" already exists.', $tableName));
    }
}
