# Pilot-Installation Checkliste

Diese Checkliste dient zur Qualitätssicherung bei der Installation von Recruiting Playbook bei Pilotkunden.

---

## Vor der Installation

### Systemanforderungen prüfen

- [ ] WordPress Version ≥ 6.0
- [ ] PHP Version ≥ 8.0
- [ ] MySQL Version ≥ 5.7
- [ ] Ausreichend Speicherplatz (mind. 100 MB frei)
- [ ] PHP Memory Limit ≥ 128 MB

### Backup erstellen

- [ ] Datenbank-Backup
- [ ] Dateien-Backup (wp-content)
- [ ] Backup-Wiederherstellung getestet

### Hosting-Umgebung

- [ ] SMTP-Zugang vorhanden oder Plugin installiert
- [ ] SSL-Zertifikat aktiv (HTTPS)
- [ ] Upload-Verzeichnis beschreibbar
- [ ] PHP-Upload-Limit geprüft (mind. 10 MB)

---

## Installation

### Plugin-Installation

- [ ] Plugin hochgeladen und aktiviert
- [ ] Keine PHP-Fehler beim Aktivieren
- [ ] Datenbank-Tabellen wurden erstellt:
  - [ ] `wp_rp_candidates`
  - [ ] `wp_rp_applications`
  - [ ] `wp_rp_documents`
  - [ ] `wp_rp_activity_log`

### Setup-Wizard

- [ ] Wizard startet automatisch
- [ ] Schritt 1 (Willkommen) angezeigt
- [ ] Schritt 2 (Firmendaten) speichert korrekt
- [ ] Schritt 3 (E-Mail) zeigt SMTP-Status
- [ ] Test-E-Mail funktioniert
- [ ] Schritt 4 (Erste Stelle) optional
- [ ] Schritt 5 (Fertig) zeigt Zusammenfassung
- [ ] Dashboard nach Abschluss erreichbar

---

## Funktionstest

### Stellenverwaltung

- [ ] Neue Stelle erstellen
- [ ] Titel und Beschreibung eingeben
- [ ] Kategorien zuweisen
- [ ] Meta-Felder ausfüllen:
  - [ ] Gehalt
  - [ ] Bewerbungsfrist
  - [ ] Ansprechpartner
  - [ ] Remote-Option
- [ ] Stelle veröffentlichen
- [ ] Stelle im Frontend sichtbar
- [ ] Schema-Markup im Quellcode vorhanden

### Bewerbungsformular

- [ ] Formular auf Einzelseite angezeigt
- [ ] Alle Felder funktional:
  - [ ] Anrede
  - [ ] Vorname/Nachname
  - [ ] E-Mail
  - [ ] Telefon
  - [ ] Datei-Upload (Drag & Drop)
  - [ ] Anschreiben
  - [ ] Datenschutz-Checkbox
- [ ] Validierung funktioniert
- [ ] Erfolgsmeldung nach Absenden
- [ ] Bewerbung in Admin sichtbar

### E-Mail-Versand

- [ ] HR erhält Benachrichtigung
- [ ] Bewerber erhält Bestätigung
- [ ] E-Mails formatiert (HTML)
- [ ] Links in E-Mails funktionieren

### Spam-Schutz

- [ ] Honeypot blockiert Bots (Test mit ausgefülltem Feld)
- [ ] Rate-Limiting nach 5 Versuchen aktiv
- [ ] Zeit-Check bei zu schnellem Absenden

### Bewerbungsverwaltung

- [ ] Bewerbungsliste zeigt alle Bewerbungen
- [ ] Filter funktionieren (Stelle, Status)
- [ ] Suche funktioniert
- [ ] Detailansicht öffnet sich
- [ ] Status ändern möglich
- [ ] Dokumente downloadbar

### Shortcodes

- [ ] `[rp_jobs]` zeigt Stellenliste
- [ ] `[rp_job_search]` zeigt Suche mit Filtern
- [ ] `[rp_application_form]` zeigt Formular
- [ ] Responsive Design (Mobile)

---

## Kompatibilität

### Theme-Kompatibilität

- [ ] Archiv-Seite (/jobs/) korrekt dargestellt
- [ ] Einzelseite korrekt dargestellt
- [ ] Keine CSS-Konflikte
- [ ] Navigation funktioniert

### Plugin-Kompatibilität

Getestet mit:
- [ ] Caching-Plugin (falls vorhanden)
- [ ] SEO-Plugin (Yoast/RankMath)
- [ ] Security-Plugin (falls vorhanden)
- [ ] Page Builder (falls verwendet)

### Browser-Test

- [ ] Chrome (aktuell)
- [ ] Firefox (aktuell)
- [ ] Safari (aktuell)
- [ ] Edge (aktuell)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

---

## Performance

### Ladezeiten

- [ ] Admin-Dashboard < 3 Sekunden
- [ ] Stellenliste < 2 Sekunden
- [ ] Einzelseite < 2 Sekunden
- [ ] Bewerbungsformular interaktiv < 1 Sekunde

### Ressourcen

- [ ] Keine Memory-Limit-Fehler
- [ ] Keine Timeout-Fehler beim Upload
- [ ] JavaScript keine Konsolenfehler

---

## Dokumentation & Schulung

### Übergabe an Kunden

- [ ] Zugangsdaten für Admin dokumentiert
- [ ] Kurzeinführung gegeben (15-30 Min)
- [ ] Wichtige Funktionen gezeigt:
  - [ ] Stelle erstellen
  - [ ] Bewerbung ansehen
  - [ ] Status ändern
- [ ] Support-Kontakt mitgeteilt
- [ ] Benutzerhandbuch verlinkt

### Feedback-Sammlung

- [ ] Feedback-Formular bereitgestellt
- [ ] Erster Check-in nach 1 Woche geplant
- [ ] Kontaktmöglichkeit für Fragen

---

## Nach der Installation

### Monitoring

- [ ] Error-Log beobachten (erste 24h)
- [ ] E-Mail-Zustellung verifizieren
- [ ] Performance-Monitoring einrichten (optional)

### Bekannte Einschränkungen kommuniziert

- [ ] Maximale Dateigröße
- [ ] Erlaubte Dateitypen
- [ ] Rate-Limiting für Bewerbungen

---

## Abschluss

| Datum | Kunde | Installation | Wizard | Funktionen | E-Mail | Feedback |
|-------|-------|--------------|--------|------------|--------|----------|
| ___ | ___ | ✅/❌ | ✅/❌ | ✅/❌ | ✅/❌ | ___ |

### Unterschriften

**Durchgeführt von:** _________________________ Datum: _________

**Bestätigt von (Kunde):** ____________________ Datum: _________

---

## Notizen

```
(Hier Anmerkungen, Besonderheiten oder Probleme dokumentieren)




```
