<?php
/**
 * Richtige PDF-Vorlage für unbezahlte Bestellungen.
 *
 * Der Download auf der Belegseite nutzt immer die Vorlage „order_receipt“, deren Zahlungsstatus fest „Bezahlt“ lautet.
 * Bricht jemand die Zahlung ab (z. B. Klarna), landet er trotzdem auf der Belegseite und bekäme dort einen Beleg mit
 * „Bezahlt“. Ist die Bestellung nicht bezahlt, wird stattdessen FluentCarts Vorlage „proforma_invoice“ (Zahlungsstatus
 * „Offen“) erzeugt. Läuft nach FluentCart Pro (Priorität 10), das den Filter ohne Rücksicht auf den Vorwert befüllt.
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Receipt_Status
{
    const PAID = ['paid', 'partially_refunded', 'refunded'];

    public static function init()
    {
        add_filter('fluent_cart/pdf/generate_receipt', [__CLASS__, 'template'], 20, 2);
    }

    public static function template($pdfPath, $args)
    {
        $order = $args['order'] ?? null;
        if (($args['template_id'] ?? '') !== 'order_receipt' || !is_object($order)
            || in_array($order->payment_status, self::PAID, true)
            || !class_exists('\FluentCartPro\App\Services\PDF\OrderReceiptPdfService')) {
            return $pdfPath;
        }

        $proforma = (new \FluentCartPro\App\Services\PDF\OrderReceiptPdfService())->generateReceiptPdf($order, 'proforma_invoice');
        if (!$proforma) {
            return $pdfPath;
        }
        if ($pdfPath && file_exists($pdfPath)) {
            @unlink($pdfPath);
        }

        return $proforma;
    }
}
