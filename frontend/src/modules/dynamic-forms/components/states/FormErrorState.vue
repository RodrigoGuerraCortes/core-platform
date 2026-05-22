<script setup lang="ts">
defineProps<{
  title: string
  message: string
}>()
</script>

<template>
  <div class="df-error d-flex flex-column align-center justify-center py-12 text-center">
    <v-icon icon="mdi-alert-circle" color="error" size="64" />
    <h2 class="text-h5 mt-4 mb-2">{{ title }}</h2>
    <p class="text-body-1 text-medium-emphasis">{{ message }}</p>
  </div>
</template>
