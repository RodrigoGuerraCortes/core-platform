<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const emit = defineEmits<{
  toggleDrawer: []
}>()

const authStore = useAuthStore()
const router = useRouter()

function handleLogout(): void {
  authStore.logout()
  router.push('/')
}
</script>

<template>
  <v-app-bar flat border="b" elevation="0">
    <!-- Sidebar toggle — visible on mobile; hidden when sidebar is permanent -->
    <v-app-bar-nav-icon
      class="d-flex d-md-none"
      @click="emit('toggleDrawer')"
    />

    <v-app-bar-title class="text-body-1 font-weight-medium">
      Core Platform
    </v-app-bar-title>

    <template #append>
      <!-- Notification bell — placeholder for future notification panel -->
      <v-btn
        icon="mdi-bell-outline"
        variant="text"
        size="small"
        disabled
        title="Notifications (coming soon)"
      />

      <!-- User menu -->
      <v-menu :close-on-content-click="true">
        <template #activator="{ props: menuProps }">
          <v-btn
            v-bind="menuProps"
            variant="text"
            size="small"
            class="mr-1"
            :append-icon="authStore.user ? 'mdi-chevron-down' : undefined"
          >
            <v-icon icon="mdi-account-circle-outline" class="mr-1" />
            {{ authStore.user?.name ?? 'Account' }}
          </v-btn>
        </template>

        <v-list density="compact" min-width="180">
          <v-list-item
            :subtitle="authStore.user?.email"
            density="compact"
          >
            <template #title>
              <span class="text-caption text-medium-emphasis">Signed in as</span>
            </template>
          </v-list-item>
          <v-divider class="my-1" />
          <v-list-item
            title="Sign out"
            prepend-icon="mdi-logout"
            @click="handleLogout"
          />
        </v-list>
      </v-menu>
    </template>
  </v-app-bar>
</template>
