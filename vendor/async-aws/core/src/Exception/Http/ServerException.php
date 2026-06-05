<?php

declare (strict_types=1);
namespace OmniMailDeps\AsyncAws\Core\Exception\Http;

use OmniMailDeps\Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
/**
 * Represents a 5xx response.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ServerException extends \RuntimeException implements HttpException, ServerExceptionInterface
{
    use HttpExceptionTrait;
}
