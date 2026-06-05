<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\DefaultExpression;

use OmniMailDeps\Doctrine\DBAL\Platforms\AbstractPlatform;
use OmniMailDeps\Doctrine\DBAL\Schema\DefaultExpression;
/**
 * Represents the "current date" default expression.
 */
final readonly class CurrentDate implements DefaultExpression
{
    public function toSQL(AbstractPlatform $platform): string
    {
        return $platform->getCurrentDateSQL();
    }
}
