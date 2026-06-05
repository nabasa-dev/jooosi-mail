<?php

declare (strict_types=1);
namespace OmniMail\Infrastructure\Database;

/**
 * Resolves plugin table names using the active WordPress prefix.
 *
 * @since 0.1.0
 */
final readonly class TableNameResolver
{
    /**
     * Resolve the full table name for an Omni Mail table suffix.
     *
     * @since 0.1.0
     */
    public function resolve(string $suffix): string
    {
        global $wpdb;
        return $wpdb->prefix . 'omni_mail_' . $suffix;
    }
}
