<template>
  <div :class="['flex flex-col gap-1', props.class]">
    <label v-if="props.label" class="text-small font-medium text-dc-body dark:text-dc-primary-muted">
      {{ props.label }}
    </label>
    <input
      :class="[
        'w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-surface border border-dc-muted rounded-md text-body',
        'focus:outline-none focus:ring-2 focus:ring-dc-primary focus:border-transparent',
        'hover:border-dc-primary transition-colors',
        { 'border-dc-danger focus:ring-dc-danger': props.error }
      ]"
      :value="props.modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      v-bind="$attrs"
    />
    <span v-if="props.error" class="text-[12px] text-dc-danger">{{ props.error }}</span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label?: string
  error?: string
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
</script>
