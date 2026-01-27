# User Stories & Anforderungen

## Personas

| Persona | Beschreibung | Ziel | Tier |
|---------|--------------|------|------|
| **Admin** | Plugin-Administrator, IT-Verantwortlicher | Plugin einrichten, Einstellungen verwalten | Alle |
| **Recruiter** | HR-Mitarbeiter, Disponent | Stellen veröffentlichen, Bewerbungen bearbeiten | Alle |
| **Hiring Manager** | Fachabteilungsleiter | Bewerber sichten, Feedback geben | Pro |
| **Bewerber** | Externe Person | Job finden, sich bewerben | Alle |
| **Entwickler** | Technischer Integrator | System anbinden via API | Pro |

---

## Priorisierungs-Schema

| Priorität | Label | Beschreibung |
|-----------|-------|--------------|
| **P0** | Must-Have | Ohne das funktioniert nichts |
| **P1** | Should-Have | Wichtig für Nutzbarkeit |
| **P2** | Nice-to-Have | Verbessert Erlebnis |
| **P3** | Future | Später evaluieren |

---

## Phase 1: MVP (Free Version)

**Ziel:** Funktionierendes Plugin, das echten Mehrwert bietet
**Zeitrahmen:** 8 Wochen
**Ergebnis:** Launch auf wordpress.org möglich

---

### Epic 1: Plugin-Grundgerüst

#### US-1.1: Plugin aktivieren
> **Als** Admin  
> **möchte ich** das Plugin installieren und aktivieren können  
> **damit** ich es auf meiner WordPress-Seite nutzen kann

**Akzeptanzkriterien:**
- [ ] Plugin lässt sich über WordPress-Admin installieren
- [ ] Aktivierung erstellt notwendige Datenbank-Tabellen
- [ ] Aktivierung erstellt Upload-Verzeichnis mit Schreibrechten
- [ ] Bei Fehler wird verständliche Meldung angezeigt
- [ ] PHP 8.0+ und WordPress 6.0+ werden geprüft

**Priorität:** P0  
**Story Points:** 3

---

#### US-1.2: Plugin deaktivieren
> **Als** Admin  
> **möchte ich** das Plugin deaktivieren können  
> **damit** ich es temporär ausschalten kann ohne Datenverlust

**Akzeptanzkriterien:**
- [ ] Deaktivierung behält alle Daten
- [ ] Stellen werden nicht mehr angezeigt (404)
- [ ] Reaktivierung stellt alles wieder her

**Priorität:** P1  
**Story Points:** 1

---

#### US-1.3: Plugin deinstallieren
> **Als** Admin  
> **möchte ich** das Plugin vollständig entfernen können  
> **damit** keine Datenreste zurückbleiben

**Akzeptanzkriterien:**
- [ ] Option "Daten bei Deinstallation löschen" in Einstellungen
- [ ] Bei aktivierter Option: Tabellen, Uploads, Options werden gelöscht
- [ ] Bei deaktivierter Option: Daten bleiben erhalten
- [ ] Warnung vor Datenlöschung

**Priorität:** P2  
**Story Points:** 2

---

### Epic 2: Stellenverwaltung (Admin)

#### US-2.1: Stelle erstellen
> **Als** Recruiter  
> **möchte ich** eine neue Stellenanzeige erstellen  
> **damit** ich offene Positionen veröffentlichen kann

**Akzeptanzkriterien:**
- [ ] Menüpunkt "Stellen" → "Neue Stelle" im Admin
- [ ] Felder: Titel, Beschreibung (Editor), Standort, Beschäftigungsart
- [ ] Optionale Felder: Gehalt (von/bis), Bewerbungsfrist, Startdatum
- [ ] Ansprechpartner auswählbar (WordPress-User oder manuell)
- [ ] Stelle als Entwurf speicherbar
- [ ] Validierung der Pflichtfelder

**Priorität:** P0  
**Story Points:** 5

---

#### US-2.2: Stelle bearbeiten
> **Als** Recruiter  
> **möchte ich** eine bestehende Stelle bearbeiten  
> **damit** ich Fehler korrigieren oder Inhalte aktualisieren kann

**Akzeptanzkriterien:**
- [ ] Alle Felder sind editierbar
- [ ] Änderungen werden gespeichert
- [ ] "Zuletzt bearbeitet" wird aktualisiert
- [ ] Toast-Benachrichtigung bei Erfolg

