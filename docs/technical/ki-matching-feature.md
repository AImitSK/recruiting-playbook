# KI-Matching Feature

## Übersicht

Das KI-Matching Feature ermöglicht Bewerbern, ihren Lebenslauf hochzuladen und sofort zu erfahren, wie gut sie zu einer Stelle passen – ähnlich wie bei Stepstone.

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER FLOW                                    │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Bewerber sieht Stellenanzeige                                   │
│                    │                                                 │
│                    ▼                                                 │
│  2. Klickt "Bin ich ein Match?"                                     │
│                    │                                                 │
│                    ▼                                                 │
│  3. Modal öffnet sich → Lebenslauf hochladen                        │
│                    │                                                 │
│                    ▼                                                 │
│  4. Dokument wird anonymisiert (Presidio)                           │
│                    │                                                 │
│                    ▼                                                 │
│  5. Anonymisierte Daten → Claude API                                │
│                    │                                                 │
│                    ▼                                                 │
│  6. Ergebnis: "78% Match - Gute Übereinstimmung!"                   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Kernprinzipien

### DSGVO-Compliance durch Anonymisierung

**Keine personenbezogenen Daten verlassen den Server.**

```
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│    Original      │     │   Anonymisiert   │     │    Claude API    │
│                  │     │                  │     │                  │
│  Max Mustermann  │────▶│  ██████████████  │────▶│  Analyse ohne    │
│  Musterstr. 12   │     │  ████████████ ██ │     │  PII möglich     │
│  8 Jahre Pflege  │     │  8 Jahre Pflege  │     │                  │
└──────────────────┘     └──────────────────┘     └──────────────────┘
        │                                                   │
        │                                                   │
        ▼                                                   ▼
   BLEIBT LOKAL                                    GEHT ZU ANTHROPIC
   (wird gelöscht)                                 (nur Berufsdaten)
```

### Unterstützte Dateiformate

| Format | Verarbeitung |
|--------|--------------|
| PDF (mit Text) | Text extrahieren → Presidio Text Anonymizer |
| PDF (Scan) | Presidio Image Redactor (OCR + Schwärzen) |
| JPG/PNG | Presidio Image Redactor |
| DOCX | Text extrahieren → Presidio Text Anonymizer |

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                    SYSTEM-ARCHITEKTUR                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  WORDPRESS (Kunde)          EUER BACKEND           EXTERNE DIENSTE  │
│  ─────────────────          ────────────           ────────────────  │
│                                                                      │
│  ┌─────────────┐           ┌─────────────┐                          │
│  │   Plugin    │           │  Cloudflare │                          │
│  │             │──────────▶│   Worker    │                          │
│  │  • Button   │  HTTPS    │             │                          │
│  │  • Modal    │           │  • Auth     │                          │
│  │  • Ergebnis │◀──────────│  • Routing  │                          │
│  └─────────────┘           └──────┬──────┘                          │
│                                   │                                  │
│                                   ▼                                  │
│                            ┌─────────────┐                          │
│                            │  Presidio   │                          │
│                            │  Service    │                          │
│                            │             │                          │
│                            │  • OCR      │                          │
│                            │  • PII      │                          │
│                            │  • Redact   │                          │
│                            └──────┬──────┘                          │
│                                   │                                  │
│                                   ▼                                  │
│                            ┌─────────────┐       ┌─────────────┐   │
│                            │   Claude    │──────▶│  Anthropic  │   │
│                            │   Client    │◀──────│     API     │   │
│                            └─────────────┘       └─────────────┘   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Phasen-Übersicht

| Phase | Inhalt | Dokument |
|-------|--------|----------|
| **1** | Infrastruktur (Cloudflare Worker, API Gateway) | [ki-matching-phase-1-infrastructure.md](./ki-matching-phase-1-infrastructure.md) |
| **2** | Presidio Service (Anonymisierung) | [ki-matching-phase-2-anonymization.md](./ki-matching-phase-2-anonymization.md) |
| **3** | Claude API Integration (Analyse) | [ki-matching-phase-3-analysis.md](./ki-matching-phase-3-analysis.md) |
| **4** | WordPress Frontend (UI) | [ki-matching-phase-4-frontend.md](./ki-matching-phase-4-frontend.md) |

---

## Kosten-Übersicht

### Infrastruktur (monatlich)

| Service | Free Tier | Geschätzte Kosten |
|---------|-----------|-------------------|
| Cloudflare Workers | 100k Requests/Tag | 0€ |
| Cloudflare D1 | 5GB Storage | 0€ |
| Presidio Service (Railway/Fly.io) | - | ~5€ |
| **Gesamt Infrastruktur** | | **~5€/Monat** |

### Pro Analyse

| Szenario | Methode | Kosten |
|----------|---------|--------|
| Text-PDF | Presidio Text + Claude Haiku | ~0,01€ |
| Bild/Scan | Presidio Image + Claude Vision | ~0,02-0,03€ |

### Beispielrechnung (100 Kunden mit AI-Addon)

```
Einnahmen:  100 × 19€/Monat           = 1.900€
Kosten:
  - Infrastruktur                     =     5€
  - Analysen (10.000 × 0,02€)         =   200€
  - Gesamt                            =   205€

Marge:      1.900€ - 205€             = 1.695€ (89%)
```

---

## Ergebnis-Darstellung

### Drei Kategorien

| Score | Kategorie | Farbe | Message |
|-------|-----------|-------|---------|
| 0-40% | Eher nicht passend | Rot | "Diese Stelle passt wahrscheinlich nicht zu Ihrem Profil." |
| 41-70% | Teilweise passend | Gelb | "Sie erfüllen einige Anforderungen. Eine Bewerbung könnte sich lohnen." |
| 71-100% | Gute Übereinstimmung | Grün | "Ihr Profil passt gut zu dieser Stelle!" |

### Visuelle Darstellung

```
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│                        78%                                  │
│            ████████████████░░░░░░                           │
│                                                              │
│              Gute Übereinstimmung                           │
│                                                              │
│   Ihr Profil passt gut zu dieser Stelle!                    │
│                                                              │
│            [ Jetzt bewerben ]                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Datenschutz-Hinweis (für Bewerber)

Im Modal vor dem Upload:

```
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│   ℹ️ Datenschutz                                            │
│                                                              │
│   • Ihre persönlichen Daten (Name, Adresse, etc.) werden   │
│     automatisch entfernt bevor die Analyse startet          │
│   • Nur Ihre beruflichen Qualifikationen werden analysiert │
│   • Nach der Analyse werden alle Daten sofort gelöscht     │
│                                                              │
│   Mehr dazu in unserer Datenschutzerklärung.               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Nächste Schritte

1. **Phase 1 lesen:** [Infrastruktur aufsetzen](./ki-matching-phase-1-infrastructure.md)
2. **Phase 2 lesen:** [Presidio Service implementieren](./ki-matching-phase-2-anonymization.md)
3. **Phase 3 lesen:** [Claude API Integration](./ki-matching-phase-3-analysis.md)
4. **Phase 4 lesen:** [WordPress Frontend](./ki-matching-phase-4-frontend.md)

---

*Erstellt: Januar 2025*
