FROM php:8.3-fpm

# --- Install dependency sistem yang dibutuhkan Laravel + generate .docx/PDF ---
#     libreoffice-writer + java + font: untuk konversi .docx -> PDF
#     (proposal: Word = master, PDF = hasil render LibreOffice atas .docx tsb)
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
    && rm -rf /var/lib/apt/lists/*

# Path binary LibreOffice untuk App\Services\DocxToPdf (di Debian: 'soffice' on PATH).
ENV LIBREOFFICE_BIN=soffice

# --- Install Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Copy source code aplikasi ---
COPY . .

# --- Install dependency PHP (production, tanpa dev-dependencies) ---
RUN composer install --no-dev --optimize-autoloader --no-interaction

# --- Permission storage & cache (wajib untuk Laravel) ---
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- Config Nginx & Supervisor (menjalankan nginx + php-fpm dalam 1 container) ---
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
