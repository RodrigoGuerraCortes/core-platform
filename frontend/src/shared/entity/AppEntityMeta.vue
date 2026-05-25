<script setup lang="ts">
/**
 * AppEntityMeta — horizontal metadata strip for entity detail pages.
 *
 * Renders a list of label → value pairs in a responsive flex row.
 * Collapses to a vertical list on mobile.
 *
 * Accepts either declarative `items` prop (preferred) or the default
 * slot for fully custom content.
 *
 * Usage (declarative):
 *   <AppEntityMeta :items="[
 *     { label: 'Role',    value: 'Admin',           icon: 'mdi-shield-outline' },
 *     { label: 'Joined',  value: '12 Jan 2025' },
 *     { label: 'Last active', value: '2 hours ago' },
 *   ]" />
 *
 * Usage (slot):
 *   <AppEntityMeta>
 *     <AppEntityMetaItem label="Department" value="Engineering" />
 *   </AppEntityMeta>
 */
export interface MetaItem {
  label: string
  value: string | null | undefined
  icon?: string
  /** Vuetify colour applied to the value text. */
  valueColor?: string
}

withDefaults(
  defineProps<{
    items?: MetaItem[]
    /** Separator between items in horizontal mode. Default: true. */
    dividers?: boolean
  }>(),
  {
    items: undefined,
    dividers: true,
  },
)
</script>

<template>
  <div class="app-entity-meta d-flex flex-wrap gap-x-6 gap-y-2 align-center">
    <!-- Declarative items -->
    <template v-if="items">
      <div
        v-for="(item, idx) in items"
        :key="idx"
        class="d-flex align-center gap-1"
      >
        <!-- Divider between items (not before first) -->
        <span
          v-if="dividers && idx > 0"
          class="text-disabled mr-6 d-none d-sm-inline"
          aria-hidden="true"
        >·</span>

        <v-icon v-if="item.icon" :icon="item.icon" size="14" class="text-medium-emphasis" />

        <span class="text-caption text-medium-emphasis">{{ item.label }}:</span>

        <span
          class="text-caption font-weight-medium"
          :class="item.valueColor ? `text-${item.valueColor}` : ''"
        >
          {{ item.value ?? '—' }}
        </span>
      </div>
    </template>

    <!-- Slot-based items -->
    <slot />
  </div>
</template>
