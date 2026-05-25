<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useCreateFormMutation } from '../queries/useCreateFormMutation'
import { AppPageLayout, AppButton, AppTextField, AppTextarea } from '@/shared/ui'

const router = useRouter()
const tenantStore = useTenantStore()

const form = reactive({ name: '', description: '' })
const error = ref<string | null>(null)

const { mutateAsync, isPending } = useCreateFormMutation()

async function submit(): Promise<void> {
  if (!form.name.trim()) return

  error.value = null

  try {
    const created = await mutateAsync({
      name: form.name.trim(),
      description: form.description.trim() || null,
    })

    router.push({
      name: 'forms.edit',
      params: { tenantSlug: tenantStore.tenantSlug, formId: created.id },
    })
  } catch {
    error.value = 'Could not create form. Please try again.'
  }
}

function cancel(): void {
  router.push({ name: 'forms.index', params: { tenantSlug: tenantStore.tenantSlug } })
}
</script>

<template>
  <AppPageLayout
    title="New Form"
    description="Create a form. You'll add fields in the editor."
  >
    <v-card variant="outlined" rounded="lg" max-width="560">
      <v-card-text>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-4" rounded="lg">
          {{ error }}
        </v-alert>

        <v-form class="d-flex flex-column gap-4" @submit.prevent="submit">
          <AppTextField
            v-model="form.name"
            label="Form Name"
            placeholder="e.g. Customer Onboarding"
            :disabled="isPending"
            :required="true"
            autofocus
          />

          <AppTextarea
            v-model="form.description"
            label="Description (optional)"
            :rows="3"
            :disabled="isPending"
          />
        </v-form>
      </v-card-text>

      <v-card-actions class="pa-4 pt-0 gap-3">
        <AppButton
          type="submit"
          :loading="isPending"
          :disabled="!form.name.trim()"
          @click="submit"
        >
          Create &amp; Open Editor
        </AppButton>
        <AppButton variant="ghost" :disabled="isPending" @click="cancel">
          Cancel
        </AppButton>
      </v-card-actions>
    </v-card>
  </AppPageLayout>
</template>
