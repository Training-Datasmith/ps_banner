#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
# Plan digest is arm/v6; on linux/amd64 hosts use the official amd64 manifest for php:7.1-cli-alpine (PHP 7.1.33).
PHP_IMAGE_DIGEST="sha256:2fbf149aa1e9ab4fda5e6a82261114543cc3c2419f46bb20d33cc01345ebd7c8"
PHP_IMAGE_PLATFORM="linux/amd64"
PHPUNIT_URL="https://phar.phpunit.de/phpunit-7.5.20.phar"
PHPUNIT_ASC_URL="https://phar.phpunit.de/phpunit-7.5.20.phar.asc"
PHPUNIT_PHAR="/tmp/phpunit-7.5.20.phar"
PHPUNIT_ASC="/tmp/phpunit-7.5.20.phar.asc"
PHPUNIT_KEY_FPR="D8406D0D82947747293778314AA394086372C20A"
RANDOM_ORDER_SEED="20261008"

run_in_php() {
  if command -v docker >/dev/null 2>&1 && (docker info >/dev/null 2>&1 || sudo docker info >/dev/null 2>&1); then
    sudo docker run --rm --platform "${PHP_IMAGE_PLATFORM}" \
      -v "${REPO_ROOT}:/app" \
      -v "${PHPUNIT_PHAR}:${PHPUNIT_PHAR}:ro" \
      -w /app/tests \
      "php@${PHP_IMAGE_DIGEST}" php "$@"
    return
  fi
  if [[ -x /usr/local/bin/php7.1 ]]; then
    /usr/local/bin/php7.1 "$@"
    return
  fi
  php "$@"
}

ensure_phpunit_phar() {
  if [[ ! -f "${PHPUNIT_PHAR}" ]]; then
    curl -fsSL -o "${PHPUNIT_PHAR}" "${PHPUNIT_URL}"
    curl -fsSL -o "${PHPUNIT_ASC}" "${PHPUNIT_ASC_URL}"
  fi
  if ! command -v gpg >/dev/null 2>&1; then
    echo "gpg is required to verify ${PHPUNIT_PHAR}" >&2
    exit 1
  fi
  gpg --batch --quiet --keyserver hkps://keyserver.ubuntu.com --recv-keys "${PHPUNIT_KEY_FPR}" >/dev/null 2>&1 || true
  gpg --batch --verify "${PHPUNIT_ASC}" "${PHPUNIT_PHAR}"
}

ensure_phpunit_phar

cd "${REPO_ROOT}/tests"

echo "PHP version:"
run_in_php -v

run_suite() {
  local config="$1"
  shift
  run_in_php "${PHPUNIT_PHAR}" -c "${config}" "$@"
}

for config in phpunit.xml phpunit-ps17.xml; do
  for _ in 1 2; do
    run_suite "${config}"
  done
  for _ in 1 2; do
    run_suite "${config}" --order-by=random --random-order-seed="${RANDOM_ORDER_SEED}"
  done
done
