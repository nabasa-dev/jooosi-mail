<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\DefaultExpression;

use OmniMailDeps\Doctrine\DBAL\Platforms\AbstractPlatform;
use OmniMailDeps\Doctrine\DBAL\Schema\DefaultExpression;
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
