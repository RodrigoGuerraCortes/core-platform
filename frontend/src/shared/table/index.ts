/**
 * @module shared/table
 *
 * THE canonical enterprise CRUD table system for Core Platform.
 *
 * Modules import exclusively from here — never from sub-paths.
 *
 * Allowed:
 *   import { AppDataTable, AppTableToolbar, AppFilterBar, useTableState } from '@/shared/table'
 *
 * Forbidden:
 *   import AppDataTable from '@/shared/table/components/AppDataTable.vue'
 *   import { VDataTable } from 'vuetify/components'
 */

// ── Components ───────────────────────────────────────────────────────────────
export { AppDataTable, AppTableToolbar, AppFilterBar } from './components'

// ── Composables ──────────────────────────────────────────────────────────────
export { useTableState, useFilterState } from './composables'
export type { UseTableStateOptions, UseTableStateReturn, UseFilterStateReturn } from './composables'

// ── Types ────────────────────────────────────────────────────────────────────
export type {
  TableColumn,
  TableRow,
  SortState,
  SortDirection,
  PaginationState,
  FilterValues,
  FilterField,
  TableQueryParams,
} from './types'
export { DEFAULT_PAGE_SIZE, PAGE_SIZE_OPTIONS } from './types'
