# Docker Runtime Ownership — Laravel on Bind-Mounted Volumes

**Block:** 8.1 — SPA Authentication Foundation  
**Status:** Active — Production Standard  
**Date:** 2026-05-24

---

## Overview

This document describes the runtime filesystem ownership strategy for Core
Platform's Docker-based development and production environments. It documents
a class of failure discovered during Sanctum SPA integration, its root cause,
the architectural fix applied, and the operational rules that must be followed
permanently.

---

## The Problem

### Symptom

After integrating Sanctum SPA authentication, the login endpoint and the
Filament admin panel both returned HTTP 500. The Laravel log showed:

```
ErrorException: tempnam(): file created in the system's temporary directory
```

This originated inside Blade's view compiler when attempting to write a compiled
view file. It was not a warning — it was an exception.

### Why a warning became an exception

Laravel's `HandleExceptions` handler converts PHP `E_WARNING` level errors into
`ErrorException` instances. `tempnam()` emits `E_WARNING` when it falls back
from the requested directory to the system temp directory. In a Laravel
application with `APP_DEBUG=true`, this crashes the request with HTTP 500.

In production with `APP_DEBUG=false`, the exception is still thrown — it is
merely not displayed to the user. The request still fails.

### Sequence of events

```
Blade::compileString() needs to write compiled view
  → tempnam(storage/framework/views, 'laravel-') called
  → OS checks write permission for www-data on storage/framework/views/
  → PERMISSION DENIED (directory owned by uid 1000 or root, mode 775)
  → tempnam() falls back to /tmp
  → PHP emits E_WARNING
  → HandleExceptions converts to ErrorException
  → Request returns HTTP 500
```

---

## Root Cause

### PHP-FPM runs as `www-data`

The PHP-FPM pool is configured with:

```ini
user = www-data
group = www-data
```

`www-data` has uid=33 on Debian/Ubuntu-based images (`php:8.3-fpm`).

### Bind-mounted volumes are owned by the host user

Docker bind mounts preserve the host filesystem ownership. On Linux, the
developer's user is typically uid=1000. The repository was initialised on the
host, so all files and directories — including `storage/` and `bootstrap/cache/`
— are owned by uid=1000.

`www-data` (uid=33) is not uid=1000. The directories have `drwxrwxr-x` (mode
775), which gives "other" (`r-x`) — read and execute, but **no write**.

### Running `artisan` as root further corrupts ownership

`docker compose exec app php artisan ...` executes as root (uid=0) by default.
Every file written during that command — compiled views, `bootstrap/cache/packages.php`,
`bootstrap/cache/services.php`, framework cache — is owned by root with mode 755.

root-owned files are readable by `www-data` but **not writable**. Once root
writes a compiled view, `www-data` cannot overwrite it when the view source
changes.

### Mixed ownership state

After normal development operations, the runtime directories accumulate files
from three different owners:

| Owner | Source |
|---|---|
| uid=1000 | Host git checkout, `docker cp`, volume mount |
| uid=0 (root) | `docker compose exec app php artisan ...` |
| uid=33 (www-data) | PHP-FPM process writing at request time |

`www-data` can only write to files it owns. Any file owned by root or uid=1000
becomes a read-only fixture from `www-data`'s perspective, even if the directory
permissions appear permissive.

---

## Why This Surfaced During Sanctum Integration

Prior to Sanctum integration, the most common runtime path was API-only JSON
responses. These routes don't compile Blade views — they return `JsonResponse`
objects directly. The broken permissions existed throughout development but were
never exercised.

Sanctum integration triggered:

1. **Filament admin panel** — Filament renders many Blade views. Any uncached
   view triggers a write to `storage/framework/views/`.
2. **`POST /api/auth/login`** — The login error response path renders a Blade
   view in some error handling paths.
3. **Session writes** — Sanctum SPA sessions are stored in the database
   (correct), but session startup itself touches framework files.

The permission fault was latent from the beginning. Sanctum made it critical.

---

## Required Writable Directories

Laravel requires write access to the following directories at runtime. Any
process that serves HTTP requests, runs queue workers, compiles views, or writes
logs must have write permission to all of them.

```
storage/
├── app/              # application file storage (uploads, exports, etc.)
│   └── public/       # publicly-linked storage
├── framework/
│   ├── cache/        # Laravel framework cache (not application cache)
│   │   └── data/     # cache store if driver is 'file'
│   ├── sessions/     # session files (if SESSION_DRIVER=file)
│   ├── testing/      # test-only storage
│   └── views/        # Blade compiled views ← most commonly broken
└── logs/             # application logs (laravel.log, etc.)

bootstrap/
└── cache/            # package manifest, service provider cache, route cache
    ├── packages.php
    ├── services.php
    ├── routes-v7.php  # php artisan route:cache
    └── config.php     # php artisan config:cache
```

