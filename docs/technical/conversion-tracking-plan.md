# Conversion Tracking: Planungsdokument

> **Status:** Planung
> **Erstellt:** 10. Februar 2026
> **Ziel:** Fehlende Pro-Features implementieren, bestehende Free-Features konfigurierbar machen

---

## Ausgangslage

### Bereits implementiert (FREE)

| Feature | Datei | Status |
|---------|-------|--------|
| `rp_job_viewed` DataLayer Event | `tracking.js` | Funktioniert |
| `rp_application_started` DataLayer Event | `tracking.js` | Funktioniert |
| `rp_application_submitted` DataLayer Event | `tracking.js` + `application-form.js` | Funktioniert |
| DOM-Tracking-Container `[data-rp-tracking]` | `single-job_listing.php` | Funktioniert |
| Debug-Modus `RP_DEBUG_TRACKING` | `Plugin.php` | Funktioniert |
| ConversionService (Backend-Metriken) | `ConversionService.php` | Funktioniert + Tests |

### Fehlend

| Feature | Tier | Prioritaet |
|---------|------|------------|
| Settings-Tab "Tracking" im Admin | Free + Pro | Hoch |
| Granulare Event-Steuerung (ein/aus pro Event) | Free | Hoch |
| Custom JavaScript Events (DOM Events) | Pro | Hoch |
| Google Ads Conversion direkt (ohne GTM) | Pro | Hoch |
| PHP Hook `rp_application_submitted` | Pro | Mittel |
| Custom Tracking Code (Freitext JS) | Pro | Mittel |
| Admin-Aufrufe ausschliessen | Free | Niedrig |

---

## 1. Features im Detail

### 1.1 Settings-Tab "Tracking"

| | |
|---|---|
| **Prioritaet** | Hoch |
| **Tier** | Free + Pro (gemischt) |
| **Komplexitaet** | Mittel |

**Was:** Neuer Tab in den Plugin-Einstellungen fuer alle Tracking-bezogenen Optionen.
Free-Nutzer sehen die DataLayer-Einstellungen, Pro-Nutzer zusaetzlich Google Ads und Custom Events.

**Einordnung im bestehenden Layout:**

```
Einstellungen (rp-settings)
├── Tab: Allgemein
├── Tab: Firmendaten
├── Tab: Export
├── Tab: Benutzerrollen        (Pro)
├── Tab: Design & Branding     (Pro)
├── Tab: Integrationen
├── Tab: Tracking              ← NEU (Free + Pro gemischt)
├── Tab: API                   (Pro)
└── Tab: KI-Analyse            (Addon)
```

Der Tab steht nach Integrationen und vor API, da Tracking fuer alle Nutzer relevant ist
(DataLayer Events = Free) und thematisch zwischen Integrationen und API passt.

**Tab-Sichtbarkeit:** Immer sichtbar (wie Integrationen). Pro-Bereiche mit Lock-Badge.

---

### 1.2 DataLayer Events (Free)

| | |
|---|---|
| **Prioritaet** | Hoch |
| **Tier** | Free |
| **Aenderung** | Bestehende Events konfigurierbar machen |

**Aktuell:** Alle drei Events feuern immer, ohne Steuerungsmoeglichkeit.

**Neu:** Granulare Steuerung pro Event:
- `rp_job_viewed` ein/aus
- `rp_application_started` ein/aus
- `rp_application_submitted` ein/aus
- Admin-Aufrufe ausschliessen (Logged-in Admins werden nicht getrackt)
- Debug-Modus ein/aus (ersetzt `RP_DEBUG_TRACKING` Konstante)

**Implementierung:**
- Settings aus `rp_tracking` Option laden
- `tracking.js` liest Settings ueber `window.rpTrackingConfig` (von PHP inline ausgegeben)
- Pruefen vor jedem Event ob aktiviert

---

### 1.3 Google Ads Conversion (Pro)

