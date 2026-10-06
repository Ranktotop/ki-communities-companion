<?php
/**
 * Fluent Community Portal: zusätzliche Styles.
 *
 * - Hinweisband auf YouTube-Videos vor dem Abspielen (Zwei-Klick-Lösung; der Player lädt YouTube erst beim Klick,
 *   Voraussetzung: Ladestrategie „play“ in den Fluent-Community-Player-Einstellungen).
 * - Font Awesome 5 Solid lokal für das Link-Icon in ul.cst_linklist (ersetzt das frühere Font-Awesome-Kit).
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Portal_Styles
{
    public static function init()
    {
        add_action('fluent_community/portal_head', [__CLASS__, 'render']);
    }

    public static function render()
    {
        $font   = KIC_URL . 'assets/fonts/fa-solid-900.woff2?ver=' . KIC_VERSION;
        $notice = apply_filters('kic/youtube_notice', 'Mit dem Abspielen wird das Video von YouTube geladen. Dabei werden Daten an Google übermittelt, siehe Datenschutzerklärung.');

        $css = '@font-face{font-family:"Font Awesome 5 Free";font-style:normal;font-weight:900;font-display:swap;src:url("' . esc_url($font) . '") format("woff2");}'
            . '.fluent-player[data-provider="youtube"]{position:relative;}'
            . '.fluent-player[data-provider="youtube"]:has(media-player:not([data-started]))::after{content:"' . str_replace(['\\', '"', '<'], ['\\\\', '\"', ''], $notice) . '";position:absolute;left:0;right:0;top:0;z-index:5;padding:6px 10px;font-size:12px;line-height:1.4;color:#fff;background:rgba(0,0,0,.55);pointer-events:none;}';

        echo '<style id="kic-portal-styles">' . $css . '</style>' . "\n";
    }
}
