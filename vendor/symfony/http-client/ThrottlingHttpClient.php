<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\HttpClient;

use OmniMailDeps\Symfony\Component\RateLimiter\LimiterInterface;
use OmniMailDeps\Symfony\Contracts\HttpClient\HttpClientInterface;
use OmniMailDeps\Symfony\Contracts\HttpClient\ResponseInterface;
use OmniMailDeps\Symfony\Contracts\Service\ResetInterface;
/**
 * Limits the number of requests within a certain period.
 */
class ThrottlingHttpClient implements HttpClientInterface, ResetInterface
{
    use DecoratorTrait {
        reset as private traitReset;
    }
    public function __construct(HttpClientInterface $client, private readonly LimiterInterface $rateLimiter)
    {
        $this->client = $client;
    }
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $response = $this->client->request($method, $url, $options);
        if (0 < $waitDuration = $this->rateLimiter->reserve()->getWaitDuration()) {
            $response->getInfo('pause_handler')($waitDuration);
        }
        return $response;
    }
    public function reset(): void
    {
        $this->traitReset();
        $this->rateLimiter->reset();
    }
}
