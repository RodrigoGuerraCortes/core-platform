<script setup lang="ts">
/**
 * ApprovalWorkflowPage — canonical approval workflow reference.
 *
 * Demonstrates:
 *   - Status-driven UX (pending → approved/rejected)
 *   - Action buttons gated by current status
 *   - Comment thread pattern
 *   - Optimistic status transitions via useMutation
 *   - AppErrorState / AppLoadingState integration
 *
 * Pattern:
 *   useReferenceApprovalsQuery(statusFilter) → list
 *   useUpdateApprovalStatusMutation() → approve/reject with comment
 */
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { AppPageLayout, AppButton, AppCard, AppTextarea } from '@/shared/ui'
import { AppFilterBar } from '@/shared/table'
import type { FilterField, FilterValues } from '@/shared/table'
import { useReferenceApprovalsQuery, useUpdateApprovalStatusMutation } from '../composables'

const router = useRouter()
import type { ReferenceApproval, ApprovalStatus } from '../types'

// ── Status filter ──────────────────────────────────────────────────────────
const filterValues = ref<FilterValues>({ status: null })

const activeStatus = computed(
  () => (filterValues.value.status as ApprovalStatus | null) ?? '',
)

const filterFields: FilterField[] = [
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    options: [
      { label: 'Pending',  value: 'pending' },
      { label: 'Approved', value: 'approved' },
      { label: 'Rejected', value: 'rejected' },
    ],
  },
]

// ── Query ─────────────────────────────────────────────────────────────────
const { data: approvals, isLoading, isError } = useReferenceApprovalsQuery(activeStatus)

// ── Action dialog ─────────────────────────────────────────────────────────
interface ActionDialog {
  approval: ReferenceApproval
  intent: 'approve' | 'reject'
}

const dialog = ref<ActionDialog | null>(null)
const comment = ref('')

const { mutateAsync: updateStatus, isPending: isUpdating } = useUpdateApprovalStatusMutation()

function openDialog(approval: ReferenceApproval, intent: 'approve' | 'reject'): void {
  dialog.value = { approval, intent }
  comment.value = ''
}

async function confirmAction(): Promise<void> {
  if (!dialog.value) return
  const status: ApprovalStatus = dialog.value.intent === 'approve' ? 'approved' : 'rejected'
  await updateStatus({
    approvalId: dialog.value.approval.id,
    status,
    comment: comment.value.trim() || undefined,
  })
  dialog.value = null
}

// ── Display helpers ───────────────────────────────────────────────────────
const STATUS_COLOR: Record<ApprovalStatus, string> = {
  pending: 'warning', approved: 'success', rejected: 'error',
}

