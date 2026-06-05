<?php

namespace OmniMailDeps\AsyncAws\Ses\Exception;

use OmniMailDeps\AsyncAws\Core\Exception\Http\ClientException;
/**
 * The message can't be sent because the account's ability to send email has been permanently restricted.
 */
final class AccountSuspendedException extends ClientException
{
}
