# Website-Spezifikation

Produktwebseite für das WordPress-Plugin "Recruiting Playbook".

**URL:** recruiting-playbook.com (geplant)
**Zweck:** Marketing, Dokumentation, Verkauf (Pro + AI-Addon), Support

---

## 1. Tech Stack

| Bereich | Technologie | Begründung |
|---------|-------------|------------|
| **Framework** | Next.js 14 (App Router) | Bereits vorhanden, SSR/SSG für SEO |
| **Template** | Salient (Tailwind Labs) | Professionelle SaaS-Marketing-Basis |
| **Styling** | Tailwind CSS | Konsistenz mit Plugin, schnelle Entwicklung |
| **Content** | MDX (im Repo) | Docs als Markdown, React-Komponenten einbettbar, kein externer Service |
| **Payment** | LemonSqueezy | Merchant of Record, EU-VAT, Lizenzen, Abo-Verwaltung |
| **Hosting** | Vercel | Bereits konfiguriert, optimal für Next.js |
| **Auth** | Nicht nötig | LemonSqueezy Customer Portal deckt Lizenzverwaltung ab |
| **Analytics** | Plausible oder Vercel Analytics | DSGVO-konform, kein Cookie-Banner nötig |

### Warum diese Entscheidungen?

**LemonSqueezy statt Stripe:**
- Merchant of Record = kein eigenes Handling von EU-Umsatzsteuer, Rechnungen, Rückerstattungen
- Automatische Lizenzschlüssel-Generierung
- Kunden-Portal für Lizenzverwaltung, Abo-Management, Downloads
- WordPress-Plugin-Verkäufer nutzen LemonSqueezy häufig (Etablierter Workflow)
- Höhere Gebühren (~5-10%) sind bei projiziertem Umsatz Jahr 1 (~13.600 €) irrelevant

**MDX statt Sanity/CMS:**
- Docs existieren bereits als Markdown im Repo
- Nur ein Entwickler pflegt Inhalte
- Versioniert in Git, keine externe Abhängigkeit
- React-Komponenten in Markdown einbettbar (z.B. interaktive API-Beispiele)
- Bei Bedarf kann Sanity später ergänzt werden

**Kein eigenes Auth:**
- LemonSqueezy Customer Portal deckt Login/Lizenzverwaltung ab
- Docs und API-Referenz sind öffentlich (SEO, Vertrauen)
- Kein Firebase/Supabase = weniger Infrastruktur

---

## 2. Branding & Design System

### Logo

| Asset | Datei | Verwendung |
|-------|-------|------------|
| Logo (voll) | `docs/website/rp-logo.png` | Header, Footer, OG-Image |
| Icon | `docs/website/rp-icon.png` | Favicon, App-Icon, Social Media |

Das Logo zeigt "RP" in einem eckigen Icon mit Gradient (Grün → Blau), daneben "RECRUITING PLAYBOOK" in Blau.
Das Icon ist das alleinstehende RP-Symbol mit dem gleichen Gradient und einer umgeklappten Ecke unten rechts (dunkelblau).

### Farben

```
Primary:         #1d71b8 (Blau — Hauptfarbe für Text, CTAs, Links)
Secondary 1:     #2fac66 (Grün — Akzent, Erfolg, Start des Gradients)
Secondary 2:     #36a9e1 (Hellblau — Akzent, Ende des Gradients)
Gradient:        #2fac66 → #36a9e1 (Linear, für Hero-Elemente, Badges, Icon-Hintergründe)
Text:            oklch(20.8% .042 265.755) (Dunkles Blau-Grau)
```

### Tailwind-Konfiguration (Vorschlag)

```js
// tailwind.config.js
colors: {
  primary: {
    DEFAULT: '#1d71b8',
    50:  '#eef6ff',
    100: '#d9eaff',
    200: '#bcdbff',
    300: '#8ec4ff',
    400: '#59a3ff',
    500: '#1d71b8',
    600: '#1a63a3',
    700: '#15508a',
    800: '#114272',
    900: '#0e375e',
  },
  secondary: {
    green: '#2fac66',
    blue: '#36a9e1',
  },
}
```

