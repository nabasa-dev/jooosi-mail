<?php

declare (strict_types=1);
namespace OmniMail\Infrastructure\WordPress;

use OmniMail\Infrastructure\Event\EventPublisherInterface;
use function apply_filters;
use function do_action;
/**
 * Publishes Omni Mail events through WordPress hooks and filters.
 *
 * @since 0.1.0
 */
final readonly class WordPressEventPublisher implements EventPublisherInterface
{
    /**
     * @since 0.1.0
     */
    public function doAction(string $hookName, mixed ...$arguments): void
    {
        do_action($hookName, ...$arguments);
    }
    /**
     * @since 0.1.0
     */
    public function applyFilters(string $hookName, mixed $value, mixed ...$arguments): mixed
    {
        return apply_filters($hookName, $value, ...$arguments);
    }
}
