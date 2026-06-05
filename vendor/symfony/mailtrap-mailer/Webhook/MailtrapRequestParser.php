<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Bridge\Mailtrap\Webhook;

use OmniMailDeps\Symfony\Component\HttpFoundation\ChainRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\Request;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcherInterface;
use OmniMailDeps\Symfony\Component\Mailer\Bridge\Mailtrap\RemoteEvent\MailtrapPayloadConverter;
use OmniMailDeps\Symfony\Component\RemoteEvent\Exception\ParseException;
use OmniMailDeps\Symfony\Component\RemoteEvent\RemoteEvent;
use OmniMailDeps\Symfony\Component\Webhook\Client\AbstractRequestParser;
use OmniMailDeps\Symfony\Component\Webhook\Exception\RejectWebhookException;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MailtrapRequestParser extends AbstractRequestParser
{
    public function __construct(private readonly MailtrapPayloadConverter $converter)
    {
    }
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([new MethodRequestMatcher('POST'), new IsJsonRequestMatcher()]);
    }
    protected function doParse(
        Request $request,
        #[\SensitiveParameter]
        string $secret
    ): RemoteEvent|array|null
    {
        if ($secret) {
            if (!$signature = $request->headers->get('Mailtrap-Signature')) {
                throw new RejectWebhookException(406, 'Signature is required.');
            }
            if (!hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
                throw new RejectWebhookException(406, 'Signature is wrong.');
            }
        }
        $payload = $request->toArray();
        if (!isset($payload['events'][0]['event']) || !isset($payload['events'][0]['message_id'])) {
            throw new RejectWebhookException(406, 'Payload is malformed.');
        }
        try {
            return array_map($this->converter->convert(...), $payload['events']);
        } catch (ParseException $e) {
            throw new RejectWebhookException(406, $e->getMessage(), $e);
        }
    }
}
