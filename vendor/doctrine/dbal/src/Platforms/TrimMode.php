<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL\Platforms;

enum TrimMode
{
    case UNSPECIFIED;
    case LEADING;
    case TRAILING;
    case BOTH;
}