**Priorität:** P0  
**Story Points:** 2

---

#### US-2.3: Stelle veröffentlichen
> **Als** Recruiter  
> **möchte ich** eine Stelle veröffentlichen  
> **damit** Bewerber sie sehen und sich bewerben können

**Akzeptanzkriterien:**
- [ ] Button "Veröffentlichen" im Editor
- [ ] Stelle erscheint auf der Website
- [ ] URL ist öffentlich zugänglich
- [ ] Google for Jobs Schema wird generiert
- [ ] Toast-Benachrichtigung "Stelle veröffentlicht"

**Priorität:** P0  
**Story Points:** 2

---

#### US-2.4: Stelle pausieren
> **Als** Recruiter  
> **möchte ich** eine Stelle temporär offline nehmen  
> **damit** keine neuen Bewerbungen eingehen

**Akzeptanzkriterien:**
- [ ] Button "Pausieren" / Status auf "Privat"
- [ ] Stelle nicht mehr öffentlich sichtbar
- [ ] Bewerbungsformular zeigt Hinweis "Stelle nicht mehr verfügbar"
- [ ] Bestehende Bewerbungen bleiben erhalten
- [ ] Stelle kann reaktiviert werden

**Priorität:** P1  
**Story Points:** 2

---

#### US-2.5: Stelle archivieren
> **Als** Recruiter  
> **möchte ich** eine besetzte Stelle archivieren  
> **damit** sie nicht mehr aktiv ist aber nachvollziehbar bleibt

**Akzeptanzkriterien:**
- [ ] Button "Archivieren" 
- [ ] Status "Archiviert" in der Übersicht sichtbar
- [ ] Stelle nicht mehr öffentlich
- [ ] Bewerbungen bleiben verknüpft
- [ ] Filter "Archiviert" in Stellen-Liste

**Priorität:** P1  
**Story Points:** 2

---

#### US-2.6: Stelle löschen
> **Als** Recruiter  
> **möchte ich** eine Stelle löschen können  
> **damit** ich fehlerhafte Einträge entfernen kann

**Akzeptanzkriterien:**
- [ ] Button "Löschen" mit Bestätigungsdialog
- [ ] Warnung wenn Bewerbungen vorhanden
- [ ] Verknüpfte Bewerbungen werden NICHT gelöscht (nur Verknüpfung)
- [ ] Papierkorb-Funktion (Wiederherstellen möglich)

**Priorität:** P1  
**Story Points:** 2

---

#### US-2.7: Stellen-Übersicht
> **Als** Recruiter  
> **möchte ich** alle Stellen in einer Übersicht sehen  
> **damit** ich den Überblick behalte

**Akzeptanzkriterien:**
- [ ] Tabellarische Liste aller Stellen
- [ ] Spalten: Titel, Status, Standort, Bewerbungen (Anzahl), Erstellt am
- [ ] Sortierbar nach jeder Spalte
- [ ] Filterbar nach Status
- [ ] Quick-Actions: Bearbeiten, Ansehen, Archivieren
- [ ] Bulk-Actions: Löschen, Archivieren

**Priorität:** P0  
**Story Points:** 3

---

---

### Epic 3: Stellenanzeigen (Frontend)

#### US-3.1: Stellen-Archivseite
> **Als** Bewerber  
> **möchte ich** alle offenen Stellen auf einer Seite sehen  
> **damit** ich passende Jobs finden kann

**Akzeptanzkriterien:**
- [ ] Automatische Archivseite unter /jobs/ (konfigurierbar)
- [ ] Nur veröffentlichte Stellen werden angezeigt
- [ ] Pagination bei vielen Stellen
- [ ] Responsive Design
- [ ] SEO-optimiert (Title, Meta-Description)

**Priorität:** P0  
**Story Points:** 3

---

#### US-3.2: Stellen filtern
> **Als** Bewerber  
> **möchte ich** Stellen nach Kriterien filtern  
> **damit** ich schneller passende Jobs finde

**Akzeptanzkriterien:**
- [ ] Filter: Standort, Beschäftigungsart
- [ ] Suchfeld für Freitext
- [ ] Filter funktioniert ohne Seiten-Reload (Alpine.js)
- [ ] URL wird aktualisiert (teilbar, Browser-Back funktioniert)
- [ ] "Keine Ergebnisse" Meldung wenn leer

