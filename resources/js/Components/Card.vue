<template>
  <div
    :class="[
      'bg-white dark:bg-dc-dark-surface border-[0.5px] border-[#E2E1F5] dark:border-dc-dark-border rounded-[12px] p-[20px]',
      props.class
    ]"
    v-bind="$attrs"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})
</script>
