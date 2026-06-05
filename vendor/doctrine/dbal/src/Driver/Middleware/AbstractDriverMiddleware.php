<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\Middleware;

use OmniMailDeps\Doctrine\DBAL\Driver;
use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter;
use OmniMailDeps\Doctrine\DBAL\Driver\Connection as DriverConnection;
use OmniMailDeps\Doctrine\DBAL\Platforms\AbstractPlatform;
use OmniMailDeps\Doctrine\DBAL\ServerVersionProvider;
use SensitiveParameter;
abstract class AbstractDriverMiddleware implements Driver
{
    public function __construct(private readonly Driver $wrappedDriver)
    {
    }
    /**
     * {@inheritDoc}
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ): DriverConnection
    {
        return $this->wrappedDriver->connect($params);
    }
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
    {
        return $this->wrappedDriver->getDatabasePlatform($versionProvider);
    }
    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->wrappedDriver->getExceptionConverter();
    }
}
