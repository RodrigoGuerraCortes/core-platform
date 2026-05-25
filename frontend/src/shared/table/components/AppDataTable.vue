<script setup lang="ts" generic="TRow extends TableRow">
import { computed } from 'vue'
import type { TableColumn, TableRow, SortState } from '../types'
import { PAGE_SIZE_OPTIONS } from '../types'
import AppLoadingState from '../../feedback/AppLoadingState.vue'
import AppEmptyState from '../../feedback/AppEmptyState.vue'
import AppErrorState from '../../feedback/AppErrorState.vue'

/**
 * AppDataTable — THE canonical enterprise CRUD table for Core Platform.
 *
 * Responsibilities:
 *   - Renders server-paginated, sortable tabular data
 *   - Integrates loading / empty / error states deterministically
 *   - Provides row-actions and toolbar slots
 *   - Emits typed page/sort/per-page events for useTableState
 *
 * Modules MUST NOT:
 *   - Use VDataTable directly
 *   - Inline skeleton loaders inside table pages
 *   - Manage pagination state outside useTableState
 *
 * Usage:
 *   <AppDataTable
 *     :columns="columns"
 *     :rows="forms"
 *     :total="meta.total"
 *     :page="table.page.value"
 *     :per-page="table.perPage.value"
 *     :loading="isLoading"
 *     :error="isError"
 *     @update:page="table.setPage"
 *     @update:per-page="table.setPerPage"
 *     @update:sort="table.setSort"
 *   >
 *     <template #actions="{ row }">
 *       <AppButton variant="ghost" icon="mdi-pencil-outline" @click="edit(row.id)" />
 *     </template>
 *   </AppDataTable>
 */

const props = withDefaults(
  defineProps<{
    columns: TableColumn<TRow>[]
    rows: TRow[]
    /** Total records count (from PaginatedMeta.total). */
    total?: number
    page?: number
    perPage?: number
    loading?: boolean
    error?: boolean
    errorMessage?: string
    /** Show the actions column (right-most). */
    showActions?: boolean
    /** Text shown in the empty state. */
    emptyTitle?: string
    emptyDescription?: string
    emptyIcon?: string
    /** Disable server-side sort (for client-filtered lists). */
    disableSort?: boolean
  }>(),
  {
    total: 0,
    page: 1,
    perPage: 15,
    showActions: true,
    emptyTitle: 'No results',
    emptyDescription: 'There is nothing here yet.',
    emptyIcon: 'mdi-table-off',
    errorMessage: 'Could not load data. Please try again.',
  },
)

const emit = defineEmits<{
  'update:page': [page: number]
  'update:perPage': [perPage: number]
  'update:sort': [sort: SortState | null]
}>()

// Map our column definitions to Vuetify's headers format
const headers = computed(() => {
  const cols = props.columns.map((col) => ({
    key: col.key,
    title: col.label,
    width: col.width,
    minWidth: col.minWidth,
    sortable: !props.disableSort && (col.sortable ?? false),
    align: col.align ?? 'start',
    value: col.value ?? col.key,
  }))

  if (props.showActions) {
    cols.push({
      key: 'actions',
      title: '',
      width: 120,
      minWidth: undefined,
      sortable: false,
      align: 'end',
      value: 'actions',
    })
  }

  return cols
})

const isEmpty = computed(() => !props.loading && !props.error && props.rows.length === 0)

function onSortUpdate(sortBy: Array<{ key: string; order: 'asc' | 'desc' }>): void {
  if (sortBy.length === 0) {
    emit('update:sort', null)
  } else {
    emit('update:sort', { key: sortBy[0].key, direction: sortBy[0].order })
  }
}
</script>

<template>
  <v-card variant="outlined" rounded="lg">
    <!-- Toolbar slot (search, filters, actions) -->
    <template v-if="$slots.toolbar">
      <div class="pa-3 border-b">
        <slot name="toolbar" />
      </div>
    </template>

    <!-- Loading state -->
    <div v-if="loading" class="pa-4">
      <AppLoadingState :rows="5" type="table-row" />
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="pa-4">
      <AppErrorState :message="errorMessage">
        <template v-if="$slots['error-action']" #action>
          <slot name="error-action" />
        </template>
      </AppErrorState>
    </div>

    <!-- Empty state -->
    <AppEmptyState
      v-else-if="isEmpty"
      :icon="emptyIcon"
      :title="emptyTitle"
      :description="emptyDescription"
    >
      <template v-if="$slots['empty-action']" #action>
        <slot name="empty-action" />
      </template>
    </AppEmptyState>

    <!-- Data table -->
    <template v-else>
      <!-- overflow-x: auto lets the table scroll horizontally on narrow
           viewports instead of overflowing the card boundary. -->
      <div style="overflow-x: auto">
        <v-data-table-server
          :items="rows"
          :headers="headers"
          :items-length="total"
          :page="page"
          :items-per-page="perPage"
          :items-per-page-options="PAGE_SIZE_OPTIONS.map((v) => ({ value: v, title: String(v) }))"
          density="comfortable"
          hover
          @update:page="emit('update:page', $event)"
          @update:items-per-page="emit('update:perPage', $event)"
          @update:sort-by="onSortUpdate"
        >
          <!-- Forward all column slots so modules can customise cell rendering -->
          <template
            v-for="col in columns"
            :key="col.key"
            #[`item.${col.key}`]="slotProps"
          >
            <slot :name="`col-${col.key}`" v-bind="slotProps">
              {{ slotProps.value }}
            </slot>
          </template>

          <!-- Row actions column -->
          <template v-if="showActions" #[`item.actions`]="{ item }">
            <div class="d-flex justify-end gap-1">
              <slot name="actions" :row="item" />
            </div>
          </template>
        </v-data-table-server>
      </div>
    </template>
  </v-card>
</template>
