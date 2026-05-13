<template>
  <Badge variant="ai" :class="props.class">
    AI
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