**Priorität:** P1  
**Story Points:** 3

---

#### US-3.3: Stelle ansehen
> **Als** Bewerber  
> **möchte ich** Details einer Stelle sehen  
> **damit** ich entscheiden kann, ob ich mich bewerben möchte

**Akzeptanzkriterien:**
- [ ] Einzelseite mit allen Stellen-Details
- [ ] Beschreibung, Standort, Beschäftigungsart, Gehalt (wenn öffentlich)
- [ ] Ansprechpartner mit Kontaktdaten
- [ ] Prominent: "Jetzt bewerben" Button
- [ ] Google for Jobs Schema im Quellcode
- [ ] Social-Share-Möglichkeit (optional)

**Priorität:** P0  
**Story Points:** 3

---

#### US-3.4: Shortcode für Stellen
> **Als** Admin  
> **möchte ich** Stellen per Shortcode einbinden  
> **damit** ich sie auf beliebigen Seiten anzeigen kann

**Akzeptanzkriterien:**
- [ ] `[recruiting_jobs]` zeigt alle Stellen
- [ ] `[recruiting_jobs limit="5"]` begrenzt Anzahl
- [ ] `[recruiting_jobs category="pflege"]` filtert nach Kategorie
- [ ] `[recruiting_jobs location="berlin"]` filtert nach Standort
- [ ] Dokumentation der Shortcode-Parameter

**Priorität:** P1  
**Story Points:** 2

---

#### US-3.5: Gutenberg Block für Stellen
> **Als** Admin  
> **möchte ich** Stellen per Gutenberg-Block einbinden  
> **damit** ich den modernen Editor nutzen kann

**Akzeptanzkriterien:**
- [ ] Block "Stellenanzeigen" im Block-Inserter
- [ ] Visuelle Vorschau im Editor
- [ ] Einstellungen: Anzahl, Filter, Layout
- [ ] Responsive im Frontend

**Priorität:** P2  
**Story Points:** 3

---

### Epic 4: Bewerbungsformular

#### US-4.1: Bewerbungsformular anzeigen
> **Als** Bewerber  
> **möchte ich** ein Bewerbungsformular sehen  
> **damit** ich mich auf eine Stelle bewerben kann

**Akzeptanzkriterien:**
- [ ] Formular auf der Stellen-Einzelseite
- [ ] Felder: Vorname, Nachname, E-Mail, Telefon (optional)
- [ ] Datei-Upload: Lebenslauf (Pflicht)
- [ ] Anschreiben-Textfeld (optional)
- [ ] DSGVO-Checkbox (Pflicht)
- [ ] "Absenden" Button

**Priorität:** P0  
**Story Points:** 5

---

#### US-4.2: Formular validieren
> **Als** Bewerber  
> **möchte ich** Feedback zu Eingabefehlern bekommen  
> **damit** ich meine Bewerbung korrekt absenden kann

**Akzeptanzkriterien:**
- [ ] Client-seitige Validierung (Alpine.js)
- [ ] Server-seitige Validierung (PHP)
- [ ] Fehler werden am jeweiligen Feld angezeigt
- [ ] E-Mail-Format wird geprüft
- [ ] Dateigröße wird geprüft (max. 10 MB)
- [ ] Dateityp wird geprüft (PDF, DOC, DOCX)
- [ ] Pflichtfelder werden geprüft

**Priorität:** P0  
**Story Points:** 3

---

#### US-4.3: Datei hochladen
> **Als** Bewerber  
> **möchte ich** meinen Lebenslauf hochladen  
> **damit** der Arbeitgeber meine Qualifikationen sieht

**Akzeptanzkriterien:**
- [ ] Drag & Drop Upload
- [ ] Alternativ: Datei-Auswahl-Dialog
- [ ] Fortschrittsanzeige bei Upload
- [ ] Vorschau des Dateinamens nach Upload
- [ ] Möglichkeit, Datei zu entfernen und neu hochzuladen
- [ ] Erlaubte Typen: PDF, DOC, DOCX
- [ ] Max. Größe: 10 MB (konfigurierbar)

**Priorität:** P0  
**Story Points:** 3

---

#### US-4.4: Bewerbung absenden
> **Als** Bewerber  
> **möchte ich** meine Bewerbung absenden  
> **damit** sie beim Unternehmen ankommt

