<script setup lang="ts">
import AppNotifications from '@/shared/components/AppNotifications.vue'
// Root component. Vuetify requires <v-app> at the tree root.
// Business logic lives in modules — this file stays minimal.
</script>

<template>
  <v-app>
    <router-view />
    <!-- Global notification toasts — rendered above all layouts. -->
    <AppNotifications />
  </v-app>
</template>
