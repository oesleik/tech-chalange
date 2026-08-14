FROM php:8.4-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configura e instala extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        xml

RUN pecl install pcov
RUN docker-php-ext-enable pcov

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

# Copia composer.json primeiro para aproveitar cache de layers
COPY --chown=www-data:www-data composer.json composer.lock* ./

# removido --no-dev para ter todas as ferramentas disponiveis no K8s
RUN composer install --optimize-autoloader --no-scripts
# RUN composer install --no-dev --optimize-autoloader --no-scripts


# Copia o restante do código da aplicação
COPY --chown=www-data:www-data . .

# gera o openapi.json durante o build para que fique dentro da imagem
RUN vendor/bin/openapi src -o public/openapi.json 2>/dev/null || true

EXPOSE 9000

CMD ["php-fpm"]
