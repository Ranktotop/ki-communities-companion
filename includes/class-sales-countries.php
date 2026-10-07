<?php
/**
 * Verkauf nur an Kunden mit Rechnungsadresse in bestimmten Ländern (Standard: Deutschland).
 *
 * FluentCart hat keine Einstellung „Verkaufen nach“. Dieses Modul
 * - begrenzt die Länderauswahl an der Kasse (Filter fluent_cart/util/countries, nur im Frontend),
 * - lehnt Bestellungen mit anderer Rechnungsadresse serverseitig ab (fluent_cart/checkout/validate_before_process),
 * - weist zu Beginn der Kasse auf die Beschränkung hin (§ 312j Abs. 1 BGB).
 *
 * Länder per Filter kic/sales_countries (ISO-Codes), Hinweistext per kic/sales_countries_notice.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Sales_Countries
{
    public static function init()
    {
        add_filter('fluent_cart/util/countries', [__CLASS__, 'limitCountryList'], 20);
        add_filter('fluent_cart/checkout/validate_before_process', [__CLASS__, 'validateCheckout'], 20, 2);
        add_filter('fluent_cart/checkout_page_notices', [__CLASS__, 'addNotice'], 20);
    }

    public static function countries()
    {
        return array_map('strtoupper', (array) apply_filters('kic/sales_countries', ['DE']));
    }

    /**
     * Admin-Oberfläche (inkl. REST-API von FluentCart) behält die volle Liste, z. B. für die Ladenadresse.
     */
    private static function isFrontend()
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        return !is_admin() || wp_doing_ajax();
    }

    public static function limitCountryList($options)
    {
        if (!self::isFrontend() || !is_array($options)) {
            return $options;
        }

        $allowed  = self::countries();
        $filtered = array_values(array_filter($options, function ($option) use ($allowed) {
            return in_array(strtoupper((string) ($option['value'] ?? '')), $allowed, true);
        }));

        return $filtered ?: $options;
    }

    public static function validateCheckout($isValid, $data)
    {
        if (is_wp_error($isValid)) {
            return $isValid;
        }

        $country = strtoupper((string) ($data['billing_country'] ?? ''));
        if ($country && !in_array($country, self::countries(), true)) {
            return new \WP_Error('kic_country_not_allowed', self::noticeText());
        }

        return $isValid;
    }

    public static function addNotice($notices)
    {
        $notices   = is_array($notices) ? $notices : [];
        $notices[] = ['content' => esc_html(self::noticeText())];

        return $notices;
    }

    public static function noticeText()
    {
        return (string) apply_filters('kic/sales_countries_notice', 'Wir verkaufen derzeit nur an Kundinnen und Kunden mit Rechnungsadresse in Deutschland.');
    }
}
