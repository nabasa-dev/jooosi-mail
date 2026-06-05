<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace OmniMailDeps\Symfony\Component\Mailer\Bridge\Mailjet\Webhook;

use OmniMailDeps\Symfony\Component\HttpFoundation\ChainRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\Request;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use OmniMailDeps\Symfony\Component\HttpFoundation\RequestMatcherInterface;
use OmniMailDeps\Symfony\Component\Mailer\Bridge\Mailjet\RemoteEvent\MailjetPayloadConverter;
use OmniMailDeps\Symfony\Component\RemoteEvent\Event\Mailer\AbstractMailerEvent;
use OmniMailDeps\Symfony\Component\RemoteEvent\Exception\ParseException;
use OmniMailDeps\Symfony\Component\Webhook\Client\AbstractRequestParser;
use OmniMailDeps\Symfony\Component\Webhook\Exception\RejectWebhookException;
final class MailjetRequestParser extends AbstractRequestParser
{
    public function __construct(private readonly MailjetPayloadConverter $converter)
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
    ): ?AbstractMailerEvent
    {
        if ($secret && !hash_equals('Basic ' . base64_encode($secret), $request->headers->get('Authorization', ''))) {
            throw new RejectWebhookException(403, 'Invalid credentials.');
        }
        try {
            return $this->converter->convert($request->toArray());
        } catch (ParseException $e) {
            throw new RejectWebhookException(406, $e->getMessage(), $e);
        }
    }
}
