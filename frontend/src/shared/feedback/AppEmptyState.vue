<script setup lang="ts">
/**
 * AppEmptyState — standard empty-collection placeholder.
 *
 * Used when a data set is loaded successfully but contains zero items.
 * Provides a consistent visual pattern: icon → title → description → CTA.
 *
 * Slots:
 *   action — primary call-to-action (typically an AppButton)
 *
 * Forbidden patterns:
 *   <v-card><v-card-text class="text-center py-12">...</v-card-text></v-card>
 */

withDefaults(
  defineProps<{
    /** Material Design icon name (mdi-*). */
    icon?: string
    title: string
    description?: string
  }>(),
  {
    icon: 'mdi-inbox-outline',
  },
)
</script>

<template>
  <v-card variant="outlined" rounded="lg">
    <v-card-text class="text-center py-12 px-8">
      <v-icon
        :icon="icon"
        size="48"
        color="medium-emphasis"
        class="mb-4"
      />

      <p class="text-h6 font-weight-medium mb-2">{{ title }}</p>

      <p
        v-if="description"
        class="text-body-2 text-medium-emphasis mb-6"
      >
        {{ description }}
      </p>

      <div v-if="$slots.action">
        <slot name="action" />
      </div>
    </v-card-text>
  </v-card>
</template>
