<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\AbstractSQLServerDriver\Exception;

use OmniMailDeps\Doctrine\DBAL\Driver\AbstractException;
/** @internal */
final class PortWithoutHost extends AbstractException
{
    public static function new(): self
    {
        return new self('Connection port specified without the host');
    }
}
