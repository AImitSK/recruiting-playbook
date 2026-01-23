# Conversion Tracking

Dieses Dokument beschreibt die Conversion-Tracking-Funktionen des Recruiting Playbook Plugins.

## Übersicht

Das Plugin bietet umfassende Tracking-Möglichkeiten für Recruiting-Conversions:

| Feature | FREE | Pro |
|---------|------|-----|
| DataLayer Events (GTM) | ✅ | ✅ |
| Google Ads Conversion ID | ❌ | ✅ |
| Custom JavaScript Events | ❌ | ✅ |
| PHP Hooks (Server-Side) | ❌ | ✅ |
| Tracking-Einstellungen Tab | ❌ | ✅ |

---

## FREE: DataLayer Events

### Verfügbare Events

Das Plugin pusht automatisch Events in den `dataLayer`, die von Google Tag Manager (GTM) oder anderen Tag-Management-Systemen verarbeitet werden können.

#### 1. `rp_job_viewed` - Stellenanzeige angesehen

Wird gefeuert, wenn ein Besucher eine Stellenanzeige öffnet.

```javascript
{
    'event': 'rp_job_viewed',
    'rp_job_id': 123,
    'rp_job_title': 'Software Developer (m/w/d)',
    'rp_job_category': 'IT & Development',
    'rp_job_location': 'Berlin',
    'rp_employment_type': 'Vollzeit'
}
```

#### 2. `rp_application_started` - Bewerbungsformular geöffnet

Wird gefeuert, wenn der Besucher das Bewerbungsformular öffnet/sichtbar macht.

```javascript
{
    'event': 'rp_application_started',
    'rp_job_id': 123,
    'rp_job_title': 'Software Developer (m/w/d)'
}
```

#### 3. `rp_application_submitted` - Bewerbung abgeschickt

**Hauptkonversion** - Wird gefeuert, wenn eine Bewerbung erfolgreich abgeschickt wurde.

```javascript
{
    'event': 'rp_application_submitted',
    'rp_job_id': 123,
    'rp_job_title': 'Software Developer (m/w/d)',
    'rp_job_category': 'IT & Development',
    'rp_job_location': 'Berlin',
    'rp_application_id': 456
}
```

---

## GTM Setup-Anleitung

### Schritt 1: GTM Container einbinden

Falls noch nicht geschehen, GTM Container-Code in WordPress einbinden:
- Empfohlen: Plugin "GTM4WP" oder "Google Tag Manager for WordPress"
- Oder manuell im Theme-Header

### Schritt 2: Trigger erstellen

In GTM einen **Custom Event Trigger** erstellen:

1. Triggers → Neu → Trigger-Konfiguration
2. Trigger-Typ: **Benutzerdefiniertes Ereignis**
3. Ereignisname: `rp_application_submitted`
4. Trigger auslösen bei: Alle benutzerdefinierten Ereignisse

### Schritt 3: Variablen erstellen

DataLayer-Variablen für die Event-Daten:

| Variable Name | DataLayer Variable |
|---------------|-------------------|
| RP - Job ID | `rp_job_id` |
| RP - Job Title | `rp_job_title` |
| RP - Job Category | `rp_job_category` |
| RP - Job Location | `rp_job_location` |
| RP - Application ID | `rp_application_id` |

### Schritt 4: Tags erstellen

#### Google Analytics 4 - Event

1. Tags → Neu → GA4-Ereignis
2. Ereignisname: `generate_lead` (Standard-Event für Leads)
3. Parameter:
   - `job_id`: `{{RP - Job ID}}`
   - `job_title`: `{{RP - Job Title}}`
   - `job_category`: `{{RP - Job Category}}`
4. Trigger: `rp_application_submitted`

#### Google Ads - Conversion

1. Tags → Neu → Google Ads-Conversion-Tracking
2. Conversion-ID: Ihre Google Ads Conversion ID
3. Conversion-Label: Ihr Conversion Label
4. Trigger: `rp_application_submitted`

