<script setup lang="ts">
// Auth layout — login, register, password reset, and other pre-auth pages.
// Centers content on the page. No sidebar or topbar.
</script>

<template>
  <v-main>
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="5" lg="4">
          <router-view />
        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>
