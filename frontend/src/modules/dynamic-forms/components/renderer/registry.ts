import type { Component } from 'vue'
import { defineAsyncComponent } from 'vue'

/**
 * Explicit field-type → component loader map.
 *
 * Rules:
 * - All entries are lazy-loaded (code-split by Vite).
 * - No auto-discovery, no reflection, no filesystem magic.
 * - Adding a new field type requires an explicit entry here.
 * - `file` and `repeater` are intentionally absent in V1.
 */
const REGISTRY: Record<string, () => Promise<{ default: Component }>> = {
  text: () => import('../fields/TextField.vue'),
  textarea: () => import('../fields/TextareaField.vue'),
  email: () => import('../fields/EmailField.vue'),
  number: () => import('../fields/NumberField.vue'),
  date: () => import('../fields/DateField.vue'),
  select: () => import('../fields/SelectField.vue'),
  radio: () => import('../fields/RadioField.vue'),
  checkbox: () => import('../fields/CheckboxField.vue'),
  section: () => import('../fields/SectionField.vue'),
}

/**
 * Cached async component instances per field type.
 *
 * Creating a new `defineAsyncComponent` on every call would give Vue a new
 * component definition identity each render, forcing it to unmount/remount
 * the field on every update. Caching ensures the definition is stable.
 */
const COMPONENT_CACHE = new Map<string, Component>()

/**
 * Returns a `defineAsyncComponent`-compatible loader for the given field type.
 * Returns null for unsupported types — callers must handle the null case.
 */
export function resolveFieldRenderer(type: string): Component | null {
  const loader = REGISTRY[type]
  if (!loader) return null

  if (!COMPONENT_CACHE.has(type)) {
    COMPONENT_CACHE.set(
      type,
      defineAsyncComponent({
        loader,
        // Callers that need a loading/error slot can wrap in <Suspense>.
        delay: 0,
        timeout: 10_000,
      }),
    )
  }

  return COMPONENT_CACHE.get(type)!
}

/** All registered field types. Useful for tests and guards. */
export const SUPPORTED_FIELD_TYPES = Object.keys(REGISTRY) as ReadonlyArray<string>
