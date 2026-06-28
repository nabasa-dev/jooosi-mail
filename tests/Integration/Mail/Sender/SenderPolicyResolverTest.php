<?php

declare(strict_types=1);

namespace JooosiMail\Tests\Integration\Mail\Sender;

use JooosiMail\Mail\Delivery\EmailFactory;
use JooosiMail\Mail\Sender\SenderPolicyResolver;
use JooosiMail\Mail\ValueObject\MailAddress;
use JooosiMail\Mail\ValueObject\MailRequest;
use JooosiMail\Tests\Integration\Support\JooosiMailIntegrationTestCase;

/**
 * Covers delivery-time sender policy resolution.
 *
 * @since 0.1.0
 */
final class SenderPolicyResolverTest extends JooosiMailIntegrationTestCase
{
    /**
     * @since 0.1.0
     */
    public function testConnectionSenderReplacesWordPressDefaultAndSetsEnvelopeSender(): void
    {
        $this->optionStore()->set('settings.mail.sender.email', 'global@example.test');
        $this->optionStore()->set('settings.mail.sender.name', 'Global Sender');
        $connection = $this->createNullConnection([
            'sender' => [
                'email' => 'connection@example.test',
                'name' => 'Connection Sender',
                'return_path_mode' => SenderPolicyResolver::RETURN_PATH_MODE_MATCH_FROM,
            ],
        ]);
        $mailRequest = $this->createPolicyMailRequest([
            'jooosi_mail_default_from_applied' => true,
        ]);

        $resolved = $this->senderPolicyResolver()->apply($mailRequest, $connection);
        $envelope = $this->emailFactory()->createEnvelope($resolved);

        self::assertSame('connection@example.test', $resolved->from[0]->address);
        self::assertSame('Connection Sender', $resolved->from[0]->name);
        self::assertSame('connection@example.test', $resolved->envelopeSender?->address);
        self::assertNotNull($envelope);
        self::assertSame('connection@example.test', $envelope->getSender()->getAddress());
        self::assertSame(['recipient@example.test'], array_map(static fn ($address): string => $address->getAddress(), $envelope->getRecipients()));
    }

    /**
     * @since 0.1.0
     */
    public function testExplicitFromIsPreservedWhenNoForcePolicyApplies(): void
    {
        $connection = $this->createNullConnection([
            'sender' => [
                'email' => 'connection@example.test',
                'name' => 'Connection Sender',
            ],
        ]);
        $mailRequest = $this->createPolicyMailRequest();

        $resolved = $this->senderPolicyResolver()->apply($mailRequest, $connection);

        self::assertSame('plugin@example.test', $resolved->from[0]->address);
        self::assertSame('Plugin Sender', $resolved->from[0]->name);
    }

    /**
     * @since 0.1.0
     */
    public function testGlobalForceAppliesWhenConnectionDoesNotForce(): void
    {
        $this->optionStore()->set('settings.mail.sender.email', 'global@example.test');
        $this->optionStore()->set('settings.mail.sender.force_email', true);
        $connection = $this->createNullConnection();
        $mailRequest = $this->createPolicyMailRequest();

        $resolved = $this->senderPolicyResolver()->apply($mailRequest, $connection);

        self::assertSame('global@example.test', $resolved->from[0]->address);
        self::assertSame('Plugin Sender', $resolved->from[0]->name);
    }

    /**
     * @since 0.1.0
     */
    public function testConnectionForceOverridesExplicitFrom(): void
    {
        $connection = $this->createNullConnection([
            'sender' => [
                'email' => 'forced@example.test',
                'name' => 'Forced Sender',
                'force_email' => true,
                'force_name' => true,
            ],
        ]);
        $mailRequest = $this->createPolicyMailRequest();

        $resolved = $this->senderPolicyResolver()->apply($mailRequest, $connection);

        self::assertSame('forced@example.test', $resolved->from[0]->address);
        self::assertSame('Forced Sender', $resolved->from[0]->name);
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @since 0.1.0
     */
    private function createPolicyMailRequest(array $metadata = []): MailRequest
    {
        return new MailRequest(
            from: [new MailAddress('plugin@example.test', 'Plugin Sender')],
            to: [new MailAddress('recipient@example.test')],
            cc: [],
            bcc: [],
            replyTo: [],
            subject: 'Sender policy test',
            textBody: 'Body',
            htmlBody: null,
            attachments: [],
            headers: [],
            metadata: $metadata,
        );
    }

    /**
     * @since 0.1.0
     */
    private function senderPolicyResolver(): SenderPolicyResolver
    {
        return $this->container()->get(SenderPolicyResolver::class);
    }

    /**
     * @since 0.1.0
     */
    private function emailFactory(): EmailFactory
    {
        return $this->container()->get(EmailFactory::class);
    }
}
