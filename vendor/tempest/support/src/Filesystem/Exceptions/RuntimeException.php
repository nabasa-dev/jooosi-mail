<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Support\Filesystem\Exceptions;

use RuntimeException as PhpRuntimeException;
final class RuntimeException extends PhpRuntimeException implements FilesystemException
{
}
