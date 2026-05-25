<script setup lang="ts">
/**
 * ApprovalDetailPage — canonical approval detail page reference.
 *
 * Demonstrates:
 *   - AppDetailLayout with sidebar
 *   - AppEntityHeader for approval presentation
 *   - AppStatusChip preset (pending/approved/rejected)
 *   - AppActivityTimeline rendering comment thread as timeline
 *   - AppPermissionBoundary gating approve/reject actions
 *   - AppConfirmDialog with comment field
 *   - AppSection for content grouping
 *   - Metadata via AppEntityMeta
 */
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  AppDetailLayout,
  AppEntityMeta,
  AppEntityActions,
  AppStatusChip,
  AppButton,
  AppCard,
  AppSection,
  AppPermissionBoundary,
  AppActivityTimeline,
  AppTimelineItem,
  AppTextarea,
} from '@/shared/ui'
import type { DetailBreadcrumb, MetaItem } from '@/shared/ui'
import { useReferenceApprovalsQuery, useUpdateApprovalStatusMutation } from '../composables'
import type { ApprovalStatus } from '../types'

const route = useRoute()
const approvalId = computed(() => Number(route.params.id))

const { data: allApprovals, isLoading, isError } = useReferenceApprovalsQuery('')

const approval = computed(() =>
  allApprovals.value?.find((a) => a.id === approvalId.value),
)

// ── Mutation ──────────────────────────────────────────────────────────────
const { mutateAsync: updateStatus, isPending: isUpdating } = useUpdateApprovalStatusMutation()

// Action dialog state
interface ActionDialog { intent: 'approve' | 'reject' }
const dialog = ref<ActionDialog | null>(null)
const comment = ref('')

async function confirmAction() {
  if (!dialog.value || !approval.value) return
  const status: ApprovalStatus = dialog.value.intent === 'approve' ? 'approved' : 'rejected'
  await updateStatus({
    approvalId: approval.value.id,
    status,
    comment: comment.value.trim() || undefined,
  })
  dialog.value = null
}

// ── Breadcrumbs ───────────────────────────────────────────────────────────
const breadcrumbs = computed<DetailBreadcrumb[]>(() => [
  { title: 'Reference', to: { name: 'reference' } },
  { title: 'Approvals', to: { name: 'reference-approvals' } },
  { title: approval.value?.title ?? 'Approval', disabled: true },
])

// ── Metadata strip ────────────────────────────────────────────────────────
const metaItems = computed<MetaItem[]>(() => {
  if (!approval.value) return []
  const items: MetaItem[] = [
    { label: 'Submitted by', value: approval.value.requester, icon: 'mdi-account-outline' },
    {
      label: 'Submitted',
      value: new Date(approval.value.submitted_at).toLocaleDateString(undefined, { dateStyle: 'medium' }),
      icon: 'mdi-calendar-outline',
    },
  ]
  if (approval.value.resolved_at) {
    items.push({
      label: 'Resolved',
      value: new Date(approval.value.resolved_at).toLocaleDateString(undefined, { dateStyle: 'medium' }),
      icon: 'mdi-check-outline',
    })
  }
  return items
})

const STATUS_ICON: Record<ApprovalStatus, string> = {
  pending:  'mdi-clock-outline',
  approved: 'mdi-check-circle-outline',
  rejected: 'mdi-close-circle-outline',
}

const STATUS_COLOR: Record<ApprovalStatus, string> = {
  pending: 'warning', approved: 'success', rejected: 'error',
}
</script>

<template>
  <AppDetailLayout
    :title="approval?.title"
    :breadcrumbs="breadcrumbs"
    :loading="isLoading"
    :error="isError"
    error-message="Could not load approval details."
    with-sidebar
    sticky-header
  >
    <!-- Status chip -->
    <template #status>
      <AppStatusChip v-if="approval" :status="approval.status" />
    </template>

    <!-- Subtitle -->
    <template #subtitle>
      <p v-if="approval" class="text-body-2 text-medium-emphasis">
        Requested by {{ approval.requester }}
      </p>
    </template>

    <!-- Metadata -->
    <template #metadata>
      <AppEntityMeta v-if="approval" :items="metaItems" />
    </template>

    <!-- Actions — only for pending items -->
    <template #actions>
      <AppEntityActions v-if="approval?.status === 'pending'">
        <template #primary>
          <AppPermissionBoundary permission="approvals.action">
            <AppButton
              variant="primary"
              prepend-icon="mdi-check"
              @click="() => { dialog = { intent: 'approve' }; comment = '' }"
            >
              Approve
            </AppButton>
          </AppPermissionBoundary>
        </template>

        <template #secondary>
          <AppPermissionBoundary permission="approvals.action">
            <AppButton
              variant="secondary"
              prepend-icon="mdi-close"
              @click="() => { dialog = { intent: 'reject' }; comment = '' }"
            >
              Reject
            </AppButton>
          </AppPermissionBoundary>
        </template>
      </AppEntityActions>
    </template>

    <!-- Main content -->
    <AppSection title="Description">
      <AppCard>
        <p class="text-body-2">{{ approval?.description }}</p>
      </AppCard>
    </AppSection>

    <!-- Sidebar -->
    <template #sidebar>
      <AppCard>
        <p class="text-subtitle-2 font-weight-semibold mb-3">Details</p>
        <div class="d-flex flex-column gap-2">
          <div v-if="approval" class="d-flex justify-space-between align-center">
            <span class="text-body-2 text-medium-emphasis">Status</span>
            <AppStatusChip :status="approval.status" size="x-small" />
          </div>
          <div v-if="approval" class="d-flex justify-space-between">
            <span class="text-body-2 text-medium-emphasis">Comments</span>
            <span class="text-body-2 font-weight-medium">{{ approval.comments.length }}</span>
          </div>
        </div>
      </AppCard>
    </template>

    <!-- Activity / Comments timeline -->
    <template #activity>
      <AppSection title="Comments & Activity" divider>
        <AppActivityTimeline :empty="!approval?.comments.length">
          <AppTimelineItem
            v-for="(c, idx) in approval?.comments ?? []"
            :key="c.id"
            :icon="STATUS_ICON[approval!.status]"
            :icon-color="STATUS_COLOR[approval!.status]"
            :label="c.author"
            :actor="c.author"
            :timestamp="c.created_at"
            :last="idx === (approval?.comments.length ?? 0) - 1"
          >
            <template #body>{{ c.body }}</template>
          </AppTimelineItem>
        </AppActivityTimeline>
      </AppSection>
    </template>
  </AppDetailLayout>

  <!-- Approve / Reject dialog -->
  <v-dialog :model-value="!!dialog" max-width="440" @update:model-value="dialog = null">
    <v-card v-if="dialog" rounded="lg">
      <v-card-title class="text-h6 pa-6 pb-3">
        {{ dialog.intent === 'approve' ? 'Approve request?' : 'Reject request?' }}
      </v-card-title>
      <v-card-text class="px-6">
        <AppTextarea
          v-model="comment"
          label="Comment (optional)"
          placeholder="Add a reason or note…"
          :rows="3"
        />
      </v-card-text>
      <v-card-actions class="pa-4 gap-2">
        <v-spacer />
        <AppButton variant="ghost" :disabled="isUpdating" @click="dialog = null">Cancel</AppButton>
        <AppButton
          :variant="dialog.intent === 'approve' ? 'primary' : 'danger'"
          :loading="isUpdating"
          @click="confirmAction"
        >
          {{ dialog.intent === 'approve' ? 'Approve' : 'Reject' }}
        </AppButton>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
