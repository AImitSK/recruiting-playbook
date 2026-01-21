#!/bin/bash
# Recruiting Playbook - Dev Container Setup Script
# Wird nach dem Erstellen des Containers ausgeführt

set -e

echo "=========================================="
echo "Recruiting Playbook - Setup"
echo "=========================================="

# Warten bis WordPress verfügbar ist
echo "Warte auf WordPress..."
sleep 5

# WordPress installieren falls noch nicht geschehen
if ! wp core is-installed --path=/var/www/html 2>/dev/null; then
    echo "Installiere WordPress..."
    wp core install \
        --path=/var/www/html \
        --url=http://localhost:8080 \
        --title="Recruiting Playbook Dev" \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@example.com \
        --skip-email \
        --allow-root

    echo "WordPress installiert!"
else
    echo "WordPress bereits installiert."
fi

# Deutsche Sprache installieren
echo "Installiere deutsche Sprache..."
wp language core install de_DE --path=/var/www/html --allow-root || true
wp site switch-language de_DE --path=/var/www/html --allow-root || true

# Permalink-Struktur setzen
echo "Setze Permalinks..."
wp rewrite structure '/%postname%/' --path=/var/www/html --allow-root

# MailHog als SMTP konfigurieren (wp_mail geht an MailHog)
echo "Konfiguriere E-Mail (MailHog)..."
wp config set SMTP_HOST mailhog --path=/var/www/html --allow-root || true
wp config set SMTP_PORT 1025 --path=/var/www/html --allow-root || true

# Plugin aktivieren falls vorhanden
PLUGIN_DIR="/var/www/html/wp-content/plugins/recruiting-playbook"
if [ -f "$PLUGIN_DIR/recruiting-playbook.php" ]; then
    echo "Aktiviere Recruiting Playbook Plugin..."
    wp plugin activate recruiting-playbook --path=/var/www/html --allow-root || true
fi

# Composer Dependencies installieren
if [ -f "$PLUGIN_DIR/composer.json" ]; then
    echo "Installiere Composer Dependencies..."
    cd "$PLUGIN_DIR"
    composer install --no-interaction || true
fi

# npm Dependencies installieren
if [ -f "$PLUGIN_DIR/package.json" ]; then
    echo "Installiere npm Dependencies..."
    cd "$PLUGIN_DIR"
    npm install || true
fi

echo ""
echo "=========================================="
echo "Setup abgeschlossen!"
echo "=========================================="
echo ""
echo "WordPress:   http://localhost:8080"
echo "Admin:       http://localhost:8080/wp-admin"
echo "             User: admin / Password: admin"
echo ""
echo "phpMyAdmin:  http://localhost:8081"
echo "MailHog:     http://localhost:8025"
echo ""
echo "=========================================="
