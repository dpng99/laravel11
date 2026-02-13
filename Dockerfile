FROM php:8.4-fpm

## Daffa Sukaphorn Pukhirapat was here ##

# 1. Install Dependencies System (Termasuk git & unzip yg wajib untuk Composer)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP Extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd mysqli intl

# 3. --- BAGIAN PENTING: INSTALL COMPOSER ---
# Kita menyalin Composer dari image resminya langsung
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set Work Directory
WORKDIR /var/www/html

# 5. Copy file project
COPY . .

# 6. Set permission folder agar bisa diedit
RUN chown -R www-data:www-data /var/www/html

# 7. Expose Port
EXPOSE 9000

# 8. Jalankan PHP-FPM
CMD ["php-fpm"]