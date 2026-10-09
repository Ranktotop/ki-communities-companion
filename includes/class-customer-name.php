<?php
/**
 * Fehlenden Kundennamen aus der Rechnungsadresse ergänzen.
 *
 * Gibt es zu einer E-Mail-Adresse schon einen FluentCart-Kunden ohne Namen (z. B. weil die Person vorher der Community
 * beigetreten ist), übernimmt FluentCart den Namen aus der Kasse nicht. Der Beleg begrüßt dann mit „Hallo !“.
 * Beim Anlegen der Bestellung (fluent_cart/order_created, läuft vor der Belegseite) und nach der Zahlung
 * (fluent_cart/order_paid_done, als Rückfall) wird ein leerer Vor- und Nachname aus der Rechnungsadresse gesetzt.
 * Vorhandene Namen bleiben unangetastet.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Customer_Name
{
    public static function init()
    {
        add_action('fluent_cart/order_created', [__CLASS__, 'fill'], 20, 1);
        add_action('fluent_cart/order_paid_done', [__CLASS__, 'fill'], 20, 1);
    }

    public static function fill($data)
    {
        $order    = is_array($data) ? ($data['order'] ?? null) : null;
        $customer = is_object($order) ? $order->customer : null;
        if (!$customer || trim((string) $customer->first_name . (string) $customer->last_name) !== '') {
            return;
        }

        $address = $order->billing_address ?: $order->shipping_address;
        if (!$address) {
            return;
        }

        $meta  = is_array($address->meta) ? $address->meta : (json_decode((string) $address->meta, true) ?: []);
        $first = trim((string) ($meta['other_data']['first_name'] ?? ''));
        $last  = trim((string) ($meta['other_data']['last_name'] ?? ''));
        if ($first === '' && $last === '') {
            $parts = preg_split('/\s+/', trim((string) $address->name), 2);
            $first = $parts[0] ?? '';
            $last  = $parts[1] ?? '';
        }
        if ($first === '' && $last === '') {
            return;
        }

        $customer->first_name = sanitize_text_field($first);
        $customer->last_name  = sanitize_text_field($last);
        $customer->save();
    }
}
