<script setup lang="ts">
/**
 * AppEntityActions — standardised entity-level action bar.
 *
 * Renders a group of actions for a detail page header.
 * On small screens the actions collapse into a "More" overflow menu
 * so the header doesn't break on mobile.
 *
 * The primary action is always visible. Secondary actions collapse.
 *
 * Slots:
 *   primary    — the main CTA (e.g. Edit button) — always visible
 *   secondary  — supporting actions — visible on md+, collapsed on mobile
 *   danger     — destructive actions — always in the overflow menu on mobile
 *
 * Usage:
 *   <AppEntityActions>
 *     <template #primary>
 *       <AppButton variant="primary">Edit</AppButton>
 *     </template>
 *     <template #secondary>
 *       <AppButton variant="secondary">Export</AppButton>
 *       <AppButton variant="ghost">Duplicate</AppButton>
 *     </template>
 *     <template #danger>
 *       <AppButton variant="danger">Delete</AppButton>
 *     </template>
 *   </AppEntityActions>
 */
</script>

<template>
  <div class="app-entity-actions d-flex align-center gap-2">
    <!-- Danger (always last in flow, visible md+) -->
    <div v-if="$slots.danger" class="d-none d-md-flex gap-2">
      <slot name="danger" />
    </div>

    <!-- Secondary actions (visible md+) -->
    <div v-if="$slots.secondary" class="d-none d-md-flex gap-2">
      <slot name="secondary" />
    </div>

    <!-- Primary action — always visible -->
    <slot name="primary" />

    <!-- Mobile overflow menu — collapses secondary + danger -->
    <v-menu v-if="$slots.secondary || $slots.danger">
      <template #activator="{ props: menuProps }">
        <v-btn
          v-bind="menuProps"
          icon="mdi-dots-vertical"
          variant="text"
          size="small"
          class="d-md-none"
        />
      </template>
      <v-list density="compact" min-width="180">
        <template v-if="$slots.secondary">
          <v-list-item>
            <slot name="secondary" />
          </v-list-item>
        </template>
        <v-divider v-if="$slots.secondary && $slots.danger" />
        <template v-if="$slots.danger">
          <v-list-item>
            <slot name="danger" />
          </v-list-item>
        </template>
      </v-list>
    </v-menu>
  </div>
</template>
