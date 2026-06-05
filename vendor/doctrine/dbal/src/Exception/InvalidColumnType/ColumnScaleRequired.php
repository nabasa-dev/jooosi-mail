<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Exception\InvalidColumnType;

use OmniMailDeps\Doctrine\DBAL\Exception\InvalidColumnType;
/** @internal */
final class ColumnScaleRequired extends InvalidColumnType
{
    public static function new(): self
    {
        return new self('Column scale is not specified');
    }
}
