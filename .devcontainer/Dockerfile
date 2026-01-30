# Recruiting Playbook - Development Container
# PHP 8.2 + WordPress Development Tools

FROM php:8.2-apache

# System-Abhängigkeiten
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    mariadb-client \
    less \
    vim \
    msmtp \
    msmtp-mta \
    && rm -rf /var/lib/apt/lists/*

# PHP Extensions für WordPress
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    gd \
    zip \
    mbstring \
    exif \
    intl \
    opcache \
    bcmath

# Xdebug für Debugging
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Xdebug Konfiguration
RUN echo "xdebug.mode=debug,develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# PHP Konfiguration für Entwicklung
RUN echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/uploads.ini

# MailHog SMTP Konfiguration (E-Mail-Testing)
RUN echo "account default\nhost mailhog\nport 1025\nfrom wordpress@localhost\n" > /etc/msmtprc \
    && echo "sendmail_path = /usr/bin/msmtp -t" >> /usr/local/etc/php/conf.d/mail.ini

# Apache mod_rewrite für WordPress Permalinks
RUN a2enmod rewrite

# Apache Konfiguration
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/wordpress.conf \
    && a2enconf wordpress

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node.js 20 LTS installieren
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# WP-CLI installieren
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Wrangler CLI für Cloudflare Workers (KI-Matching API)
RUN npm install -g wrangler

# Arbeitsverzeichnis
WORKDIR /var/www/html

# Apache als www-data User starten (für Berechtigungen)
RUN chown -R www-data:www-data /var/www/html

# VS Code / Cursor Server und Config-Verzeichnisse für www-data User erstellen
# Das Home-Verzeichnis von www-data ist /var/www
RUN mkdir -p /var/www/.vscode-server /var/www/.vscode-server-insiders /var/www/.cursor-server /var/www/.config /var/www/.npm \
    && chown -R www-data:www-data /var/www/.vscode-server /var/www/.vscode-server-insiders /var/www/.cursor-server /var/www/.config /var/www/.npm

# Port 80 für Apache
EXPOSE 80
