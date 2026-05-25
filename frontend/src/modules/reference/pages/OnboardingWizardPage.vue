<script setup lang="ts">
/**
 * OnboardingWizardPage — canonical multi-step wizard reference.
 *
 * Demonstrates:
 *   - Stepper navigation (linear, validated per step)
 *   - Per-step validation before advancing
 *   - Optimistic save on completion
 *   - Progress persistence (survives tab changes via local ref)
 *   - Cancel flow with confirmation
 *   - Review & confirm step
 *   - Success state after submission
 *
 * Pattern:
 *   useWizardState (local) — step index + form data + validation
 *   Each step is a v-window-item with its own validation function.
 *   Navigation footer is shared and driven by the wizard state.
 *
 * This pattern will be reused by:
 *   - HIS patient intake
 *   - Condo resident onboarding
 *   - Contracts wizard
 *   - Admissions flow
 */
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { AppPageLayout, AppButton, AppCard, AppSection, AppTextField, AppSelect, AppCheckbox } from '@/shared/ui'
import type { DetailBreadcrumb } from '@/shared/ui'

const router = useRouter()

// ─── Wizard steps ─────────────────────────────────────────────────────────────

interface Step {
  key: string
  title: string
  subtitle: string
  icon: string
}

const STEPS: Step[] = [
  { key: 'basic', title: 'Basic Info', subtitle: 'Your account details', icon: 'mdi-account-outline' },
  { key: 'workspace', title: 'Workspace Setup', subtitle: 'Configure your workspace', icon: 'mdi-office-building-outline' },
  { key: 'permissions', title: 'Permissions', subtitle: 'Set access control', icon: 'mdi-shield-account-outline' },
  { key: 'review', title: 'Review & Confirm', subtitle: 'Confirm before saving', icon: 'mdi-check-circle-outline' },
]

const currentStep = ref(0)
const isSubmitting = ref(false)
const isDone = ref(false)
const showCancelDialog = ref(false)

// ─── Form data ────────────────────────────────────────────────────────────────

const form = ref({
  // Step 1 — Basic Info
  name: '',
  email: '',
  jobTitle: '',
  // Step 2 — Workspace
  workspaceName: '',
  workspaceSlug: '',
  plan: '' as '' | 'starter' | 'pro' | 'enterprise',
  // Step 3 — Permissions
  role: '' as '' | 'owner' | 'admin' | 'member' | 'readonly',
  sendInvite: true,
  agreeToTerms: false,
})

// ─── Validation ───────────────────────────────────────────────────────────────

interface FieldErrors {
  name?: string
  email?: string
  workspaceName?: string
  workspaceSlug?: string
  plan?: string
  role?: string
  agreeToTerms?: string
}

const errors = ref<FieldErrors>({})

function validateStep(index: number): boolean {
  errors.value = {}

  if (index === 0) {
    if (!form.value.name.trim()) errors.value.name = 'Full name is required.'
    if (!form.value.email.trim()) errors.value.email = 'Email is required.'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email))
      errors.value.email = 'Enter a valid email address.'
  }

  if (index === 1) {
    if (!form.value.workspaceName.trim()) errors.value.workspaceName = 'Workspace name is required.'
    if (!form.value.workspaceSlug.trim()) errors.value.workspaceSlug = 'Workspace slug is required.'
    else if (!/^[a-z0-9-]+$/.test(form.value.workspaceSlug))
      errors.value.workspaceSlug = 'Only lowercase letters, numbers, and hyphens.'
    if (!form.value.plan) errors.value.plan = 'Select a plan.'
  }

  if (index === 2) {
    if (!form.value.role) errors.value.role = 'Select a role.'
    if (!form.value.agreeToTerms) errors.value.agreeToTerms = 'You must agree to the terms.'
  }

  return Object.keys(errors.value).length === 0
}

// ─── Navigation ───────────────────────────────────────────────────────────────

const isLastStep = computed(() => currentStep.value === STEPS.length - 1)
const isFirstStep = computed(() => currentStep.value === 0)
const progressPercent = computed(() => Math.round((currentStep.value / (STEPS.length - 1)) * 100))

function goNext(): void {
  if (isLastStep.value) { submit(); return }
  if (!validateStep(currentStep.value)) return
  errors.value = {}
  currentStep.value++
}

function goBack(): void {
  errors.value = {}
  if (!isFirstStep.value) currentStep.value--
}

function goToStep(idx: number): void {
  // Only allow navigating to already-visited steps
  if (idx < currentStep.value) {
    errors.value = {}
    currentStep.value = idx
  }
}

// ─── Auto-slug from workspace name ───────────────────────────────────────────

function onWorkspaceNameInput(val: string): void {
  form.value.workspaceName = val
  if (!form.value.workspaceSlug || form.value.workspaceSlug === slugify(val.slice(0, -1))) {
    form.value.workspaceSlug = slugify(val)
  }
}

function slugify(s: string): string {
  return s.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')
}

