# Recruiting Playbook

**WordPress-Plugin für professionelles Stellenausschreibungs- und Bewerbermanagement mit KI-gestützter Bewerber-Analyse**

---

## 🎯 Vision

Ein einfaches, bezahlbares Recruiting-Tool für KMUs und Personaldienstleister – direkt in WordPress integriert. Von der Stellenausschreibung bis zur Bewerberauswahl, mit einzigartiger KI-Unterstützung.

## 💡 Das Problem

- Kleine Personaldienstleister nutzen Excel, E-Mail-Postfächer oder teure Enterprise-Software
- WordPress-Nutzer haben keine native Lösung für Recruiting
- Bewerber wissen nicht, ob sie für eine Stelle qualifiziert sind
- Arbeitgeber erhalten viele unpassende Bewerbungen

## ✅ Die Lösung

Ein WordPress-Plugin mit drei Stufen:

| Tier | Preis | Kernfunktionen |
|------|-------|----------------|
| **Free** | 0 € | 3 Stellenanzeigen, Bewerbungsformular, E-Mail-Benachrichtigung |
| **Pro** | 149 € einmalig | Unbegrenzt Stellen, Kanban-Board, E-Mail-Templates, API |
| **AI-Addon** | 19 €/Monat | 🔥 KI-Bewerber-Analyse, Job-Matching, Texterstellung |

---

## 🔥 Killer-Feature: KI-Bewerber-Analyse

Das Alleinstellungsmerkmal – kein anderes WordPress-Plugin bietet das!

```
┌─────────────────────────────────────────────────────────────────┐
│                    3 KI-ANALYSE-MODI                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐            │
│  │   MODUS A   │  │   MODUS B   │  │   MODUS C   │            │
│  │             │  │             │  │             │            │
│  │  Job-Match  │  │ Job-Finder  │  │  Chancen-   │            │
│  │             │  │             │  │   Check     │            │
│  └─────────────┘  └─────────────┘  └─────────────┘            │
│                                                                 │
│  "Passe ich       "Welche Jobs      "Wie hoch ist             │
│   zu diesem        passen zu         meine                     │
│   Job?"            mir?"             Einstellungs-             │
│                                      chance?"                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

**Modus A: Job-Match** – Bewerber lädt Lebenslauf hoch → KI prüft Qualifikation für konkrete Stelle → Match-Score (z.B. 85%)

**Modus B: Job-Finder** – Bewerber lädt Lebenslauf hoch → KI analysiert ALLE Stellen → Top 5 Matches werden vorgeschlagen

**Modus C: Chancen-Check** – Detaillierte Einstellungschancen (0-100%) mit Tipps zur Verbesserung

---

## 📦 Pricing

| Produkt | Preis | Inhalt |
|---------|-------|--------|
| **Free** | 0 € | 3 Stellen, Basis-Features |
| **Pro** | 149 € einmalig | Unbegrenzt Stellen, Kanban, API, Page Builder |
| **Pro Agency** | 249 € einmalig | Pro für 5 Websites |
| **AI-Addon** | 19 €/Monat | 100 KI-Analysen/Monat |
| **AI Extra-Paket** | 9 € einmalig | +50 KI-Analysen |

---

## 🛠 Tech-Stack

| Bereich | Technologie |
|---------|-------------|
| Backend | PHP 8.0+, WordPress 6.x, OOP, PSR-4 |
| Admin UI | React (@wordpress/scripts) |
| Frontend | Alpine.js, Tailwind CSS |
| Notifications | react-hot-toast (Admin), Alpine Store (Frontend) |
| AI | Anthropic Claude API |
| Testing | PHPUnit, Jest, Playwright |
| CI/CD | GitHub Actions |
| Lizenzierung | Eigener Server, Domain-gebunden |

---

## 🎨 Page Builder Support

| Builder | Status |
|---------|--------|
| **Avada / Fusion Builder** | ✅ Native Elements (MVP) |
| **Shortcodes** | ✅ Alle Themes (MVP) |
| **Gutenberg Blocks** | ✅ Pro |
| **Elementor Widgets** | ✅ Pro |
| **Divi Modules** | ✅ Pro |

---

## 📁 Dokumentation

### Produkt
- [Produktvision](docs/product/vision.md)
- [Pricing-Modell](docs/product/pricing-model.md)
- [Feature-Übersicht](docs/product/features.md)
- [Integrationen & Europa-Strategie](docs/product/integrations.md)

### Technisch
- [Plugin-Architektur](docs/technical/plugin-architecture.md)
- [Datenbank-Schema](docs/technical/database-schema.md)
- [REST API Spezifikation](docs/technical/api-specification.md)
- [Frontend-Architektur](docs/technical/frontend-architecture.md)
- [UI Notifications & Messaging](docs/technical/ui-notifications.md)
- [Theme & Page Builder Integration](docs/technical/theme-integration.md)
- [KI-Analyse Feature](docs/technical/ai-analysis-feature.md)
- [Lizenz-System](docs/technical/licensing-system.md)
- [Spam-Schutz](docs/technical/spam-protection.md)
- [Testing-Strategie](docs/technical/testing-strategy.md)
- [Mehrsprachigkeit (i18n)](docs/technical/i18n-multilingual.md)

### Anforderungen
- [User Stories & MVP](docs/requirements/user-stories.md)

### Planung
- [Roadmap](docs/roadmap.md)

---

## 🚀 Status

**Phase:** Konzeption abgeschlossen ✅ – Bereit für Entwicklung

### Erledigte Entscheidungen

- [x] Pricing-Modell: Free → Pro (einmalig) → AI (Abo)
- [x] Tech-Stack: React (Admin) + Alpine.js (Frontend)
- [x] Lizenzierung: Eigener Server, Domain-gebunden
- [x] Page Builder: Avada-Priorität (MVP), Elementor/Gutenberg (Pro)
- [x] AI-Provider: Anthropic Claude
- [x] KI-Killer-Feature: Job-Match, Job-Finder, Chancen-Check
- [x] Spam-Schutz: Honeypot + Turnstile
- [x] Testing: PHPUnit + Jest, 50-60% Coverage

---

## 📅 Timeline

| Phase | Zeitraum | Fokus |
|-------|----------|-------|
| **Phase 1** | Q1 2025 | MVP / Free-Version beim Pilotkunden |
| **Phase 2** | Q2 2025 | Pro-Version, wordpress.org Launch |
| **Phase 3** | Q3 2025 | AI-Addon mit Killer-Feature |
| **Phase 4** | Q4 2025 | Scale & Marketing |

---

## 🏆 USP / Warum dieses Plugin?

> **"Der erste WordPress-Recruiter mit eingebauter KI, der Bewerbern sagt, ob sie den Job kriegen."**

- ✅ Einzige WordPress-Lösung mit echter KI-Bewerber-Analyse
- ✅ Günstigste Lösung mit AI-Integration
- ✅ Native Page Builder Integration (Avada, Elementor, Gutenberg)
- ✅ DSGVO-konform, Made in Germany
- ✅ Für Personaldienstleister optimiert (Pflege, Zeitarbeit)

---

*Entwickelt von [AImitSK](https://github.com/AImitSK)*
