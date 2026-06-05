<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Types;

use OmniMailDeps\Doctrine\DBAL\ParameterType;
use OmniMailDeps\Doctrine\DBAL\Platforms\AbstractPlatform;
final class AsciiStringType extends StringType
{
    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getAsciiStringTypeDeclarationSQL($column);
    }
    public function getBindingType(): ParameterType
    {
        return ParameterType::ASCII;
    }
}
