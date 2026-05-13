<template>
  <Card :style="cardStyle" :class="`p-4 flex flex-col gap-3 ${statusStyles[props.status]} ${props.class}`">
    <h3 class="text-h3">{{ props.title }}</h3>

    <div class="flex flex-wrap items-center gap-2">
      <span
        :class="[
          'inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase',
          energyStyles[props.energy]
        ]"
      >
        {{ props.energy }}
      </span>
      <SkillTag>{{ props.role }}</SkillTag>
    </div>

    <div class="flex items-center justify-between mt-1">
      <span v-if="props.dueDate" class="text-small text-dc-muted">{{ props.dueDate }}</span>
      <div v-else />

      <AvatarInitials v-if="props.assigneeInitials" :initials="props.assigneeInitials" size="sm" />
    </div>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Card from './Card.vue'
import SkillTag from './SkillTag.vue'
import AvatarInitials from './AvatarInitials.vue'

interface Props {
  title: string
  energy: 'Quick win' | 'Deep work' | 'Blocked'
  role: string
  assigneeInitials?: string
  status: 'To do' | 'In progress' | 'Done'
  dueDate?: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: ''
})

const statusStyles = {
  'To do': '',
  'In progress': 'border-l-[3px] border-l-dc-primary',
  Done: 'border-l-[3px] border-l-dc-success bg-dc-success-tint dark:bg-dc-success-dark/20'
}

const energyStyles = {
  'Quick win': 'bg-dc-success-tint text-dc-success-dark',
  'Deep work': 'bg-dc-primary-tint text-dc-primary-dark',
  Blocked: 'bg-dc-danger-tint text-dc-danger-dark'
}

const cardStyle = computed(() => {
  if (props.status === 'In progress' || props.status === 'Done') {
    return {
      borderTopLeftRadius: '0px',
      borderBottomLeftRadius: '0px'
    }
  }
  return {}
})
</script>
