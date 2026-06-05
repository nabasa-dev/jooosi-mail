<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\IBMDB2\Exception;

use OmniMailDeps\Doctrine\DBAL\Driver\AbstractException;
/** @internal */
final class PrepareFailed extends AbstractException
{
    /** @phpstan-param array{message: string, ...}|null $error */
    public static function new(?array $error): self
    {
        if ($error === null) {
            return new self('Unknown error');
        }
        return new self($error['message']);
    }
}
