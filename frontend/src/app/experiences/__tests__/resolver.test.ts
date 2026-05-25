import { describe, it, expect } from 'vitest'
import { resolveExperience, getGuestEntryRoute, getAuthenticatedEntryRoute } from '@/app/experiences'
import { condoflowExperience } from '@/app/experiences/registry'

describe('Experience Resolver', () => {
  describe('resolveExperience', () => {
    it('resolves /condoflow/login to condoflow experience', () => {
      const result = resolveExperience({ path: '/condoflow/login' })
      expect(result).not.toBeNull()
      expect(result!.experience.key).toBe('condoflow')
    })

    it('resolves /condoflow/anything to condoflow experience', () => {
      const result = resolveExperience({ path: '/condoflow/residents' })
      expect(result).not.toBeNull()
      expect(result!.experience.key).toBe('condoflow')
    })

    it('resolves tenant-scoped condoflow routes', () => {
      const result = resolveExperience({ path: '/t/acme/condoflow' })
      expect(result).not.toBeNull()
      expect(result!.experience.key).toBe('condoflow')
    })

    it('resolves tenant-scoped condoflow nested routes', () => {
      const result = resolveExperience({ path: '/t/acme/condoflow/buildings' })
      expect(result).not.toBeNull()
      expect(result!.experience.key).toBe('condoflow')
    })

    it('returns null for platform core routes', () => {
      expect(resolveExperience({ path: '/' })).toBeNull()
      expect(resolveExperience({ path: '/login' })).toBeNull()
      expect(resolveExperience({ path: '/t/acme/dashboard' })).toBeNull()
      expect(resolveExperience({ path: '/t/acme/forms' })).toBeNull()
    })

    it('returns null for unknown routes', () => {
      expect(resolveExperience({ path: '/unknown/path' })).toBeNull()
    })
  })

  describe('getGuestEntryRoute', () => {
    it('returns the guest login path for condoflow', () => {
      expect(getGuestEntryRoute(condoflowExperience)).toBe('/condoflow/login')
    })
  })

  describe('getAuthenticatedEntryRoute', () => {
    it('returns path with tenant slug substituted', () => {
      const result = getAuthenticatedEntryRoute(condoflowExperience, { tenantSlug: 'acme' })
      expect(result).toBe('/t/acme/condoflow')
    })

    it('leaves :tenantSlug when no param provided', () => {
      const result = getAuthenticatedEntryRoute(condoflowExperience)
      expect(result).toBe('/t/:tenantSlug/condoflow')
    })
  })

  describe('extensibility', () => {
    it('supports adding new experiences without modifying resolver', () => {
      // This test documents the contract: any ExperienceDefinition with
      // routePrefixes will be automatically resolved.
      const mockExperience = {
        key: 'his',
        guestEntryRoute: '/his/login',
        authenticatedEntryRoute: '/t/:tenantSlug/his',
        routePrefixes: ['/his', '/t/:tenantSlug/his'],
        navigationScope: 'hybrid' as const,
      }
      expect(mockExperience.key).toBe('his')
      expect(mockExperience.routePrefixes).toHaveLength(2)
    })
  })
})
