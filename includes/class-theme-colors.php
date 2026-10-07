<?php
/**
 * FluentCart-Farben seitenweit
 *
 * FluentCart leitet die Theme-Farben in --fct-* ab (überall geladen), übersetzt sie aber nur in checkout.css
 * in die --fct-checkout-* Variablen. Erweiterungen wie FluentCart Customer Rights (Widerrufsformular) nutzen
 * --fct-checkout-* auch außerhalb der Kasse und fallen dort auf das graue Standard-#253241 zurück.
 * Dieses Modul setzt dieselbe Zuordnung wie checkout.css auf allen Seiten.
 *
 * Außerdem: Divis Linkfarbe im Theme-Builder-Inhalt (.et_pb_post_content_* a) ist spezifischer als FluentCarts
 * Farbe für den Abmelden-Button im Kundenkonto. Der Button bekommt seine FluentCart-Farbe zurück.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Theme_Colors
{
    public static function init()
    {
        add_action('wp_head', [__CLASS__, 'render'], 99);
    }

    public static function render()
    {
        echo '<style id="kic-fct-colors">body:not(.fluent-cart-no-colors){'
            . '--fct-checkout-primary-text-color:var(--fct-primary-text-color,#2F3448);'
            . '--fct-checkout-secondary-text-color:var(--fct-secondary-text-color,#565865);'
            . '--fct-checkout-border-color:var(--fct-border-color,#D6DAE1);'
            . '--fct-checkout-active-border-color:var(--fct-active-border-color,#8D9095);'
            . '--fct-checkout-primary-bg-color:var(--fct-primary-bg-color,#253241);'
            . '--fct-checkout-btn-bg-color:var(--fct-btn-bg-color,#253241);'
            . '--fct-checkout-btn-text-color:var(--fct-btn-text-color,#ffffff);'
            . '--fct-checkout-btn-hover-bg-color:var(--fct-btn-hover-bg-color,var(--fct-checkout-btn-bg-color));'
            . '--fct-checkout-btn-hover-text-color:var(--fct-btn-hover-text-color,var(--fct-checkout-btn-text-color));'
            . '}'
            . 'a.fct-customer-logout-btn.fct-customer-logout-btn,'
            . 'a.fct-customer-logout-btn.fct-customer-logout-btn:hover,'
            . 'a.fct-customer-logout-btn.fct-customer-logout-btn:focus{color:var(--fct-customer-dashboard-logout-btn-text-color,#F04438);}'
            // Divi färbt alle Kassen-Buttons violett, der Gutschein-Button behält aber FluentCarts dunkle Schrift.
            . '.fct_checkout .fct_coupon_field button,.fct_checkout .fct_coupon_field button:hover{color:var(--fct-btn-text-color,#ffffff);}'
            . '</style>' . "\n";
    }
}
