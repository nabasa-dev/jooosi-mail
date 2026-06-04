<?php

declare(strict_types=1);

namespace OmniMail\Tests\Integration\Settings;

use OmniMail\Settings\Config;
use WP_UnitTestCase;

/**
 * Covers WordPress-backed config persistence behavior.
 *
 * @since 0.1.0
 */
final class ConfigTest extends WP_UnitTestCase
{
    /**
     * @since 0.1.0
     */
    public function testSetGetAndDeletePersistNestedConfigPaths(): void
    {
        delete_option('omni_mail_config');

        $config = new Config();

        self::assertTrue($config->set('settings.mail.intercept.enabled', false));
        self::assertFalse($config->get('settings.mail.intercept.enabled', true));
        self::assertSame(
            [
                'settings' => [
                    'mail' => [
                        'intercept' => [
                            'enabled' => false,
                        ],
                    ],
                ],
            ],
            get_option('omni_mail_config'),
        );

        self::assertTrue($config->delete('settings.mail.intercept.enabled'));
        self::assertFalse(get_option('omni_mail_config', false));
    }

    /**
     * @since 0.1.0
     */
    public function testAllReturnsTheSharedConfigPayload(): void
    {
        delete_option('omni_mail_config');

        $config = new Config();
        $payload = [
            'routing' => [
                'strategy' => 'weighted_random',
            ],
            'mail' => [
                'default_connection_id' => 42,
            ],
        ];

        self::assertTrue($config->set('', $payload));
        self::assertSame($payload, $config->all());
    }
}
