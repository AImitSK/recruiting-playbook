# i18n Translation Plan — Europaeische Lokalisierung

**Projekt:** Recruiting Playbook
**Quellsprache:** Englisch (en_US) — kein eigenes .po noetig, dient als Fallback
**Text Domain:** `recruiting-playbook`
**Datum:** 2026-02-11
**Status:** Planung

---

## Ausgangslage

Nach dem i18n-Refactoring sind alle 207 Quelldateien (129 PHP + 64 JS/React + 14 Fallback-Dateien) auf Englisch umgestellt. Die englischen Strings in `__()`, `_e()`, `esc_html__()` etc. sind die Source-Strings fuer alle Uebersetzungen.

Zielmarkt ist Europa. Die folgende Priorisierung basiert auf:
- Marktgroesse (BIP, Anzahl Unternehmen)
- WordPress-Marktanteil im jeweiligen Land
- Englisch-Kompetenz (Laender mit hoher Kompetenz benoetigen Uebersetzungen weniger dringend)
- Relevanz fuer HR/Recruiting-Software (Business-Kontext → formelle Anrede wichtig)

---

## Tier 1 — Pflicht (Launch)

Groesste Maerkte und/oder niedrige Englisch-Kompetenz. Deckt ca. 75% des europaeischen WordPress-Marktes ab.

| # | Locale | Sprache | Markt | Anmerkung |
|---|--------|---------|-------|-----------|
| 1 | `de_DE` | Deutsch | Deutschland | Heimatmarkt, groesster EU-Markt |
| 2 | `de_DE_formal` | Deutsch (Sie) | Deutschland | Business-Kontext, wichtig fuer HR! |
| 3 | `de_AT` | Deutsch (Oesterreich) | Oesterreich | Kann auf de_DE basieren, regionale Anpassungen |
| 4 | `de_CH` | Deutsch (Schweiz) | Schweiz | Kein "ss" → "ss" (kein Eszett), CHF statt EUR |
| 5 | `fr_FR` | Franzoesisch | Frankreich | 2. groesster EU-Markt |
| 6 | `es_ES` | Spanisch | Spanien | 4. groesste EU-Volkswirtschaft |
| 7 | `it_IT` | Italienisch | Italien | 3. groesste EU-Volkswirtschaft |
| 8 | `nl_NL` | Niederlaendisch | Niederlande, Belgien | Starker WordPress-Markt |

**Hinweis zu `de_DE` vs. `de_DE_formal`:**
WordPress unterscheidet zwischen informellem ("Du") und formellem ("Sie") Deutsch. Fuer ein Recruiting-Plugin im Business-Umfeld ist `de_DE_formal` besonders wichtig. Beide Varianten sollten gepflegt werden.

---

## Tier 2 — Empfohlen (nach Launch)

Starke WordPress-Maerkte und/oder wachsende Recruiting-Branche.

| # | Locale | Sprache | Markt | Anmerkung |
|---|--------|---------|-------|-----------|
| 9 | `pl_PL` | Polnisch | Polen | Grosser IT-Markt, stark wachsend |
| 10 | `pt_PT` | Portugiesisch | Portugal | |
| 11 | `sv_SE` | Schwedisch | Schweden | Hohe Digitalisierung |
| 12 | `da_DK` | Daenisch | Daenemark | |
| 13 | `nb_NO` | Norwegisch (Bokmal) | Norwegen | |
| 14 | `fi` | Finnisch | Finnland | |
| 15 | `cs_CZ` | Tschechisch | Tschechien | |

---

## Tier 3 — Nice-to-have (spaeter)

Kleinere Maerkte oder hohe Englisch-Kompetenz.