**Akzeptanzkriterien:**
- [ ] AJAX-Submit (kein Seiten-Reload)
- [ ] Loading-State während Übermittlung
- [ ] Erfolgs-Meldung nach Absenden
- [ ] Formular wird zurückgesetzt / ausgeblendet
- [ ] Daten werden in Datenbank gespeichert
- [ ] Dokumente werden sicher gespeichert

**Priorität:** P0  
**Story Points:** 3

---

#### US-4.5: DSGVO-Einwilligung
> **Als** Bewerber  
> **möchte ich** der Datenverarbeitung zustimmen  
> **damit** meine Bewerbung rechtskonform verarbeitet wird

**Akzeptanzkriterien:**
- [ ] Pflicht-Checkbox mit Link zur Datenschutzerklärung
- [ ] Einwilligung wird mit Timestamp gespeichert
- [ ] Version der Datenschutzerklärung wird gespeichert
- [ ] Optional: Checkbox für Talent-Pool

**Priorität:** P0  
**Story Points:** 2

---

### Epic 5: Bewerbungsverwaltung (Admin)

#### US-5.1: Bewerbungen auflisten
> **Als** Recruiter  
> **möchte ich** alle eingegangenen Bewerbungen sehen  
> **damit** ich sie bearbeiten kann

**Akzeptanzkriterien:**
- [ ] Menüpunkt "Bewerbungen" im Admin
- [ ] Tabellarische Liste mit: Name, Stelle, Datum, Status
- [ ] Sortierbar nach Datum, Name, Stelle
- [ ] Filterbar nach Stelle, Status
- [ ] Anzahl neue Bewerbungen als Badge im Menü

**Priorität:** P0  
**Story Points:** 3

---

#### US-5.2: Bewerbung ansehen
> **Als** Recruiter  
> **möchte ich** Details einer Bewerbung sehen  
> **damit** ich den Bewerber beurteilen kann

**Akzeptanzkriterien:**
- [ ] Detailansicht mit allen Bewerber-Daten
- [ ] Verknüpfte Stelle anzeigen
- [ ] Hochgeladene Dokumente mit Download-Link
- [ ] Anschreiben (wenn vorhanden)
- [ ] DSGVO-Einwilligung mit Timestamp

**Priorität:** P0  
**Story Points:** 3

---

#### US-5.3: Dokumente herunterladen
> **Als** Recruiter  
> **möchte ich** Bewerber-Dokumente herunterladen  
> **damit** ich sie offline prüfen kann

**Akzeptanzkriterien:**
- [ ] Download-Button pro Dokument
- [ ] Berechtigungsprüfung (nur eingeloggte Recruiter)
- [ ] Dokumente werden nicht direkt verlinkt (Sicherheit)
- [ ] Original-Dateiname bleibt erhalten

**Priorität:** P0  
**Story Points:** 2

---

#### US-5.4: Bewerbung löschen
> **Als** Recruiter  
> **möchte ich** eine Bewerbung löschen können  
> **damit** ich DSGVO-Anfragen erfüllen kann

**Akzeptanzkriterien:**
- [ ] "Löschen" Button mit Bestätigungsdialog
- [ ] Bewerbung wird soft-deleted (anonymisiert)
- [ ] Dokumente werden physisch gelöscht
- [ ] Activity-Log wird erstellt (ohne personenbezogene Daten)
- [ ] Toast-Benachrichtigung

**Priorität:** P0  
**Story Points:** 2

---

### Epic 6: E-Mail-Benachrichtigungen

#### US-6.1: Admin bei neuer Bewerbung benachrichtigen
> **Als** Recruiter  
> **möchte ich** per E-Mail über neue Bewerbungen informiert werden  
> **damit** ich zeitnah reagieren kann

**Akzeptanzkriterien:**
- [ ] E-Mail wird automatisch gesendet bei neuer Bewerbung
- [ ] Empfänger in Einstellungen konfigurierbar
- [ ] E-Mail enthält: Stellentitel, Bewerber-Name, Link zur Bewerbung
- [ ] E-Mail kann in Einstellungen deaktiviert werden

**Priorität:** P0  
**Story Points:** 2

---

#### US-6.2: Bewerber Eingangsbestätigung senden
> **Als** Bewerber  
> **möchte ich** eine Bestätigung meiner Bewerbung erhalten  
> **damit** ich weiß, dass sie angekommen ist

