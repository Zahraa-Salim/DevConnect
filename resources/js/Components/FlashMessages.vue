<template>
  <div
    v-if="visibleMessages.length"
    class="fixed top-20 right-4 z-[70] w-[calc(100%-2rem)] max-w-sm space-y-2"
  >
    <div
      v-for="message in visibleMessages"
      :key="message.type"
      :class="[
        'flex items-start justify-between gap-3 rounded-md border px-4 py-3 shadow-lg dark:bg-dc-dark-surface',
        styles[message.type],
      ]"
      role="status"
    >
      <p class="text-small font-medium">{{ message.text }}</p>
      <button
        type="button"
        class="text-current opacity-60 hover:opacity-100"
        aria-label="Dismiss message"
        @click="dismiss(message.type)"
      >
        x
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

type FlashType = 'success' | 'error' | 'info'

interface FlashMessage {
  type: FlashType
  text: string
}

const page = usePage()
const dismissed = reactive<Record<FlashType, boolean>>({
  success: false,
  error: false,
  info: false,
})
const timers: Partial<Record<FlashType, ReturnType<typeof setTimeout>>> = {}

const flash = computed(() => (page.props as any).flash ?? {})

const visibleMessages = computed<FlashMessage[]>(() => {
  return (['success', 'error', 'info'] as FlashType[])
    .filter((type) => flash.value?.[type] && !dismissed[type])
    .map((type) => ({ type, text: flash.value[type] }))
})

watch(
  () => [flash.value?.success, flash.value?.error, flash.value?.info],
  () => {
    ;(['success', 'error', 'info'] as FlashType[]).forEach((type) => {
      clearTimer(type)

      if (!flash.value?.[type]) {
        dismissed[type] = false
        return
      }

      dismissed[type] = false
      timers[type] = setTimeout(() => {
        dismissed[type] = true
        delete timers[type]
      }, 4000)
    })
  },
  { immediate: true }
)

function clearTimer(type: FlashType) {
  if (!timers[type]) return
  clearTimeout(timers[type])
  delete timers[type]
}

function dismiss(type: FlashType) {
  clearTimer(type)
  dismissed[type] = true
}

onBeforeUnmount(() => {
  ;(['success', 'error', 'info'] as FlashType[]).forEach(clearTimer)
})

const styles: Record<FlashType, string> = {
  success: 'bg-dc-success-tint border-dc-success text-dc-success-dark',
  error: 'bg-dc-danger-tint border-dc-danger text-dc-danger-dark',
  info: 'bg-dc-primary-tint border-dc-primary text-dc-primary-dark dark:text-dc-primary-muted',
}
</script>
