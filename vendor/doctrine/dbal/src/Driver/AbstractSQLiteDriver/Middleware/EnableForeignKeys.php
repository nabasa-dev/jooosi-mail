<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\AbstractSQLiteDriver\Middleware;

use OmniMailDeps\Doctrine\DBAL\Driver;
use OmniMailDeps\Doctrine\DBAL\Driver\Connection;
use OmniMailDeps\Doctrine\DBAL\Driver\Middleware;
use OmniMailDeps\Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use SensitiveParameter;
final class EnableForeignKeys implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class($driver) extends AbstractDriverMiddleware
        {
            /**
             * {@inheritDoc}
             */
            public function connect(
                #[SensitiveParameter]
                array $params
            ): Connection
            {
                $connection = parent::connect($params);
                $connection->exec('PRAGMA foreign_keys=ON');
                return $connection;
            }
        };
    }
}
