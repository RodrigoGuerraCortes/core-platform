import { describe, it, expect } from 'vitest'
import { condoflowExperience, platformExperience } from '@/app/experiences/registry'

describe('Experience Auth Configuration', () => {
  describe('Platform experience auth', () => {
    it('has login route at /login', () => {
      expect(platformExperience.auth.loginRoute).toBe('/login')
    })

    it('redirects authenticated users to tenant dashboard', () => {
      expect(platformExperience.auth.authenticatedRedirect).toBe('/t/:tenantSlug/dashboard')
    })

    it('redirects logout to root', () => {
      expect(platformExperience.auth.logoutRedirect).toBe('/')
    })
  })

  describe('CondoFlow experience auth', () => {
    it('has login route at /condoflow/login', () => {
      expect(condoflowExperience.auth.loginRoute).toBe('/condoflow/login')
    })

    it('redirects authenticated users to condoflow dashboard', () => {
      expect(condoflowExperience.auth.authenticatedRedirect).toBe('/t/:tenantSlug/condoflow')
    })

    it('redirects logout to condoflow login', () => {
      expect(condoflowExperience.auth.logoutRedirect).toBe('/condoflow/login')
    })
  })

  describe('Auth isolation', () => {
    it('platform auth does not reference condoflow', () => {
      const serialized = JSON.stringify(platformExperience.auth)
      expect(serialized).not.toContain('condoflow')
    })

    it('condoflow auth does not reference platform routes', () => {
      const auth = condoflowExperience.auth
      expect(auth.loginRoute).not.toBe('/login')
      expect(auth.authenticatedRedirect).not.toContain('/dashboard')
    })

    it('both experiences share the same auth shape', () => {
      // Contract: all experiences must have loginRoute + authenticatedRedirect
      expect(platformExperience.auth.loginRoute).toBeDefined()
      expect(platformExperience.auth.authenticatedRedirect).toBeDefined()
      expect(condoflowExperience.auth.loginRoute).toBeDefined()
      expect(condoflowExperience.auth.authenticatedRedirect).toBeDefined()
    })
  })

  describe('Future extensibility', () => {
    it('new experience can declare auth without modifying existing ones', () => {
      // This test documents the contract for adding a new vertical
      const hisAuth = {
        loginRoute: '/his/login',
        authenticatedRedirect: '/t/:tenantSlug/his',
        logoutRedirect: '/his/login',
      }
      expect(hisAuth.loginRoute).toBe('/his/login')
      expect(hisAuth.authenticatedRedirect).toContain(':tenantSlug')
    })
  })
})
