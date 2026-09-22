# Pin Debian Bookworm supaya base image tidak berpindah diam-diam.
FROM php:8.3-fpm-bookworm

# LibreOffice DIKUNCI ke versi yang sama dengan laptop pengembang (2026-09-22):
# paket resmi The Document Foundation, bukan paket Debian (bookworm = 7.4,
# hasil PDF-nya berbeda dari yang dicek di laptop). Ganti versi = ubah dua
# baris ini, build ulang, lalu cek ulang hasil PDF proposal.
ARG LO_VERSION=25.2.7.2
ARG LO_SERIES=25.2

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

# --- LibreOffice resmi (TDF) versi terkunci ---
# Pustaka sistem yang dibutuhkan LibreOffice headless untuk konversi DOCX->PDF.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libxinerama1 libdbus-1-3 libcups2 libnss3 libsm6 libice6 libxext6 libxrender1 \
        libx11-xcb1 libcairo2 libglib2.0-0 libxml2 libxslt1.1 \
    && curl -fsSL -o /tmp/lo.tar.gz \
        "https://downloadarchive.documentfoundation.org/libreoffice/old/${LO_VERSION}/deb/x86_64/LibreOffice_${LO_VERSION}_Linux_x86-64_deb.tar.gz" \
    && mkdir /tmp/lo && tar -xzf /tmp/lo.tar.gz -C /tmp/lo --strip-components=1 \
    && dpkg -i /tmp/lo/DEBS/*.deb \
    && ln -sf /opt/libreoffice${LO_SERIES}/program/soffice /usr/local/bin/soffice \
    && rm -rf /tmp/lo /tmp/lo.tar.gz /var/lib/apt/lists/* \
    && soffice --version

# Path binary LibreOffice untuk App\Services\DocxToPdf ("soffice" = symlink di atas).
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
# chmod a+rX: file yang disalin dari Windows lewat SMB sering mendarat 770
# root:root, sehingga nginx/php-fpm (www-data) tidak bisa membaca
# public/index.php -> 403/404 (2026-09-16, kejadian nyata saat deploy NAS).
RUN sed -i 's/\r$//' /usr/local/bin/kjpp-entrypoint \
    && chmod +x /usr/local/bin/kjpp-entrypoint \
    && chmod -R a+rX /var/www/html \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/kjpp-entrypoint"]
