<?php

namespace OmniMailDeps\AsyncAws\Ses\Exception;

use OmniMailDeps\AsyncAws\Core\Exception\Http\ClientException;
/**
 * The message can't be sent because the sending domain isn't verified.
 */
final class MailFromDomainNotVerifiedException extends ClientException
{
}
