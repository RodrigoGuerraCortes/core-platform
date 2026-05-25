<script setup lang="ts">
/**
 * PermissionsExamplePage — canonical permission visibility pattern reference.
 *
 * Demonstrates the four permission visibility modes:
 *   1. HIDDEN   — element not rendered for users without permission
 *   2. DISABLED — element rendered but non-interactive (pointer-events off)
 *   3. READONLY — form field rendered in read-only mode
 *   4. LOCKED   — workflow state gated behind a permission check
 *
 * Includes a role switcher (owner / admin / member / readonly) that mutates
 * a local permission set, showing how the UI responds to each role.
 *
 * CRITICAL RULE:
 *   No inline role checks in templates.
 *   All checks go through AppPermissionBoundary or usePermission().
 *   This page is the canonical demonstration of that rule.
 */
import { ref, computed } from 'vue'
import {
  AppPageLayout,
  AppButton,
  AppCard,
  AppSection,
  AppTextField,
  AppStatusChip,
  AppEmptyState,
} from '@/shared/ui'
import type { DetailBreadcrumb } from '@/shared/ui'

// ─── Mock role system ─────────────────────────────────────────────────────────
// In production, this comes from useAuthStore().currentUser.permissions[].
// Here we simulate it locally so the page is self-contained.

type MockRole = 'owner' | 'admin' | 'member' | 'readonly'

interface RoleConfig {
  label: string
  color: string
  icon: string
  permissions: string[]
  description: string
}

const ROLE_CONFIGS: Record<MockRole, RoleConfig> = {
  owner: {
    label: 'Owner',
    color: 'error',
    icon: 'mdi-crown-outline',
    description: 'Full control. All actions enabled.',
    permissions: ['users.view', 'users.edit', 'users.delete', 'users.invite', 'settings.manage', 'billing.manage'],
  },
  admin: {
    label: 'Admin',
    color: 'warning',
    icon: 'mdi-shield-account-outline',
    description: 'Manage members and settings. Cannot delete or access billing.',
    permissions: ['users.view', 'users.edit', 'users.invite', 'settings.manage'],
  },
  member: {
    label: 'Member',
    color: 'primary',
    icon: 'mdi-account-outline',
    description: 'Standard access. Read and create. Cannot manage users.',
    permissions: ['users.view'],
  },
  readonly: {
    label: 'Read-only',
    color: 'default',
    icon: 'mdi-eye-outline',
    description: 'View only. No mutations allowed.',
    permissions: [],
  },
}

const selectedRole = ref<MockRole>('member')
const config = computed(() => ROLE_CONFIGS[selectedRole.value])
const permissions = computed(() => config.value.permissions)

// ── Local permission checks (mirrors usePermission() API) ─────────────────
function can(permission: string): boolean {
  return permissions.value.includes(permission)
}
function canAny(list: string[]): boolean {
  return list.some((p) => permissions.value.includes(p))
}

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────
const breadcrumbs: DetailBreadcrumb[] = [
  { title: 'Reference', to: { name: 'reference' } },
  { title: 'Permission Patterns', disabled: true },
]

// ─── Demo state ───────────────────────────────────────────────────────────────
const workflowStatus = ref<'draft' | 'pending' | 'approved'>('draft')
const editValue = ref('Alice Merchant')

function advanceWorkflow(): void {
  if (workflowStatus.value === 'draft') workflowStatus.value = 'pending'
  else if (workflowStatus.value === 'pending') workflowStatus.value = 'approved'
}
</script>

