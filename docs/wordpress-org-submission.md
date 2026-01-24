# WordPress.org Plugin Submission

Diese Anleitung beschreibt, wie das Plugin für WordPress.org vorbereitet und eingereicht wird.

## Voraussetzungen

- WordPress.org Account (https://login.wordpress.org/register)
- Plugin-Code ist getestet und funktionsfähig
- readme.txt ist vollständig ausgefüllt

## ZIP-Datei erstellen

### Schritt 1: Produktions-Build erstellen

```bash
cd plugin
npm run build
```

### Schritt 2: ZIP-Datei erstellen

**PowerShell-Befehle (Windows):**

```powershell
# Vom Repository-Root ausführen
cd C:\Users\StefanKühne\Desktop\Projekte\recruiting-playbook

# Temporäres Verzeichnis erstellen
Remove-Item -Recurse -Force temp-zip -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path temp-zip/recruiting-playbook

# Produktionsdateien kopieren
Copy-Item -Recurse plugin/src temp-zip/recruiting-playbook/
Copy-Item -Recurse plugin/templates temp-zip/recruiting-playbook/
Copy-Item -Recurse plugin/languages temp-zip/recruiting-playbook/
Copy-Item -Recurse plugin/vendor temp-zip/recruiting-playbook/
Copy-Item plugin/readme.txt temp-zip/recruiting-playbook/
Copy-Item plugin/recruiting-playbook.php temp-zip/recruiting-playbook/
Copy-Item plugin/composer.json temp-zip/recruiting-playbook/

# Assets (nur dist-Ordner)
New-Item -ItemType Directory -Path temp-zip/recruiting-playbook/assets
Copy-Item -Recurse plugin/assets/dist temp-zip/recruiting-playbook/assets/

# Dev-Dependencies entfernen
Remove-Item -Recurse -Force temp-zip/recruiting-playbook/vendor/phpstan -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force temp-zip/recruiting-playbook/vendor/squizlabs -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force temp-zip/recruiting-playbook/vendor/wp-coding-standards -ErrorAction SilentlyContinue

# Verbotene Dateien entfernen
Get-ChildItem -Path temp-zip -Recurse -Filter ".gitkeep" | Remove-Item -Force
Get-ChildItem -Path temp-zip -Recurse -Filter "*.phar" | Remove-Item -Force
Get-ChildItem -Path temp-zip -Recurse -Filter "*_test.sh" | Remove-Item -Force

# ZIP erstellen
Remove-Item recruiting-playbook.zip -ErrorAction SilentlyContinue
Compress-Archive -Path temp-zip/recruiting-playbook -DestinationPath recruiting-playbook.zip

# Aufräumen
Remove-Item -Recurse -Force temp-zip

# Größe prüfen (max 10 MB)
(Get-Item recruiting-playbook.zip).Length / 1MB
```

**Bash-Befehle (Linux/Mac/Git Bash):**

```bash
# Vom Repository-Root ausführen
cd /path/to/recruiting-playbook

# Temporäres Verzeichnis erstellen
rm -rf temp-zip
mkdir -p temp-zip/recruiting-playbook

# Produktionsdateien kopieren
cp -r plugin/src temp-zip/recruiting-playbook/
cp -r plugin/templates temp-zip/recruiting-playbook/
cp -r plugin/languages temp-zip/recruiting-playbook/
cp -r plugin/vendor temp-zip/recruiting-playbook/
cp plugin/readme.txt temp-zip/recruiting-playbook/
cp plugin/recruiting-playbook.php temp-zip/recruiting-playbook/
cp plugin/composer.json temp-zip/recruiting-playbook/

# Assets (nur dist-Ordner)
mkdir -p temp-zip/recruiting-playbook/assets
cp -r plugin/assets/dist temp-zip/recruiting-playbook/assets/

# Dev-Dependencies entfernen
rm -rf temp-zip/recruiting-playbook/vendor/phpstan
rm -rf temp-zip/recruiting-playbook/vendor/squizlabs
rm -rf temp-zip/recruiting-playbook/vendor/wp-coding-standards

# Verbotene Dateien entfernen
find temp-zip -name ".gitkeep" -delete
find temp-zip -name "*.phar" -delete
find temp-zip -name "*_test.sh" -delete

# ZIP erstellen
rm -f recruiting-playbook.zip
cd temp-zip
zip -r ../recruiting-playbook.zip recruiting-playbook
cd ..

# Aufräumen
rm -rf temp-zip

# Größe prüfen (max 10 MB)
ls -lh recruiting-playbook.zip
```

## WordPress.org Anforderungen

### Verbotene Dateien/Inhalte

| Typ | Beispiele | Lösung |
|-----|-----------|--------|
| Hidden Files | `.gitkeep`, `.gitignore`, `.DS_Store` | Vor ZIP-Erstellung löschen |
| Dev Tools | `*.phar`, `*_test.sh` | Dev-Dependencies nicht einschließen |
| Source Files | `assets/src/` | Nur `assets/dist/` einschließen |
| Node Modules | `node_modules/` | Nie einschließen |
| Tests | `tests/`, `phpunit.xml` | Nie einschließen |

### Verbotene PHP-Konstrukte

| Konstrukt | Problem | Lösung |
|-----------|---------|--------|
| Heredoc/Nowdoc (`<<<`) | Nicht erlaubt | String-Konkatenation verwenden |
| CDN-Ressourcen | Externe Abhängigkeiten verboten | Lokal bündeln |
| `load_plugin_textdomain()` | Deprecated für WP.org Plugins | Entfernen (WP lädt automatisch) |

### False Positives (werden manuell geprüft)

Diese Funktionen werden vom Scanner markiert, sind aber legitim:

- `move_uploaded_file()` - Notwendig für Datei-Uploads
- `file_put_contents()` - Mit phpcs:ignore Kommentar okay

### readme.txt Anforderungen

```
=== Recruiting Playbook ===
Contributors: aimitsk                    <- WordPress.org Username!
Tags: recruiting, jobs, ...              <- Max 5 Tags, kein "ai"
Requires at least: 6.0
Tested up to: 6.9                        <- Aktuelle WP-Version!
Requires PHP: 8.0
Stable tag: 1.0.0                        <- Muss mit Plugin-Header übereinstimmen
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

## ZIP-Struktur

Die fertige ZIP muss diese Struktur haben:

```
recruiting-playbook.zip
└── recruiting-playbook/
    ├── assets/
    │   └── dist/
    │       ├── css/
    │       │   ├── admin.css
    │       │   └── frontend.css
    │       └── js/
    │           ├── alpine.min.js
    │           └── ...
    ├── languages/
    ├── src/
    │   ├── Admin/
    │   ├── Api/
    │   ├── Constants/
    │   ├── Core/
    │   ├── ...
    ├── templates/
    │   ├── archive-job_listing.php
    │   └── single-job_listing.php
    ├── vendor/
    │   ├── autoload.php
    │   └── composer/
    ├── composer.json
    ├── readme.txt
    └── recruiting-playbook.php
```

## Plugin einreichen

1. **Upload:** https://wordpress.org/plugins/developers/add/
2. **Review:** Dauert ca. 1-5 Werktage
3. **Nach Freigabe:** SVN-Zugang für Updates und Screenshots

## Screenshots (nach Freigabe)

Screenshots gehören NICHT in die ZIP-Datei, sondern werden nach Freigabe via SVN hochgeladen:

```
/assets/
  screenshot-1.png    <- Stellenübersicht
  screenshot-2.png    <- Stellendetailseite
  screenshot-3.png    <- Bewerbungsformular
  screenshot-4.png    <- Admin Dashboard
  screenshot-5.png    <- Setup-Wizard
```

## Checkliste vor Upload

- [ ] `npm run build` ausgeführt
- [ ] Version in `recruiting-playbook.php` und `readme.txt` identisch
- [ ] `Tested up to` auf aktuelle WP-Version gesetzt
- [ ] Keine `.gitkeep` Dateien
- [ ] Keine `*.phar` Dateien
- [ ] Keine `node_modules/`
- [ ] Keine `assets/src/`
- [ ] Keine Heredoc-Syntax (`<<<`)
- [ ] `composer.json` enthalten
- [ ] ZIP-Größe unter 10 MB
