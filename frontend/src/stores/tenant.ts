import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { setActiveTenant } from '@/shared/api/client'

export interface TenantInfo {
  id: number
  slug: string
  name: string
}

export const useTenantStore = defineStore('tenant', () => {
  const current = ref<TenantInfo | null>(null)

  const isResolved = computed(() => current.value !== null)
  const tenantId = computed(() => current.value?.id ?? null)
  const tenantSlug = computed(() => current.value?.slug ?? null)

  function setTenant(tenant: TenantInfo): void {
    current.value = tenant
    setActiveTenant(String(tenant.id))
  }

  function clearTenant(): void {
    current.value = null
    setActiveTenant(null)
  }

  return { current, isResolved, tenantId, tenantSlug, setTenant, clearTenant }
})
