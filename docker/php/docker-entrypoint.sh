#!/bin/sh
# Renderiza o ini do New Relic e só então sobe o PHP-FPM.
# Não usar set -x: a license key está no ambiente.
set -eu

NEW_RELIC_ENABLED="${NEW_RELIC_ENABLED:-false}"
NEW_RELIC_APP_NAME="${NEW_RELIC_APP_NAME:-tech-challenge-local}"
NEW_RELIC_LICENSE_KEY="${NEW_RELIC_LICENSE_KEY:-}"
NEW_RELIC_LOG_FORWARDING="${NEW_RELIC_LOG_FORWARDING:-true}"
APP_ENV="${APP_ENV:-production}"

export NEW_RELIC_ENABLED NEW_RELIC_APP_NAME NEW_RELIC_LICENSE_KEY NEW_RELIC_LOG_FORWARDING APP_ENV

# conf.d é do www-data. O job de teste do CI entra com o uid do runner e não
# consegue reescrever o arquivo de lá. /tmp é gravável para qualquer uid.
# PHP_INI_SCAN_DIR começando com ":" mantém o diretório compilado e acrescenta o outro.
INI_FILE=/usr/local/etc/php/conf.d/zz-newrelic-custom.ini
if [ ! -w "$INI_FILE" ]; then
    mkdir -p /tmp/php-ini
    INI_FILE=/tmp/php-ini/zz-newrelic-custom.ini
    case ":${PHP_INI_SCAN_DIR:-}:" in
        *:/tmp/php-ini:*) ;;
        *)
            if [ -z "${PHP_INI_SCAN_DIR:-}" ]; then
                PHP_INI_SCAN_DIR=":/tmp/php-ini"
            else
                PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR}:/tmp/php-ini"
            fi
            export PHP_INI_SCAN_DIR
            ;;
    esac
fi

envsubst '${NEW_RELIC_ENABLED} ${NEW_RELIC_APP_NAME} ${NEW_RELIC_LICENSE_KEY} ${NEW_RELIC_LOG_FORWARDING} ${APP_ENV}' \
    < /usr/local/etc/newrelic/newrelic.ini.template \
    > "$INI_FILE"

exec docker-php-entrypoint "$@"
