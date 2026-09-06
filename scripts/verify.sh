#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd -P)"
NEXTCLOUD_APP_PATH='/var/www/html/apps-extra/health'

fail() {
	echo "Error: $*" >&2
	exit 1
}

section() {
	echo
	echo "==> $1"
}

find_compose_root() {
	local directory="$REPO_ROOT"
	while [[ "$directory" != '/' ]]; do
		if [[ -f "$directory/docker-compose.yml" || -f "$directory/docker-compose.yaml" || -f "$directory/compose.yml" || -f "$directory/compose.yaml" ]]; then
			printf '%s\n' "$directory"
			return
		fi
		directory="$(dirname "$directory")"
	done
	return 1
}

command -v docker >/dev/null 2>&1 || fail 'Docker is not installed or is not available on PATH.'
docker info >/dev/null 2>&1 || fail 'Docker is unavailable. Start Docker and ensure the current user can access its daemon.'
COMPOSE_ROOT="$(find_compose_root)" || fail 'Could not find a Docker Compose project above the Health repository.'

compose() {
	(cd "$COMPOSE_ROOT" && docker compose "$@")
}

COMPOSE_SERVICES="$(compose config --services)"
grep -Fxq nextcloud <<<"$COMPOSE_SERVICES" || fail 'The Docker Compose project does not define a nextcloud service.'
RUNNING_SERVICES="$(compose ps --status running --services)"
grep -Fxq nextcloud <<<"$RUNNING_SERVICES" || fail 'The nextcloud Compose service is not running. Start it before verification.'

run_nextcloud() {
	compose exec -T nextcloud sh -lc "cd '$NEXTCLOUD_APP_PATH' && $1"
}

run_nextcloud 'test -d . || { echo "Health repository is unavailable in the nextcloud container." >&2; exit 1; }
	command -v composer >/dev/null || { echo "Composer is unavailable in the nextcloud container." >&2; exit 1; }
	test -f vendor/autoload.php || { echo "PHP dependencies are missing. Run composer install in the nextcloud container." >&2; exit 1; }'

[[ -x "$REPO_ROOT/vendor/bin/psalm" ]] || fail 'Psalm dependencies are missing. Run composer install in the nextcloud container.'

cd "$REPO_ROOT"

section "PHP coding standards"
run_nextcloud 'composer run cs:check'

section "Psalm (PHP 8.2)"
docker run --rm \
	-v "$REPO_ROOT":/app \
	-w /app \
	php:8.2-cli \
	php vendor/bin/psalm \
		--threads=1 \
		--no-cache \
		--monochrome \
		--no-progress

section "PHP unit tests"
run_nextcloud 'composer run test:unit -- --do-not-cache-result'

section "OpenAPI generation"
OPENAPI_FILES=("$REPO_ROOT"/openapi*.json)
OPENAPI_BEFORE="$(git hash-object -- "${OPENAPI_FILES[@]}")"
run_nextcloud 'composer run openapi'

section "Generated OpenAPI artifacts"
OPENAPI_AFTER="$(git hash-object -- "${OPENAPI_FILES[@]}")"
if [[ "$OPENAPI_BEFORE" != "$OPENAPI_AFTER" ]]; then
	echo 'OpenAPI generation changed an artifact during verification.' >&2
	echo 'Regenerate openapi*.json and include the result in the change, then rerun verification.' >&2
	exit 1
fi

section "Frontend verification (Node 24)"
docker run --rm \
	-u "$(id -u):$(id -g)" \
	-e HOME=/tmp \
	-e NPM_CONFIG_CACHE=/tmp/npm-cache \
	-v "$REPO_ROOT":/app \
	-w /app \
	node:24-bookworm \
	sh -lc 'npm ci && npm run typecheck && npm run lint && npm run stylelint && npm run build'

section "Whitespace"
git diff --check

section "Git status"
git status --short
