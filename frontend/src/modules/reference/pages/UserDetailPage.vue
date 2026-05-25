<script setup lang="ts">
/**
 * UserDetailPage — canonical enterprise detail page reference.
 *
 * Demonstrates:
 *   - AppDetailLayout (sticky header, breadcrumbs, tabs, sidebar)
 *   - AppEntityHeader (avatar, name, identifier, status, meta)
 *   - AppEntityMeta (metadata strip)
 *   - AppEntityActions (primary/secondary/danger with mobile collapse)
 *   - AppStatusChip preset
 *   - AppPermissionBoundary (role-gated actions)
 *   - AppActivityTimeline + AppTimelineItem
 *   - AppConfirmDialog for destructive action
 *   - AppSection for content grouping
 */
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  AppDetailLayout,
  AppEntityMeta,
  AppEntityActions,
  AppStatusChip,
  AppButton,
  AppCard,
  AppSection,
  AppConfirmDialog,
  AppPermissionBoundary,
  AppActivityTimeline,
  AppTimelineItem,
  AppEmptyState,
} from '@/shared/ui'
import type { DetailBreadcrumb, MetaItem } from '@/shared/ui'
import { useReferenceUsersQuery, useDeleteUserMutation, useUpdateUserStatusMutation } from '../composables'
import type { ReferenceUser } from '../types'

const route = useRoute()
const router = useRouter()

// ── Derive user from the list query (no separate detail endpoint needed for demo) ──
const userId = computed(() => Number(route.params.id))

const { data: usersPage, isLoading, isError } = useReferenceUsersQuery({ page: 1, per_page: 100 })

const user = computed<ReferenceUser | undefined>(
  () => usersPage.value?.data.find((u) => u.id === userId.value),
)

// ── Mutations ─────────────────────────────────────────────────────────────
const { mutateAsync: deleteUser, isPending: isDeleting } = useDeleteUserMutation()
const { mutateAsync: updateStatus, isPending: isUpdating } = useUpdateUserStatusMutation()

const confirmDelete = ref(false)
const activeTab = ref('details')

async function handleDelete() {
  if (!user.value) return
  await deleteUser(user.value.id)
  router.push({ name: 'reference-users' })
}

async function toggleStatus() {
  if (!user.value) return
  const nextStatus = user.value.status === 'active' ? 'inactive' : 'active'
  await updateStatus({ userId: user.value.id, status: nextStatus })
}

// ── Breadcrumbs ───────────────────────────────────────────────────────────
const breadcrumbs = computed<DetailBreadcrumb[]>(() => [
  { title: 'Reference', to: { name: 'reference' } },
  { title: 'Users', to: { name: 'reference-users' } },
  { title: user.value?.name ?? 'User', disabled: true },
])

// ── Metadata strip ────────────────────────────────────────────────────────
const metaItems = computed<MetaItem[]>(() => {
  if (!user.value) return []
  return [
    { label: 'Role', value: user.value.role, icon: 'mdi-shield-account-outline' },
    {
      label: 'Joined',
      value: new Date(user.value.joined_at).toLocaleDateString(undefined, { dateStyle: 'medium' }),
      icon: 'mdi-calendar-outline',
    },
    {
      label: 'Last active',
      value: user.value.last_active_at
        ? new Date(user.value.last_active_at).toLocaleDateString(undefined, { dateStyle: 'medium' })
        : 'Never',
      icon: 'mdi-clock-outline',
    },
  ]
})

// ── Simulated activity (in production comes from audit log API) ───────────
const activity = computed(() => {
  if (!user.value) return []
  return [
    {
      id: 1, icon: 'mdi-account-plus-outline', iconColor: 'success',
      label: 'User account created', actor: 'System', timestamp: user.value.joined_at,
    },
    {
      id: 2, icon: 'mdi-login', iconColor: 'primary',
      label: 'Signed in', actor: user.value.name, timestamp: user.value.last_active_at,
    },
  ].filter((e) => e.timestamp)
})
</script>

