<?php

namespace OmniMailDeps\AsyncAws\Core\Signer;

use OmniMailDeps\AsyncAws\Core\Credentials\Credentials;
use OmniMailDeps\AsyncAws\Core\Request;
use OmniMailDeps\AsyncAws\Core\RequestContext;
/**
 * Interface for signing a request.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
interface Signer
{
    public function sign(Request $request, Credentials $credentials, RequestContext $context): void;
    public function presign(Request $request, Credentials $credentials, RequestContext $context): void;
}
