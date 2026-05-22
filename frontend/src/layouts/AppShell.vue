<script setup lang="ts">
import { ref } from 'vue'
import AppTopbar from '@/shared/components/AppTopbar.vue'
import AppSidebar from '@/shared/components/AppSidebar.vue'

/**
 * AppShell — the authenticated workspace layout.
 *
 * Responsibilities:
 * - Renders the topbar (AppTopbar) and sidebar (AppSidebar).
 * - Manages drawer open/close state for mobile.
 * - Renders module content via the default slot.
 *
 * Does NOT:
 * - Know which tenant is active (delegated to TenantLayout/stores).
 * - Know which module is loaded (delegated to the router).
 * - Handle auth guards (delegated to the router).
 */

const drawerOpen = ref(false) // Closed by default on mobile; sidebar is permanent on desktop.
</script>

<template>
  <v-layout>
    <AppTopbar @toggle-drawer="drawerOpen = !drawerOpen" />

    <AppSidebar v-model="drawerOpen" />

    <v-main>
      <v-container fluid class="pa-6">
        <slot />
      </v-container>
    </v-main>
  </v-layout>
</template>
