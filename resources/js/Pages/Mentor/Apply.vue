<template>
  <Head title="Become a Mentor" />

  <div class="max-w-2xl mx-auto p-4 md:p-8">
    <PageHeader
      title="Become a Mentor"
      subtitle="Share your experience and guide the next generation of developers."
    />

    <form @submit.prevent="submit">
      <!-- Motivation -->
      <Card class="p-6 mb-6">
        <h2 class="text-h3 text-dc-primary-dark mb-1">Why do you want to mentor?</h2>
        <p class="text-small text-dc-muted mb-4">Minimum 100 characters. Be specific about your experience and what you hope to offer.</p>
        <Textarea
          v-model="form.motivation"
          :rows="6"
          placeholder="I have 5+ years of experience in..."
          :class="errors.motivation ? 'border-dc-danger' : ''"
        />
        <div class="flex justify-between mt-1">
          <p v-if="errors.motivation" class="text-small text-dc-danger">{{ errors.motivation }}</p>
          <p class="text-small text-dc-muted ml-auto">{{ form.motivation.length }} / 100 min</p>
        </div>
      </Card>

      <!-- Focus Areas -->
      <Card class="p-6 mb-6">
        <h2 class="text-h3 text-dc-primary-dark mb-1">Focus areas</h2>
        <p class="text-small text-dc-muted mb-4">Select the technologies and domains where you can mentor confidently.</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="area in allAreas"
            :key="area"
            type="button"
            @click="toggleArea(area)"
            :class="[
              'px-3 py-1.5 rounded-full text-small font-medium transition-colors',
              form.focus_areas.includes(area)
                ? 'bg-dc-primary text-white'
                : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:text-dc-primary hover:bg-dc-primary-tint'
            ]"
          >
            {{ area }}
          </button>
        </div>
        <p v-if="errors.focus_areas" class="text-small text-dc-danger mt-2">{{ errors.focus_areas }}</p>
      </Card>

      <!-- Availability -->
      <Card class="p-6 mb-8">
        <h2 class="text-h3 text-dc-primary-dark mb-1">Weekly availability</h2>
        <p class="text-small text-dc-muted mb-4">Click hours to toggle availability. Times are in your local timezone.</p>

        <div class="overflow-x-auto">
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
                    :title="`${day.label} ${formatHour(hour)}`"
                  >
                    &nbsp;
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="errors.availability" class="text-small text-dc-danger mt-2">{{ errors.availability }}</p>
      </Card>

      <div class="flex justify-end">
        <Button type="submit" :isLoading="submitting" :disabled="submitting || form.motivation.length < 100">
          Submit Application
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Textarea from '@/Components/Textarea.vue'

defineOptions({ layout: AuthenticatedLayout })

const allAreas = [
  'JavaScript', 'TypeScript', 'Python', 'PHP', 'Go', 'Rust', 'Java',
  'React', 'Vue', 'Angular', 'Laravel', 'Django', 'Node.js',
  'PostgreSQL', 'MySQL', 'MongoDB', 'Redis',
  'DevOps', 'Docker', 'AWS', 'UI/UX', 'Mobile', 'Security',
]

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

const form = reactive<{
  motivation: string
  focus_areas: string[]
  availability: Record<string, number[]>
}>({
  motivation: '',
  focus_areas: [],
  availability: { mon: [], tue: [], wed: [], thu: [], fri: [], sat: [], sun: [] },
})

const errors = reactive<Record<string, string>>({})
const submitting = ref(false)

function formatHour(h: number): string {
  return h < 12 ? `${h}am` : h === 12 ? '12pm' : `${h - 12}pm`
}

function toggleArea(area: string) {
  const idx = form.focus_areas.indexOf(area)
  if (idx === -1) form.focus_areas.push(area)
  else form.focus_areas.splice(idx, 1)
}

function isSelected(day: string, hour: number): boolean {
  return (form.availability[day] ?? []).includes(hour)
}

function toggleHour(day: string, hour: number) {
  const arr = form.availability[day] ?? []
  const idx = arr.indexOf(hour)
  if (idx === -1) arr.push(hour)
  else arr.splice(idx, 1)
}

function validate(): boolean {
  Object.keys(errors).forEach(k => delete errors[k])
  let ok = true
  if (form.motivation.length < 100) { errors.motivation = 'Must be at least 100 characters.'; ok = false }
  if (form.focus_areas.length === 0) { errors.focus_areas = 'Select at least one focus area.'; ok = false }
  const hasAny = Object.values(form.availability).some(arr => arr.length > 0)
  if (!hasAny) { errors.availability = 'Select at least one available time slot.'; ok = false }
  return ok
}

function submit() {
  if (!validate()) return
  submitting.value = true
  router.post('/mentor/apply', form, {
    onError: (e) => { Object.assign(errors, e); submitting.value = false },
    onFinish: () => { submitting.value = false },
  })
}
</script>