// ─── Submit ───────────────────────────────────────────────────────────────────

async function submit(): Promise<void> {
  isSubmitting.value = true
  // Simulate optimistic save (1.5s)
  await new Promise((r) => setTimeout(r, 1500))
  isSubmitting.value = false
  isDone.value = true
}

function restart(): void {
  form.value = { name: '', email: '', jobTitle: '', workspaceName: '', workspaceSlug: '', plan: '', role: '', sendInvite: true, agreeToTerms: false }
  errors.value = {}
  currentStep.value = 0
  isDone.value = false
}

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────

const breadcrumbs: DetailBreadcrumb[] = [
  { title: 'Reference', to: { name: 'reference' } },
  { title: 'Onboarding Wizard', disabled: true },
]

// ─── Select options ───────────────────────────────────────────────────────────

const planOptions = [
  { title: 'Starter — free, 5 users', value: 'starter' },
  { title: 'Pro — $29/mo, 25 users', value: 'pro' },
  { title: 'Enterprise — custom pricing', value: 'enterprise' },
]

const roleOptions = [
  { title: 'Owner — full control', value: 'owner' },
  { title: 'Admin — manage members and settings', value: 'admin' },
  { title: 'Member — standard access', value: 'member' },
  { title: 'Read-only — view only', value: 'readonly' },
]
</script>

