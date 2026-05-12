# Bootstrap Hardening Summary

## Changes Applied

- **scripts/setup.sh**: Removed host PHP/composer dependency. All backend commands now run inside Docker containers via `docker compose exec`. Removed dynamic Filament installation (composer require). Dependencies are assumed to be declared in composer.json/package.json. Validation now only requires docker, docker compose, node, npm.
- **scripts/dev.sh**: Removed `php artisan serve` (host-side). Backend runtime now relies on Docker containers (nginx + php-fpm). Frontend Vite dev server remains host-side. Queue worker runs inside container.
- **scripts/lint.sh**: Removed `|| true` suppression. Now fails on Pint or PHPStan errors. Runs inside Docker container.
- **infra/docker/backend/Dockerfile**: Created PHP-FPM Dockerfile for Laravel application.
- **infra/docker/nginx/default.conf**: Created Nginx configuration for serving Laravel via php-fpm.

## Docker-First Improvements

- Backend runtime is fully containerized (nginx + php-fpm).
- No host PHP required for operational workflows.
- Composer install, migrations, seeders, linting, and testing all execute inside containers.
- Frontend Vite remains host-side for now (acceptable for Phase 1).

## Removed Risks

- **Host PHP inconsistency**: eliminated by running all backend commands inside Docker.
- **Dynamic dependency mutation**: Filament installation removed from setup.sh; dependencies must be declared in composer.json.
- **Lint failure suppression**: lint.sh now fails on errors, enforcing code quality.
- **Artisan serve on host**: replaced with Docker-first runtime.

## Remaining Future Improvements

- Docker Compose configuration (docker-compose.yml) is not yet created; the team must define services (app, nginx, postgres, redis).
- Frontend Vite could be containerized later for full Docker-first development.
- CI/CD pipeline is not yet defined (out of scope for this hardening pass).
- Static analysis tooling (PHPStan) level may need adjustment.

## Final Evaluation

The hardening pass removes host PHP dependency, enforces Docker-first execution, eliminates dynamic dependency mutation, and strengthens lint enforcement. The changes are pragmatic, maintainable, and aligned with the frozen architecture. The remaining gaps (Docker Compose definition, CI/CD) are execution tasks, not design risks.
# Bootstrap Hardening Summary

## Changes Applied

- **scripts/setup.sh**: Removed host PHP/composer dependency. All backend commands now run inside Docker containers via `docker compose exec`. Removed dynamic Filament installation (composer require). Dependencies are assumed to be declared in composer.json/package.json. Validation now only requires docker, docker compose, node, npm.
- **scripts/dev.sh**: Removed `php artisan serve` (host-side). Backend runtime now relies on Docker containers (nginx + php-fpm). Frontend Vite dev server remains host-side. Queue worker runs inside container.
- **scripts/lint.sh**: Removed `|| true` suppression. Now fails on Pint or PHPStan errors. Runs inside Docker container.
- **infra/docker/backend/Dockerfile**: Created PHP-FPM Dockerfile for Laravel application.
- **infra/docker/nginx/default.conf**: Created Nginx configuration for serving Laravel via php-fpm.

## Docker-First Improvements

- Backend runtime is fully containerized (nginx + php-fpm).
- No host PHP required for operational workflows.
- Composer install, migrations, seeders, linting, and testing all execute inside containers.
- Frontend Vite remains host-side for now (acceptable for Phase 1).

## Removed Risks

- **Host PHP inconsistency**: eliminated by running all backend commands inside Docker.
- **Dynamic dependency mutation**: Filament installation removed from setup.sh; dependencies must be declared in composer.json.
- **Lint failure suppression**: lint.sh now fails on errors, enforcing code quality.
- **Artisan serve on host**: replaced with Docker-first runtime.

## Remaining Future Improvements

- Docker Compose configuration (docker-compose.yml) is not yet created; the team must define services (app, nginx, postgres, redis).
- Frontend Vite could be containerized later for full Docker-first development.
- CI/CD pipeline is not yet defined (out of scope for this hardening pass).
- Static analysis tooling (PHPStan) level may need adjustment.

## Final Evaluation

The hardening pass removes host PHP dependency, enforces Docker-first execution, eliminates dynamic dependency mutation, and strengthens lint enforcement. The changes are pragmatic, maintainable, and aligned with the frozen architecture. The remaining gaps (Docker Compose definition, CI/CD) are execution tasks, not design risks.
