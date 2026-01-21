# Roadmap

## Überblick

```
2025
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Q1              │ Q2              │ Q3              │ Q4
────────────────┼─────────────────┼─────────────────┼────────────────────
PHASE 1         │ PHASE 2         │ PHASE 3         │ PHASE 4
MVP/Free        │ Pro-Version     │ AI-Addon        │ Scale & Optimize
────────────────┼─────────────────┼─────────────────┼────────────────────
                │                 │                 │
8 Wochen Dev    │ wordpress.org   │ 🔥 KI-Killer-   │ Marketing
Pilot-Kunde     │ Launch          │    Feature      │ Push
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## Getroffene Entscheidungen ✅

| Thema | Entscheidung |
|-------|--------------|
| **Pricing** | Free (0€) → Pro (149€) → AI-Addon (19€/Monat) |
| **Lifetime-Definition** | Version 1.x + 12 Monate Updates, danach 49€/Jahr Wartung |
| **Admin Framework** | React (@wordpress/scripts) |
| **Frontend Framework** | Alpine.js (~15kb) + Tailwind CSS |
| **Notifications** | react-hot-toast (Admin), Alpine Store (Frontend) |
| **Page Builder MVP** | Avada / Fusion Builder (Priorität!) |
| **Page Builder Pro** | + Gutenberg Blocks, Elementor Widgets |
| **Lizenzierung** | Eigener Server, Domain-gebunden, täglicher Remote-Check |
| **AI-Provider** | Anthropic Claude (Primary), OpenAI (Fallback) |
| **KI-Feature** | Job-Match, Job-Finder, Chancen-Check |
| **AI-Limit** | 100 Analysen/Monat + Extra-Pakete (9€/50 Stück) |
| **OCR-Limits** | Max. 10 Seiten/Dokument, 300 DPI |
| **Async-Processing** | Action Scheduler (ab Phase 2) |
| **Spam-Schutz** | Honeypot + Time-Check + Rate Limiting + Turnstile |
| **Testing** | PHPUnit + Jest, 50-60% Coverage, GitHub Actions |
| **Plugin-Name** | Recruiting Playbook |
| **Kritische Integrationen** | Zvoove + DATEV in Phase 2 (vorgezogen!) |

---

## Phase 1: MVP / Free-Version

**Zeitraum:** Q1 2025 (8 Wochen)
**Ziel:** Funktionierendes Plugin beim Pilotkunden im Einsatz

```
Phase 1 Übersicht
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WOCHE 1-2         │ WOCHE 3-4         │ WOCHE 5-6         │ WOCHE 7-8
──────────────────┼───────────────────┼───────────────────┼──────────
PHASE 1A          │ PHASE 1B          │ PHASE 1C          │ PHASE 1D
Fundament         │ Bewerbungs-Flow   │ Admin-Basics      │ Polish
──────────────────┼───────────────────┼───────────────────┼──────────
Plugin-Struktur   │ Formular          │ Bewerber-Liste    │ Setup-Wizard
Job CPT           │ Upload            │ Detailansicht     │ Shortcodes
DB-Tabellen       │ E-Mail + SMTP     │ Backup-Export     │ Pilotkunden
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

### Phase 1A: Fundament (Woche 1-2)

**Ziel:** Solide technische Basis, Job-Verwaltung funktionsfähig

#### Woche 1: Plugin-Struktur & Entwicklungsumgebung

- [ ] **Entwicklungsumgebung aufsetzen**
  - [ ] Local by Flywheel ODER Docker-Setup
  - [ ] VS Code mit PHP/WordPress Extensions
  - [ ] Xdebug für Debugging
  - [ ] Automatisches Plugin-Linking (Symlink)
- [ ] **WordPress-Plugin-Grundgerüst**
  - [ ] Hauptdatei `recruiting-playbook.php`
  - [ ] Composer Setup mit PSR-4 Autoloading
  - [ ] Namespace: `RecruitingPlaybook`
  - [ ] Ordnerstruktur: `src/`, `assets/`, `templates/`, `languages/`
- [ ] **Core-Klassen**
  - [ ] `Core/Plugin.php` (Singleton, Bootstrap)
  - [ ] `Core/Activator.php` (DB-Tabellen, Defaults)
  - [ ] `Core/Deactivator.php` (Cleanup)
  - [ ] `Core/I18n.php` (Übersetzungen)
