<?php
/**
 * Rechtsblock in der Bestellbestätigung (FluentCart „Kaufbeleg“, order_paid_customer).
 *
 * - Digitale Inhalte: Bestätigung der Zustimmung zum sofortigen Beginn und der Kenntnis vom Verlust des
 *   Widerrufsrechts (§ 356 Abs. 5 BGB) auf dauerhaftem Datenträger.
 * - Dienstleistungen: Bestätigung des Verlangens, vor Fristablauf zu beginnen (§ 356 Abs. 4 BGB).
 * - Immer angehängt: die zum Bestellzeitpunkt gültigen AGB inkl. Widerrufsbelehrung und Muster-Formular.
 *
 * Dienstleistungen werden über Produktkategorien erkannt (Filter kic/service_categories, Standard: live-call).
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Legal_Email
{
    public static function init()
    {
        add_filter('fluent_cart/email_notification/mailer', [__CLASS__, 'appendLegalBlock'], 10, 2);
    }

    public static function appendLegalBlock($mailer, $context)
    {
        if (($context['mail_name'] ?? '') !== 'order_paid_customer') {
            return $mailer;
        }

        $agbPage = get_page_by_path(apply_filters('kic/agb_page_slug', 'agb-widerrufsrecht'));
        if (!$agbPage) {
            return $mailer;
        }

        $order   = $context['data']['order'] ?? null;
        $date    = ($order && !empty($order->created_at)) ? date_i18n('d.m.Y', strtotime($order->created_at)) : date_i18n('d.m.Y');
        $invoice = ($order && !empty($order->invoice_no)) ? $order->invoice_no : '';
        $types   = KIC_Consent::classify(is_object($order) ? $order->order_items : []);
        $digital = $types['digital'];
        $service = $types['service'];

        $block  = '<table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;margin:16px auto 0;padding:24px 32px;max-width:620px"><tbody><tr><td>';

        $orderRef = esc_html($date) . ($invoice ? ' (Bestellnummer ' . esc_html($invoice) . ')' : '');

        if ($digital || $service) {
            $block .= '<h2 style="font-size:16px;line-height:24px;margin:0 0 8px;color:#111827">Bestätigung deiner Zustimmung zum vorzeitigen Vertragsbeginn</h2>';
        }

        if ($digital) {
            $block .= '<p style="font-size:14px;line-height:22px;margin:0 0 8px;color:#374151">Du hast bei deiner Bestellung vom ' . $orderRef
                . ' für folgende digitale Inhalte ausdrücklich zugestimmt, dass wir vor Ablauf der Widerrufsfrist mit der Ausführung des Vertrags beginnen und dir die Inhalte sofort bereitstellen: '
                . esc_html(implode(', ', $digital)) . '. '
                . 'Du hast zudem deine Kenntnis davon bestätigt, dass du durch diese Zustimmung mit Beginn der Ausführung des Vertrags dein Widerrufsrecht verlierst.</p>';
            $block .= '<p style="font-size:14px;line-height:22px;margin:0 0 8px;color:#374151">Unabhängig davon gilt für Online-Kurse, bei denen sie angegeben ist, die freiwillige 14-Tage-Geld-zurück-Garantie gemäß § 10 der AGB.</p>';
        }

        if ($service) {
            $block .= '<p style="font-size:14px;line-height:22px;margin:0 0 8px;color:#374151">Du hast bei deiner Bestellung vom ' . $orderRef
                . ' für folgende Dienstleistungen ausdrücklich verlangt, dass wir vor Ablauf der Widerrufsfrist mit der Ausführung beginnen: '
                . esc_html(implode(', ', $service)) . '. '
                . 'Du hast zudem deine Kenntnis davon bestätigt, dass du dein Widerrufsrecht bei vollständiger Vertragserfüllung durch uns verlierst. '
                . 'Widerrufst du vorher, schuldest du uns einen angemessenen Betrag für die bis dahin erbrachten Leistungen (siehe Widerrufsbelehrung).</p>';
        }

        if ($digital || $service) {
            $block .= '<div style="height:8px"></div>';
        }

        $block .= '<h2 style="font-size:16px;line-height:24px;margin:0 0 8px;color:#111827">Deine Vertragsbedingungen</h2>';
        $block .= '<p style="font-size:14px;line-height:22px;margin:0 0 8px;color:#374151">Nachfolgend erhältst du die zum Zeitpunkt deiner Bestellung gültigen AGB mit Widerrufsbelehrung und Muster-Widerrufsformular.</p>';
        $block .= '<div>' . self::formatAgb($agbPage->post_content) . '</div>';
        $block .= '</td></tr></tbody></table>';

        $getBody = \Closure::bind(function () {
            return $this->body;
        }, $mailer, get_class($mailer));
        $body = (string) $getBody();

        if (strpos($body, '<table class="email_footer"') !== false) {
            $body = preg_replace('/<table class="email_footer"/', $block . '<table class="email_footer"', $body, 1);
        } elseif (stripos($body, '</body>') !== false) {
            $body = preg_replace('/<\/body>/i', $block . '</body>', $body, 1);
        } else {
            $body .= $block;
        }

        $mailer->body($body);
        return $mailer;
    }

    private static function formatAgb($content)
    {
        $agb = wp_kses($content, [
            'h2' => [], 'h3' => [], 'p' => [], 'ol' => [], 'ul' => [], 'li' => [], 'strong' => [], 'em' => [], 'br' => [], 'hr' => [],
            'a'  => ['href' => []],
        ]);

        return str_replace(
            ['<h2>', '<h3>', '<p>', '<li>'],
            [
                '<h2 style="font-size:15px;line-height:22px;margin:18px 0 6px;color:#111827">',
                '<h3 style="font-size:13px;line-height:20px;margin:14px 0 4px;color:#111827">',
                '<p style="font-size:12px;line-height:18px;margin:0 0 6px;color:#374151">',
                '<li style="font-size:12px;line-height:18px;margin:0 0 4px;color:#374151">',
            ],
            $agb
        );
    }
}
