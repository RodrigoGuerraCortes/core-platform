/**
 * ESLint Flat Config — Core Platform Frontend
 *
 * Enforces architectural governance rules that prevent frontend drift.
 * Rules are grouped by concern. See docs/governance/ for rationale.
 *
 * Severity guide:
 *   'error'  — architectural violation, must fix before commit
 *   'warn'   — anti-pattern, fix before merge
 *   'off'    — rule disabled with explanation
 */

import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import tseslint from 'typescript-eslint'
import vueParser from 'vue-eslint-parser'
import globals from 'globals'

export default tseslint.config(
  // ── Base ───────────────────────────────────────────────────────────────────
  js.configs.recommended,
  ...tseslint.configs.recommended,
  ...pluginVue.configs['flat/recommended'],

  // ── Global ignores ─────────────────────────────────────────────────────────
  {
    ignores: [
      'dist/**',
      'node_modules/**',
      'public/**',
      '**/*.d.ts',
      'vite.config.ts',
      'vitest.config.ts',
    ],
  },

  // ── Vue + TypeScript source files ──────────────────────────────────────────
  {
    files: ['src/**/*.{ts,vue}'],

    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tseslint.parser,
        sourceType: 'module',
        ecmaVersion: 'latest',
        extraFileExtensions: ['.vue'],
      },
      globals: {
        ...globals.browser,
        ...globals.es2022,
      },
    },

    rules: {
      // ── TypeScript strictness ─────────────────────────────────────────────
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/explicit-function-return-type': 'off', // inferred OK
      '@typescript-eslint/consistent-type-imports': ['error', { prefer: 'type-imports' }],
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],

      // ── Vue quality ───────────────────────────────────────────────────────
      'vue/component-api-style': ['error', ['script-setup']],
      'vue/block-order': ['error', { order: ['script', 'template', 'style'] }],
      'vue/define-props-declaration': ['error', 'type-based'],
      'vue/define-emits-declaration': ['error', 'type-based'],
      'vue/no-v-html': 'error',
      'vue/no-unused-vars': 'error',
      'vue/prefer-define-options': 'error',
      'vue/require-explicit-emits': 'error',
      'vue/no-mutating-props': 'error',

      // Disabled: cosmetic HTML formatting — handled by Prettier instead.
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/html-self-closing': 'off',
      'vue/require-default-prop': 'off', // withDefaults() handles this

      // ── GOVERNANCE: Forbidden raw Vuetify components ───────────────────────
      // Use canonical shared/ui wrappers instead.
      // Rationale: docs/governance/forbidden-patterns.md

      'no-restricted-syntax': [
        'error',

        // v-btn → AppButton
        {
          selector: 'VElement[name="v-btn"]',
          message:
            'Forbidden: Use <AppButton> from @/shared/ui instead of raw <v-btn>. ' +
            'See docs/governance/forbidden-patterns.md',
        },

        // v-data-table / v-data-table-server → AppDataTable
        {
          selector: 'VElement[name="v-data-table"]',
          message:
            'Forbidden: Use <AppDataTable> from @/shared/table instead of raw <v-data-table>. ' +
            'See docs/governance/forbidden-patterns.md',
        },
        {
          selector: 'VElement[name="v-data-table-server"]',
          message:
            'Forbidden: Use <AppDataTable> from @/shared/table instead of raw <v-data-table-server>. ' +
            'See docs/governance/forbidden-patterns.md',
        },

        // v-text-field → AppTextField
        {
          selector: 'VElement[name="v-text-field"]',
          message:
            'Forbidden: Use <AppTextField> from @/shared/ui instead of raw <v-text-field>. ' +
            'See docs/governance/forbidden-patterns.md',
        },

        // v-textarea → AppTextarea
        {
          selector: 'VElement[name="v-textarea"]',
          message:
            'Forbidden: Use <AppTextarea> from @/shared/ui instead of raw <v-textarea>. ' +
            'See docs/governance/forbidden-patterns.md',
        },

        // v-select → AppSelect
        {
          selector: 'VElement[name="v-select"]',
          message:
            'Forbidden: Use <AppSelect> from @/shared/ui instead of raw <v-select>. ' +
            'See docs/governance/forbidden-patterns.md',
        },

        // v-checkbox → AppCheckbox
        {
          selector: 'VElement[name="v-checkbox"]',
          message:
            'Forbidden: Use <AppCheckbox> from @/shared/ui instead of raw <v-checkbox>. ' +
            'See docs/governance/forbidden-patterns.md',
        },
      ],

      // ── GOVERNANCE: Forbidden raw imports ─────────────────────────────────
      'no-restricted-imports': [
        'error',
        {
          patterns: [
            // Vuetify components must come via shared/ui, never directly
            {
              group: ['vuetify/components', 'vuetify/components/*'],
              message:
                'Forbidden: Import UI components from @/shared/ui, not directly from vuetify/components.',
            },
            // axios must only be imported inside api/ layers
            {
              group: ['axios'],
              message:
                'Forbidden: Import axios only inside src/shared/api/ or a module api/ directory. ' +
                'Use the shared apiClient from @/shared/api/client.',
            },
            // Block deep shared/ui sub-path imports
            {
              group: [
                '@/shared/primitives',
                '@/shared/feedback',
                '@/shared/layouts',
                '@/shared/forms',
                '@/shared/entity',
                '@/shared/timeline',
              ],
              message:
                'Forbidden: Import only from @/shared/ui (the public surface), not from internal sub-paths.',
            },
            // Block deep shared/table sub-path imports
            {
              group: ['@/shared/table/components/*', '@/shared/table/composables/*'],
              message:
                'Forbidden: Import only from @/shared/table (the public surface), not from internal sub-paths.',
            },
            // ── GOVERNANCE: No cross-vertical imports ───────────────────────
            // Verticals MUST NOT import from each other — only from @/shared/* or Core APIs.
            {
              group: [
                '@/modules/condoflow',
                '@/modules/condoflow/*',
                '@/modules/dynamic-forms',
                '@/modules/dynamic-forms/*',
                '@/modules/reference',
                '@/modules/reference/*',
              ],
              message:
                'Forbidden: Cross-vertical imports violate architecture boundaries. ' +
                'Import from @/shared/* or Core APIs only. ' +
                'See docs/governance/ownership-matrix.md',
            },
            // ── GOVERNANCE: No MSW browser in vertical-runtime modules ──────
            // Business verticals MUST use real backend APIs, not runtime mocks.
            {
              group: ['msw/browser'],
              message:
                'Forbidden: MSW browser imports not allowed in vertical modules. ' +
                'Use msw/node for tests only. ' +
                'See docs/governance/runtime-modes.md',
            },
          ],
        },
      ],
    },
  },

  // ── Test files — relaxed rules ─────────────────────────────────────────────
  {
    files: ['src/**/*.test.ts', 'src/tests/**/*.ts', 'src/**/mocks/**/*.ts'],
    rules: {
      '@typescript-eslint/no-explicit-any': 'off',
      '@typescript-eslint/no-non-null-assertion': 'off',
      'no-restricted-imports': 'off', // test helpers may import internals + MSW
    },
  },

  // ── API layer — axios allowed ──────────────────────────────────────────────
  {
    files: ['src/shared/api/**/*.ts', 'src/modules/**/api/**/*.ts'],
    rules: {
      'no-restricted-imports': 'off', // axios is allowed here
    },
  },

  // ── MSW browser worker setup — MSW/browser allowed ────────────────────────
  {
    files: ['src/mocks/browser.ts'],
    rules: {
      'no-restricted-imports': 'off', // MSW browser setup needs setupWorker
    },
  },

  // ── Router — can import module routes ─────────────────────────────────────
  {
    files: ['src/router/index.ts'],
    rules: {
      'no-restricted-imports': [
        'error',
        {
          patterns: [
            // Router can import routes, but not other module internals
            {
              group: [
                '@/modules/*/api/*',
                '@/modules/*/composables/*',
                '@/modules/*/components/*',
                '@/modules/*/pages/*',
              ],
              message:
                'Forbidden: Router should only import /routes exports from modules, not internals.',
            },
          ],
        },
      ],
    },
  },

  // ── Shared primitives — Vuetify direct use allowed ────────────────────────
  {
    files: [
      'src/shared/primitives/**/*.vue',
      'src/shared/feedback/**/*.vue',
      'src/shared/layouts/**/*.vue',
      'src/shared/forms/**/*.vue',
      'src/shared/table/components/**/*.vue',
      'src/shared/entity/**/*.vue',
      'src/shared/timeline/**/*.vue',
    ],
    rules: {
      'no-restricted-syntax': 'off', // wrappers themselves use raw Vuetify
      'no-restricted-imports': 'off',
    },
  },
)
