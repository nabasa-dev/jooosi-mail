<?php

namespace OmniMailDeps\AsyncAws\Ses\Exception;

use OmniMailDeps\AsyncAws\Core\Exception\Http\ClientException;
/**
 * There are too many instances of the specified resource type.
 */
final class LimitExceededException extends ClientException
{
}
