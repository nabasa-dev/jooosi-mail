<?php

namespace OmniMailDeps\AsyncAws\Ses\Exception;

use OmniMailDeps\AsyncAws\Core\Exception\Http\ClientException;
/**
 * Too many requests have been made to the operation.
 */
final class TooManyRequestsException extends ClientException
{
}
