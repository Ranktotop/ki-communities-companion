<?php
/**
 * Kasse: Hinweis auf AGB, Widerrufsbelehrung und Datenschutz direkt über dem Bestell-Button.
 *
 * Die FluentCart-Pflichtcheckbox enthält nur den Verzicht auf das Widerrufsrecht (§ 356 Abs. 5 BGB).
 * AGB werden per Hinweis einbezogen, eine eigene AGB-Checkbox ist nicht nötig.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Checkout_Notice
{
    public static function init()
    {
        add_action('fluent_cart/after_payment_methods', [__CLASS__, 'render']);
    }

    public static function render()
    {
        $agb = home_url('/agb-widerrufsrecht/');

        echo '<div class="fct_checkout_legal_notice" style="font-size:13px;line-height:1.5;margin:12px 0 4px;opacity:.85">'
            . 'Es gelten unsere <a href="' . esc_url($agb) . '" target="_blank" rel="noopener">AGB</a>. '
            . 'Informationen zu deinem Widerrufsrecht findest du in der <a href="' . esc_url($agb . '#widerrufsbelehrung') . '" target="_blank" rel="noopener">Widerrufsbelehrung</a>, '
            . 'Hinweise zur Verarbeitung deiner Daten in der <a href="' . esc_url(home_url('/datenschutzerklaerung/')) . '" target="_blank" rel="noopener">Datenschutzerklärung</a>.'
            . '</div>';
    }
}
