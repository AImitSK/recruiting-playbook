#!/bin/bash
# =============================================================
# Recruiting Playbook - Dev Container Setup Script
# =============================================================
# Dieses Script richtet WordPress und das Plugin vollständig ein.
#
# Verwendung:
#   Im Container:  /workspace/.devcontainer/setup.sh
#   Von außen:     docker exec devcontainer-wordpress-1 /workspace/.devcontainer/setup.sh
# =============================================================

set -e

echo "=========================================="
echo "Recruiting Playbook - Setup"
echo "=========================================="

# Farben für Output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Wechsle ins WordPress-Verzeichnis
cd /var/www/html

# =============================================================
# 1. WordPress herunterladen (falls noch nicht vorhanden)
# =============================================================
echo ""
echo "[1/7] Prüfe WordPress-Dateien..."
if [ ! -f "/var/www/html/wp-includes/version.php" ]; then
    echo "Lade WordPress herunter..."
    wp core download --allow-root
    echo -e "${GREEN}✓ WordPress heruntergeladen${NC}"
else
    echo -e "${YELLOW}✓ WordPress-Dateien bereits vorhanden${NC}"
fi

# =============================================================
# 2. wp-config.php erstellen (falls noch nicht vorhanden)
# =============================================================
echo ""
echo "[2/7] Prüfe Konfiguration..."
if [ ! -f "/var/www/html/wp-config.php" ]; then
    echo "Erstelle wp-config.php..."
    wp config create \
        --dbname=wordpress \
        --dbuser=wordpress \
        --dbpass=wordpress \
        --dbhost=mysql \
        --allow-root

    # Debug-Konstanten
    wp config set WP_DEBUG true --raw --allow-root
    wp config set WP_DEBUG_LOG true --raw --allow-root
    wp config set WP_DEBUG_DISPLAY true --raw --allow-root
    wp config set SCRIPT_DEBUG true --raw --allow-root

    # Freemius SDK Dev-Konstanten
    wp config set WP_FS__DEV_MODE true --raw --allow-root
    wp config set WP_FS__SKIP_EMAIL_ACTIVATION true --raw --allow-root
    wp config set 'WP_FS__recruiting-playbook_SECRET_KEY' 'sk_Hg&7eI]C]qSqo<T}j.ur5w8n;}8dh' --allow-root

    echo -e "${GREEN}✓ wp-config.php erstellt${NC}"
else
    echo -e "${YELLOW}✓ wp-config.php bereits vorhanden${NC}"

    # Freemius SDK Dev-Konstanten (falls noch nicht vorhanden)
    wp config set WP_FS__DEV_MODE true --raw --allow-root 2>/dev/null || true
    wp config set WP_FS__SKIP_EMAIL_ACTIVATION true --raw --allow-root 2>/dev/null || true
    wp config set 'WP_FS__recruiting-playbook_SECRET_KEY' 'sk_Hg&7eI]C]qSqo<T}j.ur5w8n;}8dh' --allow-root 2>/dev/null || true
fi

# =============================================================
# 3. WordPress installieren
# =============================================================
echo ""
echo "[3/7] Prüfe WordPress-Installation..."
if ! wp core is-installed --allow-root 2>/dev/null; then
    echo "Installiere WordPress..."
    wp core install \
        --url=http://localhost:8080 \
        --title="Recruiting Playbook Dev" \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@example.com \
        --skip-email \
        --allow-root
    echo -e "${GREEN}✓ WordPress installiert${NC}"
else
    echo -e "${YELLOW}✓ WordPress bereits installiert${NC}"
fi

# =============================================================
# 4. Upload-Verzeichnis Berechtigungen setzen
# =============================================================
echo ""
echo "[4/8] Setze Upload-Berechtigungen..."
UPLOADS_DIR="/var/www/html/wp-content/uploads"
mkdir -p "$UPLOADS_DIR"
chown -R www-data:www-data "$UPLOADS_DIR"
chmod -R 755 "$UPLOADS_DIR"
echo -e "${GREEN}✓ Upload-Berechtigungen gesetzt${NC}"

# =============================================================
# 5. Deutsche Sprache installieren
# =============================================================
echo ""
echo "[5/8] Prüfe Sprache..."
wp language core install de_DE --allow-root 2>/dev/null || true
wp site switch-language de_DE --allow-root 2>/dev/null || true
echo -e "${GREEN}✓ Deutsch aktiviert${NC}"

# =============================================================
# 6. Permalinks setzen
# =============================================================
echo ""
echo "[6/8] Setze Permalinks..."
wp rewrite structure '/%postname%/' --allow-root
echo -e "${GREEN}✓ Permalinks konfiguriert${NC}"

# =============================================================
# 7. Plugin aktivieren
# =============================================================
echo ""
echo "[7/8] Prüfe Plugin..."
PLUGIN_DIR="/var/www/html/wp-content/plugins/recruiting-playbook"
if [ -f "$PLUGIN_DIR/recruiting-playbook.php" ]; then
    if ! wp plugin is-active recruiting-playbook --allow-root 2>/dev/null; then
        wp plugin activate recruiting-playbook --allow-root
        echo -e "${GREEN}✓ Plugin aktiviert${NC}"
    else
        echo -e "${YELLOW}✓ Plugin bereits aktiv${NC}"
    fi
else
    echo -e "${RED}⚠ Plugin nicht gefunden in $PLUGIN_DIR${NC}"
fi

# =============================================================
# 8. Composer Dependencies installieren
# =============================================================
echo ""
echo "[8/8] Prüfe Dependencies..."
if [ -f "$PLUGIN_DIR/composer.json" ]; then
    cd "$PLUGIN_DIR"
    if [ ! -d "vendor" ]; then
        echo "Installiere Composer Dependencies..."
        composer install --no-interaction
        echo -e "${GREEN}✓ Composer Dependencies installiert${NC}"
    else
        echo -e "${YELLOW}✓ Composer Dependencies bereits vorhanden${NC}"
    fi
fi

# npm ist optional - nur wenn nötig
if [ -f "$PLUGIN_DIR/package.json" ] && [ ! -d "$PLUGIN_DIR/node_modules" ]; then
    echo "Installiere npm Dependencies..."
    cd "$PLUGIN_DIR"
    npm install --silent || true
    echo -e "${GREEN}✓ npm Dependencies installiert${NC}"
fi

# =============================================================
# Fertig!
# =============================================================
echo ""
echo "=========================================="
echo -e "${GREEN}Setup abgeschlossen!${NC}"
echo "=========================================="
echo ""
echo "  WordPress:    http://localhost:8080"
echo "  WP Admin:     http://localhost:8080/wp-admin"
echo "  phpMyAdmin:   http://localhost:8081"
echo "  MailHog:      http://localhost:8025"
echo ""
echo "  Login: admin / admin"
echo ""
echo "=========================================="
