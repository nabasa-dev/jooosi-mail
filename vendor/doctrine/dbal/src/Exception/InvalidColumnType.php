<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Exception;

use OmniMailDeps\Doctrine\DBAL\Exception;
use LogicException;
abstract class InvalidColumnType extends LogicException implements Exception
{
}
