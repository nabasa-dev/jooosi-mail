<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

define('OMNI_MAIL_VERSION', '1.0.2');
define('OMNI_MAIL_PLUGIN_FILE', __DIR__ . '/omni-mail.php');
define('OMNI_MAIL_PLUGIN_BASENAME', plugin_basename(OMNI_MAIL_PLUGIN_FILE));
define('OMNI_MAIL_PLUGIN_DIR', __DIR__);
define('OMNI_MAIL_PLUGIN_URL', plugin_dir_url(OMNI_MAIL_PLUGIN_FILE));
define('OMNI_MAIL_CACHE_DIR', OMNI_MAIL_PLUGIN_DIR . '/var/cache');
