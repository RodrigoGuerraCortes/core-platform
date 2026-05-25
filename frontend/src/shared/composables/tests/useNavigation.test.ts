import { describe, it, expect } from 'vitest'
import { ref } from 'vue'
import { useNavigation } from '../useNavigation'

describe('useNavigation', () => {
  it('returns an empty list when tenantSlug is null', () => {
    const { items } = useNavigation(() => null)
    expect(items.value).toHaveLength(0)
  })

  it('returns an empty list when tenantSlug is an empty string', () => {
    const { items } = useNavigation(() => '')
    expect(items.value).toHaveLength(0)
  })

  it('returns three top-level items when tenantSlug is provided', () => {
    const { items } = useNavigation(() => 'acme')
    expect(items.value).toHaveLength(3)
  })

  it('prefixes every item to path with the tenant slug', () => {
    const { items } = useNavigation(() => 'acme')
    for (const item of items.value) {
      expect(item.to).toMatch(/^\/t\/acme\//)
    }
  })

  it('includes a Dashboard item pointing to /dashboard', () => {
    const { items } = useNavigation(() => 'acme')
    const dashboard = items.value.find((i) => i.name === 'dashboard')
    expect(dashboard).toBeDefined()
    expect(dashboard!.to).toBe('/t/acme/dashboard')
  })

  it('includes a Forms item pointing to /forms', () => {
    const { items } = useNavigation(() => 'acme')
    const forms = items.value.find((i) => i.name === 'forms.index')
    expect(forms).toBeDefined()
    expect(forms!.to).toBe('/t/acme/forms')
  })

  it('includes a Reference item pointing to /reference', () => {
    const { items } = useNavigation(() => 'acme')
    const ref = items.value.find((i) => i.name === 'reference')
    expect(ref).toBeDefined()
    expect(ref!.to).toBe('/t/acme/reference')
  })

  it('reacts when a ref changes from null to a slug', () => {
    const slug = ref<string | null>(null)
    const { items } = useNavigation(slug)

    expect(items.value).toHaveLength(0)

    slug.value = 'beta'
    expect(items.value).toHaveLength(3)
    expect(items.value[0].to).toMatch(/^\/t\/beta\//)
  })

  it('reacts when the slug changes between tenants', () => {
    const slug = ref('acme')
    const { items } = useNavigation(slug)

    expect(items.value[0].to).toMatch(/^\/t\/acme\//)

    slug.value = 'other'
    expect(items.value[0].to).toMatch(/^\/t\/other\//)
  })

  it('every item has a non-empty icon and label', () => {
    const { items } = useNavigation(() => 'acme')
    for (const item of items.value) {
      expect(item.icon).toBeTruthy()
      expect(item.label).toBeTruthy()
    }
  })
})
