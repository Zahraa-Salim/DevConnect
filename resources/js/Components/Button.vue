<template>
  <button
    :class="[
      'inline-flex items-center justify-center font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dc-primary disabled:opacity-50 disabled:cursor-not-allowed rounded-md',
      variants[props.variant],
      sizes[props.size],
      { 'w-full': props.fullWidth },
      $attrs.class
    ]"
    :disabled="props.disabled || props.isLoading"
    v-bind="$attrs"
  >
    <ThreeDotBounce v-if="props.isLoading" />
    <slot v-else />
  </button>
</template>

<script setup lang="ts">
import ThreeDotBounce from './ThreeDotBounce.vue'

interface Props {
  variant?: 'primary' | 'ghost' | 'destructive'
  size?: 'sm' | 'md' | 'lg'
  isLoading?: boolean
  fullWidth?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  isLoading: false,
  fullWidth: false,
  disabled: false
})

const variants = {
  primary:
    'bg-dc-primary text-white hover:bg-dc-primary-light active:bg-dc-primary-dark',
  ghost:
    'bg-transparent border border-dc-primary text-dc-primary hover:bg-dc-primary-tint active:bg-dc-primary active:text-white dark:border-dc-primary-muted dark:text-dc-primary-muted dark:hover:bg-dc-dark-border',
  destructive: 'bg-dc-danger text-white hover:bg-dc-danger-dark'
}

const sizes = {
  sm: 'px-3 py-1.5 text-small',
  md: 'px-4 py-2 text-body',
  lg: 'px-6 py-3 text-h3'
}
</script>
