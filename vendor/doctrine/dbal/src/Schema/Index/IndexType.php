<?php

declare (strict_types=1);
namespace JooosiMailDeps\Doctrine\DBAL\Schema\Index;

enum IndexType
{
    case REGULAR;
    case UNIQUE;
    case FULLTEXT;
    case SPATIAL;
}
