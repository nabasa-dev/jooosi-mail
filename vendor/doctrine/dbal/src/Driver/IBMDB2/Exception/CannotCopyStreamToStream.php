<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\IBMDB2\Exception;

use OmniMailDeps\Doctrine\DBAL\Driver\AbstractException;
/** @internal */
final class CannotCopyStreamToStream extends AbstractException
{
    /** @phpstan-param array{message: string, ...}|null $error */
    public static function new(?array $error): self
    {
        $message = 'Could not copy source stream to temporary file';
        if ($error !== null) {
            $message .= ': ' . $error['message'];
        }
        return new self($message);
    }
}
