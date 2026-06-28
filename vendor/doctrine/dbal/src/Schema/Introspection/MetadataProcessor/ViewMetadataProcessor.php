<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Schema\Introspection\MetadataProcessor;

use JooosiMailDeps\Doctrine\DBAL\Schema\Metadata\ViewMetadataRow;
use JooosiMailDeps\Doctrine\DBAL\Schema\View;
/**
 * Converts {@see ViewMetadataRow} into a {@see View}.
 *
 * @internal Should be used only by {@link IntrospectingSchemaProvider}.
 */
final readonly class ViewMetadataProcessor
{
    public function createObject(ViewMetadataRow $row): View
    {
        return View::editor()->setQuotedName($row->getViewName(), $row->getSchemaName())->setSQL($row->getDefinition())->create();
    }
}
