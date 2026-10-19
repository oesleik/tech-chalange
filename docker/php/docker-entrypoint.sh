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

envsubst '${NEW_RELIC_ENABLED} ${NEW_RELIC_APP_NAME} ${NEW_RELIC_LICENSE_KEY} ${NEW_RELIC_LOG_FORWARDING} ${APP_ENV}' \
    < /usr/local/etc/newrelic/newrelic.ini.template \
    > /usr/local/etc/php/conf.d/zz-newrelic-custom.ini

exec docker-php-entrypoint "$@"