### Typografie

- **Headlines:** System Font Stack oder Inter/Plus Jakarta Sans (modern, professionell)
- **Body:** System Font Stack oder gleiche Schrift
- **Code:** JetBrains Mono oder Fira Code (für API-Docs)

### Design-Prinzipien

- Professionell, vertrauenswürdig (HR/Recruiting-Branche)
- Nicht zu verspielt — die Zielgruppe sind Geschäftsführer und HR-Verantwortliche
- Gradient sparsam einsetzen (Hero, CTAs, Akzente)
- Viel Whitespace, klare Hierarchie

---

## 3. Seitenstruktur

```
recruiting-playbook.com/
├── /                          Homepage (Marketing-Landingpage)
├── /features                  Feature-Übersicht (Free vs. Pro vs. AI)
├── /pricing                   Preise & Feature-Matrix
├── /docs/                     Dokumentation
│   ├── /docs/getting-started  Installation & Erstkonfiguration
│   ├── /docs/shortcodes       Shortcode-Referenz
│   ├── /docs/templates        Template-Überschreibung
│   ├── /docs/hooks            Actions & Filter
│   ├── /docs/email            E-Mail-Konfiguration
│   ├── /docs/gdpr             DSGVO-Funktionen
│   └── /docs/faq              Häufige Fragen
├── /api/                      API-Dokumentation (Pro)
│   ├── /api/authentication    Auth (App Passwords, API-Keys)
│   ├── /api/jobs              Jobs Endpoints
│   ├── /api/applications      Applications Endpoints
│   ├── /api/webhooks          Webhook-System
│   ├── /api/reports           Reports Endpoints
│   └── /api/errors            Fehlerbehandlung & Rate Limits
├── /ai                        KI-Features Landing Page + Demo
├── /changelog                 Versionshistorie
├── /support                   Support-Seite (Links, Kontakt)
├── /legal/privacy             Datenschutzerklärung
├── /legal/terms               AGB
└── /legal/imprint             Impressum
```

---

## 4. Seiten-Spezifikation

### 4.1 Homepage (`/`)

**Ziel:** Besucher überzeugen, das Plugin herunterzuladen oder Pro zu kaufen.

**Sektionen:**

1. **Hero**
   - Headline: "Professionelles Bewerbermanagement für WordPress"
   - Subline: "Stellenanzeigen erstellen, Bewerbungen verwalten, Bewerber einstellen — direkt auf deiner Website."
   - CTA 1: "Kostenlos herunterladen" → wordpress.org
   - CTA 2: "Pro entdecken" → /pricing
   - Hero-Grafik: Screenshot des Plugins oder abstraktes Dashboard-Visual

2. **Vertrauens-Leiste**
   - "Unbegrenzte Stellen — auch in der Free-Version"
   - WordPress.org Logo + Sterne-Bewertung (sobald verfügbar)
   - "DSGVO-konform"

3. **Feature-Highlights (3-4 Cards)**
   - Unbegrenzte Stellenanzeigen
   - Google for Jobs Integration
   - Mehrstufiges Bewerbungsformular
   - Sichere Dokumentenverwaltung

4. **Problem → Lösung**
   - "Über 40% aller Websites nutzen WordPress. Warum sollte Ihr Recruiting woanders stattfinden?"
   - Vergleich: Excel-Chaos vs. teure SaaS vs. Recruiting Playbook

5. **Feature-Tour (3 Tabs/Sektionen)**
   - Free: Stellen & Bewerbungen
   - Pro: Kanban, E-Mail-Templates, API
   - AI: Match-Score, Job-Finder, Chancen-Check

