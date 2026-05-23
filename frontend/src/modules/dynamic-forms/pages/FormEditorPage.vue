<script setup lang="ts">
/**
 * FormEditorPage — Draft version authoring.
 *
 * Flow:
 *   1. Load form + its versions
 *   2. Initialise local draft schema from the latest version (or empty)
 *   3. User edits fields (add / remove / reorder / configure)
 *   4. "Save Draft" → POST /forms/{id}/versions (immutable, creates new version)
 *   5. "Publish" → save if dirty, then POST /forms/{id}/publish
 *
 * Invariants:
 *   - Published versions are never mutated.
 *   - Each "Save Draft" produces a new FormVersion record.
 *   - The runtime renderer used in preview is the same component used for filling.
 */
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useFormQuery } from '../queries/useFormQuery'
import { useFormVersionsQuery } from '../queries/useFormVersionsQuery'
import { useCreateVersionMutation } from '../queries/useCreateVersionMutation'
import { usePublishFormMutation } from '../queries/usePublishFormMutation'
import { useDraftSchema } from '../composables/useDraftSchema'
import FieldList from '../components/authoring/FieldList.vue'
import AddFieldMenu from '../components/authoring/AddFieldMenu.vue'
import FieldConfigPanel from '../components/authoring/FieldConfigPanel.vue'
import type { FormField, FormSchema } from '../types'

const route = useRoute()
const router = useRouter()
const tenantStore = useTenantStore()

const formId = computed(() => Number(route.params.formId))

const { data: form, isLoading: formLoading, isError: formError } = useFormQuery(formId)
const { data: versionsData } = useFormVersionsQuery(formId)

// ─── Draft schema initialisation ─────────────────────────────────────────────

const draft = useDraftSchema()
const schemaInitialised = ref(false)

// The working schema seed: latest version's schema, or empty.
const latestVersion = computed(() => versionsData.value?.data[0] ?? null)

watch(
  [latestVersion, formLoading],
  ([version, loading]) => {
    if (loading || schemaInitialised.value) return

    if (version) {
      draft.reset(version.schema)
    } else {
      // No versions yet — seed with the form name as title
      draft.reset({
        version: 1,
        title: form.value?.name ?? 'Untitled Form',
        fields: [],
      })
    }

    schemaInitialised.value = true
  },
  { immediate: true },
)

// ─── Field selection ──────────────────────────────────────────────────────────

const selectedFieldKey = ref<string | null>(null)

const selectedField = computed(() =>
  selectedFieldKey.value
    ? draft.sortedFields.value.find((f) => f.key === selectedFieldKey.value) ?? null
    : null,
)

function selectField(key: string): void {
  selectedFieldKey.value = selectedFieldKey.value === key ? null : key
}

function deselectField(): void {
  selectedFieldKey.value = null
}

function onFieldAdded(type: FormField['type']): void {
  draft.addField(type)
  // Auto-select the newly added field
  const last = draft.sortedFields.value[draft.sortedFields.value.length - 1]
  if (last) selectedFieldKey.value = last.key
}

function onFieldRemoved(key: string): void {
  if (selectedFieldKey.value === key) selectedFieldKey.value = null
  draft.removeField(key)
}

// ─── Save draft ───────────────────────────────────────────────────────────────

const saveError = ref<string | null>(null)
const { mutateAsync: createVersion, isPending: isSaving } = useCreateVersionMutation(formId.value)

async function saveDraft(): Promise<boolean> {
  saveError.value = null
  try {
    const saved = await createVersion({ schema: draft.schema.value })
    draft.markSaved(saved.schema)
    return true
  } catch {
    saveError.value = 'Could not save draft. Please try again.'
    return false
  }
}

// ─── Publish ──────────────────────────────────────────────────────────────────

const publishError = ref<string | null>(null)
const publishConfirmOpen = ref(false)
const { mutateAsync: doPublish, isPending: isPublishing } = usePublishFormMutation(formId.value)

async function handlePublish(): Promise<void> {
  publishError.value = null
  publishConfirmOpen.value = false

  // Save unsaved changes before publishing
  if (draft.isDirty.value) {
    const saved = await saveDraft()
    if (!saved) return
  }

  try {
    await doPublish()
  } catch (err: unknown) {
    const msg =
      (err as { response?: { data?: { message?: string } } })?.response?.data?.message
    publishError.value = msg ?? 'Could not publish. Ensure the form has at least one field.'
  }
}

// ─── Navigation ───────────────────────────────────────────────────────────────

const isBusy = computed(() => isSaving.value || isPublishing.value)

function goToPreview(): void {
  router.push({
    name: 'forms.preview',
    params: { tenantSlug: tenantStore.tenantSlug, formId: formId.value },
  })
}

function goToList(): void {
  router.push({ name: 'forms.index', params: { tenantSlug: tenantStore.tenantSlug } })
}