**Akzeptanzkriterien:**
- [ ] E-Mail wird automatisch nach Absenden gesendet
- [ ] E-Mail enthält: Stellentitel, Bestätigung, Kontaktdaten
- [ ] E-Mail kann in Einstellungen deaktiviert werden
- [ ] E-Mail-Template ist anpassbar (Pro)

**Priorität:** P1  
**Story Points:** 2

---

### Epic 7: Einstellungen

#### US-7.1: Allgemeine Einstellungen
> **Als** Admin  
> **möchte ich** das Plugin konfigurieren  
> **damit** es zu meinem Unternehmen passt

**Akzeptanzkriterien:**
- [ ] Einstellungsseite im Admin
- [ ] Firmenname
- [ ] E-Mail für Benachrichtigungen
- [ ] URL-Slug für Stellen (Standard: /jobs/)
- [ ] Speichern mit Toast-Feedback

**Priorität:** P0  
**Story Points:** 3

---

#### US-7.2: Formular-Einstellungen
> **Als** Admin  
> **möchte ich** das Bewerbungsformular konfigurieren  
> **damit** ich die richtigen Daten abfrage

**Akzeptanzkriterien:**
- [ ] Pflichtfelder definieren (Checkboxen)
- [ ] Max. Dateigröße einstellen
- [ ] Erlaubte Dateitypen einstellen
- [ ] DSGVO-Text anpassen (Link zur Datenschutzerklärung)

**Priorität:** P1  
**Story Points:** 2

---

#### US-7.3: Design-Einstellungen
> **Als** Admin  
> **möchte ich** das Aussehen anpassen  
> **damit** das Plugin zu meinem Theme passt

**Akzeptanzkriterien:**
- [ ] Primärfarbe wählbar
- [ ] Option "Vom Theme übernehmen"
- [ ] Border-Radius wählbar
- [ ] Live-Vorschau (nice-to-have)

**Priorität:** P2  
**Story Points:** 2

---

### Epic 8: SEO & Schema

#### US-8.1: Google for Jobs Schema
> **Als** Admin  
> **möchte ich** dass Stellen in Google for Jobs erscheinen  
> **damit** mehr Bewerber sie finden

**Akzeptanzkriterien:**
- [ ] JSON-LD Schema wird automatisch generiert
- [ ] Alle erforderlichen Felder: title, description, datePosted, validThrough
- [ ] Optionale Felder: salary, employmentType, jobLocation
- [ ] Schema ist valide (Google Testing Tool)

**Priorität:** P0  
**Story Points:** 2

---

## Phase 2: Pro Version

**Ziel:** Professionelle Features für zahlende Kunden
**Zeitrahmen:** +4 Wochen nach MVP
**Voraussetzung:** MVP abgeschlossen, Pilotkunde testet

---

### Epic 9: Erweitertes Bewerbermanagement

#### US-9.1: Kanban-Board
> **Als** Recruiter  
> **möchte ich** Bewerbungen in einem Kanban-Board verwalten  
> **damit** ich den Überblick über alle Status behalte

**Akzeptanzkriterien:**
- [ ] Spalten: Neu, Screening, Interview, Angebot, Eingestellt/Abgelehnt
- [ ] Drag & Drop zwischen Spalten
- [ ] Status wird automatisch aktualisiert
- [ ] Filterbar nach Stelle
- [ ] Anzahl pro Spalte sichtbar

**Priorität:** P0 (Pro)  
**Story Points:** 8

---

#### US-9.2: Bewerbungsstatus ändern
> **Als** Recruiter  
> **möchte ich** den Status einer Bewerbung ändern  
> **damit** ich den Fortschritt dokumentiere

**Akzeptanzkriterien:**
- [ ] Status-Dropdown in Detailansicht
- [ ] Nur erlaubte Übergänge möglich
- [ ] Activity-Log wird geschrieben
- [ ] Optional: Notiz bei Status-Änderung
- [ ] Webhook wird ausgelöst

**Priorität:** P0 (Pro)  
**Story Points:** 3

---

#### US-9.3: Notizen zu Bewerbung
> **Als** Recruiter  
> **möchte ich** Notizen zu einer Bewerbung hinzufügen  
> **damit** ich Informationen für Kollegen festhalte

