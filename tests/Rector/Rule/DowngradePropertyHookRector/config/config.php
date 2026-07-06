<?php

declare(strict_types=1);

use JooosiMail\Rector\Rule\DowngradePropertyHookRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withRules([
        DowngradePropertyHookRector::class,
    ]);
