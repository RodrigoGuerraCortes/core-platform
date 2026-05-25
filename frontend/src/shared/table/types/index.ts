/**
 * shared/table — canonical type contracts.
 *
 * These types define the table governance layer API surface.
 * All AppDataTable consumers depend on these contracts.
 */

// ─── Column definitions ────────────────────────────────────────────────────

/**
 * A single column descriptor.
 *
 * `key` maps to a property on the row object (or a computed value via `render`).
 * Columns are the ONLY way to configure which data is displayed — modules must
 * not manipulate the VDataTable headers array directly.
 */
export interface TableColumn<TRow extends TableRow = TableRow> {
  /** Unique identifier — maps to row[key] unless `render` is provided. */
  key: string
  /** Column header label. */
  label: string
  /** Pixel width hint. Responsive breakpoints may ignore this. */
  width?: number
  /** Minimum pixel width. */
  minWidth?: number
  /** Allow server-side sort on this column. */
  sortable?: boolean
  /** Horizontal alignment. Default: 'start'. */
  align?: 'start' | 'center' | 'end'
  /** Custom value accessor. Overrides default key-based lookup. */
  value?: (row: TRow) => unknown
}

// ─── Row contract ──────────────────────────────────────────────────────────

/**
 * Base row constraint. Every row object must have a stable `id`.
 * This allows row-level keying, selection, and action targeting.
 */
export interface TableRow {
  id: number | string
  [key: string]: unknown
}

// ─── Sort state ────────────────────────────────────────────────────────────

export type SortDirection = 'asc' | 'desc'

export interface SortState {
  key: string
  direction: SortDirection
}

// ─── Pagination state ──────────────────────────────────────────────────────

export interface PaginationState {
  page: number
  perPage: number
}

export const DEFAULT_PAGE_SIZE = 15
export const PAGE_SIZE_OPTIONS = [10, 15, 25, 50] as const

// ─── Filter state ──────────────────────────────────────────────────────────

/**
 * Raw filter values — what useFilterState returns.
 * Keys are filter field names; values are string, string[], or null.
 */
export type FilterValues = Record<string, string | string[] | null>

/**
 * A single filter field descriptor (used by AppFilterBar).
 */
export interface FilterField {
  key: string
  label: string
  type: 'text' | 'select' | 'multiselect'
  /** Options for select/multiselect types. */
  options?: Array<{ label: string; value: string }>
  placeholder?: string
}

// ─── Server query params ───────────────────────────────────────────────────

/**
 * Normalized query params sent to the backend.
 * useTableState computes this from pagination + sort + filter state.
 */
export interface TableQueryParams {
  page: number
  per_page: number
  sort_by?: string
  sort_dir?: SortDirection
  [filterKey: string]: string | string[] | number | null | undefined
}
