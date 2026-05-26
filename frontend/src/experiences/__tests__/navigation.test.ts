import { describe, it, expect } from 'vitest'
import { getPlatformNavigation } from '@/experiences/platform/navigation'
import { getCondoflowNavigation } from '@/experiences/condoflow/navigation'

describe('Experience Navigation Isolation', () => {
  describe('Platform navigation', () => {
    it('returns platform-specific items only', () => {
      const items = getPlatformNavigation('acme')
      const names = items.map((i) => i.name)
      expect(names).toContain('dashboard')
      expect(names).toContain('forms.index')
      expect(names).toContain('reference')
      // Must NOT contain condoflow items
      expect(names.some((n) => n.startsWith('condoflow'))).toBe(false)
    })

    it('prefixes all paths with tenant slug', () => {
      const items = getPlatformNavigation('acme')
      items.forEach((item) => {
        expect(item.to).toMatch(/^\/t\/acme\//)
      })
    })
  })

  describe('CondoFlow navigation', () => {
    it('returns condoflow-specific items only', () => {
      const items = getCondoflowNavigation('acme')
      const names = items.map((i) => i.name)
      expect(names).toContain('condoflow.dashboard')
      expect(names).toContain('condoflow.buildings')
      expect(names).toContain('condoflow.residents')
      expect(names).toContain('condoflow.tickets')
      // Must NOT contain platform items
      expect(names).not.toContain('forms.index')
      expect(names).not.toContain('reference')
    })

    it('prefixes all paths with tenant slug and condoflow', () => {
      const items = getCondoflowNavigation('acme')
      items.forEach((item) => {
        expect(item.to).toMatch(/^\/t\/acme\/condoflow/)
      })
    })
  })

  describe('No cross-experience leakage', () => {
    it('platform navigation has zero condoflow references', () => {
      const items = getPlatformNavigation('test')
      const serialized = JSON.stringify(items)
      expect(serialized).not.toContain('condoflow')
    })

    it('condoflow navigation has zero platform-specific references', () => {
      const items = getCondoflowNavigation('test')
      const names = items.map((i) => i.name)
      expect(names).not.toContain('dashboard') // only 'condoflow.dashboard'
      expect(names).not.toContain('forms.index')
    })
  })
})
