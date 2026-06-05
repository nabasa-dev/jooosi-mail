<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Connection;

use OmniMailDeps\Doctrine\DBAL\ServerVersionProvider;
/** @final */
class StaticServerVersionProvider implements ServerVersionProvider
{
    public function __construct(private readonly string $version)
    {
    }
    public function getServerVersion(): string
    {
        return $this->version;
    }
}
