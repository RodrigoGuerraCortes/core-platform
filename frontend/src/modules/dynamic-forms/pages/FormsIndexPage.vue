<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useFormsQuery } from '../queries/useFormsQuery'
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
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Forms</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Create and manage forms for this tenant.
        </p>
      </div>

      <v-btn
        prepend-icon="mdi-plus"
        color="primary"
        variant="flat"
        @click="navigateToCreate"
      >
        New Form
      </v-btn>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="d-flex flex-column gap-3">
      <v-skeleton-loader v-for="n in 3" :key="n" type="list-item-two-line" />
    </div>

    <!-- Error -->
    <v-alert v-else-if="isError" type="error" variant="tonal" rounded="lg">
      Could not load forms. Please refresh the page.
    </v-alert>

    <!-- Empty state -->
    <v-card v-else-if="forms.length === 0" variant="outlined" rounded="lg">
      <v-card-text class="text-center py-12">
        <v-icon icon="mdi-file-document-multiple-outline" size="48" color="medium-emphasis" class="mb-4" />
        <p class="text-h6 font-weight-medium mb-2">No forms yet</p>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Create your first form to get started.
        </p>
        <v-btn
          variant="flat"
          color="primary"
          prepend-icon="mdi-plus"
          @click="navigateToCreate"
        >
          Create Form
        </v-btn>
      </v-card-text>
    </v-card>

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
                <v-btn
                  icon="mdi-pencil-outline"
                  size="small"
                  variant="text"
                  title="Edit"
                  @click="navigateToEditor(form.id)"
                />
                <v-btn
                  v-if="form.status === 'active'"
                  icon="mdi-play-circle-outline"
                  size="small"
                  variant="text"
                  color="primary"
                  title="Fill form"
                  @click="navigateToFill(form.id)"
                />
              </div>
            </template>
          </v-list-item>
          <v-divider v-if="idx < forms.length - 1" />
        </template>
      </v-list>
    </v-card>
  </div>
</template>
