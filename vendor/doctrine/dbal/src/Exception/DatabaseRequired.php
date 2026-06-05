<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Exception;

use OmniMailDeps\Doctrine\DBAL\Exception;
use function sprintf;
class DatabaseRequired extends \Exception implements Exception
{
    public static function new(string $methodName): self
    {
        return new self(sprintf('A database is required for the method: %s.', $methodName));
    }
}
