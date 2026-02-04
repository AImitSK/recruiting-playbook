# Raidboxes Staging Deployment

## Übersicht

Die Staging-Umgebung läuft auf Raidboxes unter:
- **URL:** https://b8vsnmmra.myrdbx.io
- **WP-Admin:** https://b8vsnmmra.myrdbx.io/wp-admin

## SSH-Verbindung einrichten

### 1. SSH-Key zu Raidboxes hinzufügen

1. Öffne das **Raidboxes Dashboard**: https://dashboard.raidboxes.de
2. Wähle die Box `b8vsnmmra`
3. Gehe zu **Einstellungen** → **SSH**
4. Aktiviere SSH falls nicht aktiv
5. Füge deinen Public Key hinzu:
   ```bash
   # Zeige deinen Public Key an:
   cat ~/.ssh/id_ed25519.pub
   # oder
   cat ~/.ssh/id_rsa.pub
   ```
6. Kopiere den Key und füge ihn im Dashboard ein

### 2. SSH-Key laden (bei jeder neuen Terminal-Session)

```bash
ssh-add ~/.ssh/id_ed25519_raidboxes
# oder der Name deines Keys
```

### 3. Verbinden

```bash
ssh b8vsnmmra@b8vsnmmra.ssh.myrdbx.io
```

## Plugin aktualisieren

Nach dem Einloggen via SSH:

```bash
./update-plugin.sh
```

### Was macht das Skript?

Das `update-plugin.sh` Skript:
1. Wechselt ins Plugin-Verzeichnis
2. Führt `git pull` aus um die neuesten Änderungen zu holen
3. Installiert ggf. neue Composer-Dependencies
4. Leert den WordPress-Cache

## Troubleshooting

### SSH-Verbindung wird abgelehnt

```
Permission denied (publickey)
```

**Lösung:**
1. Prüfe ob SSH im Raidboxes Dashboard aktiviert ist
2. Prüfe ob dein Public Key hinterlegt ist
3. Lade den Key: `ssh-add ~/.ssh/DEIN_KEY`

### "Connection refused"

SSH ist möglicherweise deaktiviert. Aktiviere es im Raidboxes Dashboard unter Einstellungen → SSH.

### Mehrere SSH-Keys → "Too many authentication failures"

Verwende explizit den richtigen Key:
```bash
ssh -o IdentitiesOnly=yes -i ~/.ssh/id_ed25519_raidboxes b8vsnmmra@b8vsnmmra.ssh.myrdbx.io
```

## Wichtige Pfade auf der Box

| Pfad | Beschreibung |
|------|--------------|
| `/data/sites/web/b8vsnmmramyrdbxio/www` | WordPress Root |
| `.../www/wp-content/plugins/recruiting-playbook` | Plugin-Verzeichnis |
| `/data/sites/web/b8vsnmmramyrdbxio/update-plugin.sh` | Update-Skript |

## WP-CLI Befehle

Nach dem SSH-Login ins WordPress-Verzeichnis wechseln:
```bash
cd www
```

Dann z.B.:
```bash
wp plugin list
wp cache flush
wp post list --post_type=job_listing
```
