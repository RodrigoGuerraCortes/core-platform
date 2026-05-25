import { ref, computed, type Ref } from 'vue'
import type {
  SortState,
  PaginationState,
  FilterValues,
  TableQueryParams,
} from '../types'
import { DEFAULT_PAGE_SIZE } from '../types'

/**
 * useTableState — canonical composable for server-driven table state.
 *
 * Owns pagination, sorting, and filter state in a single reactive object.
 * Produces a `queryParams` computed ref that modules pass directly to their
 * API fetch function (or TanStack Query queryFn).
 *
 * Rules:
 *   - Every server-side table MUST use this composable.
 *   - Modules must not manage page/sort/filter state ad-hoc.
 *   - `queryParams` is the only serialization contract with the backend.
 *
 * Usage:
 *   const table = useTableState({ defaultSort: { key: 'name', direction: 'asc' } })
 *
 *   // Pass to TanStack Query:
 *   const { data } = useQuery({
 *     queryKey: ['forms', table.queryParams],
 *     queryFn: () => fetchForms(table.queryParams.value),
 *   })
 *
 *   // Bind to AppDataTable:
 *   <AppDataTable
 *     v-bind="table.tableProps"
 *     @update:page="table.setPage"
 *     @update:sort="table.setSort"
 *   />
 */
export interface UseTableStateOptions {
  defaultSort?: SortState
  defaultPerPage?: number
  defaultFilters?: FilterValues
}

export interface UseTableStateReturn {
  // ── State (readable) ──────────────────────────────────────────────────────
  page: Ref<number>
  perPage: Ref<number>
  sort: Ref<SortState | null>
  filters: Ref<FilterValues>

  // ── Computed ──────────────────────────────────────────────────────────────
  /** Normalized params to pass to your API / queryFn. */
  queryParams: Ref<TableQueryParams>
  /** Shorthand pagination state object. */
  pagination: Ref<PaginationState>

  // ── Mutations ─────────────────────────────────────────────────────────────
  /** Set the current page. Resets to 1 automatically on sort/filter change. */
  setPage: (page: number) => void
  setPerPage: (perPage: number) => void
  setSort: (sort: SortState | null) => void
  setFilter: (key: string, value: string | string[] | null) => void
  setFilters: (filters: FilterValues) => void
  /** Clear all filters and reset to page 1. */
  clearFilters: () => void
  /** Reset all state to initial values. */
  reset: () => void
}

export function useTableState(options: UseTableStateOptions = {}): UseTableStateReturn {
  const { defaultSort = null, defaultPerPage = DEFAULT_PAGE_SIZE, defaultFilters = {} } = options

  const page = ref(1)
  const perPage = ref(defaultPerPage)
  const sort = ref<SortState | null>(defaultSort)
  const filters = ref<FilterValues>({ ...defaultFilters })

  const pagination = computed<PaginationState>(() => ({
    page: page.value,
    perPage: perPage.value,
  }))

  const queryParams = computed<TableQueryParams>(() => ({
    page: page.value,
    per_page: perPage.value,
    ...(sort.value ? { sort_by: sort.value.key, sort_dir: sort.value.direction } : {}),
    ...Object.fromEntries(
      Object.entries(filters.value).filter(([, v]) => v !== null && v !== ''),
    ),
  }))

  function setPage(p: number): void {
    page.value = p
  }

  function setPerPage(pp: number): void {
    perPage.value = pp
    page.value = 1
  }

  function setSort(s: SortState | null): void {
    sort.value = s
    page.value = 1
  }

  function setFilter(key: string, value: string | string[] | null): void {
    filters.value = { ...filters.value, [key]: value }
    page.value = 1
  }

  function setFilters(f: FilterValues): void {
    filters.value = { ...f }
    page.value = 1
  }

  function clearFilters(): void {
    filters.value = { ...defaultFilters }
    page.value = 1
  }

  function reset(): void {
    page.value = 1
    perPage.value = defaultPerPage
    sort.value = defaultSort
    filters.value = { ...defaultFilters }
  }

  return {
    page,
    perPage,
    sort,
    filters,
    pagination,
    queryParams,
    setPage,
    setPerPage,
    setSort,
    setFilter,
    setFilters,
    clearFilters,
    reset,
  }
}
