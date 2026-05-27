<script setup lang="ts">
/**
 * CondoFlowLoginPage — Independent login for CondoFlow residents.
 *
 * Uses the experience-aware auth composable for redirect resolution.
 * Branding and messaging are CondoFlow-specific, but auth runtime is shared.
 */

import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { isAxiosError } from '@/shared/api/client'
import { useExperienceAuth } from '@/app/experiences/auth'

const router = useRouter()
const { login, isLoading, branding } = useExperienceAuth()

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
        const errors = err.response?.data?.errors as Record<string, string[]> | undefined
        const first = errors ? Object.values(errors)[0]?.[0] : undefined
        errorMessage.value = first ?? 'Por favor revise el formulario e intente de nuevo.'
      } else if (status === 401) {
        errorMessage.value = err.response?.data?.message ?? 'Credenciales inválidas. Intente de nuevo.'
      } else {
        errorMessage.value = 'Ocurrió un error inesperado. Intente de nuevo.'
      }
    } else {
      errorMessage.value = 'Ocurrió un error inesperado. Intente de nuevo.'
    }
  }
}
</script>

<template>
  <v-app>
    <v-main class="d-flex align-center justify-center" style="min-height: 100vh; background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);">
      <v-container class="d-flex justify-center">
        <v-card elevation="8" class="pa-4" max-width="420" width="100%">
          <v-card-title class="text-h5 pt-6 px-6 text-center">
            <v-icon size="48" color="primary" class="mb-2">{{ branding?.icon ?? 'mdi-office-building' }}</v-icon>
            <div>{{ branding?.label ?? 'CondoFlow' }}</div>
          </v-card-title>
          <v-card-subtitle class="px-6 pb-2 text-center">
            Portal de residentes — Inicie sesión para continuar
          </v-card-subtitle>

          <v-card-text class="px-6">
            <v-alert
              v-if="errorMessage"
              type="error"
              variant="tonal"
              density="compact"
              class="mb-4"
              closable
              @click:close="errorMessage = null"
            >
              {{ errorMessage }}
            </v-alert>

            <v-form @submit.prevent="handleSubmit">
              <v-text-field
                v-model="email"
                label="Correo electrónico"
                type="email"
                autocomplete="email"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-email-outline"
                class="mb-3"
                required
              />

              <v-text-field
                v-model="password"
                label="Contraseña"
                type="password"
                autocomplete="current-password"
                variant="outlined"
                density="comfortable"
                prepend-inner-icon="mdi-lock-outline"
                class="mb-4"
                required
              />

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                :loading="isLoading"
                :disabled="!email || !password"
              >
                Iniciar sesión
              </v-btn>
            </v-form>
          </v-card-text>

          <v-card-actions class="px-6 pb-6 justify-center">
            <span class="text-caption text-medium-emphasis">
              ¿Problemas para acceder? Contacte a su administrador.
            </span>
          </v-card-actions>
        </v-card>
      </v-container>
    </v-main>
  </v-app>
</template>
