<template>
  <div :class="['flex flex-col gap-1', props.class]">
    <label v-if="props.label" class="text-small font-medium text-dc-body dark:text-dc-primary-muted">
      {{ props.label }}
    </label>
    <div class="relative">
      <select
        :class="[
          'w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-surface border border-dc-muted rounded-md text-body appearance-none',
          'focus:outline-none focus:ring-2 focus:ring-dc-primary focus:border-transparent',
          'hover:border-dc-primary transition-colors',
          { 'border-dc-danger focus:ring-dc-danger': props.error }
        ]"
        :value="props.modelValue"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        v-bind="$attrs"
      >
        <option v-for="opt in props.options" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
      <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-dc-muted">
        ▼
      </div>
    </div>
    <span v-if="props.error" class="text-[12px] text-dc-danger">{{ props.error }}</span>
  </div>
</template>

<script setup lang="ts">
interface SelectOption {
  value: string | number
  label: string
}

interface Props {
  label?: string
  error?: string
  options: SelectOption[]
  modelValue?: string | number
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  class: ''
})

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>
