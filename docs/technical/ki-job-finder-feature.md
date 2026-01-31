# KI-Job-Finder Feature (Mode B)

## Übersicht

Der KI-Job-Finder ist die Erweiterung des KI-Matching Features. Während Mode A (Einzelstellen-Matching) auf der Job-Seite stattfindet, ermöglicht Mode B Bewerbern, ihren Lebenslauf gegen **ALLE aktiven Stellen** zu analysieren.

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER FLOW (Mode B)                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. Bewerber besucht Karriere-Seite mit [rp_ai_job_finder]         │
│                    │                                                 │
│                    ▼                                                 │
│  2. Lädt Lebenslauf hoch                                           │
│                    │                                                 │
│                    ▼                                                 │
│  3. System analysiert CV gegen ALLE aktiven Stellen                │
│                    │                                                 │
│                    ▼                                                 │
│  4. Ergebnis: Top 5 passende Jobs mit Score                        │
│     ┌─────────────────────────────────────────┐                    │
│     │ 1. Senior Developer      ███████░░ 87%  │                    │
│     │ 2. Frontend Engineer     ██████░░░ 72%  │                    │
│     │ 3. DevOps Engineer       █████░░░░ 65%  │                    │
│     │ ...                                      │                    │
│     └─────────────────────────────────────────┘                    │
│                    │                                                 │
│                    ▼                                                 │
│  5. Ein-Klick-Bewerbung auf beste Matches                          │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Unterschied zu Mode A

| Aspekt | Mode A (Einzelstelle) | Mode B (Job-Finder) |
|--------|----------------------|---------------------|
| **Ort** | Modal auf Job-Seite | Shortcode auf beliebiger Seite |
| **Input** | CV + 1 Job | CV + alle aktiven Jobs |
| **Output** | 1 Score | Top-N Matches mit Scores |
| **Trigger** | Button "Passe ich zu diesem Job?" | Upload auf Job-Finder Seite |
| **Ergebnis** | Score + Empfehlung | Ranking + Profil-Analyse |

---

## Architektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                    JOB-FINDER ARCHITEKTUR                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  WORDPRESS                     CLOUDFLARE              EXTERNAL     │
│  ─────────                     ──────────              ─────────    │
│                                                                      │
│  ┌─────────────┐              ┌─────────────┐                       │
│  │  Shortcode  │              │   Worker    │                       │
│  │             │              │             │                       │
│  │ [rp_ai_    │──────────────▶│  /v1/      │                       │
│  │  job_      │   CV +        │  analysis/ │                       │
│  │  finder]   │   ALL Jobs    │  job-finder│                       │
│  │             │              │             │                       │
│  └─────────────┘              └──────┬──────┘                       │
│        │                             │                              │
│        │                             ▼                              │
│        │                      ┌─────────────┐      ┌─────────────┐ │
│        │                      │  Presidio   │      │   Claude    │ │
│        │                      │  (Anonym.)  │─────▶│   (Multi-   │ │
│        │                      │             │      │    Match)   │ │
│        │                      └─────────────┘      └──────┬──────┘ │
│        │                                                  │        │
│        │◀─────────────────────────────────────────────────┘        │
│        │              Top-N Matches                                 │
│        ▼                                                            │
│  ┌─────────────┐                                                   │
│  │  Ergebnis-  │                                                   │
│  │  Liste mit  │                                                   │
│  │  Match-     │                                                   │
│  │  Cards      │                                                   │
│  └─────────────┘                                                   │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Wiederverwendung von Mode A

| Komponente | Mode A | Mode B | Wiederverwendung |
|------------|--------|--------|------------------|
| Presidio Service | ✅ | ✅ | 100% - identisch |
| Auth Middleware | ✅ | ✅ | 100% - identisch |
| Usage Tracking | ✅ | ✅ | 100% - identisch |
| Feature Flag | `ai_cv_matching` | `ai_cv_matching` | 100% - identisch |
| Upload-Zone | Modal | Shortcode | 80% - CSS anpassen |
| Claude Prompt | Single-Job | Multi-Job | NEU |
| Ergebnis-Anzeige | Score + Message | Liste + Cards | NEU |

---

## Phasen-Übersicht

| Phase | Inhalt | Dokument |
|-------|--------|----------|
| **1** | API-Erweiterung (WordPress + Worker) | [ki-job-finder-phase-1-api.md](./ki-job-finder-phase-1-api.md) |
| **2** | Frontend (Alpine.js + Template) | [ki-job-finder-phase-2-frontend.md](./ki-job-finder-phase-2-frontend.md) |
| **3** | Shortcode & Integration | [ki-job-finder-phase-3-shortcode.md](./ki-job-finder-phase-3-shortcode.md) |

---

## Kosten-Überlegungen

### Token-Verbrauch bei Multi-Job-Analyse

| Anzahl Jobs | Input Tokens (ca.) | Output Tokens (ca.) | Kosten (Claude Haiku) |
|-------------|-------------------|--------------------|-----------------------|
| 10 Jobs | 8.000 | 1.500 | ~$0.03 |
| 30 Jobs | 18.000 | 2.000 | ~$0.06 |
| 50 Jobs | 28.000 | 2.500 | ~$0.10 |
| 100 Jobs | 53.000 | 3.000 | ~$0.18 |

**Empfehlung:** Max. 100 Jobs pro Analyse, bei mehr Jobs Kategorie-Filter anbieten.

---

## Nächste Schritte

1. **Phase 1 lesen:** [API-Erweiterung](./ki-job-finder-phase-1-api.md)
2. **Phase 2 lesen:** [Frontend](./ki-job-finder-phase-2-frontend.md)
3. **Phase 3 lesen:** [Shortcode & Integration](./ki-job-finder-phase-3-shortcode.md)

---

*Erstellt: Januar 2026*
