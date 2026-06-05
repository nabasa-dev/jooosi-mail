<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\SQLite3;

use OmniMailDeps\Doctrine\DBAL\Driver\AbstractException;
/** @internal */
final class Exception extends AbstractException
{
    public static function new(\Exception $exception): self
    {
        return new self($exception->getMessage(), null, (int) $exception->getCode(), $exception);
    }
}
