/**
 * Experience Auth — Shared composable for experience-aware authentication flows.
 *
 * Login pages use this composable to handle post-login redirects without
 * hardcoding route names. The composable resolves the owning experience
 * from the current route and provides the correct redirect targets.
 *
 * This does NOT replace the auth store — it augments it with experience awareness.
 *
 * Usage:
 *   const { login, postLoginRedirect, postLogoutRedirect, branding } = useExperienceAuth()
 */

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { resolveExperience } from '@/app/experiences/resolver'
import { platformExperience } from '@/app/experiences/registry'
import type { ExperienceDefinition, ExperienceAuth, ExperienceBranding } from '@/app/experiences/types'

/**
 * Substitutes :tenantSlug in a route path.
 */
function substituteParams(path: string, params: Record<string, string>): string {
  let result = path
  for (const [key, value] of Object.entries(params)) {
    result = result.replace(`:${key}`, value)
  }
  return result
}

/**
 * Provides experience-aware auth flow helpers.
 * Automatically resolves which experience owns the current route.
 */
export function useExperienceAuth() {
  const route = useRoute()
  const authStore = useAuthStore()

  /** The experience that owns the current route (or platform as default). */
  const currentExperience = computed<ExperienceDefinition>(() => {
    const resolved = resolveExperience(route)
    return resolved?.experience ?? platformExperience
  })

  /** Auth flow config for the current experience. */
  const auth = computed<ExperienceAuth>(() => currentExperience.value.auth)

  /** Branding for the current experience (used in login pages). */
  const branding = computed<ExperienceBranding | undefined>(() => currentExperience.value.branding)

  /**
   * Compute the post-login redirect path.
   * Priority: query.redirect > experience.auth.authenticatedRedirect
   */
  function resolvePostLoginRedirect(tenantSlug?: string): string {
    const redirectQuery = route.query.redirect
    if (typeof redirectQuery === 'string' && redirectQuery.startsWith('/')) {
      return redirectQuery
    }

    const slug = tenantSlug ?? 'default'
    return substituteParams(auth.value.authenticatedRedirect, { tenantSlug: slug })
  }

  /**
   * Compute the post-logout redirect path.
   */
  function resolvePostLogoutRedirect(): string {
    return auth.value.logoutRedirect ?? auth.value.loginRoute
  }

  /**
   * Perform login and return the resolved redirect path.
   * Does NOT navigate — caller decides when/how to push.
   */
  async function login(email: string, password: string): Promise<string> {
    await authStore.login(email, password)
    // Determine tenant slug from user context (simplified — in prod would come from API)
    const tenantSlug = (route.params as Record<string, string>).tenantSlug ?? 'default'
    return resolvePostLoginRedirect(tenantSlug)
  }

  return {
    currentExperience,
    auth,
    branding,
    resolvePostLoginRedirect,
    resolvePostLogoutRedirect,
    login,
    isLoading: computed(() => authStore.isLoading),
    isAuthenticated: computed(() => authStore.isAuthenticated),
  }
}