6. **Testimonials / Social Proof**
   - Pilotkunden-Zitate (wenn verfügbar)
   - Alternativ: Use Cases ("Pflegedienst mit 12 offenen Stellen...")

7. **Pricing-Teaser**
   - Kompakte Preistabelle mit CTA → /pricing

8. **CTA-Banner**
   - "Starten Sie in 5 Minuten — kostenlos."
   - Button: "Jetzt herunterladen"

### 4.2 Features (`/features`)

**Ziel:** Detaillierte Feature-Darstellung, Abgrenzung Free/Pro/AI.

**Inhalte:**
- Feature-Matrix (aus `docs/product/features.md`)
- Jedes Feature mit kurzem Text + Screenshot/Icon
- Klarer Upgrade-Pfad: Free → Pro → AI
- CTAs pro Sektion

### 4.3 Pricing (`/pricing`)

**Ziel:** Klare Preiskommunikation, Conversion zu Pro/AI.

**Pricing Cards:**

| | Free | Pro | AI-Addon |
|--|------|-----|----------|
| **Preis** | 0 € | 149 € einmalig | 19 €/Monat |
| **Typ** | Kostenlos | Lifetime (1 Website) | Abo (benötigt Pro) |
| **CTA** | Herunterladen | Jetzt kaufen | Addon aktivieren |

**Zusätzlich:**
- Pro Agency: 249 € (5 Websites)
- AI Jahresabo: 179 €/Jahr (2 Monate gratis)
- Wartungsverlängerung: 49 €/Jahr
- Extra AI-Pakete: 9 € / 50 Analysen

**FAQ auf Pricing-Seite:**
- "Was bedeutet Lifetime-Lizenz?" → Version 1.x + 12 Monate Updates, danach 49 €/Jahr optional
- "Brauche ich Pro für das AI-Addon?" → Ja
- "Kann ich die Domain wechseln?" → Ja, über Deaktivierung
- "Gibt es eine Testversion von Pro?" → 14-Tage-Geld-zurück-Garantie

### 4.4 Dokumentation (`/docs/...`)

**Ziel:** WordPress.org-Nutzer unterstützen, Support-Tickets reduzieren.

**Struktur:**
- Sidebar-Navigation mit allen Docs-Seiten
- Suchfunktion
- "Edit on GitHub" Link pro Seite
- Breadcrumbs

**Inhaltsquellen (bereits vorhanden als Markdown):**
- Installation & Setup → aus `plugin/README.md`
- Shortcodes → aus `plugin/README.md`
- Hooks & Filter → aus `plugin/README.md`
- DSGVO → aus `docs/product/integrations.md`
- Templates → aus `plugin/README.md`

### 4.5 API-Dokumentation (`/api/...`)

**Ziel:** Entwickler-Dokumentation für Pro-Kunden.

**Design:**
- Dreispaltig: Navigation | Beschreibung | Code-Beispiele
- Oder: Zweispaltig wie Stripe Docs
- Syntax-Highlighting für cURL, PHP, JavaScript, Python
- "Try it" Buttons (optional, später)

**Inhaltsquelle:** `docs/technical/api-specification.md` (vollständig vorhanden)

**Sektionen:**
- Authentifizierung (App Passwords, API-Keys)
- Jobs (CRUD)
- Applications (CRUD, Status, Notes, Rating, Documents, Export)
- Webhooks (Events, Payload, Signatur-Validierung)
- Reports (Overview, Time-to-Hire)
- Fehlerbehandlung (Status Codes, Error Codes)
- Rate Limiting
- SDKs (PHP, JavaScript, Python, cURL)

### 4.6 KI-Features Landing Page (`/ai`)

**Ziel:** AI-Addon verkaufen, USP demonstrieren.

**Sektionen:**

1. **Hero**
   - "Der erste WordPress-Recruiter mit eingebauter KI"
   - "Bewerber wissen in Sekunden, ob sie zum Job passen."

