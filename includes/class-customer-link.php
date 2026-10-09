<?php
/**
 * Gast-Bestellung mit bestehendem Konto verknüpfen.
 *
 * Bestellt jemand mit Konto (z. B. Community-Mitglied) ohne sich anzumelden, legt FluentCart einen Kunden ohne
 * WordPress-Konto an und verknüpft ihn bewusst nicht. Die FluentCommunity-Integration will dann ein neues Konto anlegen,
 * scheitert an „E-Mail bereits registriert“ und schaltet nichts frei.
 *
 * Sobald eine Bestellung bezahlt ist (fluent_cart/order_paid, läuft vor der Integration, die erst asynchron über
 * fluent_cart/order_paid_done kommt), wird ein Kunde ohne Konto mit dem Konto verknüpft, das genau seine E-Mail-Adresse
 * hat. Niemand wird dabei eingeloggt; die Bestellung und der Zugang landen beim Inhaber dieser Adresse.
 * Unbezahlte oder fehlgeschlagene Bestellungen bleiben unberührt, eine nachträgliche Zahlung löst order_paid ebenfalls aus.
 * Gehört zum Konto schon ein anderer Kunde, wird nichts verändert, nur eine Notiz an die Bestellung geschrieben.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Customer_Link
{
    public static function init()
    {
        add_action('fluent_cart/order_paid', [__CLASS__, 'link'], 1, 1);
    }

    public static function link($data)
    {
        try {
            $order    = is_array($data) ? ($data['order'] ?? null) : null;
            $customer = is_object($order) ? $order->customer : null;
            if (!$customer || $customer->user_id || !is_email($customer->email)) {
                return;
            }

            $user = get_user_by('email', $customer->email);
            if (!$user) {
                return;
            }

            $owner = \FluentCart\App\Models\Customer::query()
                ->where('user_id', $user->ID)
                ->where('id', '!=', $customer->id)
                ->first();
            if ($owner) {
                $order->addLog(
                    'Kundenkonto nicht verknüpft',
                    sprintf('Zur E-Mail gibt es das Konto #%d, es gehört aber schon zum Kunden #%d. Bitte von Hand prüfen.', $user->ID, $owner->id),
                    'warning',
                    'KI-Communities'
                );
                return;
            }

            $customer->user_id = $user->ID;
            $customer->save();

            $order->addLog(
                'Kundenkonto verknüpft',
                sprintf('Die Bestellung wurde mit dem bestehenden Konto #%d (gleiche E-Mail-Adresse) verknüpft.', $user->ID),
                'info',
                'KI-Communities'
            );
        } catch (\Throwable $e) {
            error_log('[KIC_Customer_Link] ' . $e->getMessage());
        }
    }
}
