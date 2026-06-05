<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Platforms\MySQL;

/** @internal */
interface CollationMetadataProvider
{
    /**
     * @param non-empty-string $collation
     *
     * @return ?non-empty-string
     */
    public function getCollationCharset(string $collation): ?string;
}
