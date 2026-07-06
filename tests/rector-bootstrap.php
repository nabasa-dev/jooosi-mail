<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

if (! file_exists($autoloadPath)) {
    fwrite(STDERR, "Composer dependencies are missing. Run composer install before executing Rector tests.\n");
    exit(1);
}

require_once $autoloadPath;