| # | Locale | Sprache | Markt |
|---|--------|---------|-------|
| 16 | `ro_RO` | Rumaenisch | Rumaenien |
| 17 | `hu_HU` | Ungarisch | Ungarn |
| 18 | `el` | Griechisch | Griechenland |
| 19 | `sk_SK` | Slowakisch | Slowakei |
| 20 | `hr` | Kroatisch | Kroatien |
| 21 | `bg_BG` | Bulgarisch | Bulgarien |

---

## Technische Umsetzung

### Dateistruktur

```
plugin/languages/
├── recruiting-playbook.pot          # Template (aus Source generiert)
├── recruiting-playbook-de_DE.po     # Deutsch
├── recruiting-playbook-de_DE.mo     # Deutsch (kompiliert)
├── recruiting-playbook-de_DE_formal.po
├── recruiting-playbook-de_DE_formal.mo
├── recruiting-playbook-de_AT.po
├── recruiting-playbook-de_AT.mo
├── recruiting-playbook-de_CH.po
├── recruiting-playbook-de_CH.mo
├── recruiting-playbook-fr_FR.po
├── recruiting-playbook-fr_FR.mo
├── recruiting-playbook-es_ES.po
├── recruiting-playbook-es_ES.mo
├── recruiting-playbook-it_IT.po
├── recruiting-playbook-it_IT.mo
├── recruiting-playbook-nl_NL.po
├── recruiting-playbook-nl_NL.mo
└── ...
```

### Workflow

1. **POT-Datei generieren** — `wp i18n make-pot` extrahiert alle uebersetzbaren Strings aus PHP und JS
2. **PO-Dateien erstellen** — Pro Locale eine PO-Datei aus dem POT-Template
3. **Uebersetzen** — Strings in jeder PO-Datei uebersetzen
4. **MO-Dateien kompilieren** — `wp i18n make-mo` oder `msgfmt` fuer die binaeren MO-Dateien
5. **JSON fuer JS** — `wp i18n make-json` generiert JSON-Dateien fuer JavaScript-Uebersetzungen

### Befehle

```bash
# POT generieren
wp i18n make-pot plugin/ plugin/languages/recruiting-playbook.pot

# PO aus POT erstellen (pro Locale)
msginit -i plugin/languages/recruiting-playbook.pot -o plugin/languages/recruiting-playbook-de_DE.po -l de_DE

# MO kompilieren (alle PO-Dateien)
wp i18n make-mo plugin/languages/

# JSON fuer JS-Uebersetzungen
wp i18n make-json plugin/languages/ --no-purge
```

### WPML / Polylang Kompatibilitaet

Die `wpml-config.xml` ist bereits vorhanden und konfiguriert. Uebersetzungen funktionieren mit WPML und Polylang automatisch ueber das WordPress-Standard-i18n-System.

---

## Besonderheiten fuer Recruiting-Kontext

- **Formelle Anrede:** Im HR/Business-Bereich wird in DE, FR, ES, IT gesiezt → formelle Varianten bevorzugen
- **Rechtliche Begriffe:** DSGVO/GDPR-bezogene Strings muessen juristisch korrekt uebersetzt werden
- **Stellenbezeichnungen:** Branchenspezifische Terminologie beachten (z.B. "Applicant Tracking" ist im Deutschen gaengig)
- **Datumsformate:** Werden von WordPress/PHP automatisch lokalisiert (kein manuelles Anpassen noetig)
- **Waehrungen:** Sind nicht hartcodiert, daher kein Uebersetzungsproblem

---

## Zeitplan (Vorschlag)

| Phase | Locales | Aufwand |
|-------|---------|---------|
| Phase 1 | `de_DE`, `de_DE_formal`, `de_AT`, `de_CH` | Deutsche Strings existieren bereits als Referenz |
| Phase 2 | `fr_FR`, `es_ES`, `it_IT`, `nl_NL` | Professionelle Uebersetzung empfohlen |
| Phase 3 | Tier 2 Locales | Nach Marktfeedback priorisieren |
| Phase 4 | Tier 3 Locales | Community-Uebersetzungen via translate.wordpress.org |
