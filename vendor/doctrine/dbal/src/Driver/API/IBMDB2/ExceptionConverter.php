<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\API\IBMDB2;

use OmniMailDeps\Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use OmniMailDeps\Doctrine\DBAL\Driver\Exception;
use OmniMailDeps\Doctrine\DBAL\Exception\ConnectionException;
use OmniMailDeps\Doctrine\DBAL\Exception\DriverException;
use OmniMailDeps\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Exception\InvalidFieldNameException;
use OmniMailDeps\Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use OmniMailDeps\Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Exception\SyntaxErrorException;
use OmniMailDeps\Doctrine\DBAL\Exception\TableExistsException;
use OmniMailDeps\Doctrine\DBAL\Exception\TableNotFoundException;
use OmniMailDeps\Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OmniMailDeps\Doctrine\DBAL\Query;
/**
 * @internal
 *
 * @link https://www.ibm.com/docs/en/db2/11.5?topic=messages-sql
 */
final class ExceptionConverter implements ExceptionConverterInterface
{
    public function convert(Exception $exception, ?Query $query): DriverException
    {
        return match ($exception->getCode()) {
            -104 => new SyntaxErrorException($exception, $query),
            -203 => new NonUniqueFieldNameException($exception, $query),
            -204 => new TableNotFoundException($exception, $query),
            -206 => new InvalidFieldNameException($exception, $query),
            -407 => new NotNullConstraintViolationException($exception, $query),
            -530, -531, -532, -20356 => new ForeignKeyConstraintViolationException($exception, $query),
            -601 => new TableExistsException($exception, $query),
            -803 => new UniqueConstraintViolationException($exception, $query),
            -1336, -30082 => new ConnectionException($exception, $query),
            default => new DriverException($exception, $query),
        };
    }
}
