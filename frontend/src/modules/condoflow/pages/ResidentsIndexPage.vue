<script setup lang="ts">
import { ref, computed } from 'vue'
import { useResidentsQuery } from '../composables'
import { AppPageLayout, AppButton, AppStatusChip } from '@/shared/ui'
import { AppDataTable } from '@/shared/table'
import type { TableQueryParams } from '@/shared/table'

const queryParams = ref<TableQueryParams>({ page: 1, per_page: 15 })
const statusFilter = ref<string>('')

const { data, isLoading, isError } = useResidentsQuery(
  computed(() => ({ ...queryParams.value, status: statusFilter.value || undefined })),
)

const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)

const columns = [
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Teléfono' },
  { key: 'unit.number', label: 'Unidad' },
  { key: 'status', label: 'Estado' },
]

const statusOptions = [
  { title: 'Todos', value: '' },
  { title: 'Activo', value: 'active' },
  { title: 'Inactivo', value: 'inactive' },
]

function onPageChange(page: number) {
  queryParams.value = { ...queryParams.value, page }
}

function onSearch(search: string) {
  queryParams.value = { ...queryParams.value, page: 1, search }
}
</script>

<template>
  <AppPageLayout title="Residentes" subtitle="Gestión de residentes del condominio">
    <template #actions>
      <AppButton color="primary" prepend-icon="mdi-plus">
        Nuevo Residente
      </AppButton>
    </template>

    <v-row class="mb-4">
      <v-col cols="12" sm="4">
        <v-select
          v-model="statusFilter"
          :items="statusOptions"
          label="Estado"
          density="compact"
          variant="outlined"
          hide-details
        />
      </v-col>
    </v-row>

    <AppDataTable
      :items="items"
      :columns="columns"
      :loading="isLoading"
      :error="isError"
      :total="meta?.total ?? 0"
      :page="meta?.current_page ?? 1"
      :per-page="meta?.per_page ?? 15"
      searchable
      @update:page="onPageChange"
      @update:search="onSearch"
    >
      <template #col-status="{ item }">
        <AppStatusChip :status="item.status" />
      </template>
      <template #[`col-unit.number`]="{ item }">
        {{ item.unit?.number ?? '—' }}
      </template>
    </AppDataTable>
  </AppPageLayout>
</template>
