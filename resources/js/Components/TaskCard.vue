<template>
  <div
    class="bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border rounded-lg p-3 cursor-pointer hover:border-dc-primary/50 transition-colors select-none"
    @click="$emit('click', task)"
  >
    <!-- Priority + Energy row -->
    <div class="flex items-center gap-1.5 mb-2">
      <span :class="priorityDot[task.priority ?? 'medium']" class="w-2 h-2 rounded-full flex-shrink-0" />
      <span v-if="task.energy" :class="energyBadges[task.energy]?.class" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium">
        {{ energyBadges[task.energy]?.label }}
      </span>
      <span v-if="task.role_tag" class="text-[10px] text-dc-muted truncate">{{ task.role_tag }}</span>
    </div>

    <!-- Title -->
    <div class="flex items-start gap-2 mb-2">
      <input
        v-if="showQuickComplete"
        type="checkbox"
        class="mt-0.5 h-4 w-4 rounded border-dc-muted accent-dc-primary flex-shrink-0"
        :checked="task.status === 'done'"
        @click.stop
        @change="onQuickCompleteChange"
        aria-label="Mark task done"
      />
      <p class="text-small font-medium text-dc-body dark:text-dc-primary-muted leading-snug">
        {{ task.title }}
      </p>
    </div>

    <!-- Subtasks progress -->
    <div v-if="task.subtasks && task.subtasks.length > 0" class="mb-2">
      <div class="flex items-center gap-1.5">
        <div class="flex-1 bg-dc-surface dark:bg-dc-dark-border rounded-full h-1">
          <div
            class="bg-dc-primary rounded-full h-1 transition-all"
            :style="{ width: subtaskProgress + '%' }"
          />
        </div>
        <span class="text-[10px] text-dc-muted">
          {{ doneSubtasks }}/{{ task.subtasks.length }}
        </span>
      </div>
    </div>

    <!-- Footer: assignee + story points + due date -->
    <div class="flex items-center justify-between mt-1">
      <div class="flex items-center gap-1.5">
        <div
          v-if="task.assignee"
          class="w-5 h-5 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary text-[9px] font-bold flex-shrink-0"
          :title="task.assignee.name"
        >
          {{ task.assignee.name?.charAt(0).toUpperCase() }}
        </div>
        <span v-if="task.due_date" class="text-[10px] text-dc-muted">
          {{ formatDueDate(task.due_date) }}
        </span>
      </div>
      <span v-if="task.story_points" class="bg-dc-surface dark:bg-dc-dark-border text-dc-muted text-[10px] font-mono px-1.5 py-0.5 rounded font-semibold">
        {{ task.story_points }} SP
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface TaskAssignee {
  id: number
  name: string
  avatar_url: string | null
}

interface SubTask {
  id: number
  status: string
}

interface Task {
  id: number
  title: string
  status?: string
  position?: number
  sprint_id?: number | null
  priority?: string | null
  energy?: string | null
  role_tag?: string | null
  story_points?: number | null
  due_date?: string | null
  assignee?: TaskAssignee | null
  subtasks?: SubTask[]
}

const props = withDefaults(defineProps<{
  task: Task
  showQuickComplete?: boolean
}>(), {
  showQuickComplete: false,
})

const emit = defineEmits<{
  click: [task: Task]
  quickComplete: [task: Task]
}>()

const priorityDot: Record<string, string> = {
  high: 'bg-dc-danger',
  medium: 'bg-dc-warning',
  low: 'bg-dc-success',
}

const energyBadges: Record<string, { label: string; class: string }> = {
  quick_win: { label: 'Quick Win', class: 'bg-dc-success/15 text-dc-success' },
  deep_work: { label: 'Deep Work', class: 'bg-dc-primary/15 text-dc-primary' },
  blocked: { label: 'Blocked', class: 'bg-dc-danger/15 text-dc-danger' },
}

const doneSubtasks = computed(() =>
  props.task.subtasks?.filter(s => s.status === 'done').length ?? 0
)

const subtaskProgress = computed(() => {
  const total = props.task.subtasks?.length ?? 0
  return total === 0 ? 0 : Math.round((doneSubtasks.value / total) * 100)
})

function formatDueDate(date: string): string {
  const d = new Date(date)
  const now = new Date()
  const diff = Math.ceil((d.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
  if (diff < 0) return 'overdue'
  if (diff === 0) return 'today'
  if (diff === 1) return 'tomorrow'
  if (diff < 7) return `${diff}d`
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function onQuickCompleteChange(event: Event) {
  const checked = (event.target as HTMLInputElement).checked
  if (checked && props.task.status !== 'done') {
    emit('quickComplete', props.task)
  }
}
</script>
