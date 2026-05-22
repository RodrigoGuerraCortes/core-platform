<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useFormQuery } from '../queries/useFormQuery'
import DynamicFormRenderer from '../components/renderer/DynamicFormRenderer.vue'
import FormLoadingState from '../components/states/FormLoadingState.vue'
import FormErrorState from '../components/states/FormErrorState.vue'

const route = useRoute()
const formId = computed(() => Number(route.params.formId))

const { data: form, isLoading, isError } = useFormQuery(formId)

const activeVersion = computed(() => form.value?.active_version ?? null)
const canRender = computed(
  () => form.value && activeVersion.value && form.value.status === 'active',
)
</script>

<template>
  <v-container max-width="720" class="py-8">
    <!-- Loading -->
    <FormLoadingState v-if="isLoading" />

    <!-- Fetch error -->
    <FormErrorState
      v-else-if="isError"
      title="Could not load form"
      message="The form could not be loaded. Please refresh the page or try again later."
    />

    <!-- Form inactive (draft / archived) -->
    <FormErrorState
      v-else-if="form && !canRender"
      title="Form unavailable"
      message="This form is not currently accepting responses."
    />

    <!-- Render -->
    <DynamicFormRenderer
      v-else-if="canRender && activeVersion"
      :form-id="formId"
      :version="activeVersion"
    />
  </v-container>
</template>