const STATUS_ICON: Record<ApprovalStatus, string> = {
  pending:  'mdi-clock-outline',
  approved: 'mdi-check-circle-outline',
  rejected: 'mdi-close-circle-outline',
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <AppPageLayout
    title="Approvals"
    description="Review and action pending approval requests."
    :loading="isLoading"
    :error="isError"
    error-message="Could not load approvals. Please try again."
  >
    <!-- Status filter in header actions -->
    <template #actions>
      <AppFilterBar
        :model-value="filterValues"
        :fields="filterFields"
        @update:model-value="filterValues = $event"
      />
    </template>

    <!-- Empty state -->
    <div
      v-if="!isLoading && !isError && (!approvals || approvals.length === 0)"
      class="text-center py-12 text-medium-emphasis"
    >
      <v-icon icon="mdi-inbox-check-outline" size="48" class="mb-3" />
      <p class="text-h6 font-weight-medium mb-1">All caught up</p>
      <p class="text-body-2">No approvals match the current filter.</p>
    </div>

    <!-- Approval cards -->
    <div class="d-flex flex-column gap-4">
      <AppCard
        v-for="approval in approvals"
        :key="approval.id"
      >
        <!-- Header -->
        <template #header>
          <div class="d-flex align-start justify-space-between gap-3 pb-3 border-b mb-3">
            <div>
              <div class="d-flex align-center gap-2 mb-1">
                <v-chip
                  :color="STATUS_COLOR[approval.status]"
                  :prepend-icon="STATUS_ICON[approval.status]"
                  size="small"
                  variant="tonal"
                >
                  {{ approval.status }}
                </v-chip>
              </div>
              <p class="text-body-1 font-weight-semibold">{{ approval.title }}</p>
              <p class="text-caption text-medium-emphasis mt-1">
                Requested by <strong>{{ approval.requester }}</strong>
                · {{ formatDate(approval.submitted_at) }}
              </p>
            </div>

            <!-- Actions — only for pending items -->
            <div v-if="approval.status === 'pending'" class="d-flex gap-2 flex-shrink-0">
              <AppButton
                variant="ghost"
                size="small"
                prepend-icon="mdi-eye-outline"
                @click="router.push({ name: 'reference-approval-detail', params: { id: approval.id } })"
              >
                View
              </AppButton>
              <AppButton
                variant="tonal"
                size="small"
                prepend-icon="mdi-check"
                @click="openDialog(approval, 'approve')"
              >
                Approve
              </AppButton>
              <AppButton
                variant="secondary"
                size="small"
                prepend-icon="mdi-close"
                @click="openDialog(approval, 'reject')"
              >
                Reject
              </AppButton>
            </div>

            <!-- Resolved timestamp for non-pending -->
            <div v-else class="d-flex gap-2 align-center flex-shrink-0">
              <AppButton
                variant="ghost"
                size="small"
                prepend-icon="mdi-eye-outline"
                @click="router.push({ name: 'reference-approval-detail', params: { id: approval.id } })"
              >
                View
              </AppButton>
              <div class="text-caption text-medium-emphasis text-right">
                <p>Resolved</p>
                <p>{{ formatDate(approval.resolved_at) }}</p>
              </div>
            </div>
          </div>
        </template>

        <!-- Description -->
        <p class="text-body-2 text-medium-emphasis mb-4">{{ approval.description }}</p>

        <!-- Comment thread -->
        <template v-if="approval.comments.length > 0">
          <p class="text-caption font-weight-semibold text-medium-emphasis mb-2">Comments</p>
          <div class="d-flex flex-column gap-3">
            <div
              v-for="c in approval.comments"
              :key="c.id"
              class="d-flex gap-3"
            >
              <v-avatar size="28" color="primary" variant="tonal">
                <span class="text-caption">{{ c.author[0] }}</span>
              </v-avatar>
              <div class="flex-grow-1">
                <p class="text-body-2 font-weight-medium">{{ c.author }}
                  <span class="text-caption text-medium-emphasis font-weight-regular ml-1">
                    {{ formatDate(c.created_at) }}
                  </span>
                </p>
                <p class="text-body-2 mt-1">{{ c.body }}</p>
              </div>
            </div>
          </div>
        </template>
      </AppCard>
    </div>

    <!-- Approve / Reject dialog -->
    <v-dialog
      :model-value="!!dialog"
      max-width="440"
      @update:model-value="dialog = null"
    >
      <v-card v-if="dialog" rounded="lg">
        <v-card-title class="text-h6 pa-6 pb-3">
          {{ dialog.intent === 'approve' ? 'Approve request?' : 'Reject request?' }}
        </v-card-title>
        <v-card-text class="px-6">
          <p class="text-body-2 text-medium-emphasis mb-4">
            <strong>{{ dialog.approval.title }}</strong>
          </p>
          <AppTextarea
            v-model="comment"
            label="Comment (optional)"
            placeholder="Add a reason or note…"
            :rows="3"
          />
        </v-card-text>
        <v-card-actions class="pa-4 gap-2">
          <v-spacer />
          <AppButton variant="ghost" :disabled="isUpdating" @click="dialog = null">
            Cancel
          </AppButton>
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
  </AppPageLayout>
</template>
