<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\Name\UnqualifiedName;
use OmniMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
use function sprintf;
final class InvalidUniqueConstraintDefinition extends LogicException implements SchemaException
{
    public static function columnNamesAreNotSet(?UnqualifiedName $constraintName): self
    {
        return new self(sprintf('Column names are not set for unique constraint %s.', $constraintName === null ? '<unnamed>' : $constraintName->toString()));
    }
}
