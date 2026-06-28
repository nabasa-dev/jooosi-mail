<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Schema\Exception;

use JooosiMailDeps\Doctrine\DBAL\Schema\SchemaException;
use InvalidArgumentException;
final class InvalidIdentifier extends InvalidArgumentException implements SchemaException
{
    public static function fromEmpty(): self
    {
        return new self('Identifier cannot be empty.');
    }
}
