FROM php:8.2-apache

# Sistem bağımlılıkları
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# PHP uzantıları
RUN docker-php-ext-install pdo pdo_sqlite zip opcache

# Apache mod_rewrite
RUN a2enmod rewrite headers

# Composer kurulumu
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache yapılandırması
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/polyx.conf \
    && a2enconf polyx

# Document root ayarı
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP optimizasyonu
RUN echo 'opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=4000\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=60' > /usr/local/etc/php/conf.d/opcache.ini

# Timezone
ENV TZ=Europe/Istanbul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Çalışma dizini
WORKDIR /var/www/html

# Uygulama dosyalarını kopyala
COPY . .

# Composer bağımlılıkları
RUN composer install --no-dev --optimize-autoloader

# İzinler
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Data dizini oluştur
RUN mkdir -p /var/www/html/data && chown www-data:www-data /var/www/html/data

EXPOSE 80

# Sağlık kontrolü
HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/api.php?action=info || exit 1

CMD ["apache2-foreground"]
