<?php

/**
 * @wordpress-plugin
 * Plugin Name:         Omni Mail
 * Plugin URI:          https://github.com/nabasa-dev/omni-mail
 * Description:         A modern, robust email sending solution for WordPress sites with advanced features including multiple provider support, queue-based processing, and comprehensive monitoring.
 * Text Domain:         omni-mail
 * Version:             1.0.0
 * Requires at least:   6.8
 * Requires PHP:        8.5
 * Author:              Omni Mail
 * Author URI:          https://github.com/nabasa-dev
 * License:             GPL-3.0-or-later
 *
 * @package             OmniMail
 * @author              Joshua Gugun Siagian <suabahasa@gmail.com>
 */
declare (strict_types=1);
namespace OmniMailDeps;

\defined('ABSPATH') || exit;
if (\file_exists(__DIR__ . '/vendor/autoload.php')) {
    if (\file_exists(__DIR__ . '/vendor/scoper-autoload.php')) {
        require_once __DIR__ . '/vendor/scoper-autoload.php';
    } else {
        require_once __DIR__ . '/vendor/autoload.php';
    }
    if (\file_exists(__DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php')) {
        require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
    }
    require_once __DIR__ . '/constant.php';
    \OmniMail\Bootstrap\Plugin::boot(__FILE__);
}
