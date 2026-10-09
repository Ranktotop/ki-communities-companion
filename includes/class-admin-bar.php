<?php
/**
 * WordPress-Werkzeugleiste nur für Administratoren.
 *
 * Kunden und Mitglieder sehen sonst im Frontend die Leiste ganz oben; auf dem Handy verdeckt sie das schwebende Menü.
 * Die Profileinstellung „Werkzeugleiste anzeigen“ steht bei allen Konten auf an, deshalb hier zentral statt pro Nutzer.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Admin_Bar
{
    public static function init()
    {
        add_filter('show_admin_bar', [__CLASS__, 'filter'], 20);
    }

    public static function filter($show)
    {
        return $show && current_user_can('manage_options');
    }
}
