<?php
/**
 * Korrekturen an der deutschen FluentCart-Übersetzung.
 *
 * Einige Strings sind in der Übersetzung falsch („Apply Here“ → „Hier bewerben“). Die Korrektur hier
 * überlebt Updates der Übersetzungsdateien. Weitere Korrekturen per Filter kic/translation_fixes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Translation_Fixes
{
    public static function init()
    {
        add_filter('gettext_fluent-cart', [__CLASS__, 'fix'], 20, 2);
    }

    public static function fixes()
    {
        return (array) apply_filters('kic/translation_fixes', [
            'Apply Here'                   => 'Rabattcode eingeben',
            'Apply VAT number'             => 'USt-IdNr. prüfen',
            // Steueraufschlüsselung an der Kasse, im Beleg und im Kundenkonto
            'Tax breakdown by rate'        => 'Enthaltene Mehrwertsteuer',
            'Tax breakdown'                => 'Mehrwertsteuer',
            'Order Tax Breakdown'          => 'Mehrwertsteuer der Bestellung',
            'Rate'                         => 'Steuersatz',
            'Taxable base'                 => 'Netto',
            'Tax'                          => 'MwSt.',
            'Total tax'                    => 'MwSt. gesamt',
            'of which included in prices'  => 'davon im Preis enthalten',
            'Total tax in this order'      => 'MwSt. in dieser Bestellung',
            'VAT'                          => 'MwSt.',
        ]);
    }

    public static function fix($translation, $text)
    {
        $fixes = self::fixes();

        return $fixes[$text] ?? $translation;
    }
}
