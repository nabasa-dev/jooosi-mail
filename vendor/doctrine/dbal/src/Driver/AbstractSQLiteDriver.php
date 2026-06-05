<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver;

use OmniMailDeps\Doctrine\DBAL\Driver;
use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\API\SQLite\ExceptionConverter;
use OmniMailDeps\Doctrine\DBAL\Platforms\SQLitePlatform;
use OmniMailDeps\Doctrine\DBAL\ServerVersionProvider;
/**
 * Abstract base implementation of the {@see Driver} interface for SQLite based drivers.
 */
abstract class AbstractSQLiteDriver implements Driver
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): SQLitePlatform
    {
        return new SQLitePlatform();
    }
    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
}