**Akzeptanzkriterien:**
- [ ] Notiz-Textfeld in Detailansicht
- [ ] Mehrere Notizen möglich (Timeline)
- [ ] Autor und Zeitstempel werden gespeichert
- [ ] Nur für eingeloggte User sichtbar

**Priorität:** P1 (Pro)  
**Story Points:** 3

---

#### US-9.4: Bewerbung bewerten
> **Als** Recruiter  
> **möchte ich** Bewerber bewerten  
> **damit** ich sie vergleichen kann

**Akzeptanzkriterien:**
- [ ] Sterne-Bewertung (1-5)
- [ ] Optional: Kriterien-basierte Bewertung
- [ ] Bewertung in Übersicht sichtbar
- [ ] Sortierbar nach Bewertung

**Priorität:** P2 (Pro)  
**Story Points:** 2

---

---

### Epic 11: Benutzerrollen

#### US-11.1: Recruiter-Rolle
> **Als** Admin  
> **möchte ich** Mitarbeitern Recruiter-Rechte geben  
> **damit** sie Stellen und Bewerbungen verwalten können

**Akzeptanzkriterien:**
- [ ] Custom Role "RP Recruiter"
- [ ] Kann: Stellen erstellen/bearbeiten, Bewerbungen sehen/bearbeiten
- [ ] Kann nicht: Plugin-Einstellungen ändern

**Priorität:** P1 (Pro)  
**Story Points:** 3

---

#### US-11.2: Hiring Manager Rolle
> **Als** Admin  
> **möchte ich** Fachabteilungen Leserechte geben  
> **damit** sie Bewerber sichten können

**Akzeptanzkriterien:**
- [ ] Custom Role "RP Hiring Manager"
- [ ] Kann: Bewerbungen sehen, Notizen schreiben, Bewerten
- [ ] Kann nicht: Status ändern, Löschen, Stellen bearbeiten

**Priorität:** P2 (Pro)  
**Story Points:** 2

---

### Epic 12: E-Mail-Templates

> Siehe [email-signature-specification.md](../technical/email-signature-specification.md) für aktualisiertes Konzept

#### US-12.1: E-Mail-Templates verwalten
> **Als** Recruiter
> **möchte ich** E-Mail-Vorlagen anpassen
> **damit** unsere Kommunikation professionell ist

**Akzeptanzkriterien:**
- [ ] Automatische Templates: Eingangsbestätigung, Absage, Zurückgezogen, Talent-Pool-Aufnahme
- [ ] Manuelle Templates: Interview-Einladung, Angebot, etc. (mit Lücken `___` statt Pseudo-Variablen)
- [ ] WYSIWYG-Editor
- [ ] Nur echte Platzhalter: {vorname}, {stelle}, {firma}, etc. (16 Stück)
- [ ] Templates enthalten KEINE Signatur (wird separat angehängt)
- [ ] Vorschau-Funktion
- [ ] Pro Sprache ein Template

**Priorität:** P1 (Pro)
**Story Points:** 5

---

#### US-12.1b: Signaturen verwalten
> **Als** Recruiter
> **möchte ich** meine E-Mail-Signatur verwalten
> **damit** meine E-Mails professionell aussehen

**Akzeptanzkriterien:**
- [ ] Persönliche Signaturen erstellen/bearbeiten
- [ ] Eine Signatur als Standard markieren
- [ ] Firmen-Signatur als Fallback (Admin)
- [ ] Firmendaten automatisch anhängbar
- [ ] Vorschau der Signatur

**Priorität:** P1 (Pro)
**Story Points:** 3

---

#### US-12.2: E-Mail an Bewerber senden
> **Als** Recruiter
> **möchte ich** E-Mails direkt aus dem Plugin senden
> **damit** ich nicht zwischen Tools wechseln muss

**Akzeptanzkriterien:**
- [ ] "E-Mail senden" Button in Bewerber-Detailansicht
- [ ] Template auswählen oder Freitext
- [ ] Signatur-Auswahl vor dem Versand
- [ ] Bei manuellen Templates: Lücken ausfüllen
- [ ] E-Mail wird geloggt
- [ ] Bewerber-E-Mail wird vorausgefüllt

**Priorität:** P1 (Pro)
**Story Points:** 3

---

### Epic 13: REST API

#### US-13.1: API-Keys verwalten
> **Als** Entwickler  
> **möchte ich** API-Schlüssel erstellen  
> **damit** ich das Plugin an unser System anbinden kann

