/**
 * @module shared/ui
 *
 * THE official frontend import surface for Core Platform modules.
 *
 * All UI primitives, layouts, feedback components, and form inputs are
 * re-exported from this single entry point. Modules import exclusively
 * from here — never from sub-paths or from Vuetify directly.
 *
 * Allowed:
 *   import { AppButton, AppPageLayout, AppTextField } from '@/shared/ui'
 *
 * Forbidden:
 *   import AppButton from '@/shared/primitives/AppButton.vue'   ← internal path
 *   import { VBtn } from 'vuetify/components'                   ← Vuetify direct
 */

// ── Primitives ──────────────────────────────────────────────────────────────
export {
  AppButton,
  AppCard,
  AppSection,
  AppPageHeader,
  AppConfirmDialog,
  AppStatusChip,
  AppToolbarActions,
  AppPermissionBoundary,
} from '../primitives'
export type { AppButtonVariant } from '../primitives'

// ── Feedback ────────────────────────────────────────────────────────────────
export { AppLoadingState, AppEmptyState, AppErrorState } from '../feedback'

// ── Layouts ─────────────────────────────────────────────────────────────────
export { AppPageLayout, AppDetailLayout } from '../layouts'
export type { Breadcrumb, DetailBreadcrumb } from '../layouts'

// ── Forms ───────────────────────────────────────────────────────────────────
export { AppTextField, AppTextarea, AppSelect, AppCheckbox } from '../forms'

// ── Entity ──────────────────────────────────────────────────────────────────
export { AppEntityHeader, AppEntityMeta, AppEntityActions } from '../entity'
export type { MetaItem } from '../entity'

// ── Timeline ────────────────────────────────────────────────────────────────
export { AppActivityTimeline, AppTimelineItem } from '../timeline'
