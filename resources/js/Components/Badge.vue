<template>
  <span
    :class="[
      'inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase',
      variants[variant],
      props.class
    ]"
  >
    <slot />
  </span>
</template>

<script setup lang="ts">
interface Props {
  variant: 'skill' | 'ai' | 'open' | 'in-progress' | 'completed' | 'pending' | 'declined'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

const variants = {
  skill: 'bg-dc-primary-tint text-dc-primary-dark',
  ai: 'bg-dc-coral-tint text-dc-coral-dark',
  open: 'bg-dc-primary-tint text-dc-primary',
  'in-progress': 'bg-dc-primary-tint text-dc-primary',
  completed: 'bg-dc-success-tint text-dc-success-dark',
  pending: 'bg-dc-warning-tint text-dc-warning-dark',
  declined: 'bg-dc-danger-tint text-dc-danger-dark'
}
</script>