**Akzeptanzkriterien:**
- [ ] API-Keys in Einstellungen erstellen
- [ ] Berechtigungen pro Key konfigurierbar
- [ ] Key nur einmal sichtbar (Sicherheit)
- [ ] Keys können widerrufen werden

**Priorität:** P0 (Pro)  
**Story Points:** 3

---

#### US-13.2: Jobs via API abrufen
> **Als** Entwickler  
> **möchte ich** Stellen per API abrufen  
> **damit** ich sie in unserem System anzeigen kann

**Akzeptanzkriterien:**
- [ ] GET /wp-json/recruiting/v1/jobs
- [ ] Filterbar nach Status, Standort, Typ
- [ ] Pagination
- [ ] Authentifizierung via API-Key

**Priorität:** P0 (Pro)  
**Story Points:** 3

---

#### US-13.3: Bewerbungen via API abrufen
> **Als** Entwickler  
> **möchte ich** Bewerbungen per API abrufen  
> **damit** ich sie in unser HR-System übernehmen kann

**Akzeptanzkriterien:**
- [ ] GET /wp-json/recruiting/v1/applications
- [ ] GET /wp-json/recruiting/v1/applications/{id}
- [ ] Filterbar nach Stelle, Status, Datum
- [ ] Dokumente als Download-URL

**Priorität:** P0 (Pro)  
**Story Points:** 3

---

#### US-13.4: Status via API ändern
> **Als** Entwickler  
> **möchte ich** den Bewerbungsstatus per API ändern  
> **damit** beide Systeme synchron bleiben

**Akzeptanzkriterien:**
- [ ] PUT /wp-json/recruiting/v1/applications/{id}/status
- [ ] Validierung der Status-Übergänge
- [ ] Activity-Log wird geschrieben

**Priorität:** P1 (Pro)  
**Story Points:** 2

---

### Epic 14: Webhooks

#### US-14.1: Webhooks konfigurieren
> **Als** Entwickler  
> **möchte ich** Webhooks einrichten  
> **damit** unser System über Ereignisse informiert wird

**Akzeptanzkriterien:**
- [ ] Webhooks in Einstellungen anlegen
- [ ] URL, Events, Secret konfigurierbar
- [ ] Test-Webhook senden
- [ ] Fehler-Log einsehbar

**Priorität:** P1 (Pro)  
**Story Points:** 3

---

#### US-14.2: Webhook bei neuer Bewerbung
> **Als** Entwickler  
> **möchte ich** bei neuen Bewerbungen benachrichtigt werden  
> **damit** ich sie automatisch verarbeiten kann

**Akzeptanzkriterien:**
- [ ] Event: application.received
- [ ] Payload enthält Bewerbungsdaten
- [ ] Signatur zur Validierung
- [ ] Retry bei Fehlern

**Priorität:** P1 (Pro)  
**Story Points:** 2

---

### Epic 15: Reporting

#### US-15.1: Bewerbungsstatistik
> **Als** Recruiter  
> **möchte ich** Statistiken sehen  
> **damit** ich unseren Recruiting-Erfolg messen kann

**Akzeptanzkriterien:**
- [ ] Dashboard-Widget mit Kennzahlen
- [ ] Bewerbungen pro Stelle
- [ ] Bewerbungen nach Status
- [ ] Time-to-Hire (Durchschnitt)
- [ ] Zeitraum wählbar

**Priorität:** P2 (Pro)  
**Story Points:** 5

---

## Phase 3: AI Addon

**Ziel:** KI-gestützte Texterstellung
**Zeitrahmen:** +4 Wochen nach Pro
**Voraussetzung:** Pro abgeschlossen

---

### Epic 16: KI-Stellentexte

#### US-16.1: Stellentext generieren
> **Als** Recruiter  
> **möchte ich** Stellentexte per KI generieren lassen  
> **damit** ich Zeit spare und professionelle Texte bekomme

**Akzeptanzkriterien:**
- [ ] Button "Mit KI erstellen" im Stellen-Editor
- [ ] Eingabe: Jobtitel, Stichpunkte, Branche, Tonalität
- [ ] Ausgabe: Kompletter Stellentext
- [ ] Text kann bearbeitet werden
- [ ] Text kann in Editor übernommen werden

**Priorität:** P0 (AI)  
**Story Points:** 8

---

