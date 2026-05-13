<template>
  <label :class="['flex items-center gap-2 cursor-pointer', props.class]">
    <div
      :class="[
        'relative w-10 h-5 rounded-full transition-colors',
        props.modelValue ? 'bg-dc-primary' : 'bg-dc-muted'
      ]"
      @click="$emit('update:modelValue', !props.modelValue)"
    >
      <div
        :class="[
          'absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform',
          props.modelValue ? 'translate-x-5' : 'translate-x-0'
        ]"
      />
    </div>
    <span v-if="props.label" class="text-body text-dc-body dark:text-dc-primary-muted">
      {{ props.label }}
    </span>
  </label>
</template>

<script setup lang="ts">
interface Props {
  modelValue?: boolean
  label?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  class: ''
})

defineEmits<{
  'update:modelValue': [value: boolean]
}>()
</script>