- [ ] **Build-Prozess**
  - [ ] npm Setup für Assets
  - [ ] Tailwind CSS mit `rp-` Prefix
  - [ ] Alpine.js Integration
  - [ ] esbuild oder webpack für JS-Bundling

#### Woche 2: Job Management & Datenbank

- [ ] **Custom Post Type: `job_listing`**
  - [ ] Labels (deutsch)
  - [ ] Capabilities
  - [ ] Rewrite Rules (`/jobs/`, `/stelle/`)
  - [ ] REST API Support
- [ ] **Taxonomien**
  - [ ] `job_category` (Berufsfeld)
  - [ ] `job_location` (Standort)
  - [ ] `employment_type` (Vollzeit, Teilzeit, etc.)
- [ ] **Meta-Felder (Job)**
  - [ ] Gehalt (min/max, verstecken-Option)
  - [ ] Bewerbungsfrist
  - [ ] Ansprechpartner
  - [ ] Remote-Option
- [ ] **Custom Tables erstellen**
  - [ ] `rp_candidates`
  - [ ] `rp_applications`
  - [ ] `rp_documents`
  - [ ] `rp_activity_log`
- [ ] **Admin-Menü Grundstruktur**
  - [ ] Hauptmenü "Recruiting"
  - [ ] Untermenü: Dashboard, Bewerbungen, Einstellungen

**Deliverables Phase 1A:**
- ✅ Plugin aktivierbar ohne Fehler
- ✅ Jobs erstellen/bearbeiten möglich
- ✅ Datenbank-Tabellen angelegt
- ✅ Build-Prozess funktioniert

---

### Phase 1B: Bewerbungs-Flow (Woche 3-4)

**Ziel:** Bewerber können sich erfolgreich bewerben, HR wird benachrichtigt

#### Woche 3: Bewerbungsformular & Upload

- [ ] **Job-Templates (Frontend)**
  - [ ] `templates/archive-job.php`
  - [ ] `templates/single-job.php`
  - [ ] Theme-Override-Mechanismus
- [ ] **Bewerbungsformular (Alpine.js)**
  - [ ] Komponente: `x-data="applicationForm()"`
  - [ ] Felder: Name, E-Mail, Telefon, Nachricht
  - [ ] Datei-Upload mit Drag & Drop
  - [ ] Fortschrittsanzeige beim Upload
  - [ ] Client-Side Validierung
- [ ] **Server-Side Verarbeitung**
  - [ ] REST Endpoint: `POST /recruiting/v1/applications`
  - [ ] Datei-Validierung (Typ, Größe)
  - [ ] Sicherer Datei-Upload (UUID-Namen)
  - [ ] Kandidat erstellen/aktualisieren
  - [ ] Bewerbung in DB speichern
- [ ] **Spam-Schutz**
  - [ ] Honeypot-Feld
  - [ ] Time-Check (min. 3 Sekunden)
  - [ ] Rate Limiting (max. 5/Stunde/IP)
- [ ] **DSGVO-Compliance**
  - [ ] Consent-Checkbox
  - [ ] Consent-Version und Zeitstempel speichern

#### Woche 4: E-Mail-System & SMTP

- [ ] **E-Mail-Benachrichtigungen**
  - [ ] An HR: Neue Bewerbung eingegangen
  - [ ] An Bewerber: Eingangsbestätigung
  - [ ] HTML-Templates mit Platzhaltern
  - [ ] Plain-Text Fallback
- [ ] **SMTP-Konfigurationsprüfung**
  - [ ] Check ob SMTP-Plugin aktiv
  - [ ] Admin-Warnung wenn nicht
  - [ ] Test-E-Mail-Funktion
  - [ ] Empfohlene Plugins anzeigen
- [ ] **Einstellungen-Seite (Basis)**
  - [ ] E-Mail-Empfänger für Benachrichtigungen
  - [ ] Firmenname und Logo
  - [ ] Datenschutzerklärung-URL

**Deliverables Phase 1B:**
- ✅ Bewerbungen möglich
- ✅ E-Mails werden versendet
- ✅ SMTP-Warnung bei Bedarf
- ✅ Dateien sicher gespeichert

---

### Phase 1C: Admin-Basics (Woche 5-6)

**Ziel:** HR kann Bewerbungen verwalten, Daten sind sicher

#### Woche 5: Bewerber-Verwaltung

