<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Schema\Exception;

use JooosiMailDeps\Doctrine\DBAL\Schema\SchemaException;
use LogicException;
use function sprintf;
final class InvalidSequenceDefinition extends LogicException implements SchemaException
{
    public static function nameNotSet(): self
    {
        return new self('Sequence name is not set.');
    }
    public static function fromNegativeCacheSize(int $cacheSize): self
    {
        return new self(sprintf('Sequence cache size must be a non-negative integer, %d given.', $cacheSize));
    }
}
