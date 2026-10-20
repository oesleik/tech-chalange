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
    gettext-base \
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

# extensao OpenTelemetry — captura automatica de traces, metricas e logs
RUN pecl install opentelemetry
RUN docker-php-ext-enable opentelemetry

# New Relic PHP Agent (glibc). O tarball linux inclui x86_64 e aarch64;
# o instalador escolhe a arquitetura do build. A license key não entra na imagem.
ARG NEWRELIC_VERSION=12.11.0.40
ARG NEWRELIC_PLATFORM=linux

RUN set -eux; \
    curl -fsSL "https://download.newrelic.com/php_agent/archive/${NEWRELIC_VERSION}/newrelic-php5-${NEWRELIC_VERSION}-${NEWRELIC_PLATFORM}.tar.gz" -o /tmp/newrelic.tar.gz; \
    tar -xzf /tmp/newrelic.tar.gz -C /tmp; \
    cd /tmp/newrelic-php5-*; \
    NR_INSTALL_SILENT=1 NR_INSTALL_USE_CP_NOT_LN=1 ./newrelic-install install; \
    rm -rf /tmp/newrelic*; \
    sed -i 's|^newrelic.license = .*|;newrelic.license = ""|' "$(php-config --ini-dir)/newrelic.ini" || true; \
    grep -Eq '^[[:space:]]*clear_env[[:space:]]*=[[:space:]]*no[[:space:]]*$' /usr/local/etc/php-fpm.d/docker.conf

COPY docker/php/newrelic.ini.template /usr/local/etc/newrelic/newrelic.ini.template
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-php-newrelic-entrypoint

# O processo roda como www-data: o entrypoint precisa reescrever o ini
# e o daemon precisa de um diretório de log gravável.
# O CI sobrescreve o usuário (`docker compose run --user`); nesse caso o
# entrypoint grava o ini em /tmp e acrescenta esse diretório ao scan do PHP.
RUN set -eux; \
    chmod 755 /usr/local/bin/docker-php-newrelic-entrypoint; \
    mkdir -p /var/log/newrelic; \
    touch /usr/local/etc/php/conf.d/zz-newrelic-custom.ini; \
    chown www-data:www-data /var/log/newrelic /usr/local/etc/php/conf.d/zz-newrelic-custom.ini

ENV NEW_RELIC_ENABLED=false \
    NEW_RELIC_APP_NAME=tech-challenge-local \
    NEW_RELIC_LICENSE_KEY="" \
    NEW_RELIC_LOG_FORWARDING=true

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

# assets publicos precisam ser legiveis pelo nginx
RUN chmod -R a+rX public

EXPOSE 9000

ENTRYPOINT ["docker-php-newrelic-entrypoint"]
CMD ["php-fpm"]
