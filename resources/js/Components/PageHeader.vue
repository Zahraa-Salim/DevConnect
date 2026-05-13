<template>
  <div :class="['flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6', props.class]">
    <div>
      <h1 class="text-h1">{{ props.title }}</h1>
      <p v-if="props.subtitle" class="text-body text-dc-muted mt-1">{{ props.subtitle }}</p>
    </div>
    <slot name="action" />
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  subtitle?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})
</script>
