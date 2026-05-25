<script setup lang="ts">
/**
 * AppStatusChip — canonical status/badge chip.
 *
 * Replaces ad-hoc <v-chip color="..."> usage throughout the codebase.
 * Centralises status → colour + icon mapping so visual language stays
 * consistent without repeating mapping logic in every component.
 *
 * Built-in preset statuses cover the most common domain values.
 * Pass `color` and `icon` directly for custom statuses.
 *
 * Usage (preset):
 *   <AppStatusChip status="active" />
 *   <AppStatusChip status="pending" />
 *
 * Usage (custom):
 *   <AppStatusChip label="In Review" color="info" icon="mdi-eye-outline" />
 */

type PresetStatus =
  | 'active'
  | 'inactive'
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'draft'
  | 'published'
  | 'archived'
  | 'error'

const PRESET_MAP: Record<PresetStatus, { color: string; icon: string; label: string }> = {
  active:    { color: 'success', icon: 'mdi-check-circle-outline',  label: 'Active' },
  inactive:  { color: 'default', icon: 'mdi-minus-circle-outline',  label: 'Inactive' },
  pending:   { color: 'warning', icon: 'mdi-clock-outline',         label: 'Pending' },
  approved:  { color: 'success', icon: 'mdi-check-decagram-outline', label: 'Approved' },
  rejected:  { color: 'error',   icon: 'mdi-close-circle-outline',  label: 'Rejected' },
  draft:     { color: 'default', icon: 'mdi-pencil-outline',        label: 'Draft' },
  published: { color: 'primary', icon: 'mdi-earth',                 label: 'Published' },
  archived:  { color: 'default', icon: 'mdi-archive-outline',       label: 'Archived' },
  error:     { color: 'error',   icon: 'mdi-alert-circle-outline',  label: 'Error' },
}

const props = withDefaults(
  defineProps<{
    /** Preset status key — drives colour and icon automatically. */
    status?: PresetStatus
    /** Override or custom label. Falls back to capitalised status name. */
    label?: string
    /** Override colour (use when status is non-preset). */
    color?: string
    /** Override icon. */
    icon?: string
    /** Chip size. Default: small. */
    size?: 'x-small' | 'small' | 'default'
  }>(),
  {
    status: undefined,
    label: undefined,
    color: undefined,
    icon: undefined,
    size: 'small',
  },
)

const preset = props.status ? PRESET_MAP[props.status] : null
const resolvedColor = props.color ?? preset?.color ?? 'default'
const resolvedIcon  = props.icon  ?? preset?.icon
const resolvedLabel = props.label ?? preset?.label ?? props.status ?? ''
</script>

<template>
  <v-chip
    :color="resolvedColor"
    :prepend-icon="resolvedIcon"
    :size="size"
    variant="tonal"
    class="font-weight-medium"
  >
    {{ resolvedLabel }}
  </v-chip>
</template>
