<script setup lang="ts">
/**
 * LoginPage — Sanctum SPA session login.
 *
 * Fetches the CSRF cookie, POSTs credentials, then redirects.
 * Validation errors (422) and bad credentials (401) are surfaced inline.
 * No tokens are stored. No localStorage. Session cookie is managed by
 * the browser automatically via the Sanctum stateful flow.
 */

import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { isAxiosError } from '@/shared/api/client'
import { useExperienceAuth } from '@/app/experiences/auth'

const router = useRouter()
const { login, isLoading } = useExperienceAuth()

const email = ref('')
const password = ref('')
const errorMessage = ref<string | null>(null)

async function handleSubmit(): Promise<void> {
  errorMessage.value = null
  try {
    const redirectPath = await login(email.value, password.value)
    await router.push(redirectPath)
  } catch (err: unknown) {
    if (isAxiosError(err)) {
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
