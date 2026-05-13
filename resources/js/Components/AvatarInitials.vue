<template>
  <div
    :class="[
      'flex items-center justify-center rounded-full bg-dc-primary-tint text-dc-primary-dark font-medium',
      sizes[props.size],
      props.class
    ]"
  >
    {{ props.initials.substring(0, 2).toUpperCase() }}
  </div>
</template>

<script setup lang="ts">
interface Props {
  initials: string
  size?: 'sm' | 'md' | 'lg'
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  class: ''
})

const sizes = {
  sm: 'w-[28px] h-[28px] text-[10px]',
  md: 'w-[36px] h-[36px] text-small',
  lg: 'w-[64px] h-[64px] text-h2'
}
</script>
