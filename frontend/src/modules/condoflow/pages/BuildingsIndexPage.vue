<script setup lang="ts">
import { ref, computed } from 'vue'
import { useBuildingsQuery } from '../composables'
import { AppPageLayout, AppButton } from '@/shared/ui'
import { AppDataTable } from '@/shared/table'
import type { TableQueryParams } from '@/shared/table'

const queryParams = ref<TableQueryParams>({ page: 1, per_page: 15 })
const { data, isLoading, isError } = useBuildingsQuery(queryParams)

const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)

const columns = [
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'address', label: 'Dirección', sortable: true },
  { key: 'floors', label: 'Pisos', sortable: true },
  { key: 'units_count', label: 'Unidades' },
  { key: 'created_at', label: 'Creado', sortable: true },
]

function onPageChange(page: number) {
  queryParams.value = { ...queryParams.value, page }
}
</script>

<template>
  <AppPageLayout title="Edificios" subtitle="Gestión de edificios del condominio">
    <template #actions>
      <AppButton color="primary" prepend-icon="mdi-plus">
        Nuevo Edificio
      </AppButton>
    </template>

    <AppDataTable
      :rows="items"
      :columns="columns"
      :loading="isLoading"
      :error="isError"
      :total="meta?.total ?? 0"
      :page="meta?.current_page ?? 1"
      :per-page="meta?.per_page ?? 15"
      @update:page="onPageChange"
    >
      <template #col-created_at="{ value }">
        {{ new Date(value).toLocaleDateString() }}
      </template>
    </AppDataTable>
  </AppPageLayout>
</template>
