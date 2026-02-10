# Internationalisierung (i18n) — Planungsdokument

## Ziel

Plugin vollständig zweisprachig: **Deutsch** (Quellsprache) + **Englisch** (Übersetzung).
Backend und Frontend sollen in beiden Sprachen funktionieren.

## Aktueller Stand

### Was bereits vorhanden ist

- **PHP**: 2.367+ Strings korrekt mit `__()`, `esc_html__()`, `_e()` etc. gewrappt
- **JavaScript/React**: `@wordpress/i18n` (`__()`) durchgängig in allen JSX-Komponenten
- **`wp_set_script_translations()`**: Für Admin-JS korrekt aufgerufen (Plugin.php, ApplicationDetail.php, BlockLoader.php)
- **POT-Datei**: `languages/recruiting-playbook.pot` existiert (514 Zeilen)
- **Text Domain Header**: Korrekt im Plugin-Header (`Text Domain: recruiting-playbook`, `Domain Path: /languages`)
- **Translator-Kommentare**: Vorhanden wo nötig (`/* translators: ... */`)
- **Pluralisierung**: Korrekt mit `_n()` (z.B. Shortcodes.php)

### Was fehlt

| # | Problem | Schwere | Aufwand |
|---|---------|---------|---------|
| 1 | `load_plugin_textdomain()` nicht aufgerufen — `loadI18n()` in Plugin.php ist leer | Kritisch | 5 Min |
| 2 | Keine `.po`/`.mo` Dateien (weder de_DE noch en_US) | Kritisch | Hauptarbeit |
| 3 | Keine `.json` Dateien für JavaScript-Übersetzungen | Kritisch | 5 Min (generiert) |
| 4 | Hardcoded Status-Labels in `ApplicationsPage.jsx` (Zeile 36-43, STATUS_CONFIG) | Mittel | 5 Min |
| 5 | `ApplicationDetail.php` Zeile 70: `wp_set_script_translations()` ohne Path-Parameter | Gering | 2 Min |
| 6 | WP-CLI lokal nicht verfügbar (`npm run pot` scheitert außerhalb Container) | Gering | — |

## Umsetzungsplan

### Schritt 1: Text Domain laden (5 Min)

`src/Core/Plugin.php` — Methode `loadI18n()` befüllen:

```php
private function loadI18n(): void {
    load_plugin_textdomain(
        'recruiting-playbook',
        false,
        dirname( plugin_basename( RP_PLUGIN_FILE ) ) . '/languages'
    );
}
```

### Schritt 2: JS-Strings fixen (5 Min)

`assets/src/js/admin/applications/ApplicationsPage.jsx` — STATUS_CONFIG Labels mit `__()` wrappen:

```javascript
const STATUS_CONFIG = {
    new: { label: __( 'Neu', 'recruiting-playbook' ), color: '#2271b1', bg: '#e6f3ff' },
    screening: { label: __( 'In Prüfung', 'recruiting-playbook' ), color: '#dba617', bg: '#fff8e6' },
    // etc.
};
```

### Schritt 3: POT-Datei aktualisieren (5 Min)

Im Dev Container ausführen:

```bash
cd plugin
npm run pot
# Oder direkt:
wp i18n make-pot . languages/recruiting-playbook.pot --exclude=node_modules,vendor,tests
```

### Schritt 4: Englische Übersetzungsdatei erstellen (Hauptarbeit)

Da die Quellsprache Deutsch ist, brauchen wir eine **englische** `.po`-Datei.

**Option A — Manuell mit Poedit:**
1. POT-Datei in Poedit öffnen
2. Sprache auf `en_US` setzen
3. Alle ~200+ Strings ins Englische übersetzen
4. Speichern → erzeugt `recruiting-playbook-en_US.po` + `.mo`

**Option B — Loco Translate (WordPress-Plugin):**
1. Loco Translate installieren
2. Im WP-Admin Übersetzung für en_US anlegen
3. Direkt im Browser übersetzen
4. Dateien exportieren ins `languages/`-Verzeichnis

**Option C — AI-gestützt:**
1. POT-Datei als Vorlage nehmen
2. Übersetzungen per AI generieren lassen
3. Manuell prüfen und als `.po` speichern

### Schritt 5: .mo kompilieren (2 Min)

Falls nicht automatisch durch Poedit/Loco:

```bash
msgfmt languages/recruiting-playbook-en_US.po -o languages/recruiting-playbook-en_US.mo
```

### Schritt 6: JSON für JavaScript generieren (2 Min)

```bash
wp i18n make-json languages/recruiting-playbook-en_US.po --no-purge
```

Erzeugt `recruiting-playbook-en_US-<hash>.json` Dateien für jeden JS-Handle.

### Schritt 7: Path-Parameter fixen (2 Min)

`src/Admin/Pages/ApplicationDetail.php` Zeile 70 — Path ergänzen:

```php
wp_set_script_translations( 'rp-applicant', 'recruiting-playbook', RP_PLUGIN_DIR . 'languages' );
```

## Erwartete Dateien nach Abschluss

```
languages/
├── recruiting-playbook.pot                          # Vorlage (bereits vorhanden)
├── recruiting-playbook-en_US.po                     # Englische Übersetzung
├── recruiting-playbook-en_US.mo                     # Kompiliert
├── recruiting-playbook-en_US-<hash1>.json           # JS: rp-admin
├── recruiting-playbook-en_US-<hash2>.json           # JS: rp-applicant
├── recruiting-playbook-en_US-<hash3>.json           # JS: rp-admin-email
└── .gitkeep
```

## Offene Entscheidung

**Quellsprache beibehalten oder umstellen?**

| | Deutsch als Quelle (aktuell) | Englisch als Quelle (WordPress-Standard) |
|---|---|---|
| Vorteil | Kein Refactoring nötig | WordPress-Konvention, einfacher für Marketplace |
| Nachteil | Englisch muss übersetzt werden | Alle 2.367+ Strings in PHP/JS umschreiben + de_DE.po erstellen |
| Empfehlung | **Jetzt beibehalten** | Ggf. vor WordPress.org-Release umstellen |
