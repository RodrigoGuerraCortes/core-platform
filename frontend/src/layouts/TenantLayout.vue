<script setup lang="ts">
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import AppShell from '@/layouts/AppShell.vue'

const route = useRoute()
const tenantStore = useTenantStore()

/**
 * Keep the tenant store in sync with the route param.
 * Real auth will populate tenant info from the API; for now the slug
 * is sufficient to drive navigation and API header injection.
 */
watch(
  () => route.params.tenantSlug as string | undefined,
  (slug) => {
    if (slug && slug !== tenantStore.tenantSlug) {
      tenantStore.setTenant({ id: 1, slug, name: slug })
    }
  },
  { immediate: true },
)
</script>

<template>
  <AppShell>
    <router-view />
  </AppShell>
</template>
