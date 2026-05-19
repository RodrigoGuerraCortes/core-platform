# Identity/Auth Curl Scripts

Reusable shell scripts for testing the Identity/Auth API endpoints from the terminal and for Postman reference.

## Dependencies

- **curl** (required)
- **jq** (optional, recommended for token extraction)

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| `CORE_BASE_URL` | `http://localhost:8010` | Base URL of the API |
| `CORE_AUTH_EMAIL` | `rguerracortes@gmail.com` | Email for authentication |
| `CORE_AUTH_PASSWORD` | `ChangeMe123!` | Password for authentication |
| `CORE_TOKEN_NAME` | `postman-local` | Name for the issued token |
| `CORE_TOKEN_FILE` | `/tmp/core-platform-token.txt` | File path to store the Bearer token |

## Usage

### 1. Issue a Token

```bash
CORE_AUTH_PASSWORD='ChangeMe123!' ./scripts/http/identity-auth/issue-token.sh
```

This script:
- Calls `POST /auth/token`
- Extracts the token from the JSON response using `jq`
- Saves the token to `/tmp/core-platform-token.txt`

### 2. Get Current User

```bash
./scripts/http/identity-auth/me.sh
```

This script:
- Reads the token from `/tmp/core-platform-token.txt`
- Calls `GET /auth/me`
- Prints the JSON response

### 3. Revoke Current Token

```bash
./scripts/http/identity-auth/revoke-current-token.sh
```

This script:
- Reads the token from `/tmp/core-platform-token.txt`
- Calls `DELETE /auth/token/current`
- Prints the JSON response

### 4. Verify Revoked Token

```bash
./scripts/http/identity-auth/verify-revoked-token.sh
```

This script:
- Reads the (now revoked) token from `/tmp/core-platform-token.txt`
- Calls `GET /auth/me`
- Expects HTTP 401 and prints the result

## Token Storage

The token is stored in `/tmp/core-platform-token.txt` (configurable via `CORE_TOKEN_FILE`).

- The token file is **not** automatically deleted after revocation.
- The `verify-revoked-token.sh` script reuses the same token to confirm it is rejected.
- To clear the token file manually:

```bash
rm /tmp/core-platform-token.txt
```

## Postman Integration

You can use the same curl commands in Postman's "Code" feature to generate equivalent requests.

## Example Full Flow

```bash
# 1. Issue a token
CORE_AUTH_PASSWORD='ChangeMe123!' ./scripts/http/identity-auth/issue-token.sh

# 2. Verify the token works
./scripts/http/identity-auth/me.sh

# 3. Revoke the token
./scripts/http/identity-auth/revoke-current-token.sh

# 4. Verify the token is rejected
./scripts/http/identity-auth/verify-revoked-token.sh
```
