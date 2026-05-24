# ------------------------------------------------------------------
# Core Platform — Makefile
# ------------------------------------------------------------------

.PHONY: setup dev test lint migrate seed fresh artisan fix-permissions

setup:
	@bash scripts/setup.sh

dev:
	@bash scripts/dev.sh

test:
	@bash scripts/test.sh

lint:
	@bash scripts/lint.sh

# ── Docker artisan wrapper ──────────────────────────────────────────
# Always run artisan inside the container as www-data so that any
# files written (cache, compiled views, bootstrap/cache) are owned
# by the PHP-FPM user, preventing permission drift.
# Usage: make artisan CMD="migrate --force"
artisan:
	docker compose exec -u www-data app php artisan $(CMD)

# Convenience targets that use the www-data wrapper:
migrate:
	docker compose exec -u www-data app php artisan migrate

seed:
	docker compose exec -u www-data app php artisan db:seed

fresh:
	docker compose exec -u www-data app php artisan migrate:fresh --seed

# ── Runtime permission repair ───────────────────────────────────────
# Fixes ownership of Laravel runtime directories in the running
# container. Needed after: host-side file writes, artisan run as
# root without -u www-data, or after first `docker compose up`.
# The entrypoint script does this automatically on container start;
# use this target to repair without restarting.
fix-permissions:
	docker compose exec app sh -c "\
		chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
		chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache && \
		echo 'Runtime permissions fixed.'"
