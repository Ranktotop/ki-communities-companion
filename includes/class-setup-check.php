<?php
/**
 * Werkzeuge → KI-Communities Check
 *
 * Prüft, ob die Einstellungen, auf die sich das Plugin verlässt, noch stimmen (z. B. nach Plugin-Updates).
 * Ändert nichts, zeigt nur den Status an.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Setup_Check
{
    public static function init()
    {
        add_action('admin_menu', function () {
            add_management_page('KI-Communities Check', 'KI-Communities Check', 'manage_options', 'kic-setup-check', [__CLASS__, 'render']);
        });
    }

    public static function checks()
    {
        global $wpdb;
        $checks = [];

        // Fluent Community Player: YouTube erst beim Abspielen laden.
        $strategy = null;
        if (class_exists('\FluentCommunity\Modules\Integrations\FluentPlayer\Bootstrap')) {
            $settings = \FluentCommunity\Modules\Integrations\FluentPlayer\Bootstrap::getSettings();
            $strategy = $settings['behaviors']['load_strategy'] ?? null;
        }
        $checks[] = ['Community-Player lädt Videos erst beim Abspielen', $strategy === 'play', 'Ladestrategie: ' . ($strategy ?: 'unbekannt')];

        // FluentPlayer: YouTube-Datenschutzmodus.
        $fp = get_option('fluent_player_settings');
        $checks[] = ['FluentPlayer: YouTube-Datenschutzmodus (nocookie)', !empty($fp['youtube']['privacy_mode']), ''];

        // FluentCart: Pflichtcheckbox = Verzicht auf Widerrufsrecht.
        $terms = '';
        if (class_exists('\FluentCart\App\Services\Renderer\CheckoutFieldsSchema')) {
            $terms = \FluentCart\App\Services\Renderer\CheckoutFieldsSchema::getTermsText();
        }
        $checks[] = ['Kasse: Checkbox enthält nur den Verzicht auf das Widerrufsrecht', stripos($terms, 'Widerrufsrecht') !== false && stripos($terms, 'AGB') === false, wp_strip_all_tags($terms)];
        $checks[] = ['Kasse: Checkbox-Text passt sich dem Warenkorb an (digital / Dienstleistung)', KIC_Consent::isSupported(), 'Benötigt CheckoutFieldsSchema::$fieldsCache in FluentCart'];
        $serviceCategories = (array) apply_filters('kic/service_categories', ['live-call']);
        $existing = array_filter($serviceCategories, fn($slug) => term_exists($slug, 'product-categories'));
        $checks[] = ['Dienstleistungs-Kategorie vorhanden', (bool) $existing, implode(', ', $serviceCategories)];

        // EU-Lieferschwelle (OSS).
        $oss = KIC_OSS_Monitor::totals();
        $checks[] = ['EU-Lieferschwelle nicht erreicht (oder OSS aktiv)', KIC_OSS_Monitor::ossActive() || $oss['level'] === 'ok', KIC_OSS_Monitor::message($oss)];

        // Widerrufsbutton: abgeschlossene Bestellungen nicht gesperrt, Admin-Mail gesetzt.
        $store = get_option('fluent_cart_store_settings', []);
        $blocked = (array) ($store['fctcr_blocked_order_statuses'] ?? []);
        $checks[] = ['Widerrufsbutton auch für abgeschlossene Bestellungen', !in_array('completed', $blocked, true), 'Gesperrt: ' . implode(', ', $blocked)];
        $checks[] = ['Widerrufsbutton: Admin-Benachrichtigung', !empty($store['fctcr_admin_notification_email']), (string) ($store['fctcr_admin_notification_email'] ?? '')];

        // Seiten.
        foreach (['agb-widerrufsrecht' => 'AGB & Widerrufsrecht', 'datenschutzerklaerung' => 'Datenschutzerklärung', 'impressum' => 'Impressum', 'kontakt' => 'Kontakt', 'vom-vertrag-zuruecktreten' => 'Vom Vertrag zurücktreten'] as $slug => $label) {
            $page = get_page_by_path($slug);
            $checks[] = ['Seite „' . $label . '“ veröffentlicht', $page && $page->post_status === 'publish', '/' . $slug . '/'];
        }
        $agb = get_page_by_path('agb-widerrufsrecht');
        $checks[] = ['AGB-Seite hat Anker #widerrufsbelehrung', $agb && strpos($agb->post_content, 'id="widerrufsbelehrung"') !== false, ''];

        // Datenschutz-Einstellungen.
        $checks[] = ['WordPress-Avatare (Gravatar) aus', !get_option('show_avatars'), ''];
        $ff = get_option('_fluentform_global_form_settings', []);
        $checks[] = ['Fluent Forms: Honeypot an', ($ff['misc']['honeypotStatus'] ?? '') === 'yes', ''];

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $checks[] = ['OMGF (Google Fonts lokal) aktiv', is_plugin_active('host-webfonts-local/host-webfonts-local.php'), ''];
        $checks[] = ['Plugin „Font Awesome“ (CDN) inaktiv', !is_plugin_active('font-awesome/index.php'), ''];

        // Doppelte Ausführung: alte Code Snippets müssen aus sein.
        $snippetsTable = $wpdb->prefix . 'snippets';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $snippetsTable)) === $snippetsTable) {
            $active = $wpdb->get_col("SELECT name FROM {$snippetsTable} WHERE active = 1 AND (code LIKE '%fluent_cart/email_notification/mailer%' OR code LIKE '%fluent_cart/after_payment_methods%' OR code LIKE '%claude_fcom_localize%')");
            $checks[] = ['Keine alten Code Snippets für dieselben Funktionen aktiv', empty($active), implode(', ', $active)];
        }

        // Fluent Community Custom JS: kein externes Font-Awesome-Kit.
        $fcom = maybe_unserialize($wpdb->get_var("SELECT value FROM {$wpdb->prefix}fcom_meta WHERE meta_key = 'snippets_settings' LIMIT 1"));
        $checks[] = ['Fluent Community lädt kein externes Font-Awesome-Kit', is_array($fcom) && strpos((string) ($fcom['custom_js'] ?? ''), 'kit.fontawesome.com') === false, ''];

        return $checks;
    }

    public static function render()
    {
        echo '<div class="wrap"><h1>KI-Communities Check</h1>';
        echo '<p>Version ' . esc_html(KIC_VERSION) . '. Diese Seite ändert nichts, sie zeigt nur, ob die Einstellungen noch stimmen.</p>';
        echo '<table class="widefat striped"><thead><tr><th style="width:40px"></th><th>Prüfung</th><th>Details</th></tr></thead><tbody>';
        foreach (self::checks() as [$label, $ok, $detail]) {
            echo '<tr><td>' . ($ok ? '✅' : '⚠️') . '</td><td>' . esc_html($label) . '</td><td>' . esc_html($detail) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
