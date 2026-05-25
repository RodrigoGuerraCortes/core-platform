import { describe, it, expect } from 'vitest'
import { useTableState } from '../composables/useTableState'
import { DEFAULT_PAGE_SIZE } from '../types'

describe('useTableState', () => {
  it('initialises with default values', () => {
    const table = useTableState()

    expect(table.page.value).toBe(1)
    expect(table.perPage.value).toBe(DEFAULT_PAGE_SIZE)
    expect(table.sort.value).toBeNull()
    expect(table.filters.value).toEqual({})
  })

  it('accepts custom defaults', () => {
    const table = useTableState({
      defaultSort: { key: 'name', direction: 'asc' },
      defaultPerPage: 25,
      defaultFilters: { status: null },
    })

    expect(table.sort.value).toEqual({ key: 'name', direction: 'asc' })
    expect(table.perPage.value).toBe(25)
    expect(table.filters.value).toEqual({ status: null })
  })

  describe('queryParams', () => {
    it('includes page and per_page', () => {
      const table = useTableState()
      expect(table.queryParams.value.page).toBe(1)
      expect(table.queryParams.value.per_page).toBe(DEFAULT_PAGE_SIZE)
    })

    it('includes sort_by and sort_dir when sort is set', () => {
      const table = useTableState({ defaultSort: { key: 'created_at', direction: 'desc' } })
      expect(table.queryParams.value.sort_by).toBe('created_at')
      expect(table.queryParams.value.sort_dir).toBe('desc')
    })

    it('omits sort keys when sort is null', () => {
      const table = useTableState()
      expect(table.queryParams.value.sort_by).toBeUndefined()
      expect(table.queryParams.value.sort_dir).toBeUndefined()
    })

    it('includes active filter values', () => {
      const table = useTableState({ defaultFilters: { status: null, search: null } })
      table.setFilter('status', 'active')

      expect(table.queryParams.value.status).toBe('active')
    })

    it('omits null and empty string filter values', () => {
      const table = useTableState({ defaultFilters: { status: null, search: '' } })

      expect(table.queryParams.value).not.toHaveProperty('status')
      expect(table.queryParams.value).not.toHaveProperty('search')
    })
  })

  describe('setPage', () => {
    it('updates the page', () => {
      const table = useTableState()
      table.setPage(3)
      expect(table.page.value).toBe(3)
    })
  })

  describe('setPerPage', () => {
    it('updates perPage and resets to page 1', () => {
      const table = useTableState()
      table.setPage(4)
      table.setPerPage(50)

      expect(table.perPage.value).toBe(50)
      expect(table.page.value).toBe(1)
    })
  })

  describe('setSort', () => {
    it('updates sort and resets to page 1', () => {
      const table = useTableState()
      table.setPage(2)
      table.setSort({ key: 'name', direction: 'asc' })

      expect(table.sort.value).toEqual({ key: 'name', direction: 'asc' })
      expect(table.page.value).toBe(1)
    })

    it('accepts null to clear sort', () => {
      const table = useTableState({ defaultSort: { key: 'name', direction: 'asc' } })
      table.setSort(null)
      expect(table.sort.value).toBeNull()
    })
  })

  describe('setFilter', () => {
    it('updates a single filter and resets page', () => {
      const table = useTableState({ defaultFilters: { status: null } })
      table.setPage(3)
      table.setFilter('status', 'draft')

      expect(table.filters.value.status).toBe('draft')
      expect(table.page.value).toBe(1)
    })

    it('merges with existing filters', () => {
      const table = useTableState({ defaultFilters: { status: null, search: null } })
      table.setFilter('status', 'active')
      table.setFilter('search', 'acme')

      expect(table.filters.value.status).toBe('active')
      expect(table.filters.value.search).toBe('acme')
    })
  })

  describe('clearFilters', () => {
    it('resets filters to defaults and page to 1', () => {
      const table = useTableState({ defaultFilters: { status: null } })
      table.setPage(2)
      table.setFilter('status', 'active')
      table.clearFilters()

      expect(table.filters.value).toEqual({ status: null })
      expect(table.page.value).toBe(1)
    })
  })

  describe('reset', () => {
    it('restores all state to initial values', () => {
      const table = useTableState({
        defaultSort: { key: 'name', direction: 'asc' },
        defaultPerPage: 25,
        defaultFilters: { status: null },
      })

      table.setPage(5)
      table.setPerPage(50)
      table.setSort({ key: 'created_at', direction: 'desc' })
      table.setFilter('status', 'active')
      table.reset()

      expect(table.page.value).toBe(1)
      expect(table.perPage.value).toBe(25)
      expect(table.sort.value).toEqual({ key: 'name', direction: 'asc' })
      expect(table.filters.value).toEqual({ status: null })
    })
  })
})