<template>
  <AppDetailLayout
    :title="user?.name"
    :breadcrumbs="breadcrumbs"
    :loading="isLoading"
    :error="isError"
    error-message="Could not load user details."
    with-sidebar
    sticky-header
  >
    <!-- Status chip -->
    <template #status>
      <AppStatusChip v-if="user" :status="user.status" />
    </template>

    <!-- Subtitle -->
    <template #subtitle>
      <p v-if="user" class="text-body-2 text-medium-emphasis">{{ user.email }}</p>
    </template>

    <!-- Metadata row -->
    <template #metadata>
      <AppEntityMeta v-if="user" :items="metaItems" />
    </template>

    <!-- Actions -->
    <template #actions>
      <AppEntityActions v-if="user">
        <template #primary>
          <AppButton variant="primary" prepend-icon="mdi-pencil-outline">
            Edit
          </AppButton>
        </template>

        <template #secondary>
          <AppPermissionBoundary permission="users.manage-status">
            <AppButton
              variant="secondary"
              :loading="isUpdating"
              @click="toggleStatus"
            >
              {{ user?.status === 'active' ? 'Deactivate' : 'Activate' }}
            </AppButton>
          </AppPermissionBoundary>
        </template>

        <template #danger>
          <AppPermissionBoundary permission="users.delete">
            <AppButton
              variant="danger"
              prepend-icon="mdi-delete-outline"
              @click="confirmDelete = true"
            >
              Delete
            </AppButton>
          </AppPermissionBoundary>
        </template>
      </AppEntityActions>
    </template>

    <!-- Tabs -->
    <template #tabs>
      <v-tabs v-model="activeTab" density="compact">
        <v-tab value="details">Details</v-tab>
        <v-tab value="permissions">Permissions</v-tab>
      </v-tabs>
    </template>

    <!-- Main content -->
    <v-window v-model="activeTab" class="mt-4">
      <!-- Details tab -->
      <v-window-item value="details">
        <AppSection title="Account Information">
          <AppCard>
            <v-list density="comfortable" class="pa-0">
              <v-list-item
                v-if="user"
                prepend-icon="mdi-account-outline"
                title="Full name"
                :subtitle="user.name"
              />
              <v-divider inset />
              <v-list-item
                v-if="user"
                prepend-icon="mdi-email-outline"
                title="Email"
                :subtitle="user.email"
              />
              <v-divider inset />
              <v-list-item
                v-if="user"
                prepend-icon="mdi-shield-outline"
                title="Role"
                :subtitle="user.role"
              />
            </v-list>
          </AppCard>
        </AppSection>
      </v-window-item>

      <!-- Permissions tab -->
      <v-window-item value="permissions">
        <AppEmptyState
          preset="permission"
          title="Permission management coming soon"
          description="Granular role-based permissions will be configurable here."
          flat
        />
      </v-window-item>
    </v-window>

    <!-- Sidebar -->
    <template #sidebar>
      <AppCard>
        <p class="text-subtitle-2 font-weight-semibold mb-3">Quick Stats</p>
        <div class="d-flex flex-column gap-2">
          <div class="d-flex justify-space-between">
            <span class="text-body-2 text-medium-emphasis">Status</span>
            <AppStatusChip v-if="user" :status="user.status" size="x-small" />
          </div>
          <div class="d-flex justify-space-between">
            <span class="text-body-2 text-medium-emphasis">Role</span>
            <span class="text-body-2 font-weight-medium">{{ user?.role ?? '—' }}</span>
          </div>
        </div>
      </AppCard>
    </template>

    <!-- Activity timeline -->
    <template #activity>
      <AppSection title="Activity" divider>
        <AppActivityTimeline :empty="activity.length === 0">
          <AppTimelineItem
            v-for="(event, idx) in activity"
            :key="event.id"
            :icon="event.icon"
            :icon-color="event.iconColor"
            :label="event.label"
            :actor="event.actor"
            :timestamp="event.timestamp"
            :last="idx === activity.length - 1"
          />
        </AppActivityTimeline>
      </AppSection>
    </template>
  </AppDetailLayout>

  <!-- Delete confirm -->
  <AppConfirmDialog
    v-model="confirmDelete"
    title="Delete user?"
    :description="`${user?.name} will be permanently removed. This cannot be undone.`"
    confirm-label="Delete"
    confirm-variant="danger"
    :loading="isDeleting"
    @confirm="handleDelete"
  />
</template>
