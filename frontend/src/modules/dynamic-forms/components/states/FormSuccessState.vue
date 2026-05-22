<script setup lang="ts">
import type { FormSubmissionDetail } from '../../types'

defineProps<{
  submission: FormSubmissionDetail
}>()
</script>

<template>
  <div class="df-success d-flex flex-column align-center justify-center py-12 text-center">
    <v-icon icon="mdi-check-circle" color="success" size="64" />
    <h2 class="text-h5 mt-4 mb-2">Thank you!</h2>
    <p class="text-body-1 text-medium-emphasis">
      Your response has been recorded.
    </p>
  </div>
</template>
