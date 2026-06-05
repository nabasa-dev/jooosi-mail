<?php

namespace OmniMailDeps\AsyncAws\Core\HttpClient;

use OmniMailDeps\Psr\Log\LoggerInterface;
use OmniMailDeps\Symfony\Component\HttpClient\HttpClient;
use OmniMailDeps\Symfony\Component\HttpClient\RetryableHttpClient;
use OmniMailDeps\Symfony\Contracts\HttpClient\HttpClientInterface;
/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class AwsHttpClientFactory
{
    public static function createRetryableClient(?HttpClientInterface $httpClient = null, ?LoggerInterface $logger = null): HttpClientInterface
    {
        if (null === $httpClient) {
            $httpClient = HttpClient::create();
        }
        if (class_exists(RetryableHttpClient::class)) {
            /** @psalm-suppress MissingDependency */
            $httpClient = new RetryableHttpClient($httpClient, new AwsRetryStrategy(), 3, $logger);
        }
        return $httpClient;
    }
}
