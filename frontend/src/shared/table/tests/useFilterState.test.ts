import { describe, it, expect } from 'vitest'
import { useFilterState } from '../composables/useFilterState'
import type { FilterValues } from '../types'

describe('useFilterState', () => {
  it('initialises with provided defaults', () => {
    const f = useFilterState({ status: null, search: null })
    expect(f.values.value).toEqual({ status: null, search: null })
  })

  it('initialises hasActiveFilters as false when all values are null', () => {
    const f = useFilterState({ status: null })
    expect(f.hasActiveFilters.value).toBe(false)
  })

  it('set() updates a value and sets hasActiveFilters', () => {
    const f = useFilterState({ status: null })
    f.set('status', 'active')

    expect(f.values.value.status).toBe('active')
    expect(f.hasActiveFilters.value).toBe(true)
  })

  it('set() to null clears hasActiveFilters when all are null', () => {
    const f = useFilterState({ status: null })
    f.set('status', 'active')

    f.set('status', null) // clear it back
    expect(f.values.value.status).toBeNull()
    expect(f.hasActiveFilters.value).toBe(false)
  })

  it('setMany() merges multiple values at once', () => {
    const f = useFilterState({ status: null, search: null })
    f.setMany({ status: 'draft', search: 'acme' })

    expect(f.values.value.status).toBe('draft')
    expect(f.values.value.search).toBe('acme')
    expect(f.hasActiveFilters.value).toBe(true)
  })

  it('clear() resets to initial values', () => {
    const f = useFilterState({ status: null, search: null })
    f.setMany({ status: 'draft', search: 'acme' })
    f.clear()

    expect(f.values.value).toEqual({ status: null, search: null })
    expect(f.hasActiveFilters.value).toBe(false)
  })

  it('treats empty string as inactive', () => {
    const f = useFilterState({ search: '' })
    expect(f.hasActiveFilters.value).toBe(false)
  })

  it('treats empty array as inactive', () => {
    const f = useFilterState({ tags: [] as string[] })
    expect(f.hasActiveFilters.value).toBe(false)
  })

  it('treats non-empty array as active', () => {
    const initialTags: FilterValues = { tags: ['a', 'b'] }
    const f = useFilterState(initialTags)
    expect(f.hasActiveFilters.value).toBe(true)
  })
})
