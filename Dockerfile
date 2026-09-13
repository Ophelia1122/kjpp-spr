FROM php:8.3-fpm

# --- Dependency sistem: Laravel + nginx/supervisor + LibreOffice (proposal .docx -> PDF) ---
RUN apt-get update && apt-get install -y --no-install-recommends \
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
    nginx \
    supervisor \
    fontconfig \
    libreoffice-writer \
    libreoffice-java-common \
    default-jre-headless \
    fonts-liberation \
    fonts-dejavu-core \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Path binary LibreOffice untuk App\Services\DocxToPdf.
ENV LIBREOFFICE_BIN=soffice

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Font Arial Narrow (berlisensi, tidak ada di git): kalau file public/fonts/*.ttf
# ikut disalin ke folder proyek NAS, pasang ke sistem supaya LibreOffice
# memakainya. Kalau tidak ada, LibreOffice memakai Liberation Sans Narrow.
RUN mkdir -p /usr/share/fonts/truetype/kjpp \
    && (cp public/fonts/*.ttf /usr/share/fonts/truetype/kjpp/ 2>/dev/null || true) \
    && fc-cache -f

COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zz-kjpp.ini
COPY docker/php-fpm-kjpp.conf /usr/local/etc/php-fpm.d/zz-kjpp.conf
COPY docker/entrypoint.sh /usr/local/bin/kjpp-entrypoint

# sed: buang CRLF kalau file sempat tersimpan dengan format Windows.
RUN sed -i 's/\r$//' /usr/local/bin/kjpp-entrypoint \
    && chmod +x /usr/local/bin/kjpp-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/kjpp-entrypoint"]
