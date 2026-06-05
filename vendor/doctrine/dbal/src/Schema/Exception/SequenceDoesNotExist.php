<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
use function sprintf;
final class SequenceDoesNotExist extends LogicException implements SchemaException
{
    public static function new(string $sequenceName): self
    {
        return new self(sprintf('There exists no sequence with the name "%s".', $sequenceName));
    }
}
