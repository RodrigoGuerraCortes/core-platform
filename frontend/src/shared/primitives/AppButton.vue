<script setup lang="ts">
import { computed } from 'vue'

/**
 * AppButton — the ONLY sanctioned button primitive for Core Platform modules.
 *
 * Wraps Vuetify VBtn with a fixed set of semantic variants. Modules must not
 * use VBtn directly — this ensures consistent behavior, spacing, and loading
 * treatment across the entire platform.
 *
 * Variants map to an explicit Vuetify (color + variant) pair:
 *   primary   → color=primary,  variant=flat      ← default CTA
 *   secondary → color=default,  variant=outlined  ← secondary action
 *   ghost     → color=default,  variant=text      ← low-emphasis action
 *   danger    → color=error,    variant=flat      ← destructive action
 *   tonal     → color=primary,  variant=tonal     ← soft emphasis
 *
 * Forbidden patterns:
 *   <v-btn color="..." variant="..." />          ← use AppButton instead
 *   <AppButton variant="flat" color="success" /> ← do not mix props; use a variant
 *
 * Icon usage:
 *   <AppButton prepend-icon="mdi-plus">New</AppButton>
 *   <AppButton icon="mdi-close" aria-label="Close" />   ← icon-only
 */

export type AppButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'tonal'

const props = withDefaults(
  defineProps<{
    /** Semantic variant — controls both color and Vuetify variant. */
    variant?: AppButtonVariant
    loading?: boolean
    disabled?: boolean
    /** Prepend a Material Design icon (mdi-*). */
    prependIcon?: string
    /** Append a Material Design icon (mdi-*). */
    appendIcon?: string
    /** Icon-only button. Use aria-label for accessibility. */
    icon?: string
    size?: 'small' | 'default' | 'large'
    /** Expand button to full container width. */
    block?: boolean
    type?: 'button' | 'submit'
  }>(),
  {
    variant: 'primary',
    size: 'default',
    type: 'button',
  },
)

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const VARIANT_MAP: Record<AppButtonVariant, { color: string; vuetifyVariant: string }> = {
  primary:   { color: 'primary', vuetifyVariant: 'flat' },
  secondary: { color: 'default', vuetifyVariant: 'outlined' },
  ghost:     { color: 'default', vuetifyVariant: 'text' },
  danger:    { color: 'error',   vuetifyVariant: 'flat' },
  tonal:     { color: 'primary', vuetifyVariant: 'tonal' },
}

const resolved = computed(() => VARIANT_MAP[props.variant])
</script>

<template>
  <v-btn
    :color="resolved.color"
    :variant="resolved.vuetifyVariant as any"
    :loading="loading"
    :disabled="disabled || loading"
    :prepend-icon="prependIcon"
    :append-icon="appendIcon"
    :icon="icon"
    :size="size"
    :block="block"
    :type="type"
    @click="emit('click', $event)"
  >
    <slot />
  </v-btn>
</template>
