# Recruiting Playbook - Benutzerhandbuch

## Einführung

Recruiting Playbook ist ein WordPress-Plugin zur professionellen Verwaltung von Stellenanzeigen und Bewerbungen. Es bietet eine vollständige Lösung für kleine und mittlere Unternehmen, die ihren Recruiting-Prozess digitalisieren möchten.

---

## Erste Schritte

### Installation

1. **Plugin hochladen**
   - WordPress Admin → Plugins → Installieren → Plugin hochladen
   - ZIP-Datei auswählen und installieren

2. **Plugin aktivieren**
   - Nach der Installation auf "Aktivieren" klicken
   - Der Setup-Wizard startet automatisch

### Setup-Wizard

Der Setup-Wizard führt Sie durch die Erstkonfiguration:

**Schritt 1: Willkommen**
- Übersicht der Plugin-Features

**Schritt 2: Firmendaten**
- Firmenname eingeben
- Logo wird vom Theme übernommen (Customizer → Website-Identität)

**Schritt 3: E-Mail-Konfiguration**
- E-Mail-Adresse für Bewerbungsbenachrichtigungen
- SMTP-Status prüfen
- Test-E-Mail senden

**Schritt 4: Erste Stelle**
- Optional: Direkt erste Stellenanzeige erstellen
- Kann auch übersprungen werden

**Schritt 5: Fertig**
- Zusammenfassung der nächsten Schritte
- Link zu wichtigen Seiten

---

## Stellenanzeigen verwalten

### Neue Stelle erstellen

1. WordPress Admin → **Recruiting** → **Stellen** → **Erstellen**
2. Titel und Beschreibung eingeben
3. Kategorien zuweisen:
   - **Berufsfeld**: z.B. IT, Pflege, Verwaltung
   - **Standort**: z.B. Berlin, München
   - **Beschäftigungsart**: Vollzeit, Teilzeit, etc.

### Stellen-Details (Meta-Felder)

| Feld | Beschreibung |
|------|--------------|
| **Gehalt** | Min/Max-Werte, Währung, Zeitraum (Monat/Jahr/Stunde) |
| **Gehalt verstecken** | Checkbox um Gehalt nicht öffentlich anzuzeigen |
| **Bewerbungsfrist** | Datum bis wann Bewerbungen möglich sind |
| **Ansprechpartner** | Name, E-Mail, Telefon |
| **Remote-Option** | Vor Ort / Hybrid / Remote |
| **Startdatum** | Wann die Stelle besetzt werden soll |

### Google for Jobs

Das Plugin generiert automatisch strukturierte Daten (JSON-LD) für Google for Jobs. Für optimales Ranking sollten folgende Felder ausgefüllt sein:

- ✅ Titel
- ✅ Beschreibung (mind. 100 Zeichen)
- ✅ Standort oder Remote
- ✅ Beschäftigungsart
- ⭐ Gehalt (empfohlen)
- ⭐ Bewerbungsfrist (empfohlen)

---

## Bewerbungen verwalten

### Bewerbungsliste

**Recruiting** → **Bewerbungen** zeigt alle eingegangenen Bewerbungen.

**Filter:**
- Nach Stelle
- Nach Status
- Zeitraum
- Volltextsuche

**Spalten:**
- Bewerber-Name
- Stelle
- Status
- Eingangsdatum

### Bewerbungs-Details

Klick auf eine Bewerbung öffnet die Detailansicht:

- **Kontaktdaten**: Name, E-Mail, Telefon
- **Anschreiben**: Vom Bewerber eingegebener Text
- **Dokumente**: Lebenslauf und weitere Unterlagen (Download)
- **Status**: Aktueller Bearbeitungsstand

### Status-Workflow

```
Neu → In Prüfung → Interview → Angebot → Eingestellt
  ↓        ↓           ↓          ↓
  └────────┴───────────┴──────────┴──→ Abgelehnt
                                       Zurückgezogen
```

| Status | Beschreibung |
|--------|--------------|
| **Neu** | Bewerbung gerade eingegangen |
| **In Prüfung** | Unterlagen werden gesichtet |
| **Interview** | Vorstellungsgespräch geplant/durchgeführt |
| **Angebot** | Arbeitsangebot unterbreitet |
| **Eingestellt** | Bewerber wurde eingestellt |
| **Abgelehnt** | Bewerbung wurde abgelehnt |
| **Zurückgezogen** | Bewerber hat sich zurückgezogen |

---

## Kanban-Board (Pro)

Das Kanban-Board bietet eine visuelle Übersicht aller Bewerbungen, sortiert nach Status. Verfügbar mit der Pro-Version.

### Zugriff

**Recruiting** → **Kanban-Board**

### Funktionen

**Drag-and-Drop:**
- Bewerbungen zwischen Spalten verschieben (Status ändern)
- Bewerbungen innerhalb einer Spalte sortieren (Priorität)
- Tastaturunterstützung: Leertaste zum Greifen, Pfeiltasten zum Bewegen

**Filter:**
- Nach Stelle filtern
- Volltextsuche (Name, E-Mail)
- Aktualisieren-Button für manuelle Aktualisierung

