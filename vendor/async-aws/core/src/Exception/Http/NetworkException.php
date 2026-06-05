<?php

declare (strict_types=1);
namespace OmniMailDeps\AsyncAws\Core\Exception\Http;

use OmniMailDeps\AsyncAws\Core\Exception\Exception;
use OmniMailDeps\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
/**
 * Request could not be sent due network error.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class NetworkException extends \RuntimeException implements Exception, TransportExceptionInterface
{
}