#### US-16.2: Stellentext verbessern
> **Als** Recruiter  
> **möchte ich** bestehende Texte optimieren lassen  
> **damit** sie ansprechender werden

**Akzeptanzkriterien:**
- [ ] Button "Mit KI verbessern" bei bestehendem Text
- [ ] Optionen: Kürzer, Länger, Formeller, Lockerer
- [ ] Vorschau der Änderungen
- [ ] Vergleich Original vs. Verbessert

**Priorität:** P1 (AI)  
**Story Points:** 5

---

#### US-16.3: Branchenspezifische Vorlagen
> **Als** Recruiter  
> **möchte ich** branchenspezifische KI-Vorlagen nutzen  
> **damit** die Texte fachlich passend sind

**Akzeptanzkriterien:**
- [ ] Vorlagen für: Pflege, Handwerk, Büro, IT, Logistik
- [ ] Vorlagen enthalten typische Aufgaben, Anforderungen, Benefits
- [ ] KI nutzt Vorlage als Basis
- [ ] Eigene Vorlagen speicherbar

**Priorität:** P2 (AI)  
**Story Points:** 3

---

## Story Map

```
                    Bewerber-Journey
    ┌─────────────────────────────────────────────────────────┐
    │  Suchen  →  Finden  →  Lesen  →  Bewerben  →  Warten   │
    └─────────────────────────────────────────────────────────┘
         │          │         │          │           │
         ▼          ▼         ▼          ▼           ▼
MVP   [Filter]  [Liste]  [Detail]  [Formular]  [Bestätigung]
      US-3.2    US-3.1   US-3.3    US-4.1-4.5   US-6.2


                    Recruiter-Journey
    ┌─────────────────────────────────────────────────────────┐
    │ Erstellen → Veröffentl. → Bewerbung → Bewerten → Einstellen│
    └─────────────────────────────────────────────────────────┘
         │           │            │           │          │
         ▼           ▼            ▼           ▼          ▼
MVP   [Editor]  [Publish]    [Liste]     [Detail]      -
      US-2.1    US-2.3       US-5.1      US-5.2

Pro      -         -        [Kanban]   [Status]   [E-Mail]
                            US-9.1     US-9.2     US-12.2

AI    [KI-Text]     -           -          -          -
      US-16.1
```

---

## MVP Checkliste

### Must-Have für Launch ✅

- [ ] Plugin aktivieren/deaktivieren
- [ ] Stellen erstellen/bearbeiten/veröffentlichen
- [ ] Unbegrenzte Stellen
- [ ] Stellen-Archivseite
- [ ] Stellen-Einzelansicht
- [ ] Bewerbungsformular mit Validierung
- [ ] Datei-Upload (CV)
- [ ] DSGVO-Checkbox
- [ ] Bewerbungen auflisten
- [ ] Bewerbungen ansehen
- [ ] Dokumente herunterladen
- [ ] E-Mail bei neuer Bewerbung
- [ ] Grundeinstellungen
- [ ] Google for Jobs Schema
- [ ] Toast-Benachrichtigungen
- [ ] Responsive Design
- [ ] Deutsche Übersetzung

### Nice-to-Have für Launch

- [ ] Englische Übersetzung
- [ ] Bewerber-Eingangsbestätigung
- [ ] Stellen-Filter (Frontend)
- [ ] Shortcode
- [ ] Gutenberg Block
- [ ] Design-Einstellungen

---

## Zeitplan MVP (8 Wochen)

| Woche | Focus | User Stories |
|-------|-------|--------------|
| 1 | Setup & Grundgerüst | US-1.1, Plugin-Struktur |
| 2 | Stellenverwaltung | US-2.1, US-2.2, US-2.3, US-2.7 |
| 3 | Frontend Stellen | US-3.1, US-3.3, US-8.1 |
| 4 | Bewerbungsformular | US-4.1, US-4.2, US-4.3, US-4.4, US-4.5 |
| 5 | Bewerbungsverwaltung | US-5.1, US-5.2, US-5.3, US-5.4 |
| 6 | E-Mail & Einstellungen | US-6.1, US-7.1, US-7.2 |
| 7 | Polish & Testing | Bug-Fixes, US-2.8, Responsive |
| 8 | Launch-Vorbereitung | Dokumentation, wordpress.org Submission |

---

*Letzte Aktualisierung: Januar 2025*