**Spalten:**
- Jede Spalte entspricht einem Status
- Spalten können eingeklappt werden (Klick auf Header)
- Anzahl der Bewerbungen pro Spalte wird angezeigt

**Karten:**
- Zeigen Name, E-Mail, Stelle und Eingangsdatum
- Dokumenten-Anzahl wenn vorhanden
- Klick öffnet Bewerbungs-Details

### Tastatursteuerung

| Taste | Aktion |
|-------|--------|
| **Tab** | Zwischen Karten navigieren |
| **Leertaste** | Karte aufnehmen/ablegen |
| **Pfeiltasten** | Karte bewegen |
| **Escape** | Ziehen abbrechen |
| **Enter** | Bewerbungs-Details öffnen |

### Barrierefreiheit

Das Kanban-Board ist vollständig barrierefrei:
- Alle Aktionen per Tastatur ausführbar
- Screen-Reader-Ankündigungen für alle Drag-Aktionen
- Unterstützung für reduzierte Bewegung (prefers-reduced-motion)
- Hoher Kontrast-Modus wird unterstützt

---

## Frontend-Einbindung

### Karriereseite erstellen

1. Neue Seite erstellen: "Karriere" oder "Jobs"
2. Shortcode einfügen: `[rp_job_search]`
3. Seite veröffentlichen

### Shortcode-Beispiele

**Einfache Stellenliste:**
```
[rp_jobs limit="5"]
```

**Stellenliste mit Filter:**
```
[rp_jobs category="pflege" location="berlin" columns="2"]
```

**Vollständige Job-Suche:**
```
[rp_job_search limit="10" columns="2"]
```

**Bewerbungsformular auf beliebiger Seite:**
```
[rp_application_form job_id="123"]
```

---

## Einstellungen

### Recruiting → Einstellungen

**Allgemein:**
- Firmenname
- Standard-E-Mail für Benachrichtigungen
- Datenschutzerklärung-URL

**E-Mail:**
- Absendername
- Absender-E-Mail
- SMTP-Status (Hinweis)

**Schema:**
- Google for Jobs aktivieren/deaktivieren
- Schema-Validierung

---

## Werkzeuge

### Backup-Export

**Recruiting** → **Werkzeuge** → **Backup-Export**

Exportiert alle Plugin-Daten als JSON:
- Stellenanzeigen
- Bewerbungen (anonymisiert optional)
- Einstellungen

### Integritäts-Check

Prüft die Plugin-Installation:
- Datenbank-Tabellen vorhanden
- Verwaiste Daten
- Dateiberechtigungen

---

## Häufige Fragen (FAQ)

### E-Mails kommen nicht an

**Lösung:** SMTP-Plugin installieren und konfigurieren.

Das Plugin nutzt die Standard WordPress-Mail-Funktion (`wp_mail()`). Ohne SMTP-Konfiguration landen E-Mails oft im Spam oder werden gar nicht zugestellt.

Empfohlene Plugins:
- WP Mail SMTP
- Post SMTP
- FluentSMTP

### Bewerbungsformular wird nicht angezeigt

**Mögliche Ursachen:**
1. JavaScript-Fehler auf der Seite (Browser-Konsole prüfen)
2. Konflikt mit anderen Plugins
3. Theme-Inkompatibilität

**Lösung:**
- Andere Plugins temporär deaktivieren
- Standard-Theme testen

### Dateien werden nicht hochgeladen

**Prüfen:**
1. Upload-Verzeichnis beschreibbar (`wp-content/uploads/recruiting-playbook/`)
2. Maximale Upload-Größe in PHP-Konfiguration
3. Erlaubte Dateitypen: PDF, DOC, DOCX, JPG, PNG

### Google for Jobs zeigt Stelle nicht an

**Prüfen:**
1. Schema-Validierung unter Recruiting → Werkzeuge
2. Alle Pflichtfelder ausgefüllt
3. Stelle ist veröffentlicht
4. Mit Google Rich Results Test prüfen: https://search.google.com/test/rich-results

---

## Troubleshooting

### Debug-Modus aktivieren

In `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

Fehler werden in `wp-content/debug.log` protokolliert.

### Support kontaktieren

Bei Problemen:
1. Plugin-Version notieren
2. WordPress- und PHP-Version notieren
3. Fehlermeldung (falls vorhanden)
4. Schritte zur Reproduktion

GitHub Issues: https://github.com/AImitSK/recruiting-playbook/issues

---

## Changelog

### Version 1.1.0 (Pro)
- **NEU:** Kanban-Board für visuelle Bewerbungsverwaltung
  - Drag-and-Drop mit Maus und Tastatur
  - Vollständige Barrierefreiheit
  - Echtzeit-Filterung
- REST API Erweiterungen für Pro-Features
- Lizenz-Management-System

### Version 1.0.0
- Erste öffentliche Version
- Stellenverwaltung mit Custom Post Type
- Bewerbungsformular mit Datei-Upload
- E-Mail-Benachrichtigungen
- Google for Jobs Schema
- Setup-Wizard
- Backup-Export
