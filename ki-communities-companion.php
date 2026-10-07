<?php
/**
 * Plugin Name:       KI-Communities Companion
 * Description:       Rechtliche und datenschutzbezogene Ergänzungen für FluentCart und Fluent Community auf ki-communities.de.
 * Version:           1.4.2
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Marc Meese
 * Author URI:        https://marcmeese.de
 * Text Domain:       ki-communities-companion
 * Update URI:        https://github.com/Ranktotop/ki-communities-companion
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KIC_VERSION', '1.4.2');
define('KIC_FILE', __FILE__);
define('KIC_DIR', plugin_dir_path(__FILE__));
define('KIC_URL', plugin_dir_url(__FILE__));

require_once KIC_DIR . 'includes/class-consent.php';
require_once KIC_DIR . 'includes/class-legal-email.php';
require_once KIC_DIR . 'includes/class-checkout-notice.php';
require_once KIC_DIR . 'includes/class-community-media.php';
require_once KIC_DIR . 'includes/class-portal-styles.php';
require_once KIC_DIR . 'includes/class-theme-colors.php';
require_once KIC_DIR . 'includes/class-oss-monitor.php';
require_once KIC_DIR . 'includes/class-translation-fixes.php';
require_once KIC_DIR . 'includes/class-setup-check.php';

KIC_Consent::init();
KIC_Legal_Email::init();
KIC_Checkout_Notice::init();
KIC_Community_Media::init();
KIC_Portal_Styles::init();
KIC_Theme_Colors::init();
KIC_OSS_Monitor::init();
KIC_Translation_Fixes::init();
KIC_Setup_Check::init();

register_activation_hook(__FILE__, ['KIC_Community_Media', 'activate']);
register_deactivation_hook(__FILE__, ['KIC_Community_Media', 'deactivate']);
register_deactivation_hook(__FILE__, ['KIC_OSS_Monitor', 'deactivate']);

// Updates über GitHub-Releases (Asset ki-communities-companion.zip).
if (file_exists(KIC_DIR . 'vendor/autoload.php')) {
    require_once KIC_DIR . 'vendor/autoload.php';

    $kic_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/Ranktotop/ki-communities-companion/',
        __FILE__,
        'ki-communities-companion'
    );
    $kic_update_checker->getVcsApi()->enableReleaseAssets('/ki-communities-companion\.zip$/');
}
