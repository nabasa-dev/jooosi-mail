<?php

declare (strict_types=1);
namespace OmniMailDeps\AsyncAws\Core\Exception\Http;

use OmniMailDeps\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
/**
 * Represents a 4xx response.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ClientException extends \RuntimeException implements ClientExceptionInterface, HttpException
{
    use HttpExceptionTrait;
}
