<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Bridge\Sweego\Webhook;

use OmniMailDeps\Symfony\Component\HttpFoundation\ChainRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\Request;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\HeaderRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcherInterface;
use OmniMailDeps\Symfony\Component\Mailer\Bridge\Sweego\RemoteEvent\SweegoPayloadConverter;
use OmniMailDeps\Symfony\Component\RemoteEvent\Event\Mailer\AbstractMailerEvent;
use OmniMailDeps\Symfony\Component\RemoteEvent\Exception\ParseException;
use OmniMailDeps\Symfony\Component\Webhook\Client\AbstractRequestParser;
use OmniMailDeps\Symfony\Component\Webhook\Exception\RejectWebhookException;
final class SweegoRequestParser extends AbstractRequestParser
{
    public function __construct(private readonly SweegoPayloadConverter $converter)
    {
    }
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([new MethodRequestMatcher('POST'), new IsJsonRequestMatcher(), new HeaderRequestMatcher(['webhook-id', 'webhook-timestamp', 'webhook-signature'])]);
    }
    protected function doParse(
        Request $request,
        #[\SensitiveParameter]
        string $secret
    ): ?AbstractMailerEvent
    {
        $content = $request->toArray();
        if (!isset($content['event_type']) || !isset($content['timestamp']) || !isset($content['headers']) || !isset($content['headers']['x-transaction-id']) || !isset($content['recipient'])) {
            throw new RejectWebhookException(406, 'Payload is malformed.');
        }
        if ($secret) {
            if (!$request->headers->get('webhook-id') && !$request->headers->get('webhook-timestamp') && !$request->headers->get('webhook-signature')) {
                throw new RejectWebhookException(406, 'Signature is required.');
            }
            $this->validateSignature($request, $secret);
        }
        try {
            return $this->converter->convert($content);
        } catch (ParseException $e) {
            throw new RejectWebhookException(406, $e->getMessage(), $e);
        }
    }
    private function validateSignature(Request $request, string $secret): void
    {
        $contentToSign = \sprintf('%s.%s.%s', $request->headers->get('webhook-id'), $request->headers->get('webhook-timestamp'), $request->getContent());
        // see https://learn.sweego.io/docs/webhooks/webhook_signature
        $computedSignature = base64_encode(hash_hmac('sha256', $contentToSign, base64_decode($secret), \true));
        if (!hash_equals($request->headers->get('webhook-signature'), $computedSignature)) {
            throw new RejectWebhookException(403, 'Invalid signature.');
        }
    }
}
