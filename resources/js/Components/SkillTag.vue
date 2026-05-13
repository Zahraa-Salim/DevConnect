<template>
  <Badge variant="skill" :class="props.class">
    <slot />
  </Badge>
</template>

<script setup lang="ts">
import Badge from './Badge.vue'

interface Props {
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})
</script>
