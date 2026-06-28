<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Platforms;

use JooosiMailDeps\Doctrine\DBAL\Platforms\Exception\NotSupported;
use JooosiMailDeps\Doctrine\DBAL\Platforms\Keywords\KeywordList;
use JooosiMailDeps\Doctrine\DBAL\Platforms\Keywords\MySQLKeywords;
use JooosiMailDeps\Doctrine\DBAL\Schema\Index;
use JooosiMailDeps\Doctrine\DBAL\SQL\Builder\WithSQLBuilder;
use JooosiMailDeps\Doctrine\DBAL\Types\BlobType;
use JooosiMailDeps\Doctrine\DBAL\Types\TextType;
use JooosiMailDeps\Doctrine\Deprecations\Deprecation;
/**
 * Provides the behavior, features and SQL dialect of the Oracle MySQL database platform
 * of the oldest supported version.
 */
class MySQLPlatform extends AbstractMySQLPlatform
{
    /**
     * {@inheritDoc}
     *
     * Oracle MySQL does not support default values on TEXT/BLOB columns until 8.0.13.
     *
     * @internal The method should be only used from within the {@see AbstractPlatform} class hierarchy.
     *
     * @link https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-13.html#mysqld-8-0-13-data-types
     */
    public function getDefaultValueDeclarationSQL(array $column): string
    {
        if ($column['type'] instanceof TextType || $column['type'] instanceof BlobType) {
            unset($column['default']);
        }
        return parent::getDefaultValueDeclarationSQL($column);
    }
    public function createWithSQLBuilder(): WithSQLBuilder
    {
        throw NotSupported::new(__METHOD__);
    }
    /**
     * {@inheritDoc}
     */
    protected function getRenameIndexSQL(string $oldIndexName, Index $index, string $tableName): array
    {
        return ['ALTER TABLE ' . $tableName . ' RENAME INDEX ' . $oldIndexName . ' TO ' . $index->getQuotedName($this)];
    }
    /** @deprecated */
    protected function createReservedKeywordsList(): KeywordList
    {
        Deprecation::triggerIfCalledFromOutside('doctrine/dbal', 'https://github.com/doctrine/dbal/pull/6607', '%s is deprecated.', __METHOD__);
        return new MySQLKeywords();
    }
}
