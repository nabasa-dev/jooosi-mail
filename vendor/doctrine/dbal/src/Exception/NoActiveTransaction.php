<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Exception;

use OmniMailDeps\Doctrine\DBAL\ConnectionException;
final class NoActiveTransaction extends ConnectionException
{
    public static function new(): self
    {
        return new self('There is no active transaction.');
    }
}
