#!/usr/bin/env bash
set -euo pipefail

# revoke-current-token.sh
# Revokes the current Sanctum token using DELETE /auth/token/current.
#
# Environment variables (with defaults):
#   CORE_BASE_URL   (default: http://localhost:8010)
#   CORE_TOKEN_FILE (default: /tmp/core-platform-token.txt)

BASE_URL="${CORE_BASE_URL:-http://localhost:8010}"
TOKEN_FILE="${CORE_TOKEN_FILE:-/tmp/core-platform-token.txt}"

if [ ! -f "${TOKEN_FILE}" ]; then
    echo "ERROR: Token file not found at ${TOKEN_FILE}"
    echo "Run issue-token.sh first to obtain a token."
    exit 1
fi

TOKEN=$(cat "${TOKEN_FILE}")

echo "Revoking current token ..."
echo ""

curl -s \
    -X DELETE "${BASE_URL}/auth/token/current" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}" \
    | jq . 2>/dev/null || curl -s \
    -X DELETE "${BASE_URL}/auth/token/current" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}"
