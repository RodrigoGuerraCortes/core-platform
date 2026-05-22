<script setup lang="ts">
// Unauthenticated shell — login, register, password reset.
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
