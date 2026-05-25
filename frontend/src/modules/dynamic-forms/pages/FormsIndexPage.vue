<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useFormsQuery } from '../queries/useFormsQuery'
import { AppPageLayout, AppButton, AppEmptyState } from '@/shared/ui'
import type { FormDetail } from '../types'

const tenantStore = useTenantStore()
const router = useRouter()

const { data, isLoading, isError } = useFormsQuery()

const forms = computed(() => data.value?.data ?? [])

function statusColor(status: FormDetail['status']): string {
  return { draft: 'warning', active: 'success', archived: 'default' }[status] ?? 'default'
}

function navigateToCreate(): void {
  router.push({ name: 'forms.create', params: { tenantSlug: tenantStore.tenantSlug } })
}

function navigateToEditor(formId: number): void {
  router.push({ name: 'forms.edit', params: { tenantSlug: tenantStore.tenantSlug, formId } })
}

function navigateToFill(formId: number): void {
  router.push({ name: 'dynamic-forms.fill', params: { tenantSlug: tenantStore.tenantSlug, formId } })
}
</script>

<template>
  <AppPageLayout
    title="Forms"
    description="Create and manage forms for this tenant."
    :loading="isLoading"
    :error="isError"
    error-message="Could not load forms. Please refresh the page."
  >
    <template #actions>
      <AppButton prepend-icon="mdi-plus" @click="navigateToCreate">
        New Form
      </AppButton>
    </template>

    <!-- Empty state -->
    <AppEmptyState
      v-if="forms.length === 0"
      icon="mdi-file-document-multiple-outline"
      title="No forms yet"
      description="Create your first form to get started."
    >
      <template #action>
        <AppButton prepend-icon="mdi-plus" @click="navigateToCreate">
          Create Form
        </AppButton>
      </template>
    </AppEmptyState>

    <!-- Forms list -->
    <v-card v-else variant="outlined" rounded="lg">
      <v-list lines="two">
        <template v-for="(form, idx) in forms" :key="form.id">
          <v-list-item>
            <v-list-item-title class="font-weight-medium">{{ form.name }}</v-list-item-title>
            <v-list-item-subtitle>
              {{ form.description || 'No description' }}
            </v-list-item-subtitle>

            <template #append>
              <div class="d-flex align-center gap-3">
                <v-chip :color="statusColor(form.status)" size="small" variant="tonal">
                  {{ form.status }}
                </v-chip>
                <AppButton
                  icon="mdi-pencil-outline"
                  variant="ghost"
                  size="small"
                  :aria-label="`Edit ${form.name}`"
                  @click="navigateToEditor(form.id)"
                />
                <AppButton
                  v-if="form.status === 'active'"
                  icon="mdi-play-circle-outline"
                  variant="ghost"
                  size="small"
                  :aria-label="`Fill ${form.name}`"
                  @click="navigateToFill(form.id)"
                />
              </div>
            </template>
          </v-list-item>
          <v-divider v-if="idx < forms.length - 1" />
        </template>
      </v-list>
    </v-card>
  </AppPageLayout>
</template>
