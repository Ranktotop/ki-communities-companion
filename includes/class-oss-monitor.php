<?php
/**
 * Warnung vor der EU-Lieferschwelle (10.000 €, § 3a Abs. 5 UStG).
 *
 * Solange der Nettoumsatz mit Privatkunden im EU-Ausland im laufenden und im vorigen Kalenderjahr
 * höchstens 10.000 € beträgt, gilt deutsche Umsatzsteuer. Darüber gilt die Steuer des Käuferlandes (OSS).
 * FluentCart überwacht diese Grenze nicht. Dieses Modul summiert die bezahlten Live-Bestellungen mit
 * Rechnungsland in der EU (außer Deutschland), ohne Reverse-Charge-Bestellungen von Firmenkunden,
 * abzüglich Erstattungen und Steuer, und warnt
 * - ab 8.000 € (Filter kic/oss_warning_threshold) und ab 10.000 € (Filter kic/oss_limit),
 * - im WordPress-Admin als Hinweis und einmal pro Stufe und Jahr per E-Mail (Filter kic/oss_notify_email).
 * Ist die EU-Methode in FluentCart bereits „OSS“, bleibt es still.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_OSS_Monitor
{
    const CRON_HOOK  = 'kic_oss_monitor_check';
    const CACHE_KEY  = 'kic_oss_totals';
    const SENT_KEY   = 'kic_oss_notified';

    public static function init()
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'check']);
        add_action('fluent_cart/order_paid_done', [__CLASS__, 'onOrderChanged'], 99);
        add_action('fluent_cart/order_refunded', [__CLASS__, 'onOrderChanged'], 99);
        add_action('admin_notices', [__CLASS__, 'adminNotice']);

        // Plugin-Updates lösen keinen Aktivierungs-Hook aus, daher hier sicherstellen.
        add_action('init', function () {
            if (!wp_next_scheduled(self::CRON_HOOK)) {
                wp_schedule_event(time() + 600, 'daily', self::CRON_HOOK);
            }
        });
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function onOrderChanged()
    {
        delete_transient(self::CACHE_KEY);
        self::check();
    }

    public static function limit()
    {
        return (float) apply_filters('kic/oss_limit', 10000);
    }

    public static function warningThreshold()
    {
        return (float) apply_filters('kic/oss_warning_threshold', 8000);
    }

    /**
     * EU-Methode in FluentCart steht schon auf OSS: Steuer des Käuferlandes wird bereits berechnet.
     */
    public static function ossActive()
    {
        $settings = get_option('fluent_cart_tax_configuration_settings', []);
        return ($settings['eu_vat_settings']['method'] ?? '') === 'oss';
    }

    private static function euForeignCountries()
    {
        $eu = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];
        if (class_exists('\FluentCart\App\Services\Localization\LocalizationManager')) {
            $continent = \FluentCart\App\Services\Localization\LocalizationManager::getInstance()->taxContinents('EU');
            if (!empty($continent['countries']) && is_array($continent['countries'])) {
                $eu = $continent['countries'];
            }
        }

        $store = get_option('fluent_cart_store_settings', []);
        $home  = strtoupper((string) ($store['store_country'] ?? 'DE')) ?: 'DE';

        return array_values(array_diff(array_map('strtoupper', $eu), [$home]));
    }

    /**
     * Nettoumsatz (in Euro) mit Privatkunden im EU-Ausland für ein Kalenderjahr.
     */
    public static function netTotalForYear($year)
    {
        global $wpdb;

        $countries = self::euForeignCountries();
        if (!$countries) {
            return 0.0;
        }

        $modes = apply_filters('kic/oss_include_test_orders', false) ? ['live', 'test'] : ['live'];

        $placeholders = implode(',', array_fill(0, count($countries), '%s'));
        $modeHolders  = implode(',', array_fill(0, count($modes), '%s'));
        $from = sprintf('%04d-01-01 00:00:00', $year);
        $to   = sprintf('%04d-01-01 00:00:00', $year + 1);

        $p   = $wpdb->prefix;
        $sql = "SELECT o.total_amount, o.tax_total, o.total_refund
                FROM {$p}fct_orders o
                INNER JOIN {$p}fct_order_addresses a ON a.order_id = o.id AND a.type = 'billing'
                LEFT JOIN {$p}fct_order_meta bm ON bm.order_id = o.id AND bm.meta_key = 'business_info'
                WHERE a.country IN ($placeholders)
                  AND o.mode IN ($modeHolders)
                  AND o.payment_status IN ('paid', 'partially_refunded')
                  AND o.tax_behavior <> 0
                  AND (bm.meta_value IS NULL OR bm.meta_value NOT LIKE '%vat_number%')
                  AND o.created_at >= %s AND o.created_at < %s";

        $rows = $wpdb->get_results($wpdb->prepare($sql, array_merge($countries, $modes, [$from, $to])));

        $net = 0;
        foreach ((array) $rows as $row) {
            $total = (int) $row->total_amount;
            if ($total <= 0) {
                continue;
            }
            $netOfOrder = $total - (int) $row->tax_total;
            $kept       = max(0, $total - (int) $row->total_refund) / $total;
            $net       += $netOfOrder * $kept;
        }

        return round($net / 100, 2);
    }

    /**
     * @return array{year:int, current:float, previous:float, level:string}
     *   level: ok | warning (ab Warnschwelle) | limit (Grenze im laufenden Jahr überschritten)
     *          | previous (Grenze im Vorjahr überschritten, OSS gilt ab 1. Januar)
     */
    public static function totals()
    {
        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached) && ($cached['year'] ?? 0) === (int) wp_date('Y')) {
            return $cached;
        }

        $year     = (int) wp_date('Y');
        $current  = self::netTotalForYear($year);
        $previous = self::netTotalForYear($year - 1);

        $level = 'ok';
        if ($previous > self::limit()) {
            $level = 'previous';
        } elseif ($current > self::limit()) {
            $level = 'limit';
        } elseif ($current >= self::warningThreshold()) {
            $level = 'warning';
        }

        $totals = compact('year', 'current', 'previous', 'level');
        set_transient(self::CACHE_KEY, $totals, HOUR_IN_SECONDS);

        return $totals;
    }

    public static function message(array $t)
    {
        $eur = function ($v) {
            return number_format_i18n($v, 2) . ' €';
        };

        switch ($t['level']) {
            case 'previous':
                return sprintf('Dein Nettoumsatz mit Privatkunden im EU-Ausland lag %1$d bei %2$s und damit über %3$s. Seit 1. Januar %4$d gilt für diese Verkäufe die Umsatzsteuer des Käuferlandes: in FluentCart die EU-Methode auf „OSS“ stellen, beim BZSt für OSS registrieren und Steuerberater informieren.', $t['year'] - 1, $eur($t['previous']), $eur(self::limit()), $t['year']);
            case 'limit':
                return sprintf('Dein Nettoumsatz mit Privatkunden im EU-Ausland %1$d liegt bei %2$s und damit über %3$s. Ab jetzt gilt für diese Verkäufe die Umsatzsteuer des Käuferlandes: in FluentCart die EU-Methode auf „OSS“ stellen, beim BZSt für OSS registrieren (bis zum 10. des Folgemonats) und Steuerberater informieren.', $t['year'], $eur($t['current']), $eur(self::limit()));
            case 'warning':
                return sprintf('Dein Nettoumsatz mit Privatkunden im EU-Ausland %1$d liegt bei %2$s. Ab %3$s gilt für diese Verkäufe die Umsatzsteuer des Käuferlandes (OSS). Plane die Umstellung in FluentCart und die OSS-Registrierung beim BZSt rechtzeitig ein.', $t['year'], $eur($t['current']), $eur(self::limit()));
        }

        return sprintf('Nettoumsatz mit Privatkunden im EU-Ausland %1$d: %2$s von %3$s (Vorjahr: %4$s).', $t['year'], $eur($t['current']), $eur(self::limit()), $eur($t['previous']));
    }

    public static function check()
    {
        if (self::ossActive()) {
            return;
        }

        $t = self::totals();
        if ($t['level'] === 'ok') {
            return;
        }

        $sent = get_option(self::SENT_KEY, []);
        $sent = is_array($sent) ? $sent : [];
        if (!empty($sent[$t['year']][$t['level']])) {
            return;
        }

        $to = apply_filters('kic/oss_notify_email', get_option('admin_email'));
        if ($to) {
            $subject = $t['level'] === 'warning'
                ? 'KI-Communities Shop: EU-Lieferschwelle bald erreicht'
                : 'KI-Communities Shop: EU-Lieferschwelle überschritten, OSS nötig';
            wp_mail($to, $subject, self::message($t) . "\n\nDiese Mail kommt vom KI-Communities Companion (Werkzeuge → KI-Communities Check).");
        }

        $sent[$t['year']][$t['level']] = time();
        update_option(self::SENT_KEY, $sent, false);
    }

    public static function adminNotice()
    {
        if (!current_user_can('manage_options') || self::ossActive()) {
            return;
        }

        $t = self::totals();
        if ($t['level'] === 'ok') {
            return;
        }

        $class = $t['level'] === 'warning' ? 'notice-warning' : 'notice-error';
        echo '<div class="notice ' . esc_attr($class) . '"><p><strong>EU-Lieferschwelle:</strong> ' . esc_html(self::message($t)) . '</p></div>';
    }
}
