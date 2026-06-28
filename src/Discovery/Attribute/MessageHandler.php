<?php

declare (strict_types=1);
namespace JooosiMail\Discovery\Attribute;

use Attribute;
/**
 * Marks a message handler for Messenger routing.
 *
 * @since 0.1.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class MessageHandler
{
    public function __construct(public string $messageClass, public ?string $transport = null)
    {
    }
}
