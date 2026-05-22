<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'

const tenantStore = useTenantStore()
const router = useRouter()

function navigateToFill(formId: number): void {
  router.push({
    name: 'dynamic-forms.fill',
    params: { tenantSlug: tenantStore.tenantSlug, formId },
  })
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Forms</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Manage and fill dynamic forms for this tenant.
        </p>
      </div>

      <!-- Authoring entry point — wired in Block 7.4 -->
      <v-btn
        prepend-icon="mdi-plus"
        color="primary"
        variant="flat"
        disabled
        title="Form authoring coming in Block 7.4"
      >
        New Form
      </v-btn>
    </div>

    <!-- Placeholder empty state — real list comes with Form Authoring block -->
    <v-card variant="outlined" rounded="lg">
      <v-card-text class="text-center py-12">
        <v-icon icon="mdi-file-document-multiple-outline" size="48" color="medium-emphasis" class="mb-4" />
        <p class="text-h6 font-weight-medium mb-2">No forms yet</p>
        <p class="text-body-2 text-medium-emphasis mb-6">
          Forms you create will appear here. The authoring interface is coming in the next block.
        </p>
        <!-- Runtime demo — useful for development -->
        <v-btn
          variant="tonal"
          color="primary"
          prepend-icon="mdi-play-circle-outline"
          @click="navigateToFill(1)"
        >
          Open Demo Form
        </v-btn>
      </v-card-text>
    </v-card>
  </div>
</template>
