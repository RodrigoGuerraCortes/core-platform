<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useCreateFormMutation } from '../queries/useCreateFormMutation'

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
  <v-container max-width="560" class="py-8">
    <div class="mb-6">
      <h1 class="text-h5 font-weight-bold">New Form</h1>
      <p class="text-body-2 text-medium-emphasis mt-1">
        Create a form. You'll add fields in the editor.
      </p>
    </div>

    <v-card variant="outlined" rounded="lg">
      <v-card-text>
        <v-alert v-if="error" type="error" variant="tonal" class="mb-4" rounded="lg">
          {{ error }}
        </v-alert>

        <v-form @submit.prevent="submit">
          <div class="d-flex flex-column gap-4">
            <v-text-field
              v-model="form.name"
              label="Form Name"
              placeholder="e.g. Customer Onboarding"
              density="comfortable"
              variant="outlined"
              autofocus
              :disabled="isPending"
              required
            />

            <v-textarea
              v-model="form.description"
              label="Description (optional)"
              rows="3"
              density="comfortable"
              variant="outlined"
              :disabled="isPending"
            />
          </div>
        </v-form>
      </v-card-text>

      <v-card-actions class="pa-4 pt-0 gap-3">
        <v-btn
          color="primary"
          variant="flat"
          :loading="isPending"
          :disabled="!form.name.trim() || isPending"
          @click="submit"
        >
          Create & Open Editor
        </v-btn>
        <v-btn variant="text" :disabled="isPending" @click="cancel">
          Cancel
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-container>
</template>
