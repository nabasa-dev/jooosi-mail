<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Schema\Collections\Exception;

use OmniMailDeps\Doctrine\DBAL\Schema\Collections\Exception;
use OmniMailDeps\Doctrine\DBAL\Schema\Name\UnqualifiedName;
use LogicException;
use function sprintf;
/** @internal */
final class ObjectAlreadyExists extends LogicException implements Exception
{
    public function __construct(string $message, private readonly UnqualifiedName $objectName)
    {
        parent::__construct($message);
    }
    public function getObjectName(): UnqualifiedName
    {
        return $this->objectName;
    }
    public static function new(UnqualifiedName $objectName): self
    {
        return new self(sprintf('Object %s already exists.', $objectName->toString()), $objectName);
    }
}
