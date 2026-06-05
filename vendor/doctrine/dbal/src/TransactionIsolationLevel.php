<?php

declare (strict_types=1);
namespace OmniMailDeps\Doctrine\DBAL;

enum TransactionIsolationLevel
{
    case READ_UNCOMMITTED;
    case READ_COMMITTED;
    case REPEATABLE_READ;
    case SERIALIZABLE;
}
