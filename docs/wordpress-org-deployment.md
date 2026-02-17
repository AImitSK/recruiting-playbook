# WordPress.org Deployment Guide

## Übersicht: Freemius → WordPress.org Workflow

1. Premium-Version mit `@fs_premium_only` Tags zu Freemius hochladen
2. Freemius generiert automatisch Free-Version (stripped premium code)
3. Free-Version manuell von Freemius Dashboard downloaden
4. Free-Version per SVN zu WordPress.org hochladen

**Wichtig:** Freemius deployed NICHT automatisch zu WordPress.org!

---

## 1. Plugin bei WordPress.org einreichen

### Voraussetzungen
- WordPress.org Account: [login.wordpress.org](https://login.wordpress.org)
- Plugin-Name/Slug festlegen (permanent, SEO-relevant!)
- readme.txt vorbereitet und validiert

### Einreichung
- Formular: [wordpress.org/plugins/developers/add](https://wordpress.org/plugins/developers/add/)
- Review-Dauer: 1-10 Tage
- Antwort per E-Mail: plugins@wordpress.org

### Nach Approval
SVN Repository URL: `https://plugins.svn.wordpress.org/recruiting-playbook/`

---

## 2. readme.txt Struktur

**Datei:** `plugin/readme.txt`

**Validator:** [wordpress.org/plugins/developers/readme-validator](https://wordpress.org/plugins/developers/readme-validator/)

### Template

```
=== Recruiting Playbook ===
Contributors: stefankuehne, peterkuehne
Tags: recruiting, jobs, bewerbermanagement, stellenanzeigen, ats
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professionelles Bewerbermanagement für WordPress.

== Description ==

Recruiting Playbook ist ein professionelles ATS (Applicant Tracking System) für WordPress.

**Features:**
- Unbegrenzte Stellenanzeigen
- Mehrstufiges Bewerbungsformular
- DSGVO-konform mit Datenschutz-Checkboxen
- Automatische E-Mail-Benachrichtigungen

== Installation ==

1. Plugin hochladen und aktivieren
2. Setup-Wizard durchlaufen
3. Erste Stellenanzeige erstellen

== Frequently Asked Questions ==

= Ist das Plugin DSGVO-konform? =
Ja, das Plugin enthält alle erforderlichen Datenschutz-Features.

= Kann ich das Design anpassen? =
Ja, über Design & Branding Einstellungen oder Custom CSS.

== Screenshots ==

1. Job-Übersicht im Frontend
2. Bewerbungsformular mit Datenschutz-Checkboxen
3. Admin-Dashboard - Bewerbungsübersicht
4. Stellenanzeige bearbeiten
5. Design & Branding Einstellungen

== Changelog ==

= 1.2.5 =
* German translations for Admin Pricing Page

= 1.2.4 =
* Fix plugin icon display

= 1.2.3 =
* Custom CSS for Freemius Pricing Page

= 1.2.2 =
* Freemius customizations

== Upgrade Notice ==

= 1.2.5 =
Komplette deutsche Übersetzungen für Admin-Bereich.
```

---

## 3. Assets Spezifikationen

### SVN Verzeichnisstruktur

```
recruiting-playbook/
├── assets/                  ← Alle Assets hier!
│   ├── icon-128x128.png
│   ├── icon-256x256.png
│   ├── banner-772x250.png
│   ├── banner-1544x500.png
│   ├── screenshot-1.png
│   ├── screenshot-2.png
│   ├── screenshot-3.png
│   ├── screenshot-4.png
│   └── screenshot-5.png
├── trunk/                   ← Plugin-Code (Free-Version)
│   ├── recruiting-playbook.php
│   ├── readme.txt
│   ├── src/
│   ├── assets/
│   └── ...
└── tags/                    ← Versions-Tags
    ├── 1.2.5/
    ├── 1.2.6/
    └── ...
```

### Asset Anforderungen

| Asset | Standard | Retina | Format | Max Size |
|-------|----------|--------|--------|----------|
| **Icon** | 128×128 | 256×256 | PNG/JPG/GIF/SVG | 1MB |
| **Banner** | 772×250 | 1544×500 | PNG/JPG | 4MB |
| **Screenshots** | beliebig | - | PNG/JPG | 10MB |

**Regeln:**
- Alle Dateinamen **lowercase**!
- Screenshots brauchen Beschreibung in readme.txt (== Screenshots == Section)
- Assets liegen NICHT in trunk/, sondern parallel!
- Icon sollte quadratisch sein (128x128, 256x256)
- Banner sollte Recruiting Playbook Logo + Slogan enthalten

### Screenshot-Vorschläge

1. **Job-Liste Frontend** - Übersicht aller Stellenanzeigen
2. **Bewerbungsformular** - Mit Datenschutz-Checkboxen
3. **Admin-Dashboard** - Bewerbungsübersicht/Tabelle
4. **Job bearbeiten** - Editor-Ansicht
5. **Design & Branding** - Einstellungen-Seite

---

## 4. SVN Upload Prozess

### Erste Version hochladen

```bash
# 1. Repository auschecken
svn co https://plugins.svn.wordpress.org/recruiting-playbook recruiting-playbook-svn
cd recruiting-playbook-svn

# 2. Free-Version von Freemius Dashboard downloaden
# Freemius Dashboard → Versions → Download Free Version

# 3. Plugin-Dateien nach trunk/ kopieren
cp -r /pfad/zur/freemius-free-version/* trunk/

# 4. Assets-Verzeichnis erstellen und Dateien kopieren
mkdir assets
cp /pfad/zu/icon-128x128.png assets/
cp /pfad/zu/icon-256x256.png assets/
cp /pfad/zu/banner-772x250.png assets/
cp /pfad/zu/banner-1544x500.png assets/
cp /pfad/zu/screenshot-*.png assets/

# 5. Neue Dateien zu SVN hinzufügen
svn add trunk/* --force
svn add assets/* --force

# 6. Commit (erste Version)
svn ci -m "Initial release v1.2.5"

# 7. Tag für Version erstellen
svn cp trunk tags/1.2.5
svn ci -m "Tagging version 1.2.5"
```

### Updates deployen

```bash
# 1. Neue Version bei Freemius deployen (GitHub Action)
git tag v1.2.6
git push origin v1.2.6

# 2. Free-Version von Freemius Dashboard downloaden

# 3. SVN trunk/ aktualisieren
cd recruiting-playbook-svn
svn up
rm -rf trunk/*
cp -r /pfad/zur/freemius-free-1.2.6/* trunk/
svn status  # Prüfen welche Dateien geändert/hinzugefügt/gelöscht wurden
svn add --force trunk/*  # Neue Dateien hinzufügen
svn ci -m "Update to v1.2.6"

# 4. Neuen Tag erstellen
svn cp trunk tags/1.2.6
svn ci -m "Tagging version 1.2.6"

# 5. readme.txt "Stable tag" muss auf 1.2.6 stehen!
```

**Wichtig:** Der `Stable tag` in readme.txt bestimmt, welche Version ausgeliefert wird!

---

## 5. Freemius Free-Version Download

### Manuelle Download-Schritte

1. Freemius Dashboard öffnen: [dashboard.freemius.com](https://dashboard.freemius.com)
2. Recruiting Playbook Plugin auswählen
3. **Versions** → gewünschte Version auswählen
4. **Download** Button → **Download Free Version** wählen
5. ZIP-Datei entpacken
6. Inhalt nach SVN trunk/ kopieren

### Wichtig bei Updates

- Immer die **Released** Version downloaden (nicht Pending!)
- Vor SVN-Commit prüfen: `composer install --no-dev` wurde ausgeführt
- Vor SVN-Commit prüfen: `npm run build` wurde ausgeführt
- Keine development dependencies in Free-Version!

---

## 6. Checkliste für WordPress.org Deployment

### Vorbereitung
- [ ] WordPress.org Account erstellt
- [ ] Plugin-Name/Slug festgelegt
- [ ] readme.txt erstellt und validiert
- [ ] Icon erstellt (128×128 + 256×256 PNG)
- [ ] Banner erstellt (772×250 + 1544×500 PNG)
- [ ] Screenshots erstellt (5 Stück, beschriftet)

### Einreichung
- [ ] Plugin über WordPress.org Formular eingereicht
- [ ] Approval E-Mail erhalten (1-10 Tage)
- [ ] SVN Repository URL notiert

### Erste Version
- [ ] Free-Version von Freemius downloaded
- [ ] SVN Repository ausgecheckt
- [ ] Plugin-Code nach trunk/ kopiert
- [ ] Assets nach assets/ kopiert
- [ ] SVN commit durchgeführt
- [ ] Tag für Version erstellt
- [ ] Plugin auf WordPress.org sichtbar

### Updates
- [ ] Neue Version bei Freemius deployed (GitHub Action)
- [ ] Version auf "Released" gesetzt
- [ ] Free-Version downloaded
- [ ] SVN trunk/ aktualisiert
- [ ] SVN commit durchgeführt
- [ ] Neuen Tag erstellt
- [ ] readme.txt "Stable tag" aktualisiert

---

## 7. Design-Vorgaben für Assets

### Branding
- **Primärfarbe:** `#1d71b8` (Recruiting Playbook Blau)
- **Schriftart:** Wie auf recruiting-playbook.com
- **Stil:** Professionell, clean, modern

### Icon (128×128, 256×256)
- Recruiting Playbook Logo
- Transparenter Hintergrund oder weißer Hintergrund mit Rand
- Gut erkennbar in kleiner Größe

### Banner (772×250, 1544×500)
- Logo + Slogan: "Professionelles Bewerbermanagement für WordPress"
- Optional: Screenshots/Mockups
- Recruiting Playbook Blau als Akzentfarbe

### Screenshots
- **Auflösung:** Mind. 1280×720 (HD)
- **Format:** PNG (bessere Qualität)
- **Inhalt:** Echte Plugin-Ansichten, keine Mockups
- **Beschriftung:** In readme.txt unter == Screenshots ==

---

## 8. Häufige Fehler vermeiden

### readme.txt
- ❌ Fehlende "Tested up to" Version
- ❌ Ungültiger "Stable tag"
- ❌ Fehlende Screenshot-Beschreibungen
- ✅ Mit Validator prüfen!

### Assets
- ❌ Großbuchstaben in Dateinamen (`Icon-128x128.png`)
- ❌ Assets in trunk/ statt assets/
- ❌ Zu große Dateien (> 4MB Banner)
- ✅ Lowercase, korrekte Dimensionen!

### SVN
- ❌ Gesamtes Plugin-Verzeichnis in trunk/ kopiert (statt Inhalt)
- ❌ node_modules/ oder vendor/dev-dependencies committed
- ❌ .git/ Verzeichnis committed
- ✅ Nur production-ready Code!

---

## 9. Nützliche Links

**WordPress.org:**
- Plugin einreichen: [wordpress.org/plugins/developers/add](https://wordpress.org/plugins/developers/add/)
- readme.txt Validator: [wordpress.org/plugins/developers/readme-validator](https://wordpress.org/plugins/developers/readme-validator/)
- Plugin Assets Guide: [developer.wordpress.org/plugins/wordpress-org/plugin-assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/)

**Freemius:**
- Dashboard: [dashboard.freemius.com](https://dashboard.freemius.com)
- Deployment Docs: [freemius.com/help/documentation/wordpress/deployment-process](https://freemius.com/help/documentation/wordpress/deployment-process/)
- WordPress.org Guide: [freemius.com/blog/submit-plugin-wordpress-repository](https://freemius.com/blog/submit-plugin-wordpress-repository/)

---

## 10. Support & Troubleshooting

### SVN Probleme

**429 Too Many Requests:**
- 15-30 Minuten warten
- IP wechseln (Router neustarten, VPN aus/an)

**Commit schlägt fehl:**
```bash
svn cleanup
svn update
svn commit -m "message"
```

**Dateien werden nicht angezeigt:**
- Prüfen: Sind Dateien in trunk/ und nicht in trunk/recruiting-playbook/?
- Prüfen: Lowercase Dateinamen?

### Plugin wird nicht angezeigt

- Prüfen: readme.txt "Stable tag" korrekt?
- Prüfen: Tag existiert in SVN tags/?
- Prüfen: Version in readme.txt = Version in recruiting-playbook.php Header?

### Assets werden nicht angezeigt

- Prüfen: Assets in /assets/ (nicht in /trunk/assets/)?
- Prüfen: Dateinamen lowercase?
- Prüfen: Korrekte Dimensionen?
- Cache-Zeit: ~15 Minuten bis Assets sichtbar

---

**Letzte Aktualisierung:** 2026-02-17
