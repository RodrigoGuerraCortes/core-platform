<script setup lang="ts">
/**
 * AppEntityHeader — top-of-detail-page entity presentation block.
 *
 * Renders the entity's visual identity: avatar/icon, name, identifier,
 * status chip, and a horizontal metadata strip.
 *
 * Slots:
 *   avatar    — custom avatar, icon, or image (overrides `icon` prop)
 *   status    — AppStatusChip or AppEntityStatus (right of avatar)
 *   meta      — AppEntityMeta row (below the name line)
 *   actions   — right-aligned AppButton group
 *
 * Usage:
 *   <AppEntityHeader
 *     name="Alice Chen"
 *     identifier="#USER-001"
 *     icon="mdi-account-outline"
 *     icon-color="primary"
 *   >
 *     <template #status><AppStatusChip status="active" /></template>
 *     <template #meta><AppEntityMeta :items="metaItems" /></template>
 *     <template #actions>
 *       <AppButton variant="secondary">Edit</AppButton>
 *     </template>
 *   </AppEntityHeader>
 */
withDefaults(
  defineProps<{
    /** Primary display name. */
    name: string
    /** Secondary identifier (e.g. "#USR-001", email, code). */
    identifier?: string
    /** MDI icon for the avatar when no #avatar slot is provided. */
    icon?: string
    /** Avatar background colour. */
    iconColor?: string
    /** Avatar size in px. Default: 48. */
    avatarSize?: number
  }>(),
  {
    identifier: undefined,
    icon: 'mdi-entity',
    iconColor: 'primary',
    avatarSize: 48,
  },
)
</script>

<template>
  <div class="app-entity-header">
    <div class="d-flex align-start gap-4 flex-wrap">
      <!-- Avatar / Icon -->
      <div class="flex-shrink-0">
        <slot name="avatar">
          <v-avatar :size="avatarSize" :color="iconColor" variant="tonal">
            <v-icon :icon="icon" :size="Math.round(avatarSize * 0.5)" />
          </v-avatar>
        </slot>
      </div>

      <!-- Name + identifier + status -->
      <div class="flex-grow-1 min-w-0">
        <div class="d-flex align-center gap-2 flex-wrap mb-0-5">
          <h2 class="text-h6 font-weight-bold text-truncate">{{ name }}</h2>
          <span v-if="identifier" class="text-caption text-medium-emphasis font-mono">
            {{ identifier }}
          </span>
          <div v-if="$slots.status">
            <slot name="status" />
          </div>
        </div>

        <!-- Metadata strip -->
        <div v-if="$slots.meta" class="mt-2">
          <slot name="meta" />
        </div>
      </div>

      <!-- Actions -->
      <div v-if="$slots.actions" class="d-flex gap-2 align-center flex-shrink-0">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
