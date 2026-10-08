# KI-Communities Companion

WordPress-Plugin für ki-communities.de. Bündelt die rechtlichen und datenschutzbezogenen Ergänzungen für FluentCart und Fluent Community.

## Funktionen

| Modul | Datei | Was es tut |
|---|---|---|
| Verzicht passend zum Warenkorb | `includes/class-consent.php` | Setzt den Text der Pflichtcheckbox an der Kasse je nach Warenkorb: digitale Inhalte (§ 356 Abs. 5 BGB), Dienstleistungen (§ 356 Abs. 4 BGB) oder beides. |
| Rechtsblock in der Bestellbestätigung | `includes/class-legal-email.php` | Hängt an die Mail „Kaufbeleg“ die Bestätigung des Verzichts bzw. des vorzeitigen Beginns an (getrennt für digitale Inhalte und Dienstleistungen, mit Produktnamen) sowie die aktuellen AGB mit Widerrufsbelehrung. |
| Hinweis an der Kasse | `includes/class-checkout-notice.php` | Zeigt über dem Bestell-Button den Hinweis mit Links auf AGB, Widerrufsbelehrung und Datenschutz. |
| Community-Medien lokal | `includes/class-community-media.php` | Speichert YouTube-Vorschaubilder und GIPHY-GIFs aus Beiträgen, Lektionen und Kommentaren lokal (`uploads/fcom-extern/`). |
| Portal-Styles | `includes/class-portal-styles.php` | Hinweisband auf YouTube-Videos vor dem Abspielen; Font Awesome lokal für Linklisten. |
| FluentCart-Farben seitenweit | `includes/class-theme-colors.php` | Übersetzt die Theme-Farben auch außerhalb der Kasse in die `--fct-checkout-*`-Variablen, damit z. B. der Button im Widerrufsformular violett statt grau ist. |
| Warnung EU-Lieferschwelle | `includes/class-oss-monitor.php` | Summiert den Nettoumsatz mit Privatkunden im EU-Ausland (bezahlte Live-Bestellungen, ohne Reverse Charge, abzüglich Erstattungen) und warnt ab 8.000 € bzw. bei Überschreiten von 10.000 € im Admin und einmal pro Stufe und Jahr per E-Mail. Still, sobald die EU-Methode in FluentCart auf „OSS“ steht. |
| Shop-Seiten auf noindex | `includes/class-shop-noindex.php` | Kasse, Kundenkonto und Beleg-/Danke-Seite (aus den FluentCart-Einstellungen) bekommen `noindex, nofollow`. |
| Setup-Check | `includes/class-setup-check.php` | Werkzeuge → KI-Communities Check: zeigt, ob alle nötigen Einstellungen noch stimmen. |
| Produkt-Karten | `includes/class-product-cards.php` | Shortcode `[kic_produktkarten anzahl="3" kategorie="" ausschliessen=""]` gibt veröffentlichte FluentCart-Produkte als Karten aus (Bild, Kategorie, Titel, erster Absatz der Kurzbeschreibung, Preis mit „inkl. MwSt.“, „Kostenlos“, „ab“ und Abo-Intervall, Link). Nur HTML mit Klassen `kc-card…` (je Karte `kc-card--cat-<Kategorie-Slug>` für die Kategorie-Farbe), das Aussehen steht im Divi-CSS der Startseite. Reihenfolge: Menü-Reihenfolge, dann neueste. |

### Anpassbar per Filter

- `kic/service_categories` – Produktkategorie-Slugs, die als Dienstleistung gelten (Standard: `live-call`). Für diese Produkte gilt der Dienstleistungs-Text an der Kasse und in der Mail.
- `kic/agb_page_slug` – Slug der AGB-Seite (Standard: `agb-widerrufsrecht`).
- `kic/youtube_notice` – Text des Hinweisbands auf YouTube-Videos.
- `kic/oss_warning_threshold` / `kic/oss_limit` – Warnschwelle (Standard 8.000 €) und Grenze (10.000 €).
- `kic/oss_notify_email` – Empfänger der Warn-Mail (Standard: WordPress-Admin-E-Mail).
- `kic/oss_include_test_orders` – Testbestellungen mitzählen (Standard: nein).
- `kic/noindex_page_ids` – weitere Seiten-IDs, die nicht indexiert werden sollen.
- `kic/product_card_labels` / `kic/product_card_ctas` – Label und Button-Text der Produkt-Karten je Kategorie-Slug (z. B. `videokurse` → „Videokurs“ / „Zum Kurs“); `kic/product_card_tax_note` – Steuerhinweis hinter dem Preis (Standard „inkl. MwSt.“, leer = aus).

### Voraussetzungen auf der Seite

Werden vom Setup-Check geprüft:

- Fluent Community → Player: Ladestrategie „play“ (YouTube lädt erst beim Klick)
- FluentPlayer → YouTube: Datenschutzmodus an
- FluentCart → Checkout: Pflichtcheckbox enthält nur den Verzicht auf das Widerrufsrecht
- AGB-Seite mit Anker `id="widerrufsbelehrung"` an „Teil B – Widerrufsbelehrung“

## Neue Version veröffentlichen

1. Version an **zwei** Stellen in `ki-communities-companion.php` erhöhen: im Header (`Version:`) und bei `KIC_VERSION`.
2. Committen und auf `main` pushen.
3. Die GitHub Action baut die ZIP (inkl. Composer-Abhängigkeiten) und veröffentlicht das Release `v<Version>`.
4. WordPress zeigt das Update unter Plugins an (Prüfung alle 12 Stunden, sofort über „Nach Updates suchen“).

Updates kommen über den [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) aus den GitHub-Releases.
