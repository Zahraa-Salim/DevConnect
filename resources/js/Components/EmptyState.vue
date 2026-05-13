<template>
  <div
    :class="[
      'flex flex-col items-center justify-center p-8 text-center border border-dashed border-dc-muted rounded-xl',
      props.class
    ]"
  >
    <p class="text-body text-dc-muted mb-4">{{ props.message }}</p>
    <Button v-if="props.actionLabel" variant="primary" @click="$emit('action')">
      {{ props.actionLabel }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import Button from './Button.vue'

interface Props {
  message: string
  actionLabel?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

defineEmits<{
  action: []
}>()
</script>
