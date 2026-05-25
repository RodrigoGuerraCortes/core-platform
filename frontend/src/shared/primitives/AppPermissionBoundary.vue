<script setup lang="ts">
/**
 * AppPermissionBoundary — declarative permission gate for UI sections.
 *
 * Wraps content that should only be visible or interactive when the
 * current user has the required permission(s).
 *
 * Modes:
 *   hide     — renders nothing when permission is absent (default)
 *   disable  — renders content with pointer-events off + aria-disabled
 *   readonly — renders content but replaces actions slot with fallback
 *
 * The `fallback` slot is rendered in place of the default slot when
 * the permission check fails and mode !== 'hide'.
 *
 * Usage:
 *   <!-- Simple hide -->
 *   <AppPermissionBoundary permission="users.delete">
 *     <AppButton variant="danger">Delete</AppButton>
 *   </AppPermissionBoundary>
 *
 *   <!-- Disabled with tooltip -->
 *   <AppPermissionBoundary permission="forms.publish" mode="disable">
 *     <AppButton variant="primary">Publish</AppButton>
 *     <template #fallback>
 *       <AppButton variant="primary" disabled>Publish</AppButton>
 *     </template>
 *   </AppPermissionBoundary>
 *
 *   <!-- Any-of check -->
 *   <AppPermissionBoundary :any-of="['admin', 'users.edit']">
 *     ...
 *   </AppPermissionBoundary>
 */
import { computed } from 'vue'
import { usePermission } from '@/shared/composables/usePermission'

const props = withDefaults(
  defineProps<{
    /** Single required permission string. */
    permission?: string
    /** User must have at least one of these permissions. */
    anyOf?: string[]
    /** User must have ALL of these permissions. */
    allOf?: string[]
    /** Required role string. */
    role?: string
    /**
     * Behaviour when the check fails:
     *   hide     — render nothing (default)
     *   disable  — render with opacity + pointer-events: none
     *   readonly — render #fallback slot
     */
    mode?: 'hide' | 'disable' | 'readonly'
  }>(),
  {
    permission: undefined,
    anyOf: undefined,
    allOf: undefined,
    role: undefined,
    mode: 'hide',
  },
)

const { can, canAny, canAll, is } = usePermission()

const allowed = computed(() => {
  if (props.permission && !can(props.permission)) return false
  if (props.anyOf && !canAny(props.anyOf)) return false
  if (props.allOf && !canAll(props.allOf)) return false
  if (props.role && !is(props.role)) return false
  return true
})
</script>

<template>
  <!-- Allowed: always render default slot -->
  <slot v-if="allowed" />

  <!-- Denied + hide mode: render nothing -->
  <template v-else-if="mode === 'hide'" />

  <!-- Denied + disable mode: wrap in a disabled overlay -->
  <div
    v-else-if="mode === 'disable'"
    class="permission-disabled"
    aria-disabled="true"
  >
    <slot v-if="$slots.fallback" name="fallback" />
    <slot v-else />
  </div>

  <!-- Denied + readonly mode: render the fallback slot -->
  <slot v-else-if="mode === 'readonly' && $slots.fallback" name="fallback" />
</template>

<style scoped>
.permission-disabled {
  opacity: 0.45;
  pointer-events: none;
  user-select: none;
}
</style>
