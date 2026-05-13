<template>
  <Head title="Find a Mentor" />

  <div class="max-w-6xl mx-auto p-4 md:p-8">
    <PageHeader
      title="Find a Mentor"
      subtitle="Book a 1-hour session with an experienced developer."
    />

    <!-- Area filter -->
    <div class="flex flex-wrap gap-2 mb-8">
      <button
        @click="setArea(null)"
        :class="tabClass(!activeArea)"
      >
        All
      </button>
      <button
        v-for="area in allAreas"
        :key="area"
        @click="setArea(area)"
        :class="tabClass(activeArea === area)"
      >
        {{ area }}
      </button>
    </div>

    <!-- Mentor cards -->
    <div v-if="filteredMentors.length === 0" class="text-body text-dc-muted text-center py-12">
      No mentors found{{ activeArea ? ` for "${activeArea}"` : '' }}.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <Card
        v-for="mentor in filteredMentors"
        :key="mentor.id"
        class="p-5 flex flex-col"
      >
        <!-- Mentor header -->
        <div class="flex items-center gap-3 mb-4">
          <img
            v-if="mentor.user?.avatar_url"
            :src="mentor.user.avatar_url"
            :alt="mentor.user.name"
            class="w-12 h-12 rounded-full object-cover"
          />
          <div
            v-else
            class="w-12 h-12 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold text-lg"
          >
            {{ mentor.user?.name?.charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="font-semibold text-dc-primary-dark">{{ mentor.user?.name }}</div>
            <div class="text-small text-dc-muted capitalize">{{ mentor.user?.role }}</div>
          </div>
        </div>

        <!-- Focus areas -->
        <div class="flex flex-wrap gap-1 mb-4">
          <Badge
            v-for="area in (mentor.focus_areas ?? []).slice(0, 4)"
            :key="area"
            variant="skill"
          >
            {{ area }}
          </Badge>
          <span
            v-if="(mentor.focus_areas ?? []).length > 4"
            class="text-xs text-dc-muted self-center"
          >
            +{{ mentor.focus_areas.length - 4 }} more
          </span>
        </div>

        <!-- Available slots -->
        <div class="flex-1">
          <div class="text-small font-medium text-dc-primary-dark mb-2">
            {{ mentor.upcoming_slots_count }} slot{{ mentor.upcoming_slots_count !== 1 ? 's' : '' }} available
          </div>
          <div v-if="(mentor.slots ?? []).length === 0" class="text-small text-dc-muted">
            No upcoming slots.
          </div>
          <div v-else class="flex flex-wrap gap-1 mb-3">
            <button
              v-for="slot in mentor.slots"
              :key="slot.id"
              @click="openBooking(mentor, slot)"
              class="text-xs px-2 py-1 rounded bg-dc-primary-tint text-dc-primary font-medium hover:bg-dc-primary hover:text-white transition-colors"
            >
              {{ formatSlot(slot.starts_at) }}
            </button>
          </div>
        </div>

        <Link :href="`/profile/${mentor.user?.id}`" class="text-small text-dc-primary hover:underline mt-2">
          View profile →
        </Link>
      </Card>
    </div>

    <!-- Booking modal -->
    <div
      v-if="bookingSlot"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeBooking"
    >
      <Card class="w-full max-w-md p-6">
        <h2 class="text-h3 text-dc-primary-dark mb-1">Book a session</h2>
        <p class="text-small text-dc-muted mb-4">
          with <strong>{{ bookingMentor?.user?.name }}</strong> on
          <strong>{{ formatSlotFull(bookingSlot.starts_at) }}</strong>
        </p>

        <label class="text-small font-medium text-dc-primary-dark block mb-1">
          What do you want to discuss?
        </label>
        <Textarea
          v-model="topic"
          :rows="4"
          placeholder="e.g. I'm struggling with database indexing and want advice on optimizing my queries..."
          class="mb-1"
        />
        <p class="text-small text-dc-muted mb-4">Max 500 characters.</p>
        <p v-if="bookingError" class="text-small text-dc-danger mb-3">{{ bookingError }}</p>

        <div class="flex justify-end gap-3">
          <Button variant="ghost" @click="closeBooking">Cancel</Button>
          <Button @click="confirmBooking" :isLoading="booking" :disabled="topic.trim().length === 0">
            Confirm Booking
          </Button>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import Button from '@/Components/Button.vue'
import Textarea from '@/Components/Textarea.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<{
  mentors: any[]
}>()

const allAreas = [
  'JavaScript', 'TypeScript', 'Python', 'PHP', 'Go', 'Rust', 'Java',
  'React', 'Vue', 'Angular', 'Laravel', 'Django', 'Node.js',
  'PostgreSQL', 'MySQL', 'MongoDB', 'Redis',
  'DevOps', 'Docker', 'AWS', 'UI/UX', 'Mobile', 'Security',
]

const activeArea = ref<string | null>(null)
const bookingSlot = ref<any | null>(null)
const bookingMentor = ref<any | null>(null)
const topic = ref('')
const booking = ref(false)
const bookingError = ref('')

const filteredMentors = computed(() => {
  if (!activeArea.value) return props.mentors
  return props.mentors.filter(m =>
    (m.focus_areas ?? []).includes(activeArea.value)
  )
})

function tabClass(active: boolean): string {
  return [
    'px-3 py-1.5 rounded-full text-small font-medium transition-colors',
    active
      ? 'bg-dc-primary text-white'
      : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:text-dc-primary hover:bg-dc-primary-tint',
  ].join(' ')
}

function setArea(area: string | null) {
  activeArea.value = area
}

function formatSlot(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
    + ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

function formatSlotFull(iso: string): string {
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'long', month: 'long', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function openBooking(mentor: any, slot: any) {
  bookingMentor.value = mentor
  bookingSlot.value = slot
  topic.value = ''
  bookingError.value = ''
}

function closeBooking() {
  bookingSlot.value = null
  bookingMentor.value = null
}

function confirmBooking() {
  if (topic.value.trim().length === 0) return
  booking.value = true
  bookingError.value = ''
  router.post(`/mentor-slots/${bookingSlot.value.id}/book`, { topic: topic.value }, {
    onSuccess: () => { closeBooking(); booking.value = false },
    onError: (e) => { bookingError.value = Object.values(e)[0] as string; booking.value = false },
  })
}
</script>
