<script setup lang="ts">
/**
 * AppSection — semantic page section with optional title and divider.
 *
 * Use to group related content within a page. Provides consistent
 * heading hierarchy, spacing, and optional top divider.
 *
 * Slots:
 *   default     — section body content
 *   header-end  — right-aligned content alongside the title (e.g. actions)
 */
withDefaults(
  defineProps<{
    /** Section heading label. Omit for an untitled section. */
    title?: string
    /** Subtitle / descriptive text below the title. */
    description?: string
    /** Render a <v-divider> above the section. */
    divider?: boolean
    /** Remove the default bottom margin. */
    flush?: boolean
  }>(),
  {
    title: undefined,
    description: undefined,
    divider: false,
    flush: false,
  },
)
</script>

<template>
  <section :class="['app-section', { 'mb-6': !flush }]">
    <v-divider v-if="divider" class="mb-6" />

    <div v-if="title || $slots['header-end']" class="d-flex align-start justify-space-between gap-3 mb-4">
      <div v-if="title">
        <p class="text-subtitle-1 font-weight-semibold">{{ title }}</p>
        <p v-if="description" class="text-caption text-medium-emphasis mt-0-5">{{ description }}</p>
      </div>
      <div v-if="$slots['header-end']" class="flex-shrink-0">
        <slot name="header-end" />
      </div>
    </div>

    <slot />
  </section>
</template>
