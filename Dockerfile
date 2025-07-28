FROM php:8.2-fpm

# Installe les dépendances système
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    nano \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définit le répertoire de travail
WORKDIR /var/www

# Copie les fichiers Laravel
COPY . .

# Installe les dépendances Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Donne les bonnes permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

EXPOSE 8000

# Démarre Laravel avec le serveur intégré
CMD php artisan serve --host=0.0.0.0 --port=8000
