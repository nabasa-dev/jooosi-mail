<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Exception;

/**
 * Exception for an unknown table referenced in a statement detected in the driver.
 */
class TableNotFoundException extends DatabaseObjectNotFoundException
{
}
