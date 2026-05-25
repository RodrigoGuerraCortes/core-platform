<script setup lang="ts">
/**
 * AppConfirmDialog — canonical destructive-action confirmation dialog.
 *
 * Prevents engineers from building ad-hoc confirm dialogs with inconsistent
 * UX. All destructive confirmations (delete, archive, revoke) must use this.
 *
 * Props control the title, body, and button labels so the same component
 * covers all scenarios without duplication.
 *
 * Usage:
 *   <AppConfirmDialog
 *     v-model="showDialog"
 *     title="Delete user?"
 *     description="This action cannot be undone."
 *     confirm-label="Delete"
 *     confirm-variant="danger"
 *     :loading="isPending"
 *     @confirm="handleDelete"
 *   />
 */
import { AppButton } from '.'
import type { AppButtonVariant } from '.'

withDefaults(
  defineProps<{
    /** v-model — controls dialog visibility */
    modelValue: boolean
    title: string
    description?: string
    /** Label for the confirm button. Default: "Confirm" */
    confirmLabel?: string
    /** Label for the cancel button. Default: "Cancel" */
    cancelLabel?: string
    /** Variant applied to the confirm button. Default: "danger" */
    confirmVariant?: AppButtonVariant
    /** Show spinner on confirm button while mutation is in flight */
    loading?: boolean
  }>(),
  {
    description: undefined,
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    confirmVariant: 'danger',
    loading: false,
  },
)

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

function cancel() {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    max-width="420"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card rounded="lg">
      <v-card-title class="text-h6 pa-6 pb-3">{{ title }}</v-card-title>

      <v-card-text v-if="description" class="px-6 py-0 text-body-2 text-medium-emphasis">
        {{ description }}
      </v-card-text>

      <v-card-actions class="pa-4 gap-2">
        <v-spacer />
        <AppButton variant="ghost" :disabled="loading" @click="cancel">
          {{ cancelLabel }}
        </AppButton>
        <AppButton
          :variant="confirmVariant"
          :loading="loading"
          @click="emit('confirm')"
        >
          {{ confirmLabel }}
        </AppButton>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
