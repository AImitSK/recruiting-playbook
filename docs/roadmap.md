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
| **Pricing** | Free (0€) → Pro (149€ einmalig) → AI-Addon (19€/Monat) |
| **Admin Framework** | React (@wordpress/scripts) |
| **Frontend Framework** | Alpine.js (~15kb) + Tailwind CSS |
| **Notifications** | react-hot-toast (Admin), Alpine Store (Frontend) |
| **Page Builder MVP** | Avada / Fusion Builder (Priorität!) |
| **Page Builder Pro** | + Gutenberg Blocks, Elementor Widgets |
| **Lizenzierung** | Eigener Server, Domain-gebunden, Offline-Fallback |
| **AI-Provider** | Anthropic Claude (bevorzugt) |
| **KI-Feature** | Job-Match, Job-Finder, Chancen-Check |
| **AI-Limit** | 100 Analysen/Monat + Extra-Pakete (9€/50 Stück) |
| **Spam-Schutz** | Honeypot + Time-Check + Rate Limiting + Turnstile |
| **Testing** | PHPUnit + Jest, 50-60% Coverage, GitHub Actions |
| **Plugin-Name** | Recruiting Playbook |

---

## Phase 1: MVP / Free-Version

**Zeitraum:** Q1 2025 (8 Wochen)
**Ziel:** Funktionierendes Plugin beim Pilotkunden im Einsatz

### Woche 1: Setup & Scaffold

- [ ] WordPress-Plugin-Grundgerüst (OOP, PSR-4)
- [ ] Composer & npm Setup
- [ ] Tailwind CSS Konfiguration (rp- Prefix)
- [ ] Alpine.js Integration
- [ ] React Admin Setup (@wordpress/scripts)
- [ ] GitHub Actions CI/CD Pipeline
- [ ] Entwicklungsumgebung (Local/Docker)

### Woche 2: Job Management

- [ ] Custom Post Type `rp_job`
- [ ] Meta-Felder (Standort, Gehalt, Beschäftigungsart)
- [ ] Taxonomies (Kategorie, Standort, Beschäftigungsart)
- [ ] Admin-Liste mit Filtern
- [ ] Job-Editor (Gutenberg + Classic)
- [ ] Google for Jobs Schema (JSON-LD)

### Woche 3: Frontend Job-Anzeige

- [ ] Job-Archiv Template
- [ ] Job-Einzelseite Template
- [ ] Shortcodes: `[rp_jobs]`, `[rp_featured_jobs]`, `[rp_latest_jobs]`
- [ ] Avada Fusion Elements: Job Grid, Job Tabs, Featured Jobs
- [ ] Responsive Design
- [ ] CSS Custom Properties für Theme-Integration

### Woche 4: Bewerbungsformular

- [ ] Formular-Rendering (Alpine.js)
- [ ] Datei-Upload (Lebenslauf, Zeugnisse)
- [ ] Client-Side Validierung
- [ ] Server-Side Validierung
- [ ] DSGVO-Checkbox + Consent-Tracking
- [ ] Spam-Schutz (Honeypot, Time-Check, Rate Limiting)
- [ ] Shortcode: `[rp_application_form]`
- [ ] Avada Element: Application Form

### Woche 5: Bewerbermanagement (Basic)

- [ ] Custom Post Type `rp_application`
- [ ] Admin-Liste aller Bewerbungen
- [ ] Filterung nach Stelle
- [ ] Bewerber-Detailansicht
- [ ] Dokument-Download (sichere URLs)
- [ ] Löschfunktion (DSGVO)

### Woche 6: E-Mails & Einstellungen

- [ ] E-Mail-Benachrichtigung bei neuer Bewerbung
- [ ] Eingangsbestätigung an Bewerber
- [ ] Settings-Seite (React)
- [ ] Toast-Notifications (react-hot-toast)
- [ ] Frontend-Notifications (Alpine Store)

### Woche 7: Polish & Testing

- [ ] PHPUnit Tests (LicenseManager, ApplicationService)
- [ ] Jest Tests (Kanban, Hooks)
- [ ] Cross-Browser Testing
- [ ] Mobile Testing
- [ ] Performance-Optimierung
- [ ] Accessibility Check

