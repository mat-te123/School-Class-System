FROM dunglas/frankenphp:latest-php8.3

# Install dependensi sistem, SQLite, dan ekstensi PHP yang dibutuhkan Laravel 12
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install ekstensi PHP (termasuk pdo_sqlite dan pdo_pgsql untuk database SQLite & PostgreSQL)
RUN docker-php-ext-install gd zip bcmath pcntl opcache pdo_sqlite pdo_pgsql pgsql

# Install Composer versi terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set direktori kerja
WORKDIR /var/www/html

EXPOSE 8080