#### Meta (Facebook) Pixel - Lead

1. Tags → Neu → Benutzerdefiniertes HTML
2. Code:
```html
<script>
  fbq('track', 'Lead', {
    content_name: '{{RP - Job Title}}',
    content_category: '{{RP - Job Category}}'
  });
</script>
```
3. Trigger: `rp_application_submitted`

#### LinkedIn Insight Tag - Conversion

1. Tags → Neu → Benutzerdefiniertes HTML
2. Code:
```html
<script>
  window.lintrk('track', { conversion_id: YOUR_CONVERSION_ID });
</script>
```
3. Trigger: `rp_application_submitted`

---

## Event-Tracking ohne GTM

Wenn Sie GTM nicht verwenden, können Sie die DataLayer-Events auch direkt mit JavaScript abfangen:

```javascript
// DataLayer Event Listener
window.addEventListener('load', function() {
    // Original push-Methode speichern
    var originalPush = window.dataLayer.push;

    // Push-Methode überschreiben
    window.dataLayer.push = function(data) {
        // Event abfangen
        if (data.event === 'rp_application_submitted') {
            console.log('Bewerbung erfasst:', data);

            // Eigenes Tracking hier einfügen
            // z.B. Analytics-Call, Pixel, etc.
        }

        // Original-Funktion aufrufen
        return originalPush.apply(this, arguments);
    };
});
```

---

## Pro: Erweiterte Tracking-Features

> **Hinweis:** Die folgenden Features sind nur in der Pro-Version verfügbar.

### Google Ads Conversion ID

Direkte Integration ohne GTM:

1. Einstellungen → Recruiting → Tracking
2. Google Ads Conversion ID eingeben: `AW-XXXXXXXXX`
3. Conversion Label eingeben: `XXXXXXXXXXX`

Das Plugin feuert automatisch den Conversion-Tag bei erfolgreicher Bewerbung.

### Custom JavaScript Events

Zusätzlich zu DataLayer-Events werden DOM Custom Events gefeuert:

```javascript
// Event Listener für Bewerbungen
document.addEventListener('rp:application:submitted', function(e) {
    console.log('Bewerbung:', e.detail);
    // e.detail enthält: job_id, job_title, application_id, etc.
});

// Event Listener für Job-Views
document.addEventListener('rp:job:viewed', function(e) {
    console.log('Job angesehen:', e.detail);
});

// Event Listener für Formular-Start
document.addEventListener('rp:application:started', function(e) {
    console.log('Formular gestartet:', e.detail);
});
```

### PHP Hooks (Server-Side Tracking)

Für DSGVO-konforme Server-Side-Implementierungen:

```php
// Hook nach erfolgreicher Bewerbung
add_action('rp_application_submitted', function($application_id, $data) {
    // Server-Side Tracking
    // z.B. Google Measurement Protocol, Facebook Conversions API

    $job = get_post($data['job_id']);

    // Beispiel: Custom API Call
    wp_remote_post('https://your-tracking-endpoint.com/conversion', [
        'body' => [
            'event' => 'application',
            'job_id' => $data['job_id'],
            'job_title' => $job->post_title,
            'timestamp' => time(),
        ]
    ]);
}, 10, 2);

// Hook nach Job-View
add_action('rp_job_viewed', function($job_id) {
    // Server-Side Job View Tracking
}, 10, 1);
```

### Tracking-Einstellungen (Pro)

Im Admin unter Einstellungen → Recruiting → Tracking:

| Einstellung | Beschreibung |
|-------------|--------------|
| Google Ads Conversion ID | Format: `AW-XXXXXXXXX` |
| Google Ads Conversion Label | Conversion Label aus Google Ads |
| Conversion Value | Optionaler Wert pro Bewerbung (€) |
| Custom Tracking Code | Eigener JavaScript-Code (Experten) |
| Server-Side Tracking | Aktiviert PHP Hooks |

