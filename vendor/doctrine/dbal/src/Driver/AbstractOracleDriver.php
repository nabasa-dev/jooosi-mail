<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver;

use OmniMailDeps\Doctrine\DBAL\Driver;
use OmniMailDeps\Doctrine\DBAL\Driver\AbstractOracleDriver\EasyConnectString;
use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\API\OCI\ExceptionConverter;
use OmniMailDeps\Doctrine\DBAL\Platforms\OraclePlatform;
use OmniMailDeps\Doctrine\DBAL\ServerVersionProvider;
/**
 * Abstract base implementation of the {@see Driver} interface for Oracle based drivers.
 */
abstract class AbstractOracleDriver implements Driver
{
    public function getDatabasePlatform(ServerVersionProvider $versionProvider): OraclePlatform
    {
        return new OraclePlatform();
    }
    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
    /**
     * Returns an appropriate Easy Connect String for the given parameters.
     *
     * @param array<string, mixed> $params The connection parameters to return the Easy Connect String for.
     */
    protected function getEasyConnectString(array $params): string
    {
        return (string) EasyConnectString::fromConnectionParameters($params);
    }
}