2. **3 KI-Modi visuell erklärt**
   - Job-Match: "Passe ich zu diesem Job?"
   - Job-Finder: "Welche Jobs passen zu mir?"
   - Chancen-Check: "Wie hoch ist meine Einstellungschance?"

3. **Live-Demo** (Lead-Magnet)
   - Bewerber können ohne Account einen Lebenslauf hochladen
   - Erhalten einen echten Match-Score gegen eine Beispiel-Stelle
   - CTA: "Das für Ihre Website? → Pro + AI kaufen"

4. **Vorteile für Arbeitgeber**
   - Vorqualifizierte Bewerber
   - Weniger Fehlbewerbungen
   - Zeitsparende Vorauswahl

5. **Vorteile für Bewerber**
   - Sofort wissen ob es passt
   - Keine Bewerbung ins Leere
   - Konkrete Verbesserungstipps

6. **KI-Texterstellung**
   - Stellentexte generieren, optimieren, umschreiben
   - SEO-Vorschläge

7. **Pricing + Fair-Use**
   - 19 €/Monat für 100 Analysen
   - Extra-Pakete: 9 € / 50 Analysen

### 4.7 Changelog (`/changelog`)

**Ziel:** Transparenz, Vertrauen bei WordPress-Community.

**Format:**
- Versionsnummer + Datum
- Gruppiert nach: Added, Changed, Fixed, Removed
- MDX-Datei, manuell gepflegt

### 4.8 Support (`/support`)

**Ziel:** Anlaufstelle für Hilfe.

**Inhalte:**
- Free-Nutzer: GitHub Issues, Docs, FAQ
- Pro-Nutzer: E-Mail-Support (1 Jahr inkl.)
- AI-Nutzer: E-Mail-Support
- Link zu GitHub Issues
- Link zu Docs/FAQ
- Kontaktformular (optional)

### 4.9 Legal (`/legal/...`)

- Datenschutzerklärung
- AGB / Nutzungsbedingungen
- Impressum
- Preisanpassungsklausel für AI-Addon (aus AGB)

---

## 5. LemonSqueezy Integration

### Produkte in LemonSqueezy

| Produkt | Typ | Preis | Lizenz |
|---------|-----|-------|--------|
| Pro (1 Website) | Einmalzahlung | 149 € | `RP-PRO-*` |
| Pro Agency (5 Websites) | Einmalzahlung | 249 € | `RP-PRO-*` (5 Aktivierungen) |
| AI-Addon (monatlich) | Abo | 19 €/Monat | `RP-AI-*` |
| AI-Addon (jährlich) | Abo | 179 €/Jahr | `RP-AI-*` |
| AI Extra-Paket | Einmalzahlung | 9 € | +50 Analysen |
| Wartungsverlängerung | Einmalzahlung | 49 €/Jahr | Update-Zugang verlängern |
| Bundle (Pro + AI) | Einmalig + Abo | 149 € + 19 €/M | `RP-BUNDLE-*` |

### Checkout-Flow

```
Website (/pricing)
    ↓ Klick auf "Jetzt kaufen"
LemonSqueezy Checkout (Overlay oder Hosted Page)
    ↓ Zahlung erfolgreich
LemonSqueezy generiert Lizenzschlüssel
    ↓ Webhook an Website
Website zeigt Bestätigung + Lizenzschlüssel
    ↓ Kunde kopiert Schlüssel
WordPress-Plugin → Einstellungen → Lizenz eingeben
    ↓ Plugin validiert gegen LemonSqueezy API
Pro/AI-Features freigeschaltet
```

### LemonSqueezy Webhooks → Website

LemonSqueezy sendet Webhooks bei:
- `order_created` — Neuer Kauf
- `subscription_created` — Neues AI-Abo
- `subscription_updated` — Abo geändert (Upgrade/Downgrade)
- `subscription_cancelled` — Abo gekündigt
- `subscription_expired` — Abo abgelaufen
- `license_key_created` — Neuer Lizenzschlüssel

