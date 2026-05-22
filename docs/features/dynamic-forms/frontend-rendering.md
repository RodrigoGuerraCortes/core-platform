# DynamicForms — Frontend Rendering

**Block:** 7.1 — DynamicForms Canonical Module Architecture Freeze  
**Status:** Frozen  
**Date:** 2026-05-22

---

## Overview

The frontend renders forms entirely from the schema fetched from the API. No component hardcodes field lists. The schema is the rendering contract — the frontend is schema-driven from first render to submission.

This document describes the rendering architecture, the field renderer registry, the composables, and the TanStack Query integration.

---

## Module Directory Structure

```
src/modules/dynamic-forms/
  api/
    forms.ts               # fetchForms, fetchForm, createForm, patchForm, archiveForm, publishForm
    formVersions.ts        # fetchFormVersions, fetchFormVersion, createFormVersion
    formSubmissions.ts     # fetchSubmissions, fetchSubmission, submitForm
  composables/
    useFormList.ts
    useForm.ts
    useFormCreate.ts
    useFormUpdate.ts
    useFormPublish.ts
    useFormArchive.ts
    useFormVersion.ts
    useFormVersionCreate.ts
    useFormSubmit.ts
    useFormSubmissionList.ts
    useFormRenderer.ts     # schema → Zod schema + conditional logic
  components/
    FormList.vue
    FormCard.vue
    FormDetail.vue
    FormVersionBadge.vue
    FormSubmissionTable.vue
    renderer/
      DynamicFormRenderer.vue    # Root renderer — takes schema, emits submit
      FieldRenderer.vue          # Resolves field type → renderer component
      fields/
        TextField.vue
        TextareaField.vue
        NumberField.vue
        DateField.vue
        SelectField.vue
        CheckboxField.vue
        RadioField.vue
        EmailField.vue
        FileFieldPlaceholder.vue
        SectionDivider.vue
        UnknownField.vue         # Fallback for unrecognized types
  pages/
    FormListPage.vue
    FormDetailPage.vue
    FormBuilderPage.vue       # Admin — create/edit form versions
    FormSubmitPage.vue        # Member — fill and submit a form
    SubmissionListPage.vue    # Admin — view all submissions
  types/
    form.ts
    formVersion.ts
    formSubmission.ts
    forms.ts                  # payload types for create/update
  queryKeys.ts
  index.ts
```

---

## Schema Fetching

When a user opens a form to fill, the frontend fetches the **active version's schema**:

```typescript
// composables/useFormVersion.ts
export function useFormVersion(formId: Ref<string>, versionId: Ref<string>) {
  return useQuery({
    queryKey: formKeys.version(formId, versionId),
    queryFn: () => fetchFormVersion(formId.value, versionId.value),
    staleTime: 1000 * 60 * 10,  // schema rarely changes; cache aggressively
  })
}
```

The form detail endpoint returns `active_version_id`. The renderer fetches that version's schema separately.

---

## `DynamicFormRenderer` — Root Renderer

The root renderer receives a `FormSchema` and emits a `submit` event with the filled payload:

```vue
<!-- components/renderer/DynamicFormRenderer.vue -->
<script setup lang="ts">
import type { FormSchema, FieldDefinition } from '@/shared/types/forms'
import FieldRenderer from './FieldRenderer.vue'
import { useFormRenderer } from '../../composables/useFormRenderer'

const props = defineProps<{
  schema: FormSchema
  isSubmitting: boolean
  validationErrors: Record<string, string[]>
}>()

const emit = defineEmits<{
  submit: [payload: Record<string, unknown>]
}>()

const { form, visibleFields, zodSchema, handleSubmit } = useFormRenderer(
  toRef(props, 'schema'),
)

function onSubmit() {
  handleSubmit((payload) => emit('submit', payload))
}
</script>

<template>
  <form @submit.prevent="onSubmit" novalidate>
    <template v-if="schema.settings.show_progress_bar">
      <!-- progress bar slot — not implemented in V1 -->
    </template>

    <FieldRenderer
      v-for="field in visibleFields"
      :key="field.key"
      :field="field"
      :model-value="form[field.key]"
      :errors="validationErrors[field.key]"
      @update:model-value="form[field.key] = $event"
    />

    <v-btn
      type="submit"
      :loading="isSubmitting"
      :disabled="isSubmitting"
    >
      Submit
    </v-btn>
  </form>
</template>
```

`DynamicFormRenderer` does NOT own API calls or submission state. The page owns those.

---

## `FieldRenderer` — Type Resolution

`FieldRenderer` resolves the field type string to the correct renderer component:

```vue
<!-- components/renderer/FieldRenderer.vue -->
<script setup lang="ts">
import type { FieldDefinition } from '@/shared/types/forms'
import { resolveFieldRenderer } from '@/shared/lib/fieldRenderers'

const props = defineProps<{
  field: FieldDefinition
  modelValue: unknown
  errors?: string[]
}>()

const emit = defineEmits<{ 'update:modelValue': [value: unknown] }>()

const RendererComponent = resolveFieldRenderer(props.field.type)
</script>

<template>
  <component
    :is="RendererComponent"
    :field="field"
    :model-value="modelValue"
    :errors="errors"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
```

---

## Field Renderer Registry

The registry lives in `shared/lib/fieldRenderers.ts`:

