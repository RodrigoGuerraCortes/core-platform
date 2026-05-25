<script setup lang="ts">
/**
 * UsersExamplePage — canonical enterprise CRUD table reference.
 *
 * Demonstrates the official pattern for any paginated, filterable,
 * sortable resource list with inline row actions and delete confirmation.
 *
 * Pattern:
 *   useTableState → queryParams → useReferenceUsersQuery → AppDataTable
 *
 * Copy this pattern when implementing any new module index page.
 */
import { ref, computed } from 'vue'
import { AppPageLayout, AppButton } from '@/shared/ui'
import { AppDataTable, AppTableToolbar, AppFilterBar, useTableState } from '@/shared/table'
import type { TableColumn, FilterField } from '@/shared/table'
import { useReferenceUsersQuery, useDeleteUserMutation } from '../composables'
import type { ReferenceUser, UserStatus } from '../types'

// ── Table state (pagination + sort + filters) ─────────────────────────────
const table = useTableState({
  defaultSort: { key: 'name', direction: 'asc' },
  defaultFilters: { search: null, status: null },
})

// ── Server query — reactive to all table state changes ────────────────────
const { data, isLoading, isError } = useReferenceUsersQuery(table.queryParams)

const rows    = computed(() => data.value?.data ?? [])
const total   = computed(() => data.value?.meta.total ?? 0)

// ── Column definitions ────────────────────────────────────────────────────
const columns: TableColumn<ReferenceUser>[] = [
  { key: 'name',           label: 'Name',        sortable: true },
  { key: 'email',          label: 'Email',        sortable: true },
  { key: 'role',           label: 'Role',         sortable: true, width: 100 },
  { key: 'status',         label: 'Status',       sortable: true, width: 110 },
  { key: 'joined_at',      label: 'Joined',       sortable: true, width: 130 },
  { key: 'last_active_at', label: 'Last Active',  sortable: false, width: 130 },
]

// ── Filter descriptors ────────────────────────────────────────────────────
const filterFields: FilterField[] = [
  { key: 'search', label: 'Search', type: 'text', placeholder: 'Name or email…' },
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    options: [
      { label: 'Active',   value: 'active' },
      { label: 'Inactive', value: 'inactive' },
      { label: 'Pending',  value: 'pending' },
    ],
  },
]

// ── Status + role helpers ─────────────────────────────────────────────────
const STATUS_COLOR: Record<UserStatus, string> = {
  active: 'success', inactive: 'default', pending: 'warning',
}

// ── Delete confirmation dialog ────────────────────────────────────────────
const deleteTarget = ref<ReferenceUser | null>(null)
const { mutateAsync: deleteUser, isPending: isDeleting } = useDeleteUserMutation()

function confirmDelete(row: ReferenceUser): void {
  deleteTarget.value = row
}

async function executeDelete(): Promise<void> {
  if (!deleteTarget.value) return
  await deleteUser(deleteTarget.value.id)
  deleteTarget.value = null
}

// ── Date formatter ────────────────────────────────────────────────────────
function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}
</script>

<template>
  <AppPageLayout
    title="Users"
    description="Manage workspace members and their access roles."
  >
    <template #actions>
      <AppButton prepend-icon="mdi-account-plus-outline" variant="primary">
        Invite User
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
      empty-icon="mdi-account-multiple-outline"
      empty-title="No users found"
      empty-description="Try adjusting your filters or invite the first team member."
      error-message="Could not load users. Please try again."
      @update:page="table.setPage"
      @update:per-page="table.setPerPage"
      @update:sort="table.setSort"
    >
      <!-- Toolbar -->
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

      <!-- Status column -->
      <template #col-status="{ value }">
        <v-chip
          :color="STATUS_COLOR[value as UserStatus]"
          size="small"
          variant="tonal"
        >
          {{ value }}
        </v-chip>
      </template>

      <!-- Role column -->
      <template #col-role="{ value }">
        <span class="text-body-2 text-capitalize">{{ value }}</span>
      </template>

      <!-- Joined date -->
      <template #col-joined_at="{ value }">
        {{ formatDate(value as string) }}
      </template>

      <!-- Last active date -->
      <template #col-last_active_at="{ value }">
        {{ formatDate(value as string | null) }}
      </template>

      <!-- Row actions -->
      <template #actions="{ row }">
        <AppButton
          icon="mdi-pencil-outline"
          variant="ghost"
          size="small"
          :aria-label="`Edit ${row.name}`"
        />
        <AppButton
          icon="mdi-delete-outline"
          variant="ghost"
          size="small"
          :aria-label="`Delete ${row.name}`"
          @click="confirmDelete(row as ReferenceUser)"
        />
      </template>

      <!-- Empty state CTA -->
      <template #empty-action>
        <AppButton prepend-icon="mdi-account-plus-outline" @click="table.clearFilters">
          Clear filters
        </AppButton>
      </template>
    </AppDataTable>

    <!-- Delete confirmation dialog -->
    <v-dialog
      :model-value="!!deleteTarget"
      max-width="420"
      @update:model-value="deleteTarget = null"
    >
      <v-card rounded="lg">
        <v-card-title class="text-h6 pa-6 pb-2">Remove user?</v-card-title>
        <v-card-text class="px-6">
          <strong>{{ deleteTarget?.name }}</strong> will lose access to this workspace.
          This action cannot be undone.
        </v-card-text>
        <v-card-actions class="pa-4 gap-2">
          <v-spacer />
          <AppButton variant="ghost" :disabled="isDeleting" @click="deleteTarget = null">
            Cancel
          </AppButton>
          <AppButton variant="danger" :loading="isDeleting" @click="executeDelete">
            Remove
          </AppButton>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppPageLayout>
</template>
