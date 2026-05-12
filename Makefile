# ------------------------------------------------------------------
# Core Platform — Makefile
# ------------------------------------------------------------------

.PHONY: setup dev test lint migrate seed fresh

setup:
	@bash scripts/setup.sh

dev:
	@bash scripts/dev.sh

test:
	@bash scripts/test.sh

lint:
	@bash scripts/lint.sh

migrate:
	@cd backend && php artisan migrate

seed:
	@cd backend && php artisan db:seed

fresh:
	@cd backend && php artisan migrate:fresh --seed