**All of these directories and their contents must be writable by `www-data`.**
This is not optional. Each directory serves a runtime function:

| Directory | Written by | Written when |
|---|---|---|
| `storage/framework/views/` | PHP-FPM | First request after view changes |
| `storage/framework/cache/` | PHP-FPM + queue workers | Cache reads/writes |
| `storage/framework/sessions/` | PHP-FPM | Each request (if file driver) |
| `storage/logs/` | PHP-FPM + queue workers + CLI | Every log write |
| `bootstrap/cache/` | `artisan` commands | Config/route/view cache |

---

## The Fix

### Layer 1 — Entrypoint script (`infra/docker/backend/entrypoint.sh`)

The container entrypoint runs before PHP-FPM starts on **every container boot**:

```sh
#!/bin/sh
set -e

APP_DIR=/var/www/html

chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

chmod -R ug+rwX \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

exec "$@"
```

**Why this is the right layer:**

- It runs as root (the container default), which has permission to change
  ownership of any file regardless of current owner
- It runs before any HTTP request is served — PHP-FPM starts in a clean state
- It is idempotent — running it twice has no side effects
- It handles all sources of ownership drift: host mounts, prior artisan runs,
  Docker layer writes
- It does not require any developer action after `docker compose up`

**Why it uses `chmod ug+rwX` not `chmod 777`:**

`chmod -R 777` would make runtime files world-writable, which is a security
risk — any process on the container can modify them. `ug+rwX` restricts write
to the owner (`www-data`) and group (`www-data`). The `X` flag applies execute
permission only to directories, not plain files.

### Layer 2 — Dockerfile (`infra/docker/backend/Dockerfile`)

```dockerfile
# /tmp uses sticky-bit permissions so processes with different UIDs can each
# create temp files without interfering with each other.
RUN mkdir -p /tmp && chmod 1777 /tmp

COPY infra/docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
```

### Layer 3 — Makefile wrappers

All `artisan` invocations that may write runtime files must use `-u www-data`:

```makefile
# Correct — writes files as www-data
artisan:
    docker compose exec -u www-data app php artisan $(CMD)

migrate:
    docker compose exec -u www-data app php artisan migrate

# Emergency repair — runs as root to fix ownership
fix-permissions:
    docker compose exec app sh -c "\
        chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
        chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache && \
        echo 'Runtime permissions fixed.'"
```

The `fix-permissions` target exists for emergency recovery. It must not be a
substitute for the entrypoint approach — it only repairs the running container,
not future containers.

---

## Operational Rules

These rules are permanent. Violations create runtime instability that may be
non-obvious and delayed.

### Rule 1 — Never run `artisan` as root

```bash
# FORBIDDEN
docker compose exec app php artisan migrate

# CORRECT
docker compose exec -u www-data app php artisan migrate
# or
make migrate
```

Root-owned files in `bootstrap/cache/` are not writable by `www-data`. Once
`services.php` or `packages.php` is root-owned, PHP-FPM cannot update them
when dependencies change. The application continues to serve stale cache.

### Rule 2 — Use Makefile wrappers for all artisan commands

The Makefile `artisan` wrapper enforces `-u www-data` so developers cannot
accidentally create root-owned runtime files. Adding new development commands
to the Makefile is preferred over direct `docker compose exec` invocations.

### Rule 3 — Ownership drift is runtime corruption

Mixed ownership in runtime directories is a corruption state, not a degraded
state. The application may appear to work for some request paths while silently
failing others. Treat any ownership inconsistency in `storage/` or
`bootstrap/cache/` as a blocking issue.

### Rule 4 — After `git pull` or dependency changes, check ownership

Any operation that modifies files in `bootstrap/cache/` outside the container
(e.g. host-side `composer install`, copying vendor files) may introduce
uid=1000 owned files. Run `make fix-permissions` or restart the container to
re-run the entrypoint.

### Rule 5 — Never use `chmod 777` on runtime directories

World-writable directories allow any container process to modify application
cache, compiled views, and log files. Use `ug+rwX` with `www-data:www-data`
ownership instead.

---

## `/tmp` and `tempnam()`

PHP's `tempnam()` function creates temporary files during Blade compilation. It
first attempts the requested directory (`storage/framework/views/`). If that
fails, it falls back to the system temporary directory (`/tmp`).

The fallback emits `E_WARNING`. In Laravel, this becomes `ErrorException`.

