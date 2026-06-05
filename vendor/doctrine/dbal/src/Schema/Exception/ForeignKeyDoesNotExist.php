<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
use function sprintf;
final class ForeignKeyDoesNotExist extends LogicException implements SchemaException
{
    public static function new(string $foreignKeyName, string $table): self
    {
        return new self(sprintf('There exists no foreign key with the name "%s" on table "%s".', $foreignKeyName, $table));
    }
}
