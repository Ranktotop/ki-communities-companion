<?php
/**
 * Status-Badges im Kundenkonto übersetzbar machen.
 *
 * Das Kundenkonto von FluentCart (Vue-App) übersetzt Status wie „Completed“ nur, wenn der englische Text in der
 * Übersetzungsliste fürs Kundenkonto steht. Die Status fehlen dort, darum erscheinen sie auf Englisch.
 * Diese Klasse ergänzt die fehlenden Einträge über __() mit der Textdomain von FluentCart. Die deutschen Texte kommen
 * damit weiterhin aus der Loco-Übersetzung von FluentCart, hier wird nichts fest übersetzt.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Dashboard_Strings
{
    /** Status-Texte aus der Badge-Tabelle des Kundenkontos (FluentCart assets/Start7.js). */
    private const STATUS_LABELS = [
        'Completed', 'Paid', 'Active', 'Published', 'Draft', 'Shipped', 'Success', 'Licensed', 'Succeeded', 'Failed',
        'Error', 'Canceled', 'Expired', 'Partially Paid', 'Intended', 'Scheduled', 'On Hold', 'Pending', 'Unpaid',
        'Warning', 'Processing', 'Future', 'Inactive', 'Dispute', 'Disabled', 'Beta', 'Subscription', 'Renewal',
        'Payment', 'Unshipped', 'Trialing',
    ];

    public static function init()
    {
        add_filter('fluent_cart/customer_profile_translations', [__CLASS__, 'add_status_labels']);
    }

    public static function add_status_labels($translations)
    {
        if (!is_array($translations)) {
            return $translations;
        }
        foreach (self::STATUS_LABELS as $label) {
            if (!isset($translations[$label])) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Texte stehen in der Loco-Datei von FluentCart.
                $translations[$label] = __($label, 'fluent-cart');
            }
        }

        return $translations;
    }
}