### Plugin ↔ LemonSqueezy Validierung

Das WordPress-Plugin validiert die Lizenz gegen die LemonSqueezy API:

```
Plugin (WordPress)
    ↓ POST license key + domain
LemonSqueezy License API
    ↓ Response: valid/invalid, tier, features
Plugin schaltet Features frei/sperrt
```

**Endpunkte:**
- Aktivierung: `POST https://api.lemonsqueezy.com/v1/licenses/activate`
- Validierung: `POST https://api.lemonsqueezy.com/v1/licenses/validate`
- Deaktivierung: `POST https://api.lemonsqueezy.com/v1/licenses/deactivate`

### Kunden-Portal

LemonSqueezy bietet ein Kunden-Portal unter `{store}.lemonsqueezy.com/billing`:
- Lizenzen einsehen
- Abo verwalten (kündigen, Payment-Methode ändern)
- Rechnungen herunterladen
- Downloads (Plugin-ZIP)

Link dorthin von `/support` und Footer der Website.

---

## 6. MDX Content-Struktur

```
website/
├── content/
│   ├── docs/                    # Dokumentation
│   │   ├── getting-started.mdx
│   │   ├── shortcodes.mdx
│   │   ├── templates.mdx
│   │   ├── hooks.mdx
│   │   ├── email.mdx
│   │   ├── gdpr.mdx
│   │   └── faq.mdx
│   ├── api/                     # API-Dokumentation
│   │   ├── authentication.mdx
│   │   ├── jobs.mdx
│   │   ├── applications.mdx
│   │   ├── webhooks.mdx
│   │   ├── reports.mdx
│   │   └── errors.mdx
│   ├── changelog/               # Versionshistorie
│   │   └── index.mdx
│   └── legal/                   # Rechtliches
│       ├── privacy.mdx
│       ├── terms.mdx
│       └── imprint.mdx
├── app/
│   ├── layout.js
│   ├── page.js                  # Homepage
│   ├── features/
│   │   └── page.js
│   ├── pricing/
│   │   └── page.js
│   ├── ai/
│   │   └── page.js
│   ├── docs/
│   │   └── [...slug]/
│   │       └── page.js          # Dynamische MDX-Seiten
│   ├── api/
│   │   └── [...slug]/
│   │       └── page.js          # Dynamische API-Docs
│   ├── changelog/
│   │   └── page.js
│   ├── support/
│   │   └── page.js
│   └── legal/
│       └── [...slug]/
│           └── page.js
└── components/
    ├── layout/
    │   ├── Header.jsx
    │   ├── Footer.jsx
    │   ├── DocsSidebar.jsx
    │   └── ApiSidebar.jsx
    ├── marketing/
    │   ├── Hero.jsx
    │   ├── FeatureCard.jsx
    │   ├── PricingTable.jsx
    │   ├── ComparisonTable.jsx
    │   ├── Testimonial.jsx
    │   └── CtaBanner.jsx
    ├── docs/
    │   ├── CodeBlock.jsx         # Syntax-Highlighting
    │   ├── Callout.jsx           # Info/Warning/Tip Boxen
    │   ├── ApiEndpoint.jsx       # GET /api/jobs Darstellung
    │   └── ResponseExample.jsx   # JSON Response mit Tabs
    └── ui/
        ├── Button.jsx
        ├── Badge.jsx
        └── Gradient.jsx          # Gradient-Text/Hintergrund
```

### MDX-Beispiel (Docs)

```mdx
---
title: Shortcode-Referenz
description: Alle verfügbaren Shortcodes mit Attributen und Beispielen.
---

# Shortcode-Referenz

## [rp_jobs]

Zeigt eine Liste der Stellenanzeigen.

<Callout type="info">
  Dieser Shortcode funktioniert in der Free- und Pro-Version.
</Callout>

| Attribut | Beschreibung | Standard |
|----------|--------------|----------|
| `limit` | Anzahl Stellen | 10 |
| `category` | Filter nach Kategorie-Slug | - |

<CodeBlock language="php">
[rp_jobs limit="5" category="it" columns="2"]
</CodeBlock>
```