- [ ] **Bewerber-Listenansicht**
  - [ ] WP_List_Table basiert
  - [ ] Spalten: Name, Stelle, Status, Datum
  - [ ] Filter: Nach Stelle, Status, Zeitraum
  - [ ] Bulk-Actions: Löschen, Status ändern
  - [ ] Suche
- [ ] **Bewerber-Detailseite**
  - [ ] Kontaktdaten
  - [ ] Bewerbungstext
  - [ ] Hochgeladene Dokumente
  - [ ] Status-Dropdown
  - [ ] Erstellungsdatum, letzte Änderung
- [ ] **Dokument-Handling**
  - [ ] Sichere Download-URLs (Token-basiert)
  - [ ] Inline-Vorschau für PDFs (optional)
  - [ ] Download-Zähler
- [ ] **Status-Management (einfach)**
  - [ ] Status: Neu, In Bearbeitung, Abgelehnt, Eingestellt
  - [ ] Status-Änderung loggen
  - [ ] Farbcodierung in Liste

#### Woche 6: Backup & Integritäts-Tools

- [ ] **Backup-Export (JSON)**
  - [ ] Export aller Plugin-Daten
  - [ ] Jobs + Custom Tables + Einstellungen
  - [ ] Download als .json Datei
  - [ ] Admin-Seite unter Werkzeuge
- [ ] **Integritäts-Check (Basis)**
  - [ ] Tabellen-Existenz prüfen
  - [ ] Verwaiste Daten erkennen
  - [ ] Status-Widget im Dashboard
- [ ] **DSGVO-Funktionen**
  - [ ] Bewerber löschen (Soft-Delete)
  - [ ] Daten-Export pro Bewerber
  - [ ] Lösch-Bestätigung

**Deliverables Phase 1C:**
- ✅ Bewerbungen verwaltbar
- ✅ Backup-Export funktioniert
- ✅ Integritäts-Check vorhanden
- ✅ DSGVO-Löschung möglich

---

### Phase 1D: Polish & Pilot (Woche 7-8)

**Ziel:** Plugin produktionsreif, erste Pilotkunden aktiv

#### Woche 7: Shortcodes & Setup-Wizard

- [ ] **Shortcodes**
  - [ ] `[rp_jobs]` – Job-Liste mit Filtern
  - [ ] `[rp_job_search]` – Suchformular
  - [ ] `[rp_application_form]` – Bewerbungsformular
  - [ ] Shortcode-Parameter dokumentiert
- [ ] **Google for Jobs Schema**
  - [ ] JSON-LD automatisch generieren
  - [ ] Validierung gegen Google-Anforderungen
- [ ] **Setup-Wizard (Erstkonfiguration)**
  - [ ] Schritt 1: Willkommen
  - [ ] Schritt 2: Firmeninfo eingeben
  - [ ] Schritt 3: E-Mail-Konfiguration + SMTP-Test
  - [ ] Schritt 4: Erste Stelle erstellen
  - [ ] Schritt 5: Fertig!
- [ ] **Testing**
  - [ ] PHPUnit: ApplicationService, JobService
  - [ ] Manuelles Testing aller Flows
  - [ ] Cross-Browser (Chrome, Firefox, Safari, Edge)
  - [ ] Mobile Testing

#### Woche 8: Pilotkunden & Dokumentation

- [ ] **Pilot-Installation(en)**
  - [ ] 2-3 echte Websites
  - [ ] Feedback sammeln
  - [ ] Bugs fixen
- [ ] **Dokumentation**
  - [ ] Installation & Konfiguration
  - [ ] Shortcode-Referenz
  - [ ] FAQ
  - [ ] Troubleshooting
- [ ] **Übersetzung**
  - [ ] Alle Strings in .pot Datei
  - [ ] Deutsche Übersetzung komplett
- [ ] **Code-Review & Cleanup**
  - [ ] PHPCS (WordPress Coding Standards)
  - [ ] Keine Debug-Code übrig
  - [ ] Versionsnummer setzen (1.0.0)

**Deliverables Phase 1D:**
- ✅ Setup-Wizard funktioniert
- ✅ Shortcodes dokumentiert
- ✅ 2-3 Pilotkunden aktiv
- ✅ Feedback eingearbeitet

---

### Deliverables Phase 1 (Gesamt)

