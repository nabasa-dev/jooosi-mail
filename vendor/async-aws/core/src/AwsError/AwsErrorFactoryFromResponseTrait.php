<?php

namespace OmniMailDeps\AsyncAws\Core\AwsError;

use OmniMailDeps\Symfony\Contracts\HttpClient\ResponseInterface;
trait AwsErrorFactoryFromResponseTrait
{
    public function createFromResponse(ResponseInterface $response): AwsError
    {
        $content = $response->getContent(\false);
        $headers = $response->getHeaders(\false);
        return $this->createFromContent($content, $headers);
    }
}
