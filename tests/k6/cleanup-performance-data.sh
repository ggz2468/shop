#!/usr/bin/env bash
set -euo pipefail

LARADOCK_DIR="${LARADOCK_DIR:-../laradock}"
WORKSPACE_DIR="${WORKSPACE_DIR:-/var/www/shop-api}"
APP_ENV_NAME="${APP_ENV_NAME:-performance}"
CLEANUP_MODE="${CLEANUP_MODE:-migrate_fresh}"

if [[ ! -d "${LARADOCK_DIR}" ]]; then
    echo "Laradock directory not found: ${LARADOCK_DIR}" >&2
    exit 1
fi

cd "${LARADOCK_DIR}"

if [[ "${CLEANUP_MODE}" == "db_wipe" ]]; then
    docker compose exec --user=laradock workspace bash -lc "cd ${WORKSPACE_DIR} && php artisan db:wipe --drop-views --env='${APP_ENV_NAME}' --force"
else
    # Keep schema ready for next run but clear all test data.
    docker compose exec --user=laradock workspace bash -lc "cd ${WORKSPACE_DIR} && php artisan migrate:fresh --env='${APP_ENV_NAME}' --force"
fi

echo "Performance database cleanup completed (${APP_ENV_NAME}, mode=${CLEANUP_MODE})."
