<?php

declare (strict_types=1);
namespace OmniMailDeps\AsyncAws\Core\Exception\Http;

use OmniMailDeps\Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
/**
 * Represents a 3xx response.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class RedirectionException extends \RuntimeException implements HttpException, RedirectionExceptionInterface
{
    use HttpExceptionTrait;
}
