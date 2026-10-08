<?php
/**
 * Shortcodes für die Community-Sektion der Startseite, live aus Fluent Community.
 *
 *   [kic_community_zahlen]   Kennzahlen als Chips (Mitglieder, Lektionen im Vault, Bereiche), gerundet mit „+“.
 *   [kic_community_fenster]  Portal-Fenster: Bereichsgruppen mit ihren Bereichen und ein Feed aus Platzhaltern.
 *
 * Nur HTML mit Klassen kc-stat… / kc-portal…, das Aussehen steht im Divi-CSS der Startseite.
 * Bezahlte Kurse (Typ course mit Sichtbarkeit privat, z. B. „Dein KI Cockpit“) zählen nicht zur Community
 * und erscheinen nicht. Weitere Bereiche ausblenden per Filter kic/community_hidden_space_ids.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Community_Showcase
{
    public static function init()
    {
        add_shortcode('kic_community_zahlen', [__CLASS__, 'stats']);
        add_shortcode('kic_community_fenster', [__CLASS__, 'window']);
    }

    /** Veröffentlichte Bereiche der Community (ohne bezahlte Kurse), sortiert wie im Portal. */
    private static function spaces()
    {
        global $wpdb;

        $rows   = $wpdb->get_results("SELECT id, parent_id, title, type, privacy, serial FROM {$wpdb->prefix}fcom_spaces WHERE status IN ('published', 'active') ORDER BY serial ASC, id ASC");
        $hidden = array_map('intval', (array) apply_filters('kic/community_hidden_space_ids', []));

        return array_values(array_filter((array) $rows, function ($s) use ($hidden) {
            return !in_array((int) $s->id, $hidden, true) && !($s->type === 'course' && $s->privacy === 'private');
        }));
    }

    /** 122 → „120+“, 38 → „30+“, unter 20 exakt. */
    private static function rounded($n)
    {
        $n = (int) $n;
        if ($n < 20) {
            return (string) $n;
        }

        return number_format(floor($n / 10) * 10, 0, ',', '.') . '+';
    }

    public static function stats()
    {
        global $wpdb;

        $spaces   = self::spaces();
        $members  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fcom_xprofile WHERE status = 'active'");
        $courses  = array_map(function ($s) { return (int) $s->id; }, array_filter($spaces, function ($s) { return $s->type === 'course'; }));
        $lessons  = $courses ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fcom_posts WHERE type = 'course_lesson' AND status = 'published' AND space_id IN (" . implode(',', $courses) . ')') : 0;
        $sections = count(array_filter($spaces, function ($s) { return $s->type !== 'space_group'; }));

        $items = [
            [self::rounded($members), 'Mitglieder'],
            [self::rounded($lessons), 'Lektionen im Vault'],
            [(string) $sections, 'Bereiche'],
        ];

        $out = '<span class="kc-stats">';
        foreach ($items as $item) {
            $out .= '<span class="kc-stat"><span class="kc-stat-num">' . esc_html($item[0]) . '</span><span class="kc-stat-label">' . esc_html($item[1]) . '</span></span>';
        }

        return $out . '</span>';
    }

    public static function window()
    {
        $spaces = self::spaces();
        $groups = array_filter($spaces, function ($s) { return $s->type === 'space_group'; });
        $max    = (int) apply_filters('kic/community_window_max_spaces', 6);

        $nav = '';
        foreach ($groups as $group) {
            $children = array_slice(array_values(array_filter($spaces, function ($s) use ($group) {
                return (int) $s->parent_id === (int) $group->id;
            })), 0, $max);
            if (!$children) {
                continue;
            }
            $nav .= '<span class="kc-portal-group">' . esc_html($group->title) . '</span>';
            foreach ($children as $space) {
                $type = $space->type === 'course' ? 'course' : 'space';
                $nav .= '<span class="kc-portal-space kc-portal-space--' . $type . '"><span class="kc-portal-ico"></span>' . esc_html($space->title) . '</span>';
            }
        }

        $feed = '';
        for ($i = 1; $i <= 3; $i++) {
            $feed .= '<span class="kc-portal-post kc-portal-post--' . $i . '"><span class="kc-portal-av"></span><span class="kc-portal-lines"><span class="kc-portal-line kc-portal-line--name"></span><span class="kc-portal-line"></span><span class="kc-portal-line kc-portal-line--short"></span></span><span class="kc-portal-react"><span class="kc-portal-heart"></span><span class="kc-portal-count"></span></span></span>';
        }

        return '<span class="kc-portal">'
            . '<span class="kc-portal-head"><span class="kc-portal-dots"><span></span><span></span><span></span></span><span class="kc-portal-title">Community-Portal</span><span class="kc-portal-live">live</span></span>'
            . '<span class="kc-portal-body"><span class="kc-portal-nav">' . $nav . '</span>'
            . '<span class="kc-portal-feed"><span class="kc-portal-compose"><span class="kc-portal-av"></span><span class="kc-portal-input">Teile etwas mit der Community …</span></span>' . $feed . '</span></span>'
            . '</span>';
    }
}