`/tmp` is set to `chmod 1777` (sticky bit) in the Dockerfile. This allows any
user to create files in `/tmp` without the directory being a free-for-all for
deletion. The sticky bit ensures each process can only delete its own files.

However, relying on `/tmp` for Blade compilation is a misconfiguration signal.
Blade compiled views landing in `/tmp` means:

1. Container restarts lose all compiled views (cold boot performance regression)
2. Multiple FPM workers may race on the same temp file
3. The `tempnam()` warning log is a permanent noise source

**The correct state is that `/tmp` is never used for Blade compilation.** The
entrypoint fix ensures this.

---

## Production Considerations

### Railway

Railway deploys containers from Docker images. The entrypoint script runs on
every deploy and container restart. There are no bind mounts in Railway — all
files come from the built image. This means uid=1000 drift does not occur in
production, but root-owned files written during `railway run artisan migrate`
can still happen if the command runs as root.

The entrypoint guards against this by running `chown` on every start. Even if
`railway run` writes root-owned files to a persistent volume, the next container
boot corrects them before PHP-FPM accepts requests.

### Kubernetes

Kubernetes supports `securityContext.runAsUser` and `fsGroup` at the pod level.
For a cleaner long-term solution, set:

```yaml
securityContext:
  runAsUser: 33    # www-data
  runAsGroup: 33
  fsGroup: 33
```

With this configuration, the OS automatically chowns mounted volumes to the
specified `fsGroup` on mount. The entrypoint `chown` becomes redundant but
remains harmless.

Until Kubernetes is in use, the entrypoint approach is the correct solution.

### Read-only filesystems

Some container hardening configurations mount the filesystem as read-only
(`readOnlyRootFilesystem: true` in Kubernetes). Laravel cannot run on a
fully read-only filesystem — it needs write access to `storage/` and
`bootstrap/cache/`. In read-only configurations:

- Mount `storage/` and `bootstrap/cache/` as writable `emptyDir` or
  persistent volumes
- Pre-compile views during the Docker build (`php artisan view:cache`) to
  reduce runtime write requirements
- Pre-cache config and routes (`php artisan optimize`) for the same reason

Read-only filesystem support is a future concern. The current architecture does
not support it.

---

## Multi-Process / Multi-Container Risks

### Queue workers

Queue workers (`php artisan queue:work`) run as a separate process. If started
with `docker compose exec app php artisan queue:work` (root), they write
job-related cache and log files as root. Use `-u www-data` or a dedicated
worker container that inherits `www-data` ownership.

### Horizon

Laravel Horizon manages queue workers. Its dashboard reads from the cache driver.
Horizon should run under the same user as PHP-FPM. If Horizon runs in a
separate container, ensure it shares the same `www-data` user and has write
access to the same runtime directories.

### Octane (FrankenPHP / Swoole / RoadRunner)

Laravel Octane changes the process model: the application boots once and serves
many requests. Octane may precompile views at startup. If the Octane process
cannot write to `storage/framework/views/`, startup fails. The entrypoint fix
applies equally. Octane also uses `storage/framework/cache/` for its internal
state — ownership consistency is equally critical.

### Multiple containers sharing a volume

If multiple containers share the same `storage/` volume (e.g. web + queue worker
as separate services pointing to the same persistent volume), both must run as
`www-data` with write access. Mixed-user containers on a shared volume will
create ownership drift at runtime. Use a shared `fsGroup` (Kubernetes) or
ensure all containers use the same uid.

---

## Final State (as of Block 8.1)

| Component | State |
|---|---|
| `storage/` ownership | `www-data:www-data` on every container boot |
| `bootstrap/cache/` ownership | `www-data:www-data` on every container boot |
| `/tmp` permissions | `1777` (sticky bit) |
| Entrypoint script | Active — runs before PHP-FPM |
| Blade compilation | Writes to `storage/framework/views/` correctly |
| Session (database driver) | Writes to `sessions` table — no filesystem dependency |
| Filament admin | Renders Blade views correctly |
| `POST /api/auth/login` | HTTP 200 with session cookie |
| `GET /api/auth/me` | HTTP 200 with authenticated user |
| `POST /api/auth/logout` | HTTP 200, session invalidated |
| Backend test suite | 247/247 passing |
| Frontend test suite | 106/106 passing |

---

## Related

- `infra/docker/backend/entrypoint.sh`
- `infra/docker/backend/Dockerfile`
- `Makefile` (`artisan`, `fix-permissions`, `migrate` targets)
- `docs/adr/ADR-012-sanctum-stateful-api-middleware-ordering.md`
- `config/session.php` — `SESSION_DRIVER=database` (avoids file-based session ownership issues)