---

## Best Practices

### 1. Consent Management

Stellen Sie sicher, dass Tracking nur mit Nutzer-Einwilligung erfolgt:

```javascript
// Beispiel: Tracking nur mit Consent
window.addEventListener('consent_given', function() {
    // GTM aktivieren oder Tracking starten
});
```

Empfohlene Consent-Plugins:
- Complianz
- Borlabs Cookie
- CookieYes

### 2. Conversion Value festlegen

Für Google Ads empfehlen wir einen Conversion-Wert basierend auf:
- Durchschnittliche Kosten pro Einstellung
- Oder: Durchschnittlicher Jahresgehalt / Anzahl Bewerbungen pro Einstellung

Beispiel: Bei 50 Bewerbungen pro Einstellung und €5.000 Recruiting-Kosten = €100 pro Bewerbung.

### 3. Funnel-Tracking

Tracken Sie den gesamten Bewerbungsfunnel:

1. `rp_job_viewed` → Stellenanzeige gesehen
2. `rp_application_started` → Interesse gezeigt
3. `rp_application_submitted` → Konvertiert

So können Sie Abbrüche identifizieren und optimieren.

### 4. UTM-Parameter

Nutzen Sie UTM-Parameter in Ihren Job-Links:

```
https://example.com/jobs/developer/?utm_source=linkedin&utm_medium=social&utm_campaign=dev-hiring-q1
```

GTM kann diese automatisch an GA4 weitergeben.

---

## Debugging

### DataLayer Inspector

1. Chrome DevTools öffnen (F12)
2. Console-Tab
3. Eingeben: `dataLayer`
4. Array mit allen gepushten Events wird angezeigt

### GTM Preview Mode

1. In GTM auf "In Vorschau ansehen" klicken
2. Website öffnen
3. GTM Debug-Panel zeigt alle gefeuerten Tags

### Plugin Debug Mode

In `wp-config.php`:

```php
define('RP_DEBUG_TRACKING', true);
```

Gibt alle Tracking-Events in der Browser-Console aus.

---

## Häufige Fragen

### Warum sehe ich keine Events im DataLayer?

1. Prüfen Sie, ob `dataLayer` vor dem Plugin initialisiert wird
2. GTM-Container muss vor Plugin-Scripts laden
3. Check: `console.log(window.dataLayer)` sollte Array sein

### Funktioniert das mit Consent-Tools?

Ja. Die Events werden in den DataLayer gepusht, aber Tags feuern erst, wenn GTM entsprechend konfiguriert ist (Consent Mode).

### Kann ich eigene Events hinzufügen?

Mit Pro können Sie über PHP-Hooks beliebige Events feuern. In FREE nutzen Sie die Standard-Events und können diese in GTM beliebig weiterverarbeiten.

---

## Technische Details

### DataLayer Initialisierung

Das Plugin stellt sicher, dass `dataLayer` existiert:

```javascript
window.dataLayer = window.dataLayer || [];
```

### Event-Timing

- `rp_job_viewed`: Beim Laden der Single-Job-Seite (DOMContentLoaded)
- `rp_application_started`: Beim Öffnen/Fokussieren des Formulars
- `rp_application_submitted`: Nach erfolgreicher REST-API Response

### Daten-Sanitization

Alle Werte werden vor dem Push sanitisiert:
- HTML-Entities escaped
- Keine PII (Personal Identifiable Information) im DataLayer
- Job-Daten sind öffentlich (bereits auf der Seite sichtbar)

---

## Changelog

### Version 1.0.0
- Initial Release
- DataLayer Events für FREE
- GTM-Kompatibilität

### Geplant für Pro
- Google Ads direkte Integration
- Custom JavaScript Events
- PHP Hooks für Server-Side
- Tracking-Einstellungen Tab
