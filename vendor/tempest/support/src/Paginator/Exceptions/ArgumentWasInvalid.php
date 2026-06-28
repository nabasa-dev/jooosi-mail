<?php

namespace JooosiMailDeps\Tempest\Support\Paginator\Exceptions;

use InvalidArgumentException as PhpInvalidArgumentException;
final class ArgumentWasInvalid extends PhpInvalidArgumentException implements PaginationException
{
}
