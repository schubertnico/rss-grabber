FROM php:8.5-apache

# System-Abhaengigkeiten installieren
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    msmtp \
    msmtp-mta \
    && rm -rf /var/lib/apt/lists/*

# PHP-Extensions installieren
# mysqli  -> DB-Zugriff (db.php / classes/function.php), einzige zwingend
#            noetige Zusatz-Extension der App.
# simplexml/dom/iconv (RSS-Parsing + Zeichenkonvertierung) sind im offiziellen
# PHP-Image bereits standardmaessig aktiv.
# gd/intl/mbstring/zip: nuetzliche Dev-Extensions.
# Hinweis: pdo/pdo_mysql und opcache werden NICHT via ext-install gebaut -
# die App nutzt kein PDO, und unter PHP 8.5 sind pdo (statisch) und Zend
# OPcache (fest ins Core kompiliert) nicht mehr als Shared-Module verfuegbar.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    gd \
    mysqli \
    zip \
    intl \
    mbstring

# Composer (fuer PHPUnit/Tooling)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# pcov fuer Code-Coverage (PHPUnit). Benoetigt Build-Tools ($PHPIZE_DEPS).
RUN apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*

# Apache mod_rewrite aktivieren
RUN a2enmod rewrite headers

# MSMTP fuer Mail-Versand an Mailpit konfigurieren
RUN printf "account default\nhost mailpit\nport 1025\nfrom noreply@rss-grabber.local\n" > /etc/msmtprc

# Apache DocumentRoot setzen
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# AllowOverride fuer .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# PHP Error-Log Pfad ("Php Hourlog") vorbereiten
RUN touch /var/log/php-error.log && chmod 666 /var/log/php-error.log

WORKDIR /var/www/html

EXPOSE 80
