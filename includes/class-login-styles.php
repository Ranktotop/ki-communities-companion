<?php
/**
 * WordPress-Anmeldeseite im Brand-Look (wp-login.php: Anmelden, Passwort vergessen, Passwort festlegen).
 *
 * Die Seite lädt kein Theme und kein Divi, deshalb kommt das Stylesheet hier über login_enqueue_scripts.
 * Logo verlinkt auf die Startseite statt auf wordpress.org. Schriften aus uploads/kc-fonts (wie Portal und FluentBooking).
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Login_Styles
{
    const LOGO = '/wp-content/uploads/2026/10/kicommunities-portal-logo.png';

    public static function init()
    {
        add_action('login_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_filter('login_headerurl', [__CLASS__, 'home']);
        add_filter('login_headertext', [__CLASS__, 'title']);
        add_filter('login_display_language_dropdown', '__return_false');
    }

    public static function enqueue()
    {
        wp_enqueue_style('kic-login', KIC_URL . 'assets/login.css', ['login'], KIC_VERSION);
        wp_add_inline_style('kic-login', '#login h1.wp-login-logo a{background-image:url("' . esc_url(home_url(self::LOGO)) . '");}'
            . '@font-face{font-family:"Inter";src:url("' . esc_url(content_url('uploads/kc-fonts/inter-latin-var.woff2')) . '") format("woff2");font-weight:100 900;font-display:swap;}'
            . '@font-face{font-family:"Abel";src:url("' . esc_url(content_url('uploads/kc-fonts/abel-latin-400.woff2')) . '") format("woff2");font-weight:400;font-display:swap;}');
    }

    public static function home()
    {
        return home_url('/');
    }

    public static function title()
    {
        return get_bloginfo('name');
    }
}