| | |
|---|---|
| **Prioritaet** | Hoch |
| **Tier** | Pro |
| **Komplexitaet** | Niedrig |

**Was:** Direkte Google Ads Integration ohne GTM.
Nutzer traegt Conversion-ID und Label ein, Plugin feuert den Conversion-Tag automatisch
bei `rp_application_submitted`.

**Felder:**
- Google Ads Conversion ID (Format: `AW-XXXXXXXXX`)
- Google Ads Conversion Label
- Conversion Value (optional, in EUR)

**Implementierung:**
- Bei aktivierter Google Ads ID: `gtag('event', 'conversion', {...})` nach Submit feuern
- Global Site Tag (gtag.js) nur laden wenn nicht bereits vorhanden
- Validierung: Conversion-ID muss mit `AW-` beginnen

---

### 1.4 Custom JavaScript Events (Pro)

| | |
|---|---|
| **Prioritaet** | Hoch |
| **Tier** | Pro |
| **Komplexitaet** | Niedrig |

**Was:** Zusaetzlich zu DataLayer-Events werden DOM Custom Events dispatcht.
Ermoeglicht Integration mit beliebigen Tools ohne GTM.

**Events:**
```javascript
// Bei Job-Ansicht
document.dispatchEvent(new CustomEvent('rp:job:viewed', {
    detail: { job_id, job_title, job_category, job_location, employment_type }
}));

// Bei Formular-Start
document.dispatchEvent(new CustomEvent('rp:application:started', {
    detail: { job_id, job_title }
}));

// Bei Bewerbungs-Submit
document.dispatchEvent(new CustomEvent('rp:application:submitted', {
    detail: { job_id, job_title, job_category, job_location, application_id }
}));
```

**Implementierung:**
- In `tracking.js`: Neben `dataLayer.push()` zusaetzlich `dispatchEvent()`
- Nur wenn Pro-Lizenz aktiv (via `window.rpTrackingConfig.isPro`)

---

### 1.5 Custom Tracking Code (Pro)

| | |
|---|---|
| **Prioritaet** | Mittel |
| **Tier** | Pro |
| **Komplexitaet** | Niedrig |

**Was:** Freitext-Feld fuer eigenen JavaScript-Code, der bei `rp_application_submitted` ausgefuehrt wird.
Fuer individuelle Integrationen (z.B. HubSpot, ActiveCampaign, etc.).

**Implementierung:**
- Textarea im Admin mit Syntax-Hinweis
- Code wird als `<script>` Tag im Footer geladen
- Variablen verfuegbar: `rpConversionData.job_id`, `rpConversionData.job_title`, etc.
- Sicherheit: Nur Admins koennen Code eingeben, `wp_kses` nicht noetig da Admin-Capability

---

### 1.6 PHP Hooks (Pro)

| | |
|---|---|
| **Prioritaet** | Mittel |
| **Tier** | Pro |
| **Komplexitaet** | Niedrig |

**Was:** Der Hook `rp_application_created` existiert bereits in `ApplicationService.php` (Zeile 150).
Die Dokumentation beschreibt ihn als `rp_application_submitted` - Benennung anpassen oder Alias.

**Status:**
- `rp_application_created` → Existiert bereits (Zeile 150, ApplicationService)
- `rp_application_status_changed` → Existiert bereits (Zeile 572, ApplicationService)

**Aenderung:**
- Dokumentation anpassen: `rp_application_created` statt `rp_application_submitted`
- Alternativ: Alias-Hook feuern: `do_action('rp_application_submitted', $id, $data)` neben `rp_application_created`
- **Empfehlung:** Doku anpassen, da `rp_application_created` semantisch korrekter ist

---

## 2. Admin-UI: Settings-Tab "Tracking"

### 2.1 UI-Layout (Wireframe)