<template>
  <AppPageLayout
    title="Permission Visibility Patterns"
    subtitle="Canonical reference for hiding, disabling, and locking UI based on role."
    :breadcrumbs="breadcrumbs"
  >
    <!-- ── Role switcher ────────────────────────────────────────────────── -->
    <AppCard class="mb-6">
      <p class="text-subtitle-2 font-weight-semibold mb-1">Active role (simulated)</p>
      <p class="text-caption text-medium-emphasis mb-4">
        Switch roles to see how each permission mode responds.
      </p>
      <div class="d-flex flex-wrap gap-2 mb-4">
        <v-btn
          v-for="(cfg, role) in ROLE_CONFIGS"
          :key="role"
          :color="cfg.color"
          :variant="selectedRole === role ? 'flat' : 'outlined'"
          :prepend-icon="cfg.icon"
          size="small"
          @click="selectedRole = role as MockRole"
        >
          {{ cfg.label }}
        </v-btn>
      </div>
      <v-alert
        :color="config.color"
        variant="tonal"
        density="compact"
        :icon="config.icon"
        :text="`${config.label}: ${config.description}`"
      />
      <div class="d-flex flex-wrap gap-2 mt-3">
        <v-chip
          v-for="perm in config.permissions"
          :key="perm"
          size="x-small"
          variant="tonal"
          color="success"
          prepend-icon="mdi-check"
        >
          {{ perm }}
        </v-chip>
        <v-chip
          v-if="config.permissions.length === 0"
          size="x-small"
          variant="tonal"
          color="error"
        >
          No permissions
        </v-chip>
      </div>
    </AppCard>

    <!-- ── Pattern 1: HIDDEN ─────────────────────────────────────────────── -->
    <AppSection
      title="Pattern 1 — Hidden"
      description="Element is not rendered at all. The user cannot tell it exists. Use for destructive or sensitive actions."
      divider
      class="mb-6"
    >
      <AppCard>
        <p class="text-body-2 text-medium-emphasis mb-4">
          The <strong>Delete User</strong> and <strong>Invite User</strong> buttons below are hidden
          unless the current role has the required permission.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <AppButton variant="secondary" prepend-icon="mdi-account-outline">
            View Profile
          </AppButton>

          <!-- Hidden unless users.edit -->
          <AppButton v-if="can('users.edit')" variant="secondary" prepend-icon="mdi-pencil-outline">
            Edit User
          </AppButton>

          <!-- Hidden unless users.invite -->
          <AppButton v-if="can('users.invite')" variant="tonal" prepend-icon="mdi-account-plus-outline">
            Invite User
          </AppButton>

          <!-- Hidden unless users.delete -->
          <AppButton v-if="can('users.delete')" variant="danger" prepend-icon="mdi-delete-outline">
            Delete User
          </AppButton>
        </div>

        <v-divider class="my-4" />
        <p class="text-caption text-medium-emphasis">
          Visible buttons for current role:
          <strong>View Profile</strong>
          <template v-if="can('users.edit')">, <strong>Edit User</strong></template>
          <template v-if="can('users.invite')">, <strong>Invite User</strong></template>
          <template v-if="can('users.delete')">, <strong>Delete User</strong></template>
        </p>
      </AppCard>
    </AppSection>

    <!-- ── Pattern 2: DISABLED ───────────────────────────────────────────── -->
    <AppSection
      title="Pattern 2 — Disabled with Tooltip"
      description="Element is visible but non-interactive. Use when users need to know the action exists but cannot perform it."
      divider
      class="mb-6"
    >
      <AppCard>
        <p class="text-body-2 text-medium-emphasis mb-4">
          <strong>Manage Settings</strong> is always visible. Without
          <code>settings.manage</code> permission it is disabled with a tooltip.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <AppButton variant="secondary" prepend-icon="mdi-account-outline">
            View Profile
          </AppButton>

          <v-tooltip
            :disabled="can('settings.manage')"
            text="You need the Admin role or above to manage settings."
            location="top"
          >
            <template #activator="{ props: tooltipProps }">
              <span v-bind="tooltipProps">
                <AppButton
                  variant="secondary"
                  prepend-icon="mdi-cog-outline"
                  :disabled="!can('settings.manage')"
                >
                  Manage Settings
                </AppButton>
              </span>
            </template>
          </v-tooltip>

          <v-tooltip
            :disabled="canAny(['billing.manage'])"
            text="Only Owners can access billing."
            location="top"
          >
            <template #activator="{ props: tooltipProps }">
              <span v-bind="tooltipProps">
                <AppButton
                  variant="secondary"
                  prepend-icon="mdi-credit-card-outline"
                  :disabled="!canAny(['billing.manage'])"
                >
                  Billing
                </AppButton>
              </span>
            </template>
          </v-tooltip>
        </div>
      </AppCard>
    </AppSection>

    <!-- ── Pattern 3: READONLY form field ───────────────────────────────── -->
    <AppSection
      title="Pattern 3 — Read-only Form Field"
      description="Field is always visible. Without edit permission it renders in read-only mode. Never hide form data — degraded access is still access."
      divider
      class="mb-6"
    >
      <AppCard>
        <p class="text-body-2 text-medium-emphasis mb-4">
          The name field below becomes editable only with <code>users.edit</code>.
        </p>
        <div class="d-flex flex-column gap-4" style="max-width: 400px">
          <AppTextField
            v-model="editValue"
            label="Full name"
            prepend-inner-icon="mdi-account-outline"
            :readonly="!can('users.edit')"
            :hint="!can('users.edit') ? 'Read-only — you don\'t have permission to edit this field.' : ''"
            persistent-hint
          />
          <div v-if="can('users.edit')" class="d-flex gap-2">
            <AppButton variant="primary" size="small">Save</AppButton>
            <AppButton variant="ghost" size="small">Cancel</AppButton>
          </div>
        </div>
      </AppCard>
    </AppSection>

    <!-- ── Pattern 4: LOCKED WORKFLOW STATE ─────────────────────────────── -->
    <AppSection
      title="Pattern 4 — Locked Workflow State"
      description="Workflow advancement is gated. Users see the current state; only those with the right permission can advance it."
      class="mb-6"
    >
      <AppCard>
        <div class="d-flex align-center justify-space-between flex-wrap gap-4">
          <div>
            <p class="text-body-2 text-medium-emphasis mb-1">Approval status</p>
            <AppStatusChip :status="workflowStatus" />
          </div>

          <div class="d-flex gap-2 align-center flex-wrap">
            <template v-if="workflowStatus === 'approved'">
              <v-chip color="success" variant="tonal" prepend-icon="mdi-check-circle">
                Workflow complete
              </v-chip>
            </template>

            <template v-else>
              <!-- Submit for review — members and above can do this -->
              <AppButton
                v-if="workflowStatus === 'draft' && can('users.view')"
                variant="tonal"
                prepend-icon="mdi-send-outline"
                @click="advanceWorkflow"
              >
                Submit for Review
              </AppButton>

              <!-- Approve — requires users.edit (admin+) -->
              <template v-if="workflowStatus === 'pending'">
                <v-tooltip
                  :disabled="can('users.edit')"
                  text="Only Admins and Owners can approve."
                  location="top"
                >
                  <template #activator="{ props: tooltipProps }">
                    <span v-bind="tooltipProps">
                      <AppButton
                        variant="primary"
                        prepend-icon="mdi-check"
                        :disabled="!can('users.edit')"
                        @click="advanceWorkflow"
                      >
                        Approve
                      </AppButton>
                    </span>
                  </template>
                </v-tooltip>

                <AppButton
                  v-if="can('users.edit')"
                  variant="ghost"
                  prepend-icon="mdi-close"
                  @click="workflowStatus = 'draft'"
                >
                  Reject
                </AppButton>
              </template>
            </template>
          </div>
        </div>

        <v-divider class="my-4" />
        <p class="text-caption text-medium-emphasis">
          Workflow: <strong>draft → pending → approved</strong>.
          Submitting requires any permission. Approving requires <code>users.edit</code>.
        </p>
      </AppCard>

      <!-- No permission fallback -->
      <template v-if="!can('users.view') && workflowStatus === 'draft'">
        <AppEmptyState
          preset="permission"
          description="You don't have permission to submit this item for review."
          flat
          class="mt-4"
        />
      </template>
    </AppSection>
  </AppPageLayout>
</template>
