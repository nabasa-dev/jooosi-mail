<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JooosiMailDeps\Symfony\Component\Mailer\Bridge\Resend\Webhook;

use JooosiMailDeps\Symfony\Component\HttpFoundation\ChainRequestMatcher;
use JooosiMailDeps\Symfony\Component\HttpFoundation\HeaderBag;
use JooosiMailDeps\Symfony\Component\HttpFoundation\Request;
use JooosiMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\HeaderRequestMatcher;
use JooosiMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use JooosiMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use JooosiMailDeps\Symfony\Component\HttpFoundation\RequestMatcherInterface;
use JooosiMailDeps\Symfony\Component\Mailer\Bridge\Resend\RemoteEvent\ResendPayloadConverter;
use JooosiMailDeps\Symfony\Component\Mailer\Exception\InvalidArgumentException;
use JooosiMailDeps\Symfony\Component\RemoteEvent\Event\Mailer\AbstractMailerEvent;
use JooosiMailDeps\Symfony\Component\RemoteEvent\Exception\ParseException;
use JooosiMailDeps\Symfony\Component\Webhook\Client\AbstractRequestParser;
use JooosiMailDeps\Symfony\Component\Webhook\Exception\RejectWebhookException;
final class ResendRequestParser extends AbstractRequestParser
{
    public function __construct(private readonly ResendPayloadConverter $converter)
    {
    }
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([new MethodRequestMatcher('POST'), new IsJsonRequestMatcher(), new HeaderRequestMatcher(['svix-id', 'svix-timestamp', 'svix-signature'])]);
    }
    protected function doParse(
        Request $request,
        #[\SensitiveParameter]
        string $secret
    ): ?AbstractMailerEvent
    {
        if (!$secret) {
            throw new InvalidArgumentException('A non-empty secret is required.');
        }
        $content = $request->toArray();
        if (!isset($content['type']) || !isset($content['created_at']) || !isset($content['data']) || !isset($content['data']['created_at']) || !isset($content['data']['email_id']) || !isset($content['data']['from']) || !isset($content['data']['to']) || !isset($content['data']['subject'])) {
            throw new RejectWebhookException(406, 'Payload is malformed.');
        }
        $this->validateSignature($request->getContent(), $request->headers, $secret);
        try {
            return $this->converter->convert($content);
        } catch (ParseException $e) {
            throw new RejectWebhookException(406, $e->getMessage(), $e);
        }
    }
    private function validateSignature(string $payload, HeaderBag $headers, string $secret): void
    {
        $secret = $this->decodeSecret($secret);
        $messageId = $headers->get('svix-id');
        $messageTimestamp = (int) $headers->get('svix-timestamp');
        $messageSignature = $headers->get('svix-signature');
        $signature = $this->sign($secret, $messageId, $messageTimestamp, $payload);
        $expectedSignature = explode(',', $signature, 2)[1];
        $passedSignatures = explode(' ', $messageSignature);
        $signatureFound = \false;
        foreach ($passedSignatures as $versionedSignature) {
            $signatureParts = explode(',', $versionedSignature, 2);
            $version = $signatureParts[0];
            if ('v1' !== $version) {
                continue;
            }
            $passedSignature = $signatureParts[1];
            if (hash_equals($expectedSignature, $passedSignature)) {
                $signatureFound = \true;
                break;
            }
        }
        if (!$signatureFound) {
            throw new RejectWebhookException(406, 'No signatures found matching the expected signature.');
        }
    }
    private function sign(string $secret, string $messageId, int $timestamp, string $payload): string
    {
        $toSign = \sprintf('%s.%s.%s', $messageId, $timestamp, $payload);
        $hash = hash_hmac('sha256', $toSign, $secret);
        $signature = base64_encode(pack('H*', $hash));
        return 'v1,' . $signature;
    }
    private function decodeSecret(string $secret): string
    {
        $prefix = 'whsec_';
        if (str_starts_with($secret, $prefix)) {
            $secret = substr($secret, \strlen($prefix));
        }
        return base64_decode($secret);
    }
}
