<?php
/**
 * Verzicht auf das Widerrufsrecht – passend zum Warenkorb.
 *
 * FluentCart kennt nur einen Text für die Pflichtcheckbox. Dieses Modul setzt den Text vor dem Rendern der Kasse
 * je nach Inhalt des Warenkorbs:
 * - nur digitale Inhalte  → Zustimmung zum sofortigen Beginn, Erlöschen mit Beginn der Ausführung (§ 356 Abs. 5 BGB)
 * - nur Dienstleistungen  → Verlangen des Beginns vor Fristablauf, Erlöschen bei vollständiger Erfüllung (§ 356 Abs. 4 BGB)
 * - beides                → kombinierter Text
 *
 * Dienstleistungen werden über Produktkategorien erkannt (Filter kic/service_categories, Standard: live-call).
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIC_Consent
{
    const TEXT_DIGITAL = 'Ich stimme ausdrücklich zu, dass mit der Ausführung des Vertrags vor Ablauf der Widerrufsfrist begonnen wird und mir die digitalen Inhalte sofort bereitgestellt werden. Mir ist bekannt, dass ich durch diese Zustimmung mit Beginn der Ausführung des Vertrags mein Widerrufsrecht verliere.';

    const TEXT_SERVICE = 'Ich verlange ausdrücklich, dass mit der Ausführung der Dienstleistung vor Ablauf der Widerrufsfrist begonnen wird. Mir ist bekannt, dass ich mein Widerrufsrecht bei vollständiger Vertragserfüllung verliere.';

    const TEXT_MIXED = 'Ich stimme ausdrücklich zu, dass mit der Ausführung des Vertrags vor Ablauf der Widerrufsfrist begonnen wird: Die digitalen Inhalte werden mir sofort bereitgestellt, und mit der Dienstleistung wird vor Ablauf der Widerrufsfrist begonnen. Mir ist bekannt, dass ich mein Widerrufsrecht für die digitalen Inhalte mit Beginn der Ausführung und für die Dienstleistung bei vollständiger Vertragserfüllung verliere.';

    public static function init()
    {
        // Feuert im Checkout-Renderer vor der Checkbox und liefert den Warenkorb mit.
        add_action('fluent_cart/before_billing_fields', [__CLASS__, 'setCheckboxText']);
    }

    /**
     * Teilt Positionen in digitale Inhalte und Dienstleistungen auf.
     *
     * @param iterable $items Bestell- oder Warenkorbpositionen (Objekte oder Arrays mit post_id, fulfillment_type, Titel)
     * @return array{digital: string[], service: string[]}
     */
    public static function classify($items)
    {
        $serviceCategories = (array) apply_filters('kic/service_categories', ['live-call']);
        $result = ['digital' => [], 'service' => []];

        foreach ((array) $items as $item) {
            $item = (array) (is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item);
            if (($item['fulfillment_type'] ?? '') !== 'digital') {
                continue;
            }

            $productId = (int) ($item['post_id'] ?? 0);
            $title     = $item['post_title'] ?? '';
            if (!$title) {
                $title = $item['title'] ?? '';
            }
            if (!$title && $productId) {
                $title = get_the_title($productId);
            }

            $slugs     = $productId ? wp_get_object_terms($productId, 'product-categories', ['fields' => 'slugs']) : [];
            $isService = !is_wp_error($slugs) && (bool) array_intersect($serviceCategories, (array) $slugs);

            $result[$isService ? 'service' : 'digital'][] = $title;
        }

        $result['digital'] = array_values(array_unique(array_filter($result['digital'])));
        $result['service'] = array_values(array_unique(array_filter($result['service'])));

        return $result;
    }

    public static function textFor(array $classified)
    {
        if ($classified['digital'] && $classified['service']) {
            return self::TEXT_MIXED;
        }
        if ($classified['service']) {
            return self::TEXT_SERVICE;
        }
        return self::TEXT_DIGITAL;
    }

    public static function setCheckboxText($args = [])
    {
        $cart = is_array($args) ? ($args['cart'] ?? null) : null;
        if (!$cart || !class_exists('\FluentCart\App\Services\Renderer\CheckoutFieldsSchema')) {
            return;
        }

        $text = self::textFor(self::classify((array) $cart->cart_data));

        try {
            $schema   = \FluentCart\App\Services\Renderer\CheckoutFieldsSchema::class;
            $settings = $schema::getFieldsSettings();
            if (!is_array($settings) || empty($settings['agree_terms'])) {
                return;
            }
            $settings['agree_terms']['text'] = $text;

            $property = new \ReflectionProperty($schema, 'fieldsCache');
            $property->setValue(null, $settings);
        } catch (\Throwable $e) {
            // FluentCart hat seine Interna geändert: gespeicherter Text bleibt stehen, der Setup-Check meldet es.
        }
    }

    /**
     * Für den Setup-Check: lässt sich der Checkbox-Text noch pro Warenkorb setzen?
     */
    public static function isSupported()
    {
        return class_exists('\FluentCart\App\Services\Renderer\CheckoutFieldsSchema')
            && property_exists('\FluentCart\App\Services\Renderer\CheckoutFieldsSchema', 'fieldsCache')
            && method_exists('\FluentCart\App\Services\Renderer\CheckoutFieldsSchema', 'getFieldsSettings');
    }
}
