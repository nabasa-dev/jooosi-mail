<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Schema\DefaultExpression;

use JooosiMailDeps\Doctrine\DBAL\Platforms\AbstractPlatform;
use JooosiMailDeps\Doctrine\DBAL\Schema\DefaultExpression;
/**
 * Represents the "current time" default expression.
 */
final readonly class CurrentTime implements DefaultExpression
{
    public function toSQL(AbstractPlatform $platform): string
    {
        return $platform->getCurrentTimeSQL();
    }
}
