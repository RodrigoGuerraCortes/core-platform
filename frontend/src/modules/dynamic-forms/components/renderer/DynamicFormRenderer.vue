<script setup lang="ts">
import { reactive, computed, ref } from 'vue'
import type { FormVersionDetail, FormSubmissionDetail } from '../../types'
import { sortedRenderableFields, defaultFieldValue } from '../../types'
import { buildZodSchema, mapZodErrors } from '../../validation/buildZodSchema'
import { mapApiErrors, isGoneError, isConflictError } from '../../validation/mapApiErrors'
import { useSubmitFormMutation } from '../../queries/useSubmitFormMutation'
import DynamicFieldRenderer from './DynamicFieldRenderer.vue'
import FormSuccessState from '../states/FormSuccessState.vue'
import FormErrorState from '../states/FormErrorState.vue'

const props = defineProps<{
  formId: number
  version: FormVersionDetail
}>()

// ─── Field state ───────────────────────────────────────────────────────────────

const sortedFields = computed(() => sortedRenderableFields(props.version.schema))

// Initialize payload with sensible defaults for every renderable field.
const payload = reactive<Record<string, unknown>>({})
for (const field of sortedRenderableFields(props.version.schema)) {
  payload[field.key] = defaultFieldValue(field)
}

const fieldErrors = reactive<Record<string, string>>({})
const touched = reactive<Record<string, boolean>>({})

function onFieldUpdate(key: string, value: unknown): void {
  payload[key] = value
  touched[key] = true
  // Clear the error for this field as the user corrects it.
  delete fieldErrors[key]
}

// ─── Submission state ──────────────────────────────────────────────────────────

type SubmitStatus = 'idle' | 'submitting' | 'success' | 'gone' | 'duplicate' | 'error'

const submitStatus = ref<SubmitStatus>('idle')
const submission = ref<FormSubmissionDetail | null>(null)
const genericError = ref<string | null>(null)

const mutation = useSubmitFormMutation(props.formId)

async function handleSubmit(): Promise<void> {
  // ── Step 1: client-side Zod validation ──────────────────────────────────
  const zodSchema = buildZodSchema(props.version.schema)
  const result = zodSchema.safeParse(payload)

  if (!result.success) {
    const zodErrors = mapZodErrors(result.error)
    // Merge into fieldErrors
    for (const [key, msg] of Object.entries(zodErrors)) {
      fieldErrors[key] = msg
    }
    // Mark all fields with errors as touched so messages appear.
    for (const key of Object.keys(zodErrors)) {
      touched[key] = true
    }
    return
  }

  // ── Step 2: send to server ───────────────────────────────────────────────
  submitStatus.value = 'submitting'
  genericError.value = null

  // Build clean payload — only schema field keys that have non-null values.
  const schemaKeys = new Set(sortedFields.value.map((f) => f.key))
  const cleanPayload = Object.fromEntries(
    Object.entries(payload).filter(([k]) => schemaKeys.has(k)),
  )

  try {
    const result = await mutation.mutateAsync(cleanPayload)
    submission.value = result
    submitStatus.value = 'success'
  } catch (err) {
    const apiErrors = mapApiErrors(err)
    if (apiErrors) {
      // 422 — map field-level errors back to the form.
      for (const [key, msg] of Object.entries(apiErrors)) {
        fieldErrors[key] = msg
        touched[key] = true
      }
      submitStatus.value = 'idle'
      return
    }

    if (isGoneError(err)) {
      submitStatus.value = 'gone'
      return
    }

    if (isConflictError(err)) {
      submitStatus.value = 'duplicate'
      return
    }

    genericError.value = 'An unexpected error occurred. Please try again.'
    submitStatus.value = 'error'
  }
}

const isSubmitting = computed(() => submitStatus.value === 'submitting')
</script>

<template>
  <!-- ── Success ─────────────────────────────────────────────────────────────── -->
  <FormSuccessState v-if="submitStatus === 'success'" :submission="submission!" />

  <!-- ── Form no longer active ──────────────────────────────────────────────── -->
  <FormErrorState
    v-else-if="submitStatus === 'gone'"
    title="Form unavailable"
    message="This form is no longer accepting submissions."
  />

  <!-- ── Duplicate submission ────────────────────────────────────────────────── -->
  <FormErrorState
    v-else-if="submitStatus === 'duplicate'"
    title="Already submitted"
    message="You have already submitted this form."
  />

  <!-- ── Generic error ───────────────────────────────────────────────────────── -->
  <FormErrorState
    v-else-if="submitStatus === 'error'"
    title="Submission failed"
    :message="genericError ?? 'Something went wrong.'"
  />

  <!-- ── Main form ───────────────────────────────────────────────────────────── -->
  <v-form v-else @submit.prevent="handleSubmit">
    <v-card variant="flat">
      <v-card-title class="text-h5 pa-0 mb-4">
        {{ version.schema.title }}
      </v-card-title>

      <v-card-text class="pa-0">
        <div class="d-flex flex-column gap-3">
          <DynamicFieldRenderer
            v-for="field in sortedFields"
            :key="field.key"
            :field="field"
            :model-value="payload[field.key]"
            :error="touched[field.key] ? fieldErrors[field.key] : undefined"
            :disabled="isSubmitting"
            @update:model-value="onFieldUpdate(field.key, $event)"
          />
        </div>
      </v-card-text>

      <v-card-actions class="pa-0 mt-6">
        <v-btn
          type="submit"
          color="primary"
          variant="flat"
          size="large"
          :loading="isSubmitting"
          :disabled="isSubmitting"
        >
          Submit
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-form>
</template>
