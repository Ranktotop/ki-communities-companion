<?php
/**
 * Kasse, Kundenkonto und Beleg-/Danke-Seite von Suchmaschinen ausschließen (noindex, nofollow).
 *
 * Die Seiten kommen aus den FluentCart-Einstellungen (checkout_page_id, customer_profile_page_id, receipt_page_id),
 * damit es auch nach einem Seitenwechsel stimmt. Weitere Seiten-IDs per Filter kic/noindex_page_ids.
 * Auf der Seite ist kein SEO-Plugin installiert, das diese Einstellung übernehmen könnte.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Shop_Noindex
{
    public static function init()
    {
        add_filter('wp_robots', [__CLASS__, 'robots'], 20);
    }

    public static function pageIds()
    {
        $store = get_option('fluent_cart_store_settings', []);
        $ids   = [];
        foreach (['checkout_page_id', 'customer_profile_page_id', 'receipt_page_id'] as $key) {
            if (!empty($store[$key])) {
                $ids[] = (int) $store[$key];
            }
        }

        return array_values(array_unique(array_filter((array) apply_filters('kic/noindex_page_ids', $ids))));
    }

    public static function robots($robots)
    {
        if (is_page(self::pageIds())) {
            return wp_robots_no_robots($robots);
        }

        return $robots;
    }
}
