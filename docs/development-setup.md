# Entwicklungsumgebung einrichten

Diese Anleitung beschreibt, wie du die Entwicklungsumgebung für das Recruiting Playbook Plugin auf einem neuen PC einrichtest.

## Voraussetzungen

1. **Docker Desktop** installiert und gestartet
   - Download: https://www.docker.com/products/docker-desktop/
   - Nach Installation: Docker Desktop starten und warten bis es läuft (grünes Icon)

2. **Git** installiert
   - Download: https://git-scm.com/downloads

3. **VS Code** (empfohlen, aber optional)
   - Download: https://code.visualstudio.com/
   - Extension: "Dev Containers" (ms-vscode-remote.remote-containers)

## Setup in 3 Schritten

### Schritt 1: Repository klonen

```bash
git clone <repository-url> recruiting-playbook-docs
cd recruiting-playbook-docs
```

### Schritt 2: Docker-Container starten

```bash
cd .devcontainer
docker-compose up -d
```

Warte bis alle Container laufen (dauert beim ersten Mal etwas länger wegen Downloads):

```bash
docker ps
```

Du solltest 4 Container sehen:
- `devcontainer-wordpress-1`
- `devcontainer-mysql-1`
- `devcontainer-phpmyadmin-1`
- `devcontainer-mailhog-1`

### Schritt 3: Setup-Script ausführen

```bash
docker exec devcontainer-wordpress-1 /workspace/.devcontainer/setup.sh
```

Das Script:
- Lädt WordPress herunter
- Installiert und konfiguriert WordPress
- Aktiviert die deutsche Sprache
- Aktiviert das Plugin
- Installiert Composer-Abhängigkeiten

## Fertig!

Nach dem Setup sind folgende Services verfügbar:

| Service | URL | Zugangsdaten |
|---------|-----|--------------|
| WordPress | http://localhost:8080 | - |
| WP Admin | http://localhost:8080/wp-admin | admin / admin |
| phpMyAdmin | http://localhost:8081 | wordpress / wordpress |
| MailHog | http://localhost:8025 | - |

## Tägliche Nutzung

### Container starten (nach PC-Neustart)

```bash
cd .devcontainer
docker-compose up -d
```

### Container stoppen

```bash
cd .devcontainer
docker-compose down
```

### Container komplett zurücksetzen (Daten löschen)

```bash
cd .devcontainer
docker-compose down -v
```

Danach muss das Setup erneut ausgeführt werden.

## Alternative: VS Code Dev Container

Wenn du VS Code mit der "Dev Containers" Extension verwendest:

1. Öffne den Projektordner in VS Code
2. Klicke auf das blaue Icon unten links (oder F1 → "Reopen in Container")
3. Warte bis der Container gestartet ist
4. Terminal öffnen (Strg+Ö) und Setup ausführen:
   ```bash
   /workspace/.devcontainer/setup.sh
   ```

## Troubleshooting

### "Port already in use"

Ein anderer Dienst belegt den Port. Stoppe den Dienst oder ändere die Ports in `docker-compose.yml`.

### Container startet nicht

```bash
# Logs anzeigen
docker logs devcontainer-wordpress-1

# Alle Container neu starten
docker-compose down
docker-compose up -d
```

### WordPress zeigt Fehler 403

WordPress ist noch nicht installiert. Führe das Setup-Script aus:
```bash
docker exec devcontainer-wordpress-1 /workspace/.devcontainer/setup.sh
```

### Plugin zeigt "composer install" Meldung

```bash
docker exec devcontainer-wordpress-1 bash -c "cd /var/www/html/wp-content/plugins/recruiting-playbook && composer install"
```

### Datenbank-Verbindungsfehler

MySQL-Container ist noch nicht bereit. Warte 30 Sekunden und versuche es erneut:
```bash
docker exec devcontainer-mysql-1 mysqladmin ping -h localhost -uroot -proot
```

## Projektstruktur

```
recruiting-playbook-docs/
├── .devcontainer/           # Docker-Konfiguration
│   ├── docker-compose.yml   # Container-Definition
│   ├── Dockerfile           # WordPress-Container
│   └── setup.sh             # Automatisches Setup
├── docs/                    # Dokumentation
├── plugin/                  # Plugin-Quellcode (wird in WordPress gemountet)
│   ├── src/                 # PHP-Klassen
│   ├── assets/              # CSS, JS
│   └── templates/           # Frontend-Templates
└── CLAUDE.md                # Kontext für Claude Code
```

## Nützliche Befehle

```bash
# WP-CLI im Container nutzen
docker exec devcontainer-wordpress-1 wp --allow-root <befehl>

# Beispiele:
docker exec devcontainer-wordpress-1 wp plugin list --allow-root
docker exec devcontainer-wordpress-1 wp user list --allow-root

# Shell im Container öffnen
docker exec -it devcontainer-wordpress-1 bash

# Logs live verfolgen
docker logs -f devcontainer-wordpress-1
```
