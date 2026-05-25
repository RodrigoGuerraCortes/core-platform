<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { useFormsQuery } from '../queries/useFormsQuery'
import { useTableState } from '@/shared/table'
import type { TableColumn, FilterField } from '@/shared/table'
import { AppPageLayout, AppButton } from '@/shared/ui'
import { AppDataTable, AppTableToolbar, AppFilterBar } from '@/shared/table'
import type { FormDetail } from '../types'

/**
 * FormsIndexPage — reference CRUD table implementation.
 *
 * Demonstrates the canonical platform CRUD pattern:
 *   useTableState → useFormsQuery(queryParams) → AppDataTable + AppTableToolbar + AppFilterBar
 *
 * All future module index pages MUST follow this pattern.
 */

const tenantStore = useTenantStore()
const router = useRouter()

// ── Table state (pagination + sort + filters) ─────────────────────────────
const table = useTableState({
  defaultSort: { key: 'created_at', direction: 'desc' },
  defaultFilters: { search: null, status: null },
})

// ── Server query — reactive to all table state changes ────────────────────
const { data, isLoading, isError } = useFormsQuery(table.queryParams)

const rows = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.meta.total ?? 0)

// ── Column definitions ────────────────────────────────────────────────────
const columns: TableColumn<FormDetail>[] = [
  { key: 'name',       label: 'Name',        sortable: true },
  { key: 'status',     label: 'Status',       sortable: true, width: 120 },
  { key: 'created_at', label: 'Created',      sortable: true, width: 160 },
]

// ── Filter field descriptors ──────────────────────────────────────────────
const filterFields: FilterField[] = [
  {
    key: 'search',
    label: 'Search',
    type: 'text',
    placeholder: 'Form name…',
  },
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    options: [
      { label: 'Draft',    value: 'draft' },
      { label: 'Active',   value: 'active' },
      { label: 'Archived', value: 'archived' },
    ],
  },
]

// ── Status chip helper ────────────────────────────────────────────────────
function statusColor(status: FormDetail['status']): string {
  return { draft: 'warning', active: 'success', archived: 'default' }[status] ?? 'default'
}

// ── Navigation ────────────────────────────────────────────────────────────
function navigateToCreate(): void {
  router.push({ name: 'forms.create', params: { tenantSlug: tenantStore.tenantSlug } })
}

function navigateToEditor(formId: number): void {
  router.push({ name: 'forms.edit', params: { tenantSlug: tenantStore.tenantSlug, formId } })
}

function navigateToFill(formId: number): void {
  router.push({ name: 'dynamic-forms.fill', params: { tenantSlug: tenantStore.tenantSlug, formId } })
}

// ── Date formatter ────────────────────────────────────────────────────────
function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}
</script>

<template>
  <AppPageLayout
    title="Forms"
    description="Create and manage forms for this tenant."
  >
    <template #actions>
      <AppButton prepend-icon="mdi-plus" @click="navigateToCreate">
        New Form
      </AppButton>
    </template>

    <AppDataTable
      :columns="columns"
      :rows="rows"
      :total="total"
      :page="table.page.value"
      :per-page="table.perPage.value"
      :loading="isLoading"
      :error="isError"
      empty-title="No forms yet"
      empty-description="Create your first form to get started."
      empty-icon="mdi-file-document-multiple-outline"
      error-message="Could not load forms. Please refresh the page."
      @update:page="table.setPage"
      @update:per-page="table.setPerPage"
      @update:sort="table.setSort"
    >
      <!-- Toolbar: search + status filter + create action -->
      <template #toolbar>
        <AppTableToolbar :total="total">
          <template #filters>
            <AppFilterBar
              :model-value="table.filters.value"
              :fields="filterFields"
              @update:model-value="table.setFilters"
            />
          </template>
        </AppTableToolbar>
      </template>

      <!-- Status column — chip renderer -->
      <template #col-status="{ value }">
        <v-chip :color="statusColor(value as FormDetail['status'])" size="small" variant="tonal">
          {{ value }}
        </v-chip>
      </template>

      <!-- Created at column — formatted date -->
      <template #col-created_at="{ value }">
        {{ formatDate(value as string) }}
      </template>

      <!-- Row actions -->
      <template #actions="{ row }">
        <AppButton
          icon="mdi-pencil-outline"
          variant="ghost"
          size="small"
          :aria-label="`Edit ${row.name}`"
          @click="navigateToEditor(row.id as number)"
        />
        <AppButton
          v-if="row.status === 'active'"
          icon="mdi-play-circle-outline"
          variant="ghost"
          size="small"
          :aria-label="`Fill ${row.name}`"
          @click="navigateToFill(row.id as number)"
        />
      </template>

      <!-- Empty state CTA -->
      <template #empty-action>
        <AppButton prepend-icon="mdi-plus" @click="navigateToCreate">
          Create Form
        </AppButton>
      </template>
    </AppDataTable>
  </AppPageLayout>
</template>
