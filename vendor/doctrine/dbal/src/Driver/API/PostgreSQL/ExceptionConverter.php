<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\API\PostgreSQL;

use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\Exception;
use OmniMailDeps\Doctrine\DBAL\Exception\ConnectionException;
use OmniMailDeps\Doctrine\DBAL\Exception\ConnectionLost;
use OmniMailDeps\Doctrine\DBAL\Exception\DatabaseDoesNotExist;
use OmniMailDeps\Doctrine\DBAL\Exception\DeadlockException;
use OmniMailDeps\Doctrine\DBAL\Exception\DriverException;
use OmniMailDeps\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Exception\InvalidFieldNameException;
use OmniMailDeps\Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use OmniMailDeps\Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Exception\SchemaDoesNotExist;
use OmniMailDeps\Doctrine\DBAL\Exception\SyntaxErrorException;
use OmniMailDeps\Doctrine\DBAL\Exception\TableExistsException;
use OmniMailDeps\Doctrine\DBAL\Exception\TableNotFoundException;
use OmniMailDeps\Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Query;
use function str_contains;
/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /** @link http://www.postgresql.org/docs/9.4/static/errcodes-appendix.html */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        switch ($exception->getSQLState()) {
            case '40001':
            case '40P01':
                return new DeadlockException($exception, $query);
            case '0A000':
                // Foreign key constraint violations during a TRUNCATE operation
                // are considered "feature not supported" in PostgreSQL.
                if (str_contains($exception->getMessage(), 'truncate')) {
                    return new ForeignKeyConstraintViolationException($exception, $query);
                }
                break;
            case '23502':
                return new NotNullConstraintViolationException($exception, $query);
            case '23503':
                return new ForeignKeyConstraintViolationException($exception, $query);
            case '23505':
                return new UniqueConstraintViolationException($exception, $query);
            case '3D000':
                return new DatabaseDoesNotExist($exception, $query);
            case '3F000':
                return new SchemaDoesNotExist($exception, $query);
            case '42601':
                return new SyntaxErrorException($exception, $query);
            case '42702':
                return new NonUniqueFieldNameException($exception, $query);
            case '42703':
                return new InvalidFieldNameException($exception, $query);
            case '42P01':
                return new TableNotFoundException($exception, $query);
            case '42P07':
                return new TableExistsException($exception, $query);
            case '08006':
                return new ConnectionException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'terminating connection')) {
            return new ConnectionLost($exception, $query);
        }
        return new DriverException($exception, $query);
    }
}