### MDX-Beispiel (API-Docs)

```mdx
---
title: Jobs
description: Stellenanzeigen über die REST API verwalten.
---

# Jobs

<ApiEndpoint method="GET" path="/wp-json/recruiting/v1/jobs" auth="API-Key (Pro)" />

Gibt eine paginierte Liste aller Stellenanzeigen zurück.

## Query-Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `status` | string | `draft`, `publish`, `archived` |
| `per_page` | int | Ergebnisse pro Seite (max: 100) |

## Response

<ResponseExample>
```json
{
  "data": [
    {
      "id": 123,
      "title": "Pflegefachkraft (m/w/d)",
      "status": "publish"
    }
  ],
  "meta": {
    "total": 45,
    "per_page": 10
  }
}
```
</ResponseExample>
```

---

## 7. Sprache & Lokalisierung

Die Website ist primär **deutschsprachig** (Zielmarkt DACH).

| Bereich | Sprache |
|---------|---------|
| Marketing-Seiten (Homepage, Features, Pricing, AI) | Deutsch |
| Dokumentation | Deutsch |
| API-Dokumentation | Englisch (Entwickler-Standard) |
| Legal | Deutsch |
| Code-Beispiele | Englisch (Variablennamen etc.) |

**Später (Phase 4):** Englische Version der Marketing-Seiten für internationalen Markt.

---

## 8. SEO-Strategie

### Ziel-Keywords

| Keyword | Seite | Suchvolumen (geschätzt) |
|---------|-------|------------------------|
| "WordPress Recruiting Plugin" | Homepage | Mittel |
| "WordPress Job Board Plugin" | Homepage | Mittel |
| "Bewerbermanagement WordPress" | Features | Niedrig-Mittel |
| "ATS WordPress Plugin" | Features | Niedrig |
| "WordPress Stellenanzeigen Plugin" | Homepage | Niedrig |
| "KI Bewerberanalyse" | /ai | Niedrig |
| "Google for Jobs WordPress" | Docs | Niedrig |

### Technisches SEO

- SSG (Static Site Generation) für alle Seiten → schnelle Ladezeiten
- Strukturierte Daten (Organization, SoftwareApplication)
- Open Graph + Twitter Cards mit Logo
- Sitemap.xml automatisch (Next.js)
- robots.txt
- Canonical URLs

---

## 9. Deployment & Workflow

### Git-Workflow

```
main                    # Produktion (Vercel Auto-Deploy)
├── feature/website     # Aktueller Entwicklungsbranch
└── feature/*           # Weitere Feature-Branches
```

### Vercel

- Auto-Deploy von `main` → Production
- Preview Deployments für Pull Requests
- Environment Variables für LemonSqueezy Keys

### Environment Variables (Vercel)

```
NEXT_PUBLIC_LEMONSQUEEZY_STORE_ID=xxxxx
LEMONSQUEEZY_API_KEY=xxxxx
LEMONSQUEEZY_WEBHOOK_SECRET=xxxxx
NEXT_PUBLIC_SITE_URL=https://recruiting-playbook.com
```

---

## 10. Offene Entscheidungen

- [ ] Domain: `recruiting-playbook.com` oder `recruiting-playbook.de`?
- [ ] Salient Template: Lizenz kaufen und als Basis nutzen
- [ ] Analytics: Plausible (gehostet, 9€/M) oder Vercel Analytics (Free Tier)?
- [ ] AI-Demo auf `/ai`: Eigener Demo-Endpunkt nötig oder simuliert?
- [ ] Englische Version der Website in Phase 1 oder später?
- [ ] Cookie-Banner: Nötig? (Nur wenn Analytics Cookies setzt)

---

*Erstellt: Januar 2026*
