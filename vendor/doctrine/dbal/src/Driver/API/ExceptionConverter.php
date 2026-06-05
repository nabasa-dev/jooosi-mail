<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Driver\API;

use OmniMailDeps\Doctrine\DBAL\Driver\Exception;
use OmniMailDeps\Doctrine\DBAL\Exception\DriverException;
use OmniMailDeps\Doctrine\DBAL\Query;
interface ExceptionConverter
{
    /**
     * Converts a given driver-level exception into a DBAL-level driver exception.
     *
     * Implementors should use the vendor-specific error code and SQLSTATE of the exception
     * and instantiate the most appropriate specialized {@see DriverException} subclass.
     *
     * @param Exception  $exception The driver exception to convert.
     * @param Query|null $query     The SQL query that triggered the exception, if any.
     *
     * @return DriverException An instance of {@see DriverException} or one of its subclasses.
     */
    public function convert(Exception $exception, ?Query $query): DriverException;
}
