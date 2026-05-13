<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    :class="props.class"
  >
    <!-- Indigo arc (right side) -->
    <path
      d="M 14.59 2.35 A 10 10 0 0 1 14.59 21.65"
      :stroke="indigoColor"
      stroke-width="2.5"
      stroke-linecap="round"
      fill="none"
    />
    <!-- Coral arc (left side) -->
    <path
      d="M 9.41 21.65 A 10 10 0 0 1 9.41 2.35"
      stroke="#D85A30"
      stroke-width="2.5"
      stroke-linecap="round"
      fill="none"
    />
    <!-- Center dot -->
    <circle cx="12" cy="12" r="2.5" :fill="indigoColor" />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  size?: number | string
  dark?: boolean
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 24,
  dark: false,
  class: '',
})

const indigoColor = computed(() => (props.dark ? '#AFA9EC' : '#534AB7'))
</script>
