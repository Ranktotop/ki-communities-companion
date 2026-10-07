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
| FluentCart-Farben seitenweit | `includes/class-theme-colors.php` | Übersetzt die Theme-Farben auch außerhalb der Kasse in die `--fct-checkout-*`-Variablen, damit z. B. der Button im Widerrufsformular violett statt grau ist. Gibt dem Abmelden-Button im Kundenkonto seine FluentCart-Farbe zurück (Divis Linkfarbe würde ihn sonst überschreiben). |
| Verkauf nur nach Deutschland | `includes/class-sales-countries.php` | Länderauswahl an der Kasse auf Deutschland begrenzt, andere Rechnungsländer werden serverseitig abgelehnt, Hinweis zu Beginn der Kasse (§ 312j Abs. 1 BGB). |
| Setup-Check | `includes/class-setup-check.php` | Werkzeuge → KI-Communities Check: zeigt, ob alle nötigen Einstellungen noch stimmen. |

### Anpassbar per Filter

- `kic/service_categories` – Produktkategorie-Slugs, die als Dienstleistung gelten (Standard: `live-call`). Für diese Produkte gilt der Dienstleistungs-Text an der Kasse und in der Mail.
- `kic/agb_page_slug` – Slug der AGB-Seite (Standard: `agb-widerrufsrecht`).
- `kic/youtube_notice` – Text des Hinweisbands auf YouTube-Videos.
- `kic/sales_countries` – erlaubte Rechnungsländer als ISO-Codes (Standard: `['DE']`).
- `kic/sales_countries_notice` – Hinweistext zur Länderbeschränkung an der Kasse.

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
