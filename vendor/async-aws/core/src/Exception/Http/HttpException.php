<?php

declare (strict_types=1);
namespace OmniMailDeps\AsyncAws\Core\Exception\Http;

use OmniMailDeps\AsyncAws\Core\Exception\Exception;
use OmniMailDeps\Symfony\Contracts\HttpClient\ResponseInterface;
interface HttpException extends Exception
{
    public function getResponse(): ResponseInterface;
    public function getAwsCode(): ?string;
    public function getAwsType(): ?string;
    public function getAwsMessage(): ?string;
    public function getAwsDetail(): ?string;
}
