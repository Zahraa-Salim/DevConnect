<template>
  <Head title="Mentor Dashboard" />

  <div class="max-w-5xl mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <h1 class="text-display text-dc-primary-dark">Mentor Dashboard</h1>
        <div class="flex items-center gap-2 mt-1">
          <Badge :variant="statusVariant">{{ mentor_profile.status }}</Badge>
          <span class="text-small text-dc-muted">GitHub score: {{ mentor_profile.github_score ?? 0 }}</span>
        </div>
      </div>
      <Button @click="showAvailability = !showAvailability" variant="ghost">
        {{ showAvailability ? 'Cancel' : 'Edit Availability' }}
      </Button>
    </div>

    <!-- Pending notice -->
    <div
      v-if="mentor_profile.status === 'pending'"
      class="bg-dc-warning-tint border border-dc-warning text-dc-warning-dark rounded-lg p-4 mb-6 text-body"
    >
      Your application is under review. You'll be notified once an admin approves it.
    </div>

    <!-- Availability Editor -->
    <Card v-if="showAvailability" class="p-6 mb-8">
      <h2 class="text-h3 text-dc-primary-dark mb-4">Set Your Availability</h2>
      <div class="overflow-x-auto mb-4">
        <table class="w-full text-center text-small">
          <thead>
            <tr>
              <th class="py-2 pr-3 text-left text-dc-muted font-medium w-12">Day</th>
              <th
                v-for="hour in hours"
                :key="hour"
                class="py-2 px-1 text-dc-muted font-medium min-w-[36px]"
              >
                {{ formatHour(hour) }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="day in days" :key="day.key">
              <td class="py-1 pr-3 text-left font-medium text-dc-primary-dark">{{ day.label }}</td>
              <td v-for="hour in hours" :key="hour" class="py-1 px-1">
                <button
                  type="button"
                  @click="toggleHour(day.key, hour)"
                  :class="[
                    'w-7 h-7 rounded text-xs transition-colors',
                    isSelected(day.key, hour)
                      ? 'bg-dc-primary text-white'
                      : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:bg-dc-primary-tint hover:text-dc-primary'
                  ]"
                >
                  &nbsp;
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex justify-end">
        <Button @click="saveAvailability" :isLoading="savingAvailability">Save Availability</Button>
      </div>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Upcoming Sessions -->
      <div>
        <h2 class="text-h3 text-dc-primary-dark mb-4">Upcoming Sessions</h2>
        <div v-if="slots.length === 0" class="text-body text-dc-muted">No upcoming sessions booked.</div>
        <div v-else class="space-y-3">
          <Card
            v-for="slot in slots"
            :key="slot.id"
            class="p-4"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="text-body font-semibold text-dc-primary-dark">
                  {{ formatDate(slot.starts_at) }}
                </div>
                <div class="text-small text-dc-muted">
                  {{ formatTime(slot.starts_at) }} – {{ formatTime(slot.ends_at) }}
                </div>
                <div class="text-small text-dc-muted mt-1">
                  with <span class="font-medium text-dc-primary-dark">{{ slot.booking?.student?.name ?? 'Unknown' }}</span>
                </div>
                <div class="text-small text-dc-muted mt-1 italic">
                  "{{ slot.booking?.topic }}"
                </div>
              </div>
              <Badge variant="in-progress">Booked</Badge>
            </div>
          </Card>
        </div>
      </div>

      <!-- Matching Help Requests -->
      <div>
        <h2 class="text-h3 text-dc-primary-dark mb-4">Help Requests for You</h2>
        <div v-if="help_requests.length === 0" class="text-body text-dc-muted">No open requests match your focus areas.</div>
        <div v-else class="space-y-3">
          <Card
            v-for="req in help_requests"
            :key="req.id"
            class="p-4"
          >
            <div class="flex items-start justify-between gap-2 mb-2">
              <h3 class="text-body font-semibold text-dc-primary-dark">{{ req.title }}</h3>
              <Badge variant="open">Open</Badge>
            </div>
            <p class="text-small text-dc-muted line-clamp-2 mb-2">{{ req.description }}</p>
            <div class="flex flex-wrap gap-1 mb-3">
              <Badge
                v-for="tag in (req.tech_tags ?? [])"
                :key="tag"
                variant="skill"
              >
                {{ tag }}
              </Badge>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-small text-dc-muted">by {{ req.user?.name ?? 'Unknown' }}</span>
              <Button size="sm" @click="claimRequest(req.id)" :isLoading="claiming === req.id">
                Claim
              </Button>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import Button from '@/Components/Button.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<{
  mentor_profile: any
  slots: any[]
  help_requests: any[]
}>()

const days = [
  { key: 'mon', label: 'Mon' },
  { key: 'tue', label: 'Tue' },
  { key: 'wed', label: 'Wed' },
  { key: 'thu', label: 'Thu' },
  { key: 'fri', label: 'Fri' },
  { key: 'sat', label: 'Sat' },
  { key: 'sun', label: 'Sun' },
]

const hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]

const showAvailability = ref(false)
const savingAvailability = ref(false)
const claiming = ref<number | null>(null)

const availability = reactive<Record<string, number[]>>(
  JSON.parse(JSON.stringify(props.mentor_profile.availability ?? { mon: [], tue: [], wed: [], thu: [], fri: [], sat: [], sun: [] }))
)

const statusVariant = computed(() => {
  if (props.mentor_profile.status === 'approved') return 'completed'
  if (props.mentor_profile.status === 'pending') return 'pending'
  return 'declined'
})

function formatHour(h: number): string {
  return h < 12 ? `${h}am` : h === 12 ? '12pm' : `${h - 12}pm`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

function isSelected(day: string, hour: number): boolean {
  return (availability[day] ?? []).includes(hour)
}

function toggleHour(day: string, hour: number) {
  const arr = availability[day] ?? []
  const idx = arr.indexOf(hour)
  if (idx === -1) arr.push(hour)
  else arr.splice(idx, 1)
}

function saveAvailability() {
  savingAvailability.value = true
  router.post('/mentor/availability', { availability }, {
    onSuccess: () => { showAvailability.value = false },
    onFinish: () => { savingAvailability.value = false },
  })
}

function claimRequest(id: number) {
  claiming.value = id
  router.post(`/help-requests/${id}/claim`, {}, {
    onSuccess: () => { claiming.value = null },
    onError: () => { claiming.value = null },
  })
}
</script>
