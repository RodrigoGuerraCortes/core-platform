<script setup lang="ts">
/**
 * FormPreviewPage — renders the form's latest schema using the SAME runtime
 * renderer that end-users see. No custom rendering logic here.
 *
 * This guarantees runtime parity: what the author sees in preview is exactly
 * what the end-user will fill in.
 *
 * Uses the latest version (draft or published). Preview is intentionally
 * read-only and does not submit — it only exercises the rendering pipeline.
 */
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useFormVersionsQuery } from '../queries/useFormVersionsQuery'
import DynamicFormRenderer from '../components/renderer/DynamicFormRenderer.vue'
import FormLoadingState from '../components/states/FormLoadingState.vue'
import FormErrorState from '../components/states/FormErrorState.vue'

const route = useRoute()
const router = useRouter()
const tenantStore = useTenantStore()

const formId = computed(() => Number(route.params.formId))

const { data: versionsData, isLoading, isError } = useFormVersionsQuery(formId)

/** Use the latest version for preview — draft or published. */
const previewVersion = computed(() => versionsData.value?.data[0] ?? null)

function goBack(): void {
  router.push({
    name: 'forms.edit',
    params: { tenantSlug: tenantStore.tenantSlug, formId: formId.value },
  })
}
</script>

<template>
  <v-container max-width="720" class="py-6">
    <!-- Back bar -->
    <div class="d-flex align-center gap-2 mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="goBack" />
      <div>
        <p class="text-body-2 font-weight-semibold">Form Preview</p>
        <p class="text-caption text-medium-emphasis">
          This is how the form looks to end-users. Preview does not submit data.
        </p>
      </div>
      <v-spacer />
      <v-chip color="orange" variant="tonal" size="small" prepend-icon="mdi-eye-outline">
        Preview Mode
      </v-chip>
    </div>

    <!-- Loading -->
    <FormLoadingState v-if="isLoading" />

    <!-- Error -->
    <FormErrorState
      v-else-if="isError"
      title="Could not load form"
      message="The form could not be loaded. Please go back and try again."
    />

    <!-- No versions yet -->
    <v-card v-else-if="!previewVersion" variant="outlined" rounded="lg">
      <v-card-text class="text-center py-10">
        <v-icon icon="mdi-file-document-outline" size="40" color="medium-emphasis" class="mb-3" />
        <p class="text-body-2 text-medium-emphasis">
          No draft saved yet. Save a draft from the editor to preview it here.
        </p>
      </v-card-text>
    </v-card>

    <!-- Runtime renderer — same component used for real submissions -->
    <DynamicFormRenderer
      v-else
      :form-id="formId"
      :version="previewVersion"
    />
  </v-container>
</template>
