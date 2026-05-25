<script setup lang="ts">
/**
 * UploadExamplePage — canonical upload UX reference.
 *
 * Demonstrates:
 *   - Drag-and-drop zone
 *   - Per-file progress tracking (simulated via setTimeout)
 *   - In-progress / done / error / retry states
 *   - useUploadManager composable pattern
 *
 * No real backend — uploads are fully simulated.
 */
import { useUploadManager } from '../composables'
import { AppPageLayout, AppButton, AppCard } from '@/shared/ui'
import type { UploadItem } from '../types'

const { items, addFiles, retry, remove } = useUploadManager()

// ── Drag-and-drop ─────────────────────────────────────────────────────────
function onDragOver(e: DragEvent): void { e.preventDefault() }

function onDrop(e: DragEvent): void {
  e.preventDefault()
  const files = Array.from(e.dataTransfer?.files ?? [])
  addFiles(files)
}

function onFileInputChange(e: Event): void {
  const files = Array.from((e.target as HTMLInputElement).files ?? [])
  addFiles(files)
  // Reset so same file can be re-selected
  ;(e.target as HTMLInputElement).value = ''
}

// ── Helpers ───────────────────────────────────────────────────────────────
const STATUS_COLOR: Record<UploadItem['status'], string> = {
  pending:  'default',
  uploading: 'primary',
  done:     'success',
  error:    'error',
}

const STATUS_ICON: Record<UploadItem['status'], string> = {
  pending:   'mdi-file-outline',
  uploading: 'mdi-upload-outline',
  done:      'mdi-check-circle-outline',
  error:     'mdi-alert-circle-outline',
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1048576).toFixed(1)} MB`
}
</script>

<template>
  <AppPageLayout
    title="Upload"
    description="Drag-and-drop or select files to upload. Demonstrates per-file progress states."
  >
    <!-- Drop zone -->
    <AppCard class="mb-6">
      <div
        class="drop-zone d-flex flex-column align-center justify-center py-12 rounded-lg border-dashed cursor-pointer"
        @dragover="onDragOver"
        @drop="onDrop"
        @click="($refs.fileInput as HTMLInputElement).click()"
      >
        <v-icon icon="mdi-cloud-upload-outline" size="48" class="mb-3 text-medium-emphasis" />
        <p class="text-body-1 font-weight-medium mb-1">Drag files here or click to browse</p>
        <p class="text-caption text-medium-emphasis">Any file type · Max 10 MB each</p>

        <input
          ref="fileInput"
          type="file"
          multiple
          class="d-none"
          @change="onFileInputChange"
        />
      </div>
    </AppCard>

    <!-- File list -->
    <div v-if="items.length > 0" class="d-flex flex-column gap-3">
      <AppCard
        v-for="item in items"
        :key="item.localId"
        class="pa-4"
      >
        <div class="d-flex align-center gap-4">
          <!-- Status icon -->
          <v-icon
            :icon="STATUS_ICON[item.status]"
            :color="STATUS_COLOR[item.status]"
            size="28"
          />

          <!-- File info -->
          <div class="flex-grow-1 min-w-0">
            <p class="text-body-2 font-weight-medium text-truncate">{{ item.file.name }}</p>
            <p class="text-caption text-medium-emphasis">{{ formatBytes(item.file.size) }}</p>

            <!-- Progress bar for uploading items -->
            <v-progress-linear
              v-if="item.status === 'uploading'"
              :model-value="item.progress"
              color="primary"
              height="4"
              rounded
              class="mt-2"
            />

            <!-- Error message -->
            <p v-if="item.status === 'error'" class="text-caption text-error mt-1">
              {{ item.errorMessage ?? 'Upload failed' }}
            </p>

            <!-- Server URL for done items -->
            <p v-if="item.status === 'done' && item.serverUrl" class="text-caption text-success mt-1">
              Uploaded: {{ item.serverUrl }}
            </p>
          </div>

          <!-- Progress percentage -->
          <span v-if="item.status === 'uploading'" class="text-caption font-weight-medium text-primary">
            {{ item.progress }}%
          </span>

          <!-- Actions -->
          <div class="d-flex gap-1">
            <AppButton
              v-if="item.status === 'error'"
              variant="ghost"
              size="small"
              prepend-icon="mdi-refresh"
              @click="retry(item.localId)"
            >
              Retry
            </AppButton>
            <AppButton
              v-if="item.status !== 'uploading'"
              variant="ghost"
              size="small"
              icon="mdi-close"
              @click="remove(item.localId)"
            />
          </div>
        </div>
      </AppCard>
    </div>

    <!-- Empty state -->
    <div
      v-else
      class="text-center py-8 text-medium-emphasis"
    >
      <p class="text-body-2">No files selected yet.</p>
    </div>
  </AppPageLayout>
</template>

<style scoped>
.drop-zone {
  border: 2px dashed rgba(var(--v-border-color), 0.4);
  transition: border-color 0.2s;
}
.drop-zone:hover {
  border-color: rgb(var(--v-theme-primary));
}
.cursor-pointer {
  cursor: pointer;
}
</style>
