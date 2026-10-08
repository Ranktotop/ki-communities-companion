<?php
/**
 * Shortcode [kic_produktkarten]: Produkt-Karten für die Startseite, live aus FluentCart.
 *
 * Gibt nur das HTML aus (Klassen kc-card …, je Karte kc-card--cat-<Kategorie-Slug> für die Kategorie-Farbe). Das Aussehen steht im Divi-CSS des Textmoduls,
 * in dem der Shortcode sitzt. Bild, Kategorie, Titel, Kurzbeschreibung, Preis und Link kommen aus dem Produkt,
 * damit nach Änderungen im Shop nichts auf der Seite nachgepflegt werden muss.
 *
 * Attribute:
 *   anzahl        Höchstzahl der Karten (Standard 3)
 *   kategorie     nur diese Kategorie-Slugs, kommagetrennt
 *   ausschliessen Produkt-IDs, kommagetrennt
 * Reihenfolge: Menü-Reihenfolge des Produkts, danach neueste zuerst.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Product_Cards
{
    public static function init()
    {
        add_shortcode('kic_produktkarten', [__CLASS__, 'render']);
        add_filter('fluent_cart/product/card_classes', [__CLASS__, 'fluent_card_classes'], 10, 2);
        add_filter('body_class', [__CLASS__, 'body_class']);
    }

    /** Produktseite: Kategorie als Body-Klasse kc-cat-<Slug>, damit das Divi-CSS die Kategorie-Farbe setzen kann. */
    public static function body_class($classes)
    {
        if (is_singular('fluent-products')) {
            $category = self::category(get_queried_object_id());
            if ($category) {
                $classes[] = 'kc-cat-' . sanitize_html_class($category->slug);
            }
        }

        return $classes;
    }

    /**
     * FluentCart-Produktkarten (Shop-Seite, verwandte Produkte) bekommen dieselbe Kategorie-Klasse kc-card--cat-<Slug>
     * wie die Startseiten-Karten, damit das Divi-CSS der Shop-Seite die Kategorie-Farbe setzen kann.
     */
    public static function fluent_card_classes($classes, $context)
    {
        $product = is_array($context) || $context instanceof ArrayAccess ? ($context['product'] ?? null) : null;
        $postId  = is_object($product) ? (int) ($product->ID ?? $product->id ?? 0) : 0;
        $category = $postId ? self::category($postId) : null;
        if ($category && is_array($classes)) {
            $classes[] = 'kc-card--cat-' . sanitize_html_class($category->slug);
        }

        return $classes;
    }

    public static function render($atts)
    {
        $atts = shortcode_atts([
            'anzahl'        => 3,
            'kategorie'     => '',
            'ausschliessen' => '',
        ], $atts, 'kic_produktkarten');

        $query = [
            'post_type'      => 'fluent-products',
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) $atts['anzahl']),
            'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
            'post__not_in'   => array_filter(array_map('intval', explode(',', $atts['ausschliessen']))),
            'no_found_rows'  => true,
        ];
        if ($atts['kategorie'] !== '') {
            $query['tax_query'] = [[
                'taxonomy' => 'product-categories',
                'field'    => 'slug',
                'terms'    => array_map('trim', explode(',', $atts['kategorie'])),
            ]];
        }

        $html = '';
        foreach (get_posts($query) as $post) {
            $html .= self::card($post);
        }

        return $html;
    }

    private static function card($post)
    {
        $category = self::category($post->ID);
        $label    = $category ? self::label($category) : '';
        $title    = self::title($post->post_title, $label);
        $image    = get_the_post_thumbnail_url($post->ID, 'medium_large');

        $class = 'kc-card' . ($category ? ' kc-card--cat-' . sanitize_html_class($category->slug) : '');
        $out   = '<a class="' . esc_attr($class) . '" href="' . esc_url(get_permalink($post)) . '">';
        if ($image) {
            $alt  = get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true) ?: $title;
            $out .= '<span class="kc-card-media"><img src="' . esc_url($image) . '" alt="' . esc_attr($alt) . '" loading="lazy" /></span>';
        }
        $out .= '<span class="kc-card-body">';
        if ($label !== '') {
            $out .= '<span class="kc-card-pill">' . esc_html($label) . '</span>';
        }
        $out .= '<span class="kc-card-title">' . esc_html($title) . '</span>';
        $out .= '<span class="kc-card-text">' . esc_html(self::text($post)) . '</span>';
        $price = self::price($post->ID);
        $tax   = ($price !== '' && $price !== 'Kostenlos') ? (string) apply_filters('kic/product_card_tax_note', 'inkl. MwSt.') : '';
        $out  .= '<span class="kc-card-foot"><span class="kc-card-price">' . esc_html($price) . ($tax !== '' ? ' <span class="kc-card-tax">' . esc_html($tax) . '</span>' : '') . '</span>';
        $out .= '<span class="kc-card-cta">' . esc_html(self::cta($category)) . ' <span class="kc-arr">→</span></span></span>';
        $out .= '</span></a>';

        return $out;
    }

    /** Erste Produktkategorie (Hersteller-Kategorien wie „Marc Meese“ liegen in einer anderen Taxonomie). */
    private static function category($postId)
    {
        $terms = get_the_terms($postId, 'product-categories');

        return ($terms && !is_wp_error($terms)) ? $terms[0] : null;
    }

    private static function label($term)
    {
        $map = apply_filters('kic/product_card_labels', [
            'videokurse'  => 'Videokurs',
            'live-call'   => '1:1 Live-Call',
            'workflows'   => 'Workflow',
            'memberships' => 'Mitgliedschaft',
        ]);

        return $map[$term->slug] ?? $term->name;
    }

    private static function cta($term)
    {
        $map = apply_filters('kic/product_card_ctas', [
            'videokurse'  => 'Zum Kurs',
            'live-call'   => 'Termin sichern',
            'workflows'   => 'Zum Workflow',
            'memberships' => 'Mitglied werden',
        ]);

        return ($term && isset($map[$term->slug])) ? $map[$term->slug] : 'Ansehen';
    }

    /** „Dein KI-Cockpit - Videokurs“ → „Dein KI-Cockpit“, wenn die Kategorie schon als Label dasteht. */
    private static function title($title, $label)
    {
        $title = wp_strip_all_tags($title);
        if ($label !== '' && preg_match('/^(.*?)\s+[-–]\s+' . preg_quote($label, '/') . '$/iu', $title, $m)) {
            return $m[1];
        }

        return $title;
    }

    /** Erster Absatz der Kurzbeschreibung, höchstens 26 Wörter. */
    private static function text($post)
    {
        $text  = $post->post_excerpt !== '' ? $post->post_excerpt : wp_strip_all_tags(strip_shortcodes($post->post_content));
        $first = trim(preg_split('/\R/', trim($text))[0]);

        return wp_trim_words($first, 26, ' …');
    }

    private static function price($postId)
    {
        global $wpdb;

        $details = $wpdb->get_row($wpdb->prepare(
            "SELECT min_price, max_price FROM {$wpdb->prefix}fct_product_details WHERE post_id = %d",
            $postId
        ));
        $variation = $wpdb->get_row($wpdb->prepare(
            "SELECT payment_type, other_info FROM {$wpdb->prefix}fct_product_variations WHERE post_id = %d AND item_status = 'active' ORDER BY serial_index ASC LIMIT 1",
            $postId
        ));
        if (!$details) {
            return '';
        }

        $min = (int) $details->min_price;
        $max = (int) $details->max_price;
        if ($max === 0) {
            return 'Kostenlos';
        }

        $price = ($min !== $max ? 'ab ' : '') . self::money($min);
        if ($variation && $variation->payment_type === 'subscription') {
            $info     = json_decode((string) $variation->other_info, true);
            $interval = ['daily' => 'Tag', 'weekly' => 'Woche', 'monthly' => 'Monat', 'quarterly' => 'Quartal', 'half_yearly' => 'Halbjahr', 'yearly' => 'Jahr'];
            if (!empty($info['repeat_interval']) && isset($interval[$info['repeat_interval']])) {
                $price .= ' / ' . $interval[$info['repeat_interval']];
            }
        }

        return $price;
    }

    /** Cent-Betrag → „97 €“ bzw. „14,90 €“. */
    private static function money($cents)
    {
        $decimals = ($cents % 100 === 0) ? 0 : 2;

        return number_format($cents / 100, $decimals, ',', '.') . ' €';
    }
}
