<template>
  <div :class="['inline-flex items-center', gapClass, props.class]">
    <LogoMark :size="markSize" :dark="dark" />
    <div
      v-if="!iconOnly"
      :class="['font-semibold tracking-[-0.4px] leading-none flex', textSizeClass]"
    >
      <span class="text-dc-primary-dark dark:text-dc-primary-muted">Dev</span>
      <span class="text-dc-coral">Connect</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LogoMark from '@/Components/LogoMark.vue'

interface Props {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  iconOnly?: boolean
  dark?: boolean
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  iconOnly: false,
  dark: false,
  class: '',
})

const markSize = computed(() => {
  return {
    sm: 20,
    md: 24,
    lg: 32,
    xl: 48,
  }[props.size]
})

const textSizeClass = computed(() => {
  return {
    sm: 'text-[16px]',
    md: 'text-[20px]',
    lg: 'text-[24px]',
    xl: 'text-[32px]',
  }[props.size]
})

const gapClass = computed(() => {
  return {
    sm: 'gap-1.5',
    md: 'gap-2',
    lg: 'gap-2.5',
    xl: 'gap-3',
  }[props.size]
})
</script>
