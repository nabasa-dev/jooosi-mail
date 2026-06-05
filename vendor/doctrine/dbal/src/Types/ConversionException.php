<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Types;

use OmniMailDeps\Doctrine\DBAL\Exception;
/**
 * Conversion Exception is thrown when the database to PHP conversion fails.
 */
class ConversionException extends \Exception implements Exception
{
}
