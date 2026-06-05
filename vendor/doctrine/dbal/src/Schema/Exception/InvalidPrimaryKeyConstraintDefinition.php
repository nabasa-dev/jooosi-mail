<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
final class InvalidPrimaryKeyConstraintDefinition extends LogicException implements SchemaException
{
    public static function columnNamesNotSet(): self
    {
        return new self('Primary key constraint column names are not set.');
    }
}