```
┌──────────────────────────────────────────────────────────────────────┐
│                                                                       │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │  DataLayer Events (GTM)                          Free    [ON]  │  │
│  │  ──────────────────────────────────────────────────────────    │  │
│  │  Automatische Events fuer Google Tag Manager und andere        │  │
│  │  Tag-Management-Systeme.                                       │  │
│  │                                                                │  │
│  │  ● Aktiv – Events werden auf allen Stellenseiten gefeuert      │  │
│  │                                                                │  │
│  │  ┌─ Events ──────────────────────────────────────────────┐    │  │
│  │  │ [x] Stellenansicht tracken (rp_job_viewed)             │    │  │
│  │  │ [x] Formular-Start tracken (rp_application_started)    │    │  │
│  │  │ [x] Bewerbung tracken (rp_application_submitted)       │    │  │
│  │  └───────────────────────────────────────────────────────┘    │  │
│  │                                                                │  │
│  │  ┌─ Optionen ────────────────────────────────────────────┐    │  │
│  │  │ [x] Admin-Aufrufe ausschliessen                        │    │  │
│  │  │ [ ] Debug-Modus (Events in Browser-Console loggen)     │    │  │
│  │  └───────────────────────────────────────────────────────┘    │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │  Google Ads Conversion                        🔒 Pro    [OFF]  │  │
│  │  ──────────────────────────────────────────────────────────    │  │
│  │  Conversion-Tracking direkt mit Google Ads – ohne GTM.         │  │
│  │                                                                │  │
│  │  Conversion-ID:                                                │  │
│  │  ┌──────────────────────────────────────────────────────┐     │  │
│  │  │ AW-XXXXXXXXX                                         │     │  │
│  │  └──────────────────────────────────────────────────────┘     │  │
│  │                                                                │  │
│  │  Conversion Label:                                             │  │
│  │  ┌──────────────────────────────────────────────────────┐     │  │
│  │  │                                                       │     │  │
│  │  └──────────────────────────────────────────────────────┘     │  │
│  │                                                                │  │
│  │  Conversion Value (EUR):                                       │  │
│  │  ┌──────────────────────────────────────────────────────┐     │  │
│  │  │ 0.00                                                  │     │  │
│  │  └──────────────────────────────────────────────────────┘     │  │
│  │                                                                │  │
│  │  ℹ️  Wird bei jeder erfolgreichen Bewerbung automatisch        │  │
│  │     als Conversion an Google Ads gemeldet.                     │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │  Custom Events & Code                         🔒 Pro    [OFF]  │  │
│  │  ──────────────────────────────────────────────────────────    │  │
│  │  Erweiterte Tracking-Optionen fuer individuelle Integrationen. │  │
│  │                                                                │  │
│  │  ┌─ JavaScript Events ───────────────────────────────────┐    │  │
│  │  │ [x] DOM Custom Events feuern                           │    │  │
│  │  │     (rp:job:viewed, rp:application:submitted, etc.)    │    │  │
│  │  └───────────────────────────────────────────────────────┘    │  │
│  │                                                                │  │
│  │  Custom Tracking Code:                                         │  │
│  │  ┌──────────────────────────────────────────────────────┐     │  │
│  │  │ // Wird bei rp_application_submitted ausgefuehrt      │     │  │
│  │  │ // Verfuegbar: rpConversionData.job_id,               │     │  │
│  │  │ //              rpConversionData.job_title, etc.       │     │  │
│  │  │                                                       │     │  │
│  │  │                                                       │     │  │
│  │  └──────────────────────────────────────────────────────┘     │  │
│  │                                                                │  │
│  │  ┌─ PHP Hooks (Server-Side) ─────────────────────────────┐    │  │
│  │  │ ℹ️  Verfuegbare Action Hooks:                          │    │  │
│  │  │                                                        │    │  │
│  │  │  rp_application_created                                │    │  │
│  │  │    → Nach erfolgreicher Bewerbung                      │    │  │
│  │  │    → Parameter: $application_id, $data                 │    │  │
│  │  │                                                        │    │  │
│  │  │  rp_application_status_changed                         │    │  │
│  │  │    → Nach Statuswechsel einer Bewerbung                │    │  │
│  │  │    → Parameter: $id, $old_status, $new_status          │    │  │
│  │  │                                                        │    │  │
│  │  │  Beispiel → [Doku-Link]                                │    │  │
│  │  └───────────────────────────────────────────────────────┘    │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                       │
│                                                    [Speichern]       │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘
```

