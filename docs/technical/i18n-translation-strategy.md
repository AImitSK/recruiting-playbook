# i18n Translation Strategy — Stand & Restaufgaben

**Stand:** 2026-02-11
**Branch:** feature/pro

---

## Aktueller Stand

### Source-Refactoring (100% fertig)
- 207 Quelldateien: Deutsch → Englisch (129 PHP + 64 JS + 14 Fallback)
- Checklist: `docs/technical/i18n-refactoring-checklist.md`

### .pot Template
- Generiert mit `wp i18n make-pot` (PHP + JS + Templates)
- **2.272 uebersetzbare Strings** total
- Davon ~1.561 aus PHP, ~711 aus JS/Templates

### Tier-1 .po Dateien (8 Locales)

| Locale | Uebersetzt | Fehlend | Abdeckung |
|--------|-----------|---------|-----------|
| `de_DE` | 1.561 | 711 | 69% |
| `de_DE_formal` | 1.561 | 711 | 69% |
| `de_AT` | 1.561 | 711 | 69% |
| `de_CH` | 1.561 | 711 | 69% |
| `fr_FR` | 1.561 | 711 | 69% |
| `es_ES` | 1.561 | 711 | 69% |
| `it_IT` | 1.561 | 711 | 69% |
| `nl_NL` | 1.561 | 711 | 69% |

### Kompilierte Dateien (fertig)
- ✅ 9x `.mo` Dateien (binaere Uebersetzungen fuer PHP)
- ✅ 136x `.json` Dateien (JS-Uebersetzungen via `wp i18n make-json`)
- JS-JSONs enthalten nur Strings die bereits uebersetzt sind

---

## Fehlende 711 Strings — Analyse

Die 711 fehlenden Strings stammen aus:

### 1. Template-Dateien (noch auf Deutsch im Source!)
Dateien in `templates/` die beim i18n-Refactoring nicht erfasst wurden:
- `templates/archive-job_listing.php`
- `templates/single-job_listing.php`
- `templates/partials/*.php`
- E-Mail-Templates in `src/Services/EmailTemplateService.php`

Beispiele:
- "Sehr geehrte Bewerberin, sehr geehrter Bewerber"
- "Ihre Bewerbung im Ueberblick:"
- "Aktuell keine offenen Stellen"
- "&laquo; Zurueck" / "Weiter &raquo;"

### 2. JS-Strings die nur in gebauten Dateien vorkommen
Strings aus React-Komponenten die `@wordpress/i18n` nutzen,
aber nur von `wp i18n make-pot` (nicht `wp-pot-cli`) erkannt werden.

### 3. Plugin-Metadaten
- Plugin-Beschreibung, Autoren-URLs, etc.

---

## Restaufgaben (priorisiert)

### Prio 1: Template-Dateien refactoren (Deutsch → Englisch)
Die Template-Dateien haben noch deutsche Source-Strings.
Diese muessen wie die 207 anderen Dateien auf Englisch umgestellt werden.

```
Betroffene Dateien identifizieren:
grep -rl "__('" templates/ | sort
grep -rl "_e('" templates/ | sort
```

### Prio 2: Fehlende 711 Strings uebersetzen
Nach dem Template-Refactoring:
1. `.pot` neu generieren: `wp i18n make-pot`
2. `.po` mergen: `msgmerge --update *.po *.pot`
3. Fehlende Strings uebersetzen (Chunk-Ansatz aus scripts/)
4. `.mo` neu kompilieren: `msgfmt`
5. `.json` neu generieren: `wp i18n make-json`

### Prio 3: Tier-2 Locales (spaeter)
Siehe `docs/technical/i18n-translation-plan.md` fuer weitere Sprachen:
pl_PL, pt_PT, sv_SE, da_DK, nb_NO, fi, cs_CZ

---

## Helper-Scripts

Alle in `plugin/languages/scripts/`:

| Script | Zweck |
|--------|-------|
| `generate-po.js` | Generiert .po aus .pot + JSON-Uebersetzungsmap |
| `extract-msgids.js` | Extrahiert msgids aus .pot als JSON-Template |
| `extract-existing.js` | Extrahiert vorhandene Uebersetzungen aus .po |
| `split-json.js` | Teilt JSON-Template in N Chunks |
| `merge-json.js` | Fuehrt uebersetzte Chunks zusammen |
| `diff-translations.js` | Findet fehlende Strings |
| `convert-formal.js` | Konvertiert de_DE → de_DE_formal (du→Sie) |
| `verify-placeholders.js` | Prueft Placeholder-Konsistenz |

---

## Build-Workflow (Referenz)

```bash
# Im Dev Container:
cd /var/www/html/wp-content/plugins/recruiting-playbook

# 1. .pot generieren
wp i18n make-pot . languages/recruiting-playbook.pot --domain=recruiting-playbook --allow-root

# 2. .po mergen
cd languages
for f in recruiting-playbook-*.po; do msgmerge --update --backup=none "$f" recruiting-playbook.pot; done

# 3. .mo kompilieren
for f in recruiting-playbook-*.po; do msgfmt -o "${f%.po}.mo" "$f"; done

# 4. JSON fuer JS
cd ..
wp i18n make-json languages/ --no-purge --allow-root
```
