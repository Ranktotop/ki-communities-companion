<?php
/**
 * Landingpage je Produkt: Werkzeuge → Produkt-Landingpages.
 *
 * Ordnet einem FluentCart-Produkt eine eigene WordPress-Seite zu (z. B. die Buchungsseite eines Live-Calls).
 * Die Produkt-Karten verlinken dann auf diese Seite statt auf die Produktseite. Private Produkte erscheinen in den
 * Karten nur, wenn ihnen eine Landingpage zugeordnet ist (Produkte, die nur über ein Buchungsformular gekauft werden
 * sollen, stehen in FluentCart auf „Privat“).
 *
 * Gespeichert in der Option kic_product_landingpages als [Produkt-ID => Seiten-ID].
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Product_Landingpages
{
    const OPTION = 'kic_product_landingpages';

    public static function init()
    {
        add_action('admin_menu', function () {
            add_management_page('Produkt-Landingpages', 'Produkt-Landingpages', 'manage_options', 'kic-landingpages', [__CLASS__, 'render']);
        });
        add_action('admin_post_kic_save_landingpages', [__CLASS__, 'save']);
    }

    /** Seiten-ID der Landingpage eines Produkts, 0 wenn keine (oder die Seite nicht veröffentlicht ist). */
    public static function page_id($productId)
    {
        $map    = get_option(self::OPTION, []);
        $pageId = is_array($map) ? (int) ($map[(int) $productId] ?? 0) : 0;

        return ($pageId && get_post_status($pageId) === 'publish') ? $pageId : 0;
    }

    /** Link der Landingpage, leer wenn keine. */
    public static function url($productId)
    {
        $pageId = self::page_id($productId);

        return $pageId ? (string) get_permalink($pageId) : '';
    }

    public static function render()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $map      = get_option(self::OPTION, []);
        $products = get_posts([
            'post_type'      => 'fluent-products',
            'post_status'    => ['publish', 'private', 'draft'],
            'posts_per_page' => -1,
            'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        ]);
        $status = ['publish' => 'Veröffentlicht', 'private' => 'Privat', 'draft' => 'Entwurf'];

        echo '<div class="wrap"><h1>Produkt-Landingpages</h1>';
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Gespeichert.</p></div>';
        }
        echo '<p>Hat ein Produkt eine Landingpage, verlinken die Produkt-Karten (<code>[kic_produktkarten]</code>) auf diese Seite statt auf die Produktseite. '
            . 'Private Produkte erscheinen in den Karten nur mit Landingpage.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="kic_save_landingpages" />';
        wp_nonce_field('kic_save_landingpages');
        echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Produkt</th><th>Status</th><th>Landingpage</th><th>In den Karten</th></tr></thead><tbody>';
        foreach ($products as $product) {
            $pageId  = is_array($map) ? (int) ($map[$product->ID] ?? 0) : 0;
            $visible = $product->post_status === 'publish' || ($product->post_status === 'private' && self::page_id($product->ID));
            echo '<tr><td><a href="' . esc_url(get_edit_post_link($product->ID)) . '">' . esc_html($product->post_title) . '</a></td>';
            echo '<td>' . esc_html($status[$product->post_status] ?? $product->post_status) . '</td><td>';
            wp_dropdown_pages([
                'name'              => 'kic_lp[' . $product->ID . ']',
                'selected'          => $pageId,
                'show_option_none'  => '— Produktseite —',
                'option_none_value' => '0',
                'post_status'       => 'publish',
            ]);
            echo '</td><td>' . ($visible ? 'ja' : 'nein') . '</td></tr>';
        }
        echo '</tbody></table>';
        submit_button('Speichern');
        echo '</form></div>';
    }

    public static function save()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Keine Berechtigung.');
        }
        check_admin_referer('kic_save_landingpages');

        $map = [];
        foreach ((array) ($_POST['kic_lp'] ?? []) as $productId => $pageId) {
            $productId = absint($productId);
            $pageId    = absint($pageId);
            if ($productId && $pageId && get_post_type($productId) === 'fluent-products' && get_post_type($pageId) === 'page') {
                $map[$productId] = $pageId;
            }
        }
        update_option(self::OPTION, $map, false);

        wp_safe_redirect(add_query_arg(['page' => 'kic-landingpages', 'saved' => 1], admin_url('tools.php')));
        exit;
    }
}
