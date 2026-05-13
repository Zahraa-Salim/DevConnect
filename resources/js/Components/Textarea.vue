<template>
  <div :class="['flex flex-col gap-1', props.class]">
    <div class="flex justify-between items-baseline">
      <label v-if="props.label" class="text-small font-medium text-dc-body dark:text-dc-primary-muted">
        {{ props.label }}
      </label>
      <span v-if="props.maxLength" class="text-[10px] text-dc-muted">
        {{ charCount }}/{{ props.maxLength }}
      </span>
    </div>
    <textarea
      :class="[
        'w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-surface border border-dc-muted rounded-md text-body resize-y min-h-[80px]',
        'focus:outline-none focus:ring-2 focus:ring-dc-primary focus:border-transparent',
        'hover:border-dc-primary transition-colors',
        { 'border-dc-danger focus:ring-dc-danger': props.error }
      ]"
      :value="props.modelValue"
      :maxlength="props.maxLength"
      @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
      v-bind="$attrs"
    />
    <span v-if="props.error" class="text-[12px] text-dc-danger">{{ props.error }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  label?: string
  error?: string
  maxLength?: number
  modelValue?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  class: ''
})

defineEmits<{
  'update:modelValue': [value: string]
}>()

const charCount = computed(() => props.modelValue?.length ?? 0)
</script>