```typescript
// shared/lib/fieldRenderers.ts

import type { Component } from 'vue'
import type { FieldType } from '@/shared/types/forms'

// Lazy-loaded to avoid bundling all field renderers on every page
const fieldRenderers: Record<FieldType, () => Promise<Component>> = {
  text:     () => import('@/modules/dynamic-forms/components/renderer/fields/TextField.vue'),
  textarea: () => import('@/modules/dynamic-forms/components/renderer/fields/TextareaField.vue'),
  number:   () => import('@/modules/dynamic-forms/components/renderer/fields/NumberField.vue'),
  date:     () => import('@/modules/dynamic-forms/components/renderer/fields/DateField.vue'),
  select:   () => import('@/modules/dynamic-forms/components/renderer/fields/SelectField.vue'),
  checkbox: () => import('@/modules/dynamic-forms/components/renderer/fields/CheckboxField.vue'),
  radio:    () => import('@/modules/dynamic-forms/components/renderer/fields/RadioField.vue'),
  email:    () => import('@/modules/dynamic-forms/components/renderer/fields/EmailField.vue'),
  file:     () => import('@/modules/dynamic-forms/components/renderer/fields/FileFieldPlaceholder.vue'),
  section:  () => import('@/modules/dynamic-forms/components/renderer/fields/SectionDivider.vue'),
}

export function resolveFieldRenderer(type: string): Component {
  const loader = fieldRenderers[type as FieldType]
  if (!loader) {
    return defineAsyncComponent(
      () => import('@/modules/dynamic-forms/components/renderer/fields/UnknownField.vue')
    )
  }
  return defineAsyncComponent(loader)
}
```

All field renderers are lazy-loaded. The bundle only loads what the schema needs.

---

## Field Renderer Contract

Every field renderer component must implement this props/emits contract:

```typescript
// Props every field renderer receives:
defineProps<{
  field: FieldDefinition    // full field definition from schema
  modelValue: unknown       // current form value (type depends on field)
  errors?: string[]         // server validation errors for this field
}>()

// Emits every field renderer must support:
defineEmits<{
  'update:modelValue': [value: unknown]
}>()
```

Field renderers are purely presentational. They do not call APIs, do not access stores, and do not manage state outside of their own `v-model` binding.

---

## `useFormRenderer` Composable

This composable derives rendering state from the schema:

```typescript
// composables/useFormRenderer.ts
export function useFormRenderer(schema: Ref<FormSchema>) {
  // Reactive form payload initialized from field defaults
  const form = reactive<Record<string, unknown>>({})

  // Fields sorted by order, with sections included
  const orderedFields = computed(() =>
    [...schema.value.fields].sort((a, b) => a.order - b.order)
  )

  // Fields filtered by conditional visibility
  const visibleFields = computed(() =>
    orderedFields.value.filter(field => isFieldVisible(field, form))
  )

  // Derived Zod schema from visible fields only
  const zodSchema = computed(() => buildZodSchema(visibleFields.value))

  function handleSubmit(callback: (payload: Record<string, unknown>) => void) {
    const result = zodSchema.value.safeParse(form)
    if (!result.success) {
      // set client validation errors
      return
    }
    callback(result.data)
  }

  return { form, visibleFields, zodSchema, handleSubmit }
}
```

---

## `useFormSubmit` Composable

Owns the API submission lifecycle:

```typescript
// composables/useFormSubmit.ts
export function useFormSubmit(formId: Ref<string>) {
  const queryClient = useQueryClient()
  const { push } = useNotificationStore()

  const mutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => submitForm(formId.value, payload),
    onSuccess: () => {
      push({ type: 'success', message: 'Form submitted successfully.' })
      queryClient.invalidateQueries({ queryKey: formKeys.submissions(formId.value) })
    },
    onError: (error: ApiError) => {
      if (!error.errors) {
        push({ type: 'error', message: error.message })
      }
      // field-level errors are handled by the form renderer — no toast for those
    },
  })

  return {
    submit: mutation.mutateAsync,
    isSubmitting: mutation.isPending,
    submissionError: mutation.error as Ref<ApiError | null>,
    validationErrors: computed(() => mutation.error?.value?.errors ?? {}),
  }
}
```

---

## Query Key Structure

```typescript
// queryKeys.ts
export const formKeys = {
  all: ['dynamic-forms'] as const,
  lists: () => [...formKeys.all, 'list'] as const,
  list: (filters?: Ref<unknown>) => [...formKeys.lists(), filters] as const,
  details: () => [...formKeys.all, 'detail'] as const,
  detail: (id: string) => [...formKeys.details(), id] as const,
  versions: (formId: string) => [...formKeys.detail(formId), 'versions'] as const,
  version: (formId: Ref<string>, versionId: Ref<string>) =>
    [...formKeys.versions(formId.value), versionId.value] as const,
  submissions: (formId: string) => [...formKeys.detail(formId), 'submissions'] as const,
  submission: (formId: string, id: string) => [...formKeys.submissions(formId), id] as const,
}
```

---

## Submit Page Architecture

```vue
<!-- pages/FormSubmitPage.vue -->
<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useForm } from '../composables/useForm'
import { useFormVersion } from '../composables/useFormVersion'
import { useFormSubmit } from '../composables/useFormSubmit'

const route = useRoute()
const formId = computed(() => route.params.formId as string)

const { form, isLoading: formLoading, isError: formError } = useForm(formId)
const activeVersionId = computed(() => form.value?.active_version_id ?? '')

const { data: version, isLoading: schemaLoading } = useFormVersion(formId, activeVersionId)
const schema = computed(() => version.value?.schema)

const { submit, isSubmitting, validationErrors } = useFormSubmit(formId)
</script>

<template>
  <LoadingSpinner v-if="formLoading || schemaLoading" />
  <ErrorAlert v-else-if="formError" message="Failed to load form." />
  <DynamicFormRenderer
    v-else-if="schema"
    :schema="schema"
    :is-submitting="isSubmitting"
    :validation-errors="validationErrors"
    @submit="submit"
  />
</template>
```

The page composes composables. It contains no business logic.