### Woche 8: Launch-Vorbereitung

- [ ] Installation beim Pilotkunden
- [ ] Feedback-Runde
- [ ] Bugfixes
- [ ] Deutsche Übersetzung komplett
- [ ] Endnutzer-Dokumentation
- [ ] README für wordpress.org

### Deliverables Phase 1

- ✅ Lauffähiges Plugin (Free-Umfang)
- ✅ Avada Fusion Builder Integration
- ✅ Im Einsatz beim Pilotkunden
- ✅ Test-Coverage: 50%+

---

## Phase 2: Pro-Version

**Zeitraum:** Q2 2025 (April – Juni)
**Ziel:** Verkaufsfähige Pro-Version, Launch auf wordpress.org

### M2.1: Kanban-Board

- [ ] React Kanban-Komponente
- [ ] Drag & Drop (Status-Änderung)
- [ ] Spalten: Neu → Screening → Interview → Angebot → Eingestellt/Abgelehnt
- [ ] Quick-Actions (E-Mail, Notiz, Ablehnen)
- [ ] Filter & Suche
- [ ] Keyboard Navigation

### M2.2: Erweitertes Bewerbermanagement

- [ ] Notizen-System pro Bewerber
- [ ] Bewertungen (Sterne)
- [ ] Activity Log (Statusänderungen, E-Mails)
- [ ] Bewerber-Timeline
- [ ] Talent-Pool (Interessenten für später)

### M2.3: E-Mail-System

- [ ] Template-Editor (WYSIWYG)
- [ ] Platzhalter ({vorname}, {stelle}, {firma})
- [ ] Templates: Eingangsbestätigung, Absage, Interview-Einladung
- [ ] Manueller Versand aus Backend
- [ ] E-Mail-Historie pro Bewerber

### M2.4: Benutzerrollen

- [ ] Custom Capabilities
- [ ] Rolle: Administrator (alles)
- [ ] Rolle: Recruiter (Bewerbungen verwalten)
- [ ] Rolle: Hiring Manager (nur Lesen, Kommentieren)
- [ ] Stellen-Zuweisung pro User

### M2.5: Reporting

- [ ] Dashboard-Widget
- [ ] Bewerbungen pro Stelle
- [ ] Bewerbungen pro Zeitraum
- [ ] Time-to-Hire Berechnung
- [ ] Conversion-Rate (Besucher → Bewerbung)
- [ ] CSV-Export

### M2.6: Erweiterte Formulare

- [ ] Custom Fields Builder
- [ ] Feldtypen: Text, Textarea, Select, Checkbox, Radio, Date
- [ ] Pflichtfeld-Option
- [ ] Conditional Logic (Feld X zeigen wenn Y)
- [ ] Mehrfach-Uploads

### M2.7: REST API & Webhooks

- [ ] Vollständige REST API (Jobs, Applications)
- [ ] API-Key Management
- [ ] Webhooks (neue Bewerbung, Status-Änderung)
- [ ] Dokumentation (OpenAPI/Swagger)

### M2.8: Page Builder Pro

- [ ] Gutenberg Blocks (alle Elemente)
- [ ] Elementor Widgets (alle Elemente)
- [ ] Divi Modules (Basis)

### M2.9: Lizenz-System & Launch

- [ ] Lizenz-Server aufsetzen
- [ ] Lizenz-Validierung im Plugin
- [ ] Domain-Bindung
- [ ] Offline-Fallback (7 Tage Cache)
- [ ] GitHub Releases + Auto-Update
- [ ] Checkout-Integration (Stripe/Paddle)
- [ ] wordpress.org Submission (Free-Version)
- [ ] Landing Page
- [ ] Support-Kanal einrichten

### Deliverables Phase 2

- ✅ Pro-Version verkaufsfertig
- ✅ Free-Version auf wordpress.org
- ✅ Verkaufsseite live
- ✅ Erste zahlende Kunden
- ✅ Test-Coverage: 60%+

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
