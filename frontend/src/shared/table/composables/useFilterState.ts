import { ref, type Ref } from 'vue'
import type { FilterValues } from '../types'

/**
 * useFilterState — lightweight filter state manager for AppFilterBar.
 *
 * Tracks the current values of all filter fields and exposes helpers
 * to update individual fields or clear everything.
 *
 * This is a lower-level primitive used by AppFilterBar internally, but
 * modules can also use it independently when they need filter state
 * without a full table (e.g. a search bar above a list).
 *
 * When used alongside useTableState, prefer `table.setFilter()` directly —
 * useFilterState is only needed when filter state must be decoupled from
 * table state (e.g. a sidebar filter panel).
 *
 * Usage:
 *   const filters = useFilterState({ status: null, search: null })
 *   filters.set('search', 'acme')
 *   filters.clear()
 */
export interface UseFilterStateReturn {
  values: Ref<FilterValues>
  set: (key: string, value: string | string[] | null) => void
  setMany: (updates: FilterValues) => void
  clear: () => void
  hasActiveFilters: Ref<boolean>
}

export function useFilterState(initial: FilterValues = {}): UseFilterStateReturn {
  const values = ref<FilterValues>({ ...initial })

  const hasActiveFilters = ref(false)

  function recomputeActive(): void {
    hasActiveFilters.value = Object.values(values.value).some(
      (v) => v !== null && v !== '' && !(Array.isArray(v) && v.length === 0),
    )
  }

  function set(key: string, value: string | string[] | null): void {
    values.value = { ...values.value, [key]: value }
    recomputeActive()
  }

  function setMany(updates: FilterValues): void {
    values.value = { ...values.value, ...updates }
    recomputeActive()
  }

  function clear(): void {
    values.value = { ...initial }
    hasActiveFilters.value = false
  }

  recomputeActive()

  return { values, set, setMany, clear, hasActiveFilters }
}