<template>
  <AppPageLayout
    title="Onboarding Wizard"
    subtitle="Canonical multi-step workflow pattern."
    :breadcrumbs="breadcrumbs"
  >
    <template #actions>
      <AppButton variant="ghost" @click="showCancelDialog = true">Cancel</AppButton>
    </template>

    <!-- ── Success state ───────────────────────────────────────────────── -->
    <AppCard v-if="isDone" class="text-center py-12 px-6">
      <v-icon icon="mdi-check-circle" color="success" size="64" class="mb-4" />
      <p class="text-h5 font-weight-bold mb-2">Setup complete!</p>
      <p class="text-body-1 text-medium-emphasis mb-6">
        Workspace <strong>{{ form.workspaceName }}</strong> has been created and
        <strong>{{ form.email }}</strong> has been {{ form.sendInvite ? 'invited' : 'added' }}.
      </p>
      <div class="d-flex justify-center gap-3">
        <AppButton variant="primary" @click="router.push({ name: 'reference' })">
          Go to Dashboard
        </AppButton>
        <AppButton variant="ghost" @click="restart">Start over</AppButton>
      </div>
    </AppCard>

    <!-- ── Wizard body ─────────────────────────────────────────────────── -->
    <template v-else>
      <!-- Progress bar -->
      <div class="mb-6">
        <div class="d-flex justify-space-between mb-2">
          <span class="text-caption text-medium-emphasis">
            Step {{ currentStep + 1 }} of {{ STEPS.length }} — {{ STEPS[currentStep].title }}
          </span>
          <span class="text-caption text-medium-emphasis">{{ progressPercent }}%</span>
        </div>
        <v-progress-linear
          :model-value="progressPercent"
          color="primary"
          rounded
          height="6"
        />
      </div>

      <!-- Step indicator strip -->
      <div class="d-flex gap-2 mb-6 overflow-x-auto pb-1">
        <v-chip
          v-for="(step, idx) in STEPS"
          :key="step.key"
          :color="idx < currentStep ? 'success' : idx === currentStep ? 'primary' : undefined"
          :variant="idx <= currentStep ? 'tonal' : 'outlined'"
          :prepend-icon="idx < currentStep ? 'mdi-check' : step.icon"
          :class="idx < currentStep ? 'cursor-pointer' : ''"
          size="small"
          @click="goToStep(idx)"
        >
          {{ step.title }}
        </v-chip>
      </div>

      <!-- Step content -->
      <AppCard class="mb-4">
        <v-window v-model="currentStep" :touch="false">

          <!-- Step 1 — Basic Info -->
          <v-window-item :value="0">
            <AppSection title="Basic Information" description="Tell us about the person being onboarded.">
              <div class="d-flex flex-column gap-4">
                <AppTextField
                  v-model="form.name"
                  label="Full name"
                  placeholder="Alice Merchant"
                  prepend-inner-icon="mdi-account-outline"
                  :error-messages="errors.name"
                />
                <AppTextField
                  v-model="form.email"
                  label="Work email"
                  placeholder="alice@company.com"
                  prepend-inner-icon="mdi-email-outline"
                  type="email"
                  :error-messages="errors.email"
                />
                <AppTextField
                  v-model="form.jobTitle"
                  label="Job title (optional)"
                  placeholder="Engineering Manager"
                  prepend-inner-icon="mdi-briefcase-outline"
                />
              </div>
            </AppSection>
          </v-window-item>

          <!-- Step 2 — Workspace Setup -->
          <v-window-item :value="1">
            <AppSection title="Workspace Setup" description="Configure the workspace this user will belong to.">
              <div class="d-flex flex-column gap-4">
                <AppTextField
                  :model-value="form.workspaceName"
                  label="Workspace name"
                  placeholder="Acme Corp"
                  prepend-inner-icon="mdi-office-building-outline"
                  :error-messages="errors.workspaceName"
                  @update:model-value="onWorkspaceNameInput"
                />
                <AppTextField
                  v-model="form.workspaceSlug"
                  label="Workspace slug"
                  placeholder="acme-corp"
                  prepend-inner-icon="mdi-link-variant"
                  hint="Used in URLs — lowercase letters, numbers, and hyphens only."
                  persistent-hint
                  :error-messages="errors.workspaceSlug"
                />
                <AppSelect
                  v-model="form.plan"
                  label="Plan"
                  :items="planOptions"
                  prepend-inner-icon="mdi-star-outline"
                  :error-messages="errors.plan"
                />
              </div>
            </AppSection>
          </v-window-item>

          <!-- Step 3 — Permissions -->
          <v-window-item :value="2">
            <AppSection title="Access & Permissions" description="Define what the user can do in this workspace.">
              <div class="d-flex flex-column gap-4">
                <AppSelect
                  v-model="form.role"
                  label="Workspace role"
                  :items="roleOptions"
                  prepend-inner-icon="mdi-shield-account-outline"
                  :error-messages="errors.role"
                />
                <v-alert
                  v-if="form.role === 'owner'"
                  type="warning"
                  variant="tonal"
                  density="compact"
                  icon="mdi-alert-outline"
                  text="Owners have full control including billing and deletion. Grant sparingly."
                />
                <AppCheckbox
                  v-model="form.sendInvite"
                  label="Send invitation email immediately"
                />
                <AppCheckbox
                  v-model="form.agreeToTerms"
                  label="I confirm this user has agreed to the platform Terms of Service."
                  :error-messages="errors.agreeToTerms"
                />
              </div>
            </AppSection>
          </v-window-item>

          <!-- Step 4 — Review -->
          <v-window-item :value="3">
            <AppSection title="Review & Confirm" description="Double-check everything before creating the workspace.">
              <v-list density="comfortable" lines="two" class="pa-0">
                <v-list-subheader>Account</v-list-subheader>
                <v-list-item prepend-icon="mdi-account-outline" title="Name" :subtitle="form.name || '—'" />
                <v-list-item prepend-icon="mdi-email-outline" title="Email" :subtitle="form.email || '—'" />
                <v-list-item prepend-icon="mdi-briefcase-outline" title="Job title" :subtitle="form.jobTitle || 'Not provided'" />
                <v-divider class="my-2" />
                <v-list-subheader>Workspace</v-list-subheader>
                <v-list-item prepend-icon="mdi-office-building-outline" title="Workspace name" :subtitle="form.workspaceName || '—'" />
                <v-list-item prepend-icon="mdi-link-variant" title="Slug" :subtitle="form.workspaceSlug || '—'" />
                <v-list-item prepend-icon="mdi-star-outline" title="Plan" :subtitle="form.plan || '—'" />
                <v-divider class="my-2" />
                <v-list-subheader>Access</v-list-subheader>
                <v-list-item prepend-icon="mdi-shield-account-outline" title="Role" :subtitle="form.role || '—'" />
                <v-list-item
                  :prepend-icon="form.sendInvite ? 'mdi-email-fast-outline' : 'mdi-email-off-outline'"
                  title="Invitation"
                  :subtitle="form.sendInvite ? 'Invite email will be sent' : 'No invite — manual access'"
                />
              </v-list>
            </AppSection>
          </v-window-item>

        </v-window>
      </AppCard>

      <!-- ── Wizard footer (shared across all steps) ──────────────────── -->
      <div class="d-flex justify-space-between align-center">
        <AppButton
          variant="ghost"
          prepend-icon="mdi-chevron-left"
          :disabled="isFirstStep"
          @click="goBack"
        >
          Back
        </AppButton>

        <AppButton
          variant="primary"
          :append-icon="isLastStep ? 'mdi-check' : 'mdi-chevron-right'"
          :loading="isSubmitting"
          @click="goNext"
        >
          {{ isLastStep ? 'Create Workspace' : 'Continue' }}
        </AppButton>
      </div>
    </template>

    <!-- Cancel confirmation dialog -->
    <v-dialog v-model="showCancelDialog" max-width="400">
      <AppCard>
        <div class="pa-6">
          <p class="text-h6 mb-2">Discard progress?</p>
          <p class="text-body-2 text-medium-emphasis mb-6">
            All information entered in this wizard will be lost.
          </p>
          <div class="d-flex justify-end gap-2">
            <AppButton variant="ghost" @click="showCancelDialog = false">Keep editing</AppButton>
            <AppButton variant="danger" @click="router.push({ name: 'reference' })">Discard</AppButton>
          </div>
        </div>
      </AppCard>
    </v-dialog>
  </AppPageLayout>
</template>