---

### 2.2 Komponenten-Struktur (React)

```
plugin/assets/src/js/admin/settings/
├── components/
│   ├── TrackingSettings.jsx            ← Hauptkomponente fuer Tab
│   └── index.js                        ← Export hinzufuegen
├── hooks/
│   ├── useTracking.js                  ← Settings laden/speichern
│   └── index.js                        ← Export hinzufuegen
└── SettingsPage.jsx                    ← Tab hinzufuegen
```

---

### 2.3 Datenmodell (WordPress Options)

Alle Tracking-Einstellungen in einer eigenen Option `rp_tracking`:

```php
$defaults = [
    // DataLayer Events (Free)
    'datalayer_enabled'             => true,
    'track_job_viewed'              => true,
    'track_application_started'     => true,
    'track_application_submitted'   => true,
    'exclude_admin_views'           => true,
    'debug_mode'                    => false,

    // Google Ads Conversion (Pro)
    'google_ads_enabled'            => false,
    'google_ads_conversion_id'      => '',      // AW-XXXXXXXXX
    'google_ads_conversion_label'   => '',
    'google_ads_conversion_value'   => '',       // EUR

    // Custom Events & Code (Pro)
    'custom_events_enabled'         => false,
    'custom_js_events'              => true,     // DOM CustomEvent dispatch
    'custom_tracking_code'          => '',       // Freitext JS
];
```

**REST Endpoint:**
```
GET  /recruiting/v1/settings/tracking     → Einstellungen laden
POST /recruiting/v1/settings/tracking     → Einstellungen speichern
```

---

### 2.4 Backend-Architektur (PHP)

```
plugin/src/
├── Api/
│   └── TrackingController.php          ← REST Endpoints fuer Settings
├── Frontend/
│   └── TrackingScripts.php             ← tracking.js + Config laden (existiert tw. in Plugin.php)
```

**TrackingController.php:**
- `GET /settings/tracking` → Einstellungen aus `rp_tracking` laden
- `POST /settings/tracking` → Validieren + Speichern
- Sanitization: Conversion-ID muss `AW-` Prefix haben, Value muss numerisch sein
- Custom Code wird als `sanitize_textarea_field` gespeichert (Admin-only)

**TrackingScripts.php (oder Erweiterung von Plugin.php):**
- Settings aus `rp_tracking` lesen
- `window.rpTrackingConfig` als Inline-Script vor `tracking.js` ausgeben:
  ```javascript
  window.rpTrackingConfig = {
      enabled: true,
      trackJobViewed: true,
      trackApplicationStarted: true,
      trackApplicationSubmitted: true,
      excludeAdmin: true,
      debug: false,
      isPro: true,
      customEvents: true,
      googleAds: {
          enabled: true,
          conversionId: 'AW-123456789',
          conversionLabel: 'abc123',
          conversionValue: 100.00
      }
  };
  ```
- `tracking.js` liest diese Config und handelt entsprechend

---

### 2.5 Aenderungen an tracking.js

**Aktuell:** Alle Events feuern immer, keine Config-Pruefung.

