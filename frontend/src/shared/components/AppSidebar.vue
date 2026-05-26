<script setup lang="ts">
import { useDisplay } from 'vuetify'
import { useRoute } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useExperienceNavigation } from '@/experiences/shared/useExperienceNavigation'

defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const { smAndDown } = useDisplay()
const route = useRoute()
const tenantStore = useTenantStore()
const { items, branding } = useExperienceNavigation()

/**
 * An item is "active" when the current route name matches its name exactly
 * OR starts with it (e.g. 'forms.index' matches 'forms.index', 'forms.fill', etc.).
 */
function isActive(itemName: string): boolean {
  const name = String(route.name ?? '')
  return name === itemName || name.startsWith(`${itemName}.`)
}
</script>

<template>
  <v-navigation-drawer
    :model-value="smAndDown ? modelValue : true"
    :permanent="!smAndDown"
    :temporary="smAndDown"
    width="240"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Experience identity -->
    <v-list-item
      class="py-4"
      :title="branding.label"
      :subtitle="tenantStore.tenantSlug ? `/${tenantStore.tenantSlug}` : ''"
      :prepend-icon="branding.icon ?? 'mdi-domain'"
    />

    <v-divider />

    <!-- Experience navigation -->
    <v-list density="compact" nav class="mt-1">
      <v-list-item
        v-for="item in items"
        :key="item.name"
        :prepend-icon="item.icon"
        :title="item.label"
        :to="item.to"
        :active="isActive(item.name)"
        active-color="primary"
        rounded="lg"
      />
    </v-list>

    <!-- Bottom section: future user/settings links -->
    <template #append>
      <v-divider />
      <v-list density="compact" nav class="mb-1">
        <v-list-item
          prepend-icon="mdi-cog-outline"
          title="Settings"
          disabled
          rounded="lg"
        />
      </v-list>
    </template>
  </v-navigation-drawer>
</template>
