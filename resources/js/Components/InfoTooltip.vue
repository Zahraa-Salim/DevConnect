<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'

interface Props {
  text: string
  position?: 'top' | 'bottom' | 'right'
}

const props = withDefaults(defineProps<Props>(), {
  position: 'top',
})

const open = ref(false)
const wrapperRef = ref<HTMLElement | null>(null)

function toggle(e: Event) {
  e.stopPropagation()
  open.value = !open.value
}

function handleOutsideClick(e: MouseEvent) {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape') open.value = false
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
  document.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick)
  document.removeEventListener('keydown', handleEscape)
})
</script>

<template>
  <span ref="wrapperRef" class="relative inline-flex items-center">
    <button
      type="button"
      @click="toggle"
      :aria-expanded="open"
      aria-label="More info"
      class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-dc-muted/20 hover:bg-dc-muted/30 text-dc-muted hover:text-dc-body transition-colors text-[10px] font-bold leading-none cursor-pointer"
    >
      ?
    </button>

    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <span
        v-if="open"
        :class="[
          'absolute z-50 w-64 p-3 rounded-lg bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border shadow-lg text-small text-dc-body dark:text-dc-primary-muted leading-relaxed',
          position === 'top' && 'bottom-full mb-2 left-1/2 -translate-x-1/2',
          position === 'bottom' && 'top-full mt-2 left-1/2 -translate-x-1/2',
          position === 'right' && 'left-full ml-2 top-1/2 -translate-y-1/2',
        ]"
      >
        {{ text }}
      </span>
    </transition>
  </span>
</template>
