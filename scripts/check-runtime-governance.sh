#!/usr/bin/env bash
# scripts/check-runtime-governance.sh
# CI governance check: verifies runtime mode defaults and MSW isolation rules.
# Run as part of CI pipeline to prevent runtime contamination regressions.
set -euo pipefail

ERRORS=0

echo "=== Runtime Governance Check ==="

# ─── Rule 1: Fallback runtime MUST be 'vertical' ───────────────────────────
echo ""
echo "[1] Checking that runtime fallbacks default to 'vertical'..."

FILES_TO_CHECK=(
  "frontend/src/main.ts"
  "frontend/vite.config.ts"
  "frontend/src/mocks/browser.ts"
)

for file in "${FILES_TO_CHECK[@]}"; do
  if [ -f "$file" ]; then
    if grep -qE "VITE_RUNTIME_MODE.*\?\?.*'cookbook'" "$file"; then
      echo "  ❌ FAIL: $file has fallback defaulting to 'cookbook'"
      ERRORS=$((ERRORS + 1))
    else
      echo "  ✅ OK: $file"
    fi
  fi
done

# ─── Rule 2: .env.example MUST contain VITE_RUNTIME_MODE=vertical ──────────
echo ""
echo "[2] Checking .env.example contains VITE_RUNTIME_MODE=vertical..."

if [ -f "frontend/.env.example" ]; then
  if grep -q "^VITE_RUNTIME_MODE=vertical" "frontend/.env.example"; then
    echo "  ✅ OK: .env.example has VITE_RUNTIME_MODE=vertical"
  else
    echo "  ❌ FAIL: frontend/.env.example missing VITE_RUNTIME_MODE=vertical"
    ERRORS=$((ERRORS + 1))
  fi
else
  echo "  ❌ FAIL: frontend/.env.example not found"
  ERRORS=$((ERRORS + 1))
fi

# ─── Rule 3: setupWorker must be inside explicit cookbook guard ─────────────
echo ""
echo "[3] Checking that worker.start() only occurs inside cookbook condition..."

if grep -n "worker\.start(" frontend/src/main.ts | head -5 | while read -r line; do
  true
done; then
  # Verify that the worker.start is preceded by cookbook condition
  if grep -B 20 "worker\.start(" frontend/src/main.ts | grep -q "runtimeMode === 'cookbook'"; then
    echo "  ✅ OK: worker.start() is inside cookbook guard"
  else
    echo "  ❌ FAIL: worker.start() not properly guarded by cookbook condition"
    ERRORS=$((ERRORS + 1))
  fi
fi

# ─── Rule 4: No MSW browser imports in vertical module runtime code ────────
echo ""
echo "[4] Checking no msw/browser imports in vertical modules..."

VERTICAL_DIRS=(
  "frontend/src/modules/condoflow"
  "frontend/src/modules/mini-his"
)

for dir in "${VERTICAL_DIRS[@]}"; do
  if [ -d "$dir" ]; then
    # Exclude test/mock directories
    VIOLATIONS=$(find "$dir" -name "*.ts" -o -name "*.vue" | \
      grep -v "/tests/" | grep -v "/mocks/" | grep -v "\.spec\." | grep -v "\.test\." | \
      xargs grep -l "msw/browser" 2>/dev/null || true)
    if [ -n "$VIOLATIONS" ]; then
      echo "  ❌ FAIL: msw/browser imported in vertical runtime code:"
      echo "$VIOLATIONS" | sed 's/^/      /'
      ERRORS=$((ERRORS + 1))
    else
      echo "  ✅ OK: $dir clean"
    fi
  fi
done

echo ""
echo "=== Summary ==="
if [ $ERRORS -gt 0 ]; then
  echo "❌ $ERRORS governance violation(s) found."
  exit 1
else
  echo "✅ All runtime governance checks passed."
  exit 0
fi