const isPublished = computed(() => form.value?.status === 'active')
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="formLoading" class="d-flex flex-column gap-3">
      <v-skeleton-loader type="heading" />
      <v-skeleton-loader type="article" />
    </div>

    <!-- Error -->
    <v-alert v-else-if="formError" type="error" variant="tonal" rounded="lg">
      Could not load form. <a href="#" @click.prevent="goToList">Go back</a>
    </v-alert>

    <!-- Editor -->
    <div v-else-if="form">
      <!-- Toolbar -->
      <div class="d-flex align-center gap-3 mb-6 flex-wrap">
        <v-btn icon="mdi-arrow-left" variant="text" size="small" @click="goToList" />

        <div class="flex-grow-1">
          <div class="d-flex align-center gap-2">
            <h1 class="text-h6 font-weight-bold">{{ form.name }}</h1>
            <v-chip
              :color="form.status === 'active' ? 'success' : 'warning'"
              size="x-small"
              variant="tonal"
            >
              {{ form.status }}
            </v-chip>
            <v-chip v-if="draft.isDirty.value" color="orange" size="x-small" variant="tonal">
              Unsaved changes
            </v-chip>
          </div>
          <p v-if="latestVersion" class="text-caption text-medium-emphasis">
            v{{ latestVersion.version_number }} ·
            {{ latestVersion.published_at ? 'Published' : 'Draft' }}
          </p>
        </div>

        <div class="d-flex gap-2">
          <v-btn
            variant="tonal"
            prepend-icon="mdi-eye-outline"
            size="small"
            :disabled="!latestVersion && !draft.fieldCount.value"
            @click="goToPreview"
          >
            Preview
          </v-btn>

          <v-btn
            variant="tonal"
            prepend-icon="mdi-content-save-outline"
            size="small"
            :loading="isSaving"
            :disabled="!draft.isDirty.value || isBusy"
            @click="saveDraft"
          >
            Save Draft
          </v-btn>

          <v-btn
            v-if="!isPublished"
            color="primary"
            variant="flat"
            prepend-icon="mdi-publish"
            size="small"
            :loading="isPublishing"
            :disabled="isBusy || draft.fieldCount.value === 0"
            @click="publishConfirmOpen = true"
          >
            Publish
          </v-btn>

          <v-chip v-else color="success" prepend-icon="mdi-check-circle" variant="flat" size="small">
            Published
          </v-chip>
        </div>
      </div>

      <!-- Error alerts -->
      <v-alert v-if="saveError" type="error" variant="tonal" rounded="lg" closable class="mb-4" @click:close="saveError = null">
        {{ saveError }}
      </v-alert>
      <v-alert v-if="publishError" type="error" variant="tonal" rounded="lg" closable class="mb-4" @click:close="publishError = null">
        {{ publishError }}
      </v-alert>

      <!-- Form title -->
      <v-text-field
        :model-value="draft.schema.value.title"
        label="Form Title"
        density="comfortable"
        variant="outlined"
        class="mb-4"
        :disabled="isBusy"
        @update:model-value="draft.setTitle"
      />

      <!-- Two-column layout: field list + config panel -->
      <div class="d-flex gap-4 align-start">
        <!-- Left: field list -->
        <div style="flex: 1; min-width: 0;">
          <div class="d-flex align-center justify-space-between mb-3">
            <p class="text-body-2 font-weight-semibold">Fields</p>
            <AddFieldMenu :disabled="isBusy" @add="onFieldAdded" />
          </div>

          <FieldList
            :fields="draft.sortedFields.value"
            :selected-key="selectedFieldKey"
            @select="selectField"
            @remove="onFieldRemoved"
            @move="(key, dir) => draft.moveField(key, dir)"
          />
        </div>

        <!-- Right: field config -->
        <div v-if="selectedField" style="width: 320px; flex-shrink: 0;">
          <FieldConfigPanel
            :field="selectedField"
            @update="(patch) => draft.updateField(selectedField!.key, patch)"
            @close="deselectField"
          />
        </div>

        <!-- Right: empty prompt -->
        <div v-else-if="draft.fieldCount.value > 0" style="width: 320px; flex-shrink: 0;">
          <v-card variant="outlined" rounded="lg" class="pa-4 text-center text-medium-emphasis">
            <v-icon icon="mdi-cursor-default-click-outline" size="32" class="mb-2" />
            <p class="text-body-2">Click a field to configure it</p>
          </v-card>
        </div>
      </div>
    </div>

    <!-- Publish confirmation dialog -->
    <v-dialog v-model="publishConfirmOpen" max-width="400">
      <v-card rounded="lg">
        <v-card-title class="text-h6 pa-6 pb-2">Publish Form?</v-card-title>
        <v-card-text class="px-6">
          Publishing will make this form live and accept submissions.
          The current schema will be locked as the active version.
        </v-card-text>
        <v-card-actions class="pa-4 gap-2">
          <v-spacer />
          <v-btn variant="text" @click="publishConfirmOpen = false">Cancel</v-btn>
          <v-btn color="primary" variant="flat" :loading="isPublishing" @click="handlePublish">
            Publish
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
