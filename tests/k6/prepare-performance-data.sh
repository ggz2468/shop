#!/usr/bin/env bash
set -euo pipefail

LARADOCK_DIR="${LARADOCK_DIR:-../laradock}"
WORKSPACE_DIR="${WORKSPACE_DIR:-/var/www/shop-api}"
APP_ENV_NAME="${APP_ENV_NAME:-performance}"
SEEDER_CLASS="${SEEDER_CLASS:-Database\\Seeders\\PerformanceSeeder}"

if [[ ! -d "${LARADOCK_DIR}" ]]; then
    echo "Laradock directory not found: ${LARADOCK_DIR}" >&2
    exit 1
fi

cd "${LARADOCK_DIR}"

docker compose exec --user=laradock workspace bash -lc "cd ${WORKSPACE_DIR} && php artisan migrate:fresh --seed --seeder='${SEEDER_CLASS}' --env='${APP_ENV_NAME}' --force"

echo "Performance database has been rebuilt and seeded (${APP_ENV_NAME})."
