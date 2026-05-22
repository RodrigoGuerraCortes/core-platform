<script setup lang="ts">
// Authenticated tenant shell. Navigation and sidebar live here.
// Module-specific content is rendered via <router-view />.
</script>

<template>
  <v-layout>
    <v-app-bar flat border="b">
      <v-app-bar-title>Core Platform</v-app-bar-title>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <router-view />
      </v-container>
    </v-main>
  </v-layout>
</template>
