#!/usr/bin/env bash
set -euo pipefail

# issue-token.sh
# Issues a Sanctum personal access token and saves it to a file for later use.
#
# Environment variables (with defaults):
#   CORE_BASE_URL      (default: http://localhost:8010)
#   CORE_AUTH_EMAIL    (default: rguerracortes@gmail.com)
#   CORE_AUTH_PASSWORD (default: ChangeMe123!)
#   CORE_TOKEN_NAME    (default: postman-local)
#   CORE_TOKEN_FILE    (default: /tmp/core-platform-token.txt)

BASE_URL="${CORE_BASE_URL:-http://localhost:8010}"
EMAIL="${CORE_AUTH_EMAIL:-rguerracortes@gmail.com}"
PASSWORD="${CORE_AUTH_PASSWORD:-ChangeMe123!}"
TOKEN_NAME="${CORE_TOKEN_NAME:-postman-local}"
TOKEN_FILE="${CORE_TOKEN_FILE:-/tmp/core-platform-token.txt}"

echo "Issuing token for ${EMAIL} ..."

# Make the API call
RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST "${BASE_URL}/auth/token" \
    -H "Accept: application/json" \
    -H "Content-Type: application/json" \
    -d "$(cat <<EOF
{
  "email": "${EMAIL}",
  "password": "${PASSWORD}",
  "token_name": "${TOKEN_NAME}"
}
EOF
)")

# Extract HTTP status code (last line)
HTTP_STATUS=$(echo "${RESPONSE}" | tail -n1)
# Extract response body (everything except last line)
BODY=$(echo "${RESPONSE}" | sed '$d')

if [ "${HTTP_STATUS}" -ne 200 ]; then
    echo "ERROR: Token issuance failed (HTTP ${HTTP_STATUS})"
    echo "${BODY}"
    exit 1
fi

# Try to extract the token using jq
if command -v jq &> /dev/null; then
    TOKEN=$(echo "${BODY}" | jq -r '.data.token // empty')
    if [ -z "${TOKEN}" ]; then
        echo "ERROR: Could not extract token from response."
        echo "${BODY}"
        exit 1
    fi
    echo "${TOKEN}" > "${TOKEN_FILE}"
    echo "Token saved to ${TOKEN_FILE}"
    echo "Token issued successfully."
else
    echo "WARNING: jq is not installed. Cannot auto-save token."
    echo "Full response:"
    echo "${BODY}"
    echo ""
    echo "To install jq:"
    echo "  sudo apt-get install jq   # Debian/Ubuntu"
    echo "  brew install jq           # macOS"
    exit 1
fi
