<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver;

use OmniMailDeps\Doctrine\DBAL\Driver;
use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\API\IBMDB2\ExceptionConverter;
use OmniMailDeps\Doctrine\DBAL\Platforms\DB2Platform;
use OmniMailDeps\Doctrine\DBAL\ServerVersionProvider;
/**
 * Abstract base implementation of the {@see Driver} interface for Db2 based drivers.
 */
abstract class AbstractDB2Driver implements Driver
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): DB2Platform
    {
        return new DB2Platform();
    }
    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
}
