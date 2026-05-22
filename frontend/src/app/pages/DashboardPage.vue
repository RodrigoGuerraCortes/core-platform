<script setup lang="ts">
import { useTenantStore } from '@/stores/tenant'
import { useNavigation } from '@/shared/composables/useNavigation'

const tenantStore = useTenantStore()
const { items } = useNavigation(() => tenantStore.tenantSlug)
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Dashboard</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Welcome to {{ tenantStore.current?.name ?? 'your workspace' }}.
        </p>
      </div>
    </div>

    <!-- Quick links -->
    <v-row>
      <v-col
        v-for="item in items.filter(i => i.name !== 'dashboard')"
        :key="item.name"
        cols="12"
        sm="6"
        md="4"
      >
        <v-card :to="item.to" variant="outlined" rounded="lg" hover>
          <v-card-item>
            <template #prepend>
              <v-icon :icon="item.icon" size="28" color="primary" class="mr-2" />
            </template>
            <v-card-title class="text-body-1">{{ item.label }}</v-card-title>
          </v-card-item>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
