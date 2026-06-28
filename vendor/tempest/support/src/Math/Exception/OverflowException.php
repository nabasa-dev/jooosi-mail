<?php

declare (strict_types=1);
namespace JooosiMailDeps\Tempest\Support\Math\Exception;

use OverflowException as PhpOverflowException;
final class OverflowException extends PhpOverflowException implements MathException
{
}
