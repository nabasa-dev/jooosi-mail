<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\API\SQLite;

use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\Exception;
use OmniMailDeps\Doctrine\DBAL\Exception\ConnectionException;
use OmniMailDeps\Doctrine\DBAL\Exception\DriverException;
use OmniMailDeps\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Exception\InvalidFieldNameException;
use OmniMailDeps\Doctrine\DBAL\Exception\LockWaitTimeoutException;
use OmniMailDeps\Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use OmniMailDeps\Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Exception\ReadOnlyException;
use OmniMailDeps\Doctrine\DBAL\Exception\SyntaxErrorException;
use OmniMailDeps\Doctrine\DBAL\Exception\TableExistsException;
use OmniMailDeps\Doctrine\DBAL\Exception\TableNotFoundException;
use OmniMailDeps\Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Query;
use function str_contains;
/** @internal */
final class ExceptionConverter implements ExceptionConverterInterface
{
    /** @link http://www.sqlite.org/c3ref/c_abort.html */
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        if (str_contains($exception->getMessage(), 'database is locked')) {
            return new LockWaitTimeoutException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'must be unique') || str_contains($exception->getMessage(), 'is not unique') || str_contains($exception->getMessage(), 'are not unique') || str_contains($exception->getMessage(), 'UNIQUE constraint failed')) {
            return new UniqueConstraintViolationException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'may not be NULL') || str_contains($exception->getMessage(), 'NOT NULL constraint failed')) {
            return new NotNullConstraintViolationException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'no such table:')) {
            return new TableNotFoundException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'already exists')) {
            return new TableExistsException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'has no column named')) {
            return new InvalidFieldNameException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'ambiguous column name')) {
            return new NonUniqueFieldNameException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'syntax error')) {
            return new SyntaxErrorException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'attempt to write a readonly database')) {
            return new ReadOnlyException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'unable to open database file')) {
            return new ConnectionException($exception, $query);
        }
        if (str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
            return new ForeignKeyConstraintViolationException($exception, $query);
        }
        return new DriverException($exception, $query);
    }
}
