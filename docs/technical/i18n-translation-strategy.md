# i18n Translation Strategy — Fortsetzungsplan

**Stand:** 2026-02-11
**Branch:** feature/pro
**Letzter Commit:** i18n Source-Refactoring + Tier-1 WIP

---

## Was ist erledigt

### Source-Refactoring (100% fertig)
- 207 Quelldateien: Deutsch → Englisch (129 PHP + 64 JS + 14 Fallback)
- Frisches `.pot` generiert: **1.559 Strings**
- Checklist: `docs/technical/i18n-refactoring-checklist.md`

### Tier-1 .po Dateien (teilweise)

| Locale | Status | Uebersetzt | Fehlend |
|--------|--------|-----------|---------|
| `nl_NL` | 99.9% | 1.559 | 1 |
| `de_DE` | 70% | 1.101 | 459 |
| `it_IT` | 40% | 628 | 933 |
| `fr_FR` | 0% | 0 | 1.559 |
| `es_ES` | 0% | 0 | 1.559 |
| `de_DE_formal` | nicht erstellt | — | 1.559 |
| `de_AT` | nicht erstellt | — | 1.559 |
| `de_CH` | nicht erstellt | — | 1.559 |

---

## Strategie fuer Fortsetzung

### Problem
1.559 Strings pro Locale ist zu viel fuer einen einzelnen Agenten-Durchlauf.
Die Agenten laufen in Context/Turn-Limits.

### Loesung: Chunk-basierter Workflow mit Helper-Scripts

Es gibt bereits zwei Helper-Scripts in `plugin/languages/scripts/`:

1. **`extract-msgids.js`** — Extrahiert alle msgids aus .pot als JSON
   - Output: `msgids-template.json` (1.559 Eintraege, 51KB)

2. **`generate-po.js`** — Generiert .po aus .pot + JSON-Uebersetzungsmap
   - Input: Locale + JSON-Datei mit `{ "english": "translated", ... }`
   - Output: Komplette .po Datei

### Workflow fuer jedes fehlende Locale

#### Schritt 1: JSON-Template in Chunks aufteilen
```bash
# Neues Script schreiben: split-json.js
# Teilt msgids-template.json in 4 Chunks à ~390 Eintraege
node scripts/split-json.js 4
# Output: chunk-1.json, chunk-2.json, chunk-3.json, chunk-4.json
```

#### Schritt 2: Agenten uebersetzen Chunks parallel
Fuer jedes Locale 4 Agenten starten, jeder bekommt einen Chunk:
```
Agent 1: "Uebersetze chunk-1.json nach Franzoesisch. Schreibe das Ergebnis als chunk-1-fr_FR.json"
Agent 2: "Uebersetze chunk-2.json nach Franzoesisch. Schreibe das Ergebnis als chunk-2-fr_FR.json"
...
```

Jeder Agent:
1. Liest seinen Chunk (~13KB, ~390 Eintraege)
2. Uebersetzt alle Werte
3. Schreibt den uebersetzten Chunk als JSON

#### Schritt 3: Chunks zusammenfuehren
```bash
# Neues Script: merge-json.js
node scripts/merge-json.js fr_FR chunk-1-fr_FR.json chunk-2-fr_FR.json chunk-3-fr_FR.json chunk-4-fr_FR.json
# Output: translations-fr_FR.json
```

#### Schritt 4: .po generieren
```bash
node scripts/generate-po.js fr_FR translations-fr_FR.json
# Output: recruiting-playbook-fr_FR.po (komplett!)
```

### Reihenfolge der Arbeit

#### Phase 1: Bestehende .po vervollstaendigen
1. **nl_NL** — 1 String fehlt, manuell fixen (trivial)
2. **de_DE** — 459 fehlende Strings:
   - Bestehende Uebersetzungen aus de_DE.po extrahieren (Script: `extract-existing.js`)
   - Nur die fehlenden Strings als Chunk uebersetzen lassen
   - Zusammenfuehren mit generate-po.js
3. **it_IT** — 933 fehlende Strings: gleicher Ansatz wie de_DE

#### Phase 2: Neue Locales erstellen
4. **fr_FR** — 4 Chunks × 1 Agent = 4 Agenten parallel
5. **es_ES** — 4 Chunks × 1 Agent = 4 Agenten parallel

#### Phase 3: Deutsche Varianten ableiten
6. **de_DE_formal** — Aus fertigem de_DE.po ableiten:
   - "du" → "Sie", "dein" → "Ihr", "dir" → "Ihnen", "dich" → "Sie"
   - Verben anpassen: "kannst" → "koennen", "hast" → "haben", etc.
   - 1 Agent kann das komplett in einem Durchlauf (Edit-Operationen)
7. **de_AT** — Kopie von de_DE mit regionalen Anpassungen (minimal)
8. **de_CH** — Kopie von de_DE: "ss" statt "ß", ggf. Helvetismen

### Noch zu schreibende Scripts

| Script | Zweck |
|--------|-------|
| `split-json.js` | Teilt msgids-template.json in N Chunks |
| `merge-json.js` | Fuehrt uebersetzte Chunks zusammen |
| `extract-existing.js` | Extrahiert vorhandene Uebersetzungen aus .po als JSON |
| `diff-translations.js` | Findet fehlende Strings (Differenz .pot vs. .po) |

### Geschaetzter Aufwand

| Aufgabe | Agenten | Dauer |
|---------|---------|-------|
| nl_NL fix | 0 (manuell) | 1 min |
| de_DE vervollstaendigen | 1-2 | ~10 min |
| it_IT vervollstaendigen | 2-3 | ~15 min |
| fr_FR komplett | 4 | ~15 min |
| es_ES komplett | 4 | ~15 min |
| de_DE_formal ableiten | 1 | ~10 min |
| de_AT ableiten | 1 | ~5 min |
| de_CH ableiten | 1 | ~5 min |
| **Gesamt** | **~16 Agenten** | **~45-60 min** |

---

## Qualitaetssicherung (nach Uebersetzung)

1. **msgfmt --check** auf alle .po Dateien (Syntax-Validierung)
2. **Placeholder-Check**: Alle %s, %d, %1$s muessen in msgstr vorhanden sein
3. **Leere msgstr suchen**: `grep -c '^msgstr ""$'` sollte 0 sein (ausser Header)
4. **MO kompilieren**: `msgfmt -o *.mo *.po`
5. **JSON fuer JS**: `wp i18n make-json` (im Dev Container)

---

## Befehle zum Starten

```
# Beim naechsten Claude Code Start:
"Lies docs/technical/i18n-translation-strategy.md und fuehre die Strategie aus.
Beginne mit Phase 1 (bestehende .po vervollstaendigen), dann Phase 2 und 3."
```
