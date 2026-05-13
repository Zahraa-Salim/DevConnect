<template>
  <label :class="['flex items-start gap-2 cursor-pointer group', props.class]">
    <div class="relative flex items-center justify-center mt-0.5">
      <input
        type="checkbox"
        class="peer appearance-none w-4 h-4 border border-dc-muted rounded-sm bg-dc-surface dark:bg-dc-dark-surface checked:bg-dc-primary checked:border-dc-primary group-hover:border-dc-primary transition-colors cursor-pointer"
        :checked="props.modelValue"
        @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
        v-bind="$attrs"
      />
      <svg
        class="absolute w-3 h-3 text-white pointer-events-none opacity-0 peer-checked:opacity-100"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="3"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <polyline points="20 6 9 17 4 12"></polyline>
      </svg>
    </div>
    <span v-if="props.label" class="text-body text-dc-body dark:text-dc-primary-muted leading-tight">
      {{ props.label }}
    </span>
  </label>
</template>

<script setup lang="ts">
interface Props {
  label?: string
  modelValue?: boolean
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