| Deliverable | Status |
|-------------|--------|
| Lauffähiges Plugin (Free-Umfang) | ⬜ |
| Jobs erstellen & anzeigen | ⬜ |
| Bewerbungen empfangen | ⬜ |
| E-Mail-Benachrichtigungen | ⬜ |
| Bewerber-Verwaltung (Basic) | ⬜ |
| Backup-Export | ⬜ |
| Setup-Wizard mit SMTP-Check | ⬜ |
| Im Einsatz bei 2-3 Pilotkunden | ⬜ |
| Deutsche Übersetzung | ⬜ |
| Dokumentation | ⬜ |

---

## Phase 2: Pro-Version

**Zeitraum:** Q2 2025 (April – Juni)
**Ziel:** Verkaufsfähige Pro-Version, Launch auf wordpress.org

```
Phase 2 Übersicht
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
APRIL             │ MAI               │ JUNI
──────────────────┼───────────────────┼────────────────────────────
Kanban-Board      │ API & Webhooks    │ Launch
E-Mail-Templates  │ Action Scheduler  │ Lizenz-System
Benutzerrollen    │ Zvoove/DATEV      │ wordpress.org
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### M2.1: Kanban-Board (React)

- [ ] React Kanban-Komponente
- [ ] Drag & Drop (Status-Änderung)
- [ ] Spalten: Neu → Screening → Interview → Angebot → Eingestellt/Abgelehnt
- [ ] Quick-Actions (E-Mail, Notiz, Ablehnen)
- [ ] Filter & Suche
- [ ] Keyboard Navigation
- [ ] Optimistic Updates

### M2.2: Erweitertes Bewerbermanagement

- [ ] Notizen-System pro Bewerber
- [ ] Bewertungen (Sterne)
- [ ] Activity Log (Statusänderungen, E-Mails)
- [ ] Bewerber-Timeline
- [ ] Talent-Pool (Interessenten für später, 24 Monate)

### M2.3: E-Mail-System (Pro)

- [ ] Template-Editor (WYSIWYG)
- [ ] Platzhalter ({vorname}, {stelle}, {firma})
- [ ] Templates: Eingangsbestätigung, Absage, Interview-Einladung
- [ ] Manueller Versand aus Backend
- [ ] E-Mail-Historie pro Bewerber
- [ ] **Queued Delivery via Action Scheduler**

### M2.4: Benutzerrollen

- [ ] Custom Capabilities
- [ ] Rolle: Administrator (alles)
- [ ] Rolle: Recruiter (Bewerbungen verwalten)
- [ ] Rolle: Hiring Manager (nur Lesen, Kommentieren)
- [ ] Stellen-Zuweisung pro User

### M2.5: Reporting & Dashboard

- [ ] Dashboard-Widget
- [ ] Bewerbungen pro Stelle
- [ ] Bewerbungen pro Zeitraum
- [ ] Time-to-Hire Berechnung
- [ ] Conversion-Rate (Besucher → Bewerbung)
- [ ] CSV-Export
- [ ] **Systemstatus-Widget (Integritäts-Check)**

### M2.6: Erweiterte Formulare

- [ ] Custom Fields Builder
- [ ] Feldtypen: Text, Textarea, Select, Checkbox, Radio, Date
- [ ] Pflichtfeld-Option
- [ ] Conditional Logic (Feld X zeigen wenn Y)
- [ ] Mehrfach-Uploads

### M2.7: Hintergrund-Infrastruktur ⚡ NEU

> Vorbereitung für KI-Features in Phase 3

- [ ] **Action Scheduler Integration**
  - [ ] Composer: woocommerce/action-scheduler
  - [ ] Queue-Manager Klasse
  - [ ] E-Mail-Versand über Queue
  - [ ] Webhook-Delivery über Queue
  - [ ] Retry-Mechanismus
- [ ] **REST API (vollständig)**
  - [ ] Endpoints: Jobs, Applications, Candidates
  - [ ] Pagination, Filter, Suche
  - [ ] API-Key Management
  - [ ] Rate Limiting
- [ ] **Webhooks**
  - [ ] Events: application.received, application.status_changed, etc.
  - [ ] Webhook-Editor im Admin
  - [ ] Delivery-Log mit Retry
  - [ ] Signatur-Validierung

### M2.8: Kritische Integrationen ⚡ VORGEZOGEN

> Kritisch für Kernzielgruppe (Pflege/Zeitarbeit)

- [ ] **Zvoove-Integration (Addon)**
  - [ ] API-Anbindung
  - [ ] Bewerber-Sync
  - [ ] Status-Sync
- [ ] **DATEV-Export (Addon)**
  - [ ] Lohnrelevante Daten
  - [ ] Export-Format
  - [ ] Dokumentation

### M2.9: Page Builder Pro

- [ ] Gutenberg Blocks (alle Elemente)
- [ ] Elementor Widgets (alle Elemente)
- [ ] Divi Modules (Basis)

### M2.10: Lizenz-System & Launch

- [ ] **Lizenz-Server**
  - [ ] API aufsetzen
  - [ ] Domain-Validierung
  - [ ] Täglicher Remote-Check
  - [ ] Integritäts-Signatur
- [ ] **Lizenz-Definition (klar kommuniziert)**
  - [ ] "Lifetime = Version 1.x + 12 Monate Updates"
  - [ ] Wartungsverlängerung 49€/Jahr
  - [ ] FAQ auf Website
- [ ] **Checkout & Payment**
  - [ ] Stripe oder Paddle Integration
  - [ ] Automatische Lizenz-Generierung
- [ ] **Launch**
  - [ ] wordpress.org Submission (Free-Version)
  - [ ] Landing Page
  - [ ] Support-Kanal einrichten

### Deliverables Phase 2

| Deliverable | Status |
|-------------|--------|
| Pro-Version verkaufsfertig | ⬜ |
| Kanban-Board funktioniert | ⬜ |
| E-Mail-Templates | ⬜ |
| Action Scheduler integriert | ⬜ |
| Zvoove/DATEV Addons (Basis) | ⬜ |
| Free-Version auf wordpress.org | ⬜ |
| Lizenz-System mit klarer Definition | ⬜ |
| Verkaufsseite live | ⬜ |
| Erste zahlende Kunden | ⬜ |
| Test-Coverage: 60%+ | ⬜ |

---

## Phase 3: AI-Addon (🔥 Killer-Feature)

**Zeitraum:** Q3 2025 (Juli – September)
**Ziel:** KI-Bewerber-Analyse live, Recurring Revenue

### M3.1: AI-Backend Infrastruktur

- [ ] Anthropic Claude API Integration
- [ ] API-Key Verwaltung (Admin)
- [ ] Proxy-Server für sichere API-Calls
- [ ] Token-Tracking pro Kunde
- [ ] Rate Limiting
- [ ] Fallback bei API-Ausfall

### M3.2: Document Parser

- [ ] PDF Text-Extraktion (pdftotext / PdfParser)
- [ ] Word Text-Extraktion (PhpWord)
- [ ] Strukturierte Daten-Extraktion
- [ ] Fehlerbehandlung bei unlesbaren Dokumenten

### M3.3: 🔥 KI-Job-Match (Modus A)

- [ ] Upload-Komponente (Alpine.js, Drag & Drop)
- [ ] Prompt Engineering für Job-Match
- [ ] Match-Score Berechnung (0-100%)
- [ ] Erfüllte/Teilweise/Fehlende Anforderungen
- [ ] Empfehlung & Tipps
- [ ] Formular-Vorausfüllung mit erkannten Daten
- [ ] Shortcode: `[rp_ai_job_match]`
- [ ] Avada Element: AI Job-Match

### M3.4: 🔥 KI-Job-Finder (Modus B)

- [ ] Multi-Job Analyse
- [ ] Profil-Erkennung aus Lebenslauf
- [ ] Matching gegen alle aktiven Stellen
- [ ] Top-X Matches mit Score
- [ ] Match-Begründung
- [ ] Ein-Klick-Bewerbung
- [ ] Shortcode: `[rp_ai_job_finder]`
- [ ] Avada Element: AI Job-Finder

### M3.5: 🔥 KI-Chancen-Check (Modus C)

- [ ] Detaillierte Chancen-Berechnung
- [ ] Punkteaufschlüsselung (Qualifikation, Erfahrung, Skills)
- [ ] Positive Faktoren (was FÜR Bewerber spricht)
- [ ] Negative Faktoren (was GEGEN spricht)
- [ ] Konkrete Verbesserungstipps
- [ ] Shortcode: `[rp_ai_chance_check]`
- [ ] Avada Element: AI Chancen-Check

### M3.6: KI-Texterstellung

- [ ] Stellentext-Generator
- [ ] Input: Jobtitel, Stichpunkte, Branche
- [ ] Output: Komplette Stellenausschreibung
- [ ] Tonalitäts-Optionen (formell, locker)
- [ ] Branchen-Prompts (Pflege, Handwerk, Büro)
- [ ] Text-Optimierung bestehender Texte
- [ ] SEO-Vorschläge

### M3.7: Usage & Billing

- [ ] Limit-Checker (100 Analysen/Monat)
- [ ] Usage-Dashboard (Admin)
- [ ] Extra-Paket Kauf (9€ / 50 Analysen)
- [ ] Monatlicher Reset
- [ ] Warnungen bei niedrigem Kontingent
- [ ] Stripe/Paddle Abo-Integration

### M3.8: DSGVO & Datenschutz

- [ ] Einwilligungs-Checkbox vor Analyse
- [ ] Keine Speicherung der Dokumente nach Analyse
- [ ] Privacy Policy Text (automatisch)
- [ ] Dokumentation für Datenschutzerklärung

### Deliverables Phase 3

- ✅ AI-Addon mit 3 KI-Modi verfügbar
- ✅ Abo-System aktiv
- ✅ Erste AI-Abonnenten
- ✅ Recurring Revenue gestartet
- ✅ USP gegenüber Wettbewerb etabliert

---

## Phase 4: Scale & Optimize

**Zeitraum:** Q4 2025 (Oktober – Dezember)
**Ziel:** Wachstum, Stabilität, weitere Features

### Mögliche Features

- [ ] **Smart Matching Pro:** Arbeitgeber-Sicht (beste Bewerber für Stelle)
- [ ] **Interview-Fragen Generator:** KI erstellt Fragen basierend auf Stelle
- [ ] **Absagetexte personalisieren:** KI-generierte individuelle Absagen
- [ ] **Multisite-Support:** Eine Installation, mehrere Firmen
- [ ] **White-Label:** Für Agenturen
- [ ] **Import/Export:** Daten aus anderen Systemen
- [ ] **Mehrsprachigkeit:** Englisch, weitere Sprachen

### Marketing & Growth

- [ ] Content Marketing (Blog, Tutorials)
- [ ] SEO für "WordPress Recruiting Plugin"
- [ ] YouTube Tutorials
- [ ] Affiliate-Programm
- [ ] Partnerschaften (WP-Agenturen, Pflegedienstleister-Verbände)
- [ ] Case Studies (Pilotkunde!)
- [ ] Webinare / Live-Demos

### Optimierung

- [ ] Performance-Audit
- [ ] Security-Review
- [ ] Accessibility (WCAG 2.1)
- [ ] Test-Coverage auf 70%+
- [ ] E2E Tests (Playwright)

---

## Technologie-Stack (Final)

| Bereich | Technologie |
|---------|-------------|
| **Backend** | PHP 8.0+, WordPress 6.x, OOP, PSR-4 |
| **Admin UI** | React, @wordpress/scripts, react-hot-toast |
| **Frontend UI** | Alpine.js, Tailwind CSS (rp- Prefix) |
| **Datenbank** | WordPress Posts + Custom Tables (Hybrid) |
| **AI** | Anthropic Claude API |
| **PDF Parsing** | pdftotext, Smalot/PdfParser |
| **Testing** | PHPUnit, Brain Monkey, Jest, Playwright |
| **CI/CD** | GitHub Actions |
| **Lizenzierung** | Eigener Server, PHP + MySQL |
| **Zahlungen** | Stripe oder Paddle |
| **Updates** | GitHub Releases + Plugin Update Checker |

---

## KPIs & Erfolgsmessung

### Phase 1 (MVP)

| KPI | Ziel |
|-----|------|
| Plugin funktionsfähig | ✅ |
| Pilotkunde nutzt aktiv | ✅ |
| Kritische Bugs | 0 |

### Phase 2 (Pro)

| KPI | Ziel |
|-----|------|
| Free-Downloads (wordpress.org) | 500+ |
| Pro-Verkäufe | 50+ |
| Support-Tickets beantwortet < 24h | 90% |

### Phase 3 (AI)

| KPI | Ziel |
|-----|------|
| AI-Addon Abonnenten | 20+ |
| MRR | 400€+ |
| AI-Analysen durchgeführt | 1.000+ |

### Phase 4 (Scale)

| KPI | Ziel |
|-----|------|
| Free-Downloads | 2.000+ |
| Pro-Verkäufe gesamt | 200+ |
| AI-Abonnenten | 75+ |
| MRR | 1.500€+ |

---

*Letzte Aktualisierung: Januar 2025*
