#!/usr/bin/env bash
set -euo pipefail

# verify-revoked-token.sh
# Calls GET /auth/me with the (now revoked) token to verify that
# the token is no longer accepted.
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

echo "Verifying that the revoked token is rejected ..."
echo ""

RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X GET "${BASE_URL}/auth/me" \
    -H "Accept: application/json" \
    -H "Authorization: Bearer ${TOKEN}")

HTTP_STATUS=$(echo "${RESPONSE}" | tail -n1)
BODY=$(echo "${RESPONSE}" | sed '$d')

echo "HTTP Status: ${HTTP_STATUS}"
echo "Response body:"
echo "${BODY}" | jq . 2>/dev/null || echo "${BODY}"
echo ""

if [ "${HTTP_STATUS}" -eq 401 ]; then
    echo "SUCCESS: Token correctly rejected (HTTP 401)."
else
    echo "UNEXPECTED: Expected HTTP 401, got ${HTTP_STATUS}."
    exit 1
fi