**Neu:**
```javascript
(function() {
    'use strict';

    var config = window.rpTrackingConfig || {};

    // Tracking komplett deaktiviert?
    if (config.enabled === false) {
        return;
    }

    // Admin ausschliessen?
    if (config.excludeAdmin && config.isAdmin) {
        return;
    }

    // Debug-Modus aus Settings
    if (config.debug) {
        window.RP_DEBUG_TRACKING = true;
    }

    // ... bestehender Code ...

    function trackJobViewed() {
        if (config.trackJobViewed === false) return;   // ← NEU
        // ... bestehender Code ...

        // Pro: Custom Event dispatchen
        if (config.isPro && config.customEvents) {
            document.dispatchEvent(new CustomEvent('rp:job:viewed', {
                detail: { /* ... */ }
            }));
        }
    }

    function trackApplicationSubmitted(data) {
        if (config.trackApplicationSubmitted === false) return;  // ← NEU
        // ... bestehender DataLayer push ...

        // Pro: Custom Event dispatchen
        if (config.isPro && config.customEvents) {
            document.dispatchEvent(new CustomEvent('rp:application:submitted', {
                detail: { /* ... */ }
            }));
        }

        // Pro: Google Ads Conversion feuern
        if (config.googleAds && config.googleAds.enabled) {
            fireGoogleAdsConversion(data);
        }

        // Pro: Custom Tracking Code ausfuehren
        if (config.customCode) {
            executeCustomCode(data);
        }
    }

    // NEU: Google Ads Conversion
    function fireGoogleAdsConversion(data) {
        if (typeof gtag !== 'function') return;

        var conversionData = {
            'send_to': config.googleAds.conversionId + '/' + config.googleAds.conversionLabel
        };

        if (config.googleAds.conversionValue) {
            conversionData.value = config.googleAds.conversionValue;
            conversionData.currency = 'EUR';
        }

        gtag('event', 'conversion', conversionData);
    }

    // ... Rest ...
})();
```

---

## 3. Feature-Gating

| Feature | Tier | Bedingung Frontend | Bedingung Backend |
|---------|------|--------------------|-------------------|
| DataLayer Events | **Free** | Immer | Immer |
| Event-Steuerung (ein/aus) | **Free** | Immer | Immer |
| Admin ausschliessen | **Free** | Immer | Immer |
| Debug-Modus | **Free** | Immer | Immer |
| Google Ads Conversion | **Pro** | `config.isPro` | `rp_is_pro()` |
| Custom JS Events | **Pro** | `config.isPro` | `rp_is_pro()` |
| Custom Tracking Code | **Pro** | `config.isPro` | `rp_is_pro()` |
| PHP Hooks Info | **Pro** | `config.isPro` | - (nur Anzeige) |

Free-Nutzer sehen die Pro-Cards mit Lock-Badge und Upgrade-Hinweis (wie bei Integrationen).

---

## 4. Aenderungen an der Dokumentation

### conversion-tracking.mdx (Website)

Anpassungen noetig:
- PHP Hook Name: `rp_application_created` statt `rp_application_submitted`
- Hinweis auf Settings-Tab ergaenzen mit Screenshot
- Custom Events Beispiel-Code aktualisieren (Event-Namen bestaetigen)

---

## 5. Implementierungsreihenfolge

| Schritt | Was | Dateien |
|---------|-----|---------|
| 1 | REST Controller fuer Tracking-Settings | `TrackingController.php` |
| 2 | React Hook `useTracking` | `useTracking.js` |
| 3 | React Komponente `TrackingSettings` | `TrackingSettings.jsx` |
| 4 | Tab in SettingsPage registrieren | `SettingsPage.jsx` |
| 5 | `tracking.js` um Config-Pruefung erweitern | `tracking.js` |
| 6 | Config-Ausgabe von PHP (inline script) | `Plugin.php` |
| 7 | Google Ads Integration in tracking.js | `tracking.js` |
| 8 | Custom Events + Custom Code | `tracking.js` |
| 9 | Build + Test | `npm run build` |
| 10 | Dokumentation aktualisieren | `conversion-tracking.mdx` |

**Geschaetzter Aufwand:** ~2 Tage

---

*Erstellt: 10. Februar 2026*
