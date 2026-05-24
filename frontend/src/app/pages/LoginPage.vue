<script setup lang="ts">
/**
 * LoginPage — Sanctum SPA session login.
 *
 * Fetches the CSRF cookie, POSTs credentials, then redirects.
 * Validation errors (422) and bad credentials (401) are surfaced inline.
 * No tokens are stored. No localStorage. Session cookie is managed by
 * the browser automatically via the Sanctum stateful flow.
 */

import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const errorMessage = ref<string | null>(null)

const isLoading = computed(() => authStore.isLoading)

async function handleSubmit(): Promise<void> {
  errorMessage.value = null
  try {
    await authStore.login(email.value, password.value)

    // Redirect to the originally intended route, or fall back to home.
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null
    await router.push(redirect ?? { name: 'home' })
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      const status = err.response?.status
      if (status === 422) {
        // Validation error — surface the first field error.
        const errors = err.response?.data?.errors as Record<string, string[]> | undefined
        const first = errors ? Object.values(errors)[0]?.[0] : undefined
        errorMessage.value = first ?? 'Please check the form and try again.'
      } else if (status === 401) {
        errorMessage.value = err.response?.data?.message ?? 'Invalid credentials. Please try again.'
      } else {
        errorMessage.value = 'An unexpected error occurred. Please try again.'
      }
    } else {
      errorMessage.value = 'An unexpected error occurred. Please try again.'
    }
  }
}
</script>

<template>
  <v-card elevation="2" class="pa-2">
    <v-card-title class="text-h5 pt-6 px-6">Sign in</v-card-title>
    <v-card-subtitle class="px-6 pb-2">Enter your credentials to continue</v-card-subtitle>

    <v-card-text class="px-6">
      <v-alert
        v-if="errorMessage"
        type="error"
        variant="tonal"
        class="mb-4"
        closable
        @click:close="errorMessage = null"
      >
        {{ errorMessage }}
      </v-alert>

      <v-form @submit.prevent="handleSubmit">
        <v-text-field
          v-model="email"
          label="Email address"
          type="email"
          autocomplete="email"
          :disabled="isLoading"
          required
          variant="outlined"
          class="mb-3"
          data-testid="login-email"
        />

        <v-text-field
          v-model="password"
          label="Password"
          type="password"
          autocomplete="current-password"
          :disabled="isLoading"
          required
          variant="outlined"
          class="mb-5"
          data-testid="login-password"
        />

        <v-btn
          type="submit"
          color="primary"
          block
          size="large"
          :loading="isLoading"
          :disabled="isLoading"
          data-testid="login-submit"
        >
          Sign in
        </v-btn>
      </v-form>
    </v-card-text>
  </v-card>
</template>
