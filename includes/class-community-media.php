<?php
/**
 * Fluent Community: Vorschaubilder von YouTube und GIFs von GIPHY lokal speichern.
 *
 * Ersetzt in Beiträgen, Lektionen und Kommentaren externe Bild-URLs (i.ytimg.com, img.youtube.com, *.giphy.com)
 * durch Kopien in wp-content/uploads/fcom-extern/, damit beim Anzeigen keine Daten an Google/GIPHY gehen.
 * Läuft nach dem Speichern und stündlich als Absicherung.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Community_Media
{
    const CRON_HOOK = 'kic_localize_community_media';

    public static function init()
    {
        $after = function ($table) {
            return function ($item = null) use ($table) {
                if (is_object($item) && !empty($item->id)) {
                    self::localizeRow($table, (int) $item->id);
                }
            };
        };

        add_action('fluent_community/feed/created', $after('fcom_posts'), 99);
        add_action('fluent_community/feed/updated', $after('fcom_posts'), 99);
        add_action('fluent_community/lesson/created', $after('fcom_posts'), 99);
        add_action('fluent_community/lesson/updated', $after('fcom_posts'), 99);
        add_action('fluent_community/comment_added', $after('fcom_post_comments'), 99);
        add_action('fluent_community/comment_updated', $after('fcom_post_comments'), 99);

        add_action(self::CRON_HOOK, [__CLASS__, 'localizeAll']);
    }

    public static function activate()
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::CRON_HOOK);
        }
        self::localizeAll();
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public static function isExternalImage($url)
    {
        $host = is_string($url) ? (string) wp_parse_url($url, PHP_URL_HOST) : '';
        return $host && preg_match('/(^|\.)(ytimg\.com|img\.youtube\.com|giphy\.com)$/i', $host);
    }

    public static function localizeImage($url)
    {
        if (!self::isExternalImage($url)) {
            return $url;
        }

        $upload = wp_upload_dir();
        $dir    = trailingslashit($upload['basedir']) . 'fcom-extern/';
        $base   = trailingslashit($upload['baseurl']) . 'fcom-extern/';
        $hash   = md5(preg_replace('/\?.*$/', '', $url));

        foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
            if (file_exists($dir . $hash . '.' . $ext)) {
                return $base . $hash . '.' . $ext;
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp = download_url($url, 20);
        if (is_wp_error($tmp)) {
            return $url;
        }

        $info = @getimagesize($tmp);
        $map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $ext  = $info && isset($map[$info['mime']]) ? $map[$info['mime']] : null;
        if (!$ext) {
            @unlink($tmp);
            return $url;
        }

        wp_mkdir_p($dir);
        if (!@rename($tmp, $dir . $hash . '.' . $ext)) {
            @copy($tmp, $dir . $hash . '.' . $ext);
            @unlink($tmp);
        }

        return file_exists($dir . $hash . '.' . $ext) ? $base . $hash . '.' . $ext : $url;
    }

    private static function localizeValue($value, &$changed)
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::localizeValue($item, $changed);
            } elseif (in_array($key, ['image', 'posterSrc', 'poster', 'thumbnail'], true) && self::isExternalImage($item)) {
                $local = self::localizeImage($item);
                if ($local !== $item) {
                    $value[$key] = $local;
                    $changed     = true;
                }
            }
        }

        return $value;
    }

    public static function localizeRow($table, $id)
    {
        global $wpdb;
        $table = $wpdb->prefix . $table;
        $raw   = $wpdb->get_var($wpdb->prepare("SELECT meta FROM {$table} WHERE id = %d", $id));
        if (!$raw || !preg_match('/ytimg|img\.youtube|giphy/i', $raw)) {
            return false;
        }

        $meta = maybe_unserialize($raw);
        if (!is_array($meta)) {
            return false;
        }

        $changed = false;
        $meta    = self::localizeValue($meta, $changed);
        if ($changed) {
            $wpdb->update($table, ['meta' => maybe_serialize($meta)], ['id' => $id]);
        }

        return $changed;
    }

    public static function localizeAll()
    {
        global $wpdb;
        $done = 0;

        foreach (['fcom_posts', 'fcom_post_comments'] as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . $table)) !== $wpdb->prefix . $table) {
                continue;
            }
            $ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}{$table} WHERE meta LIKE '%ytimg%' OR meta LIKE '%img.youtube%' OR meta LIKE '%giphy%'");
            foreach ($ids as $id) {
                $done += self::localizeRow($table, (int) $id) ? 1 : 0;
            }
        }

        return $done;
    }
}
