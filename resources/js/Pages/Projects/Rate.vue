<template>
  <Head :title="`Rate your team — ${project.title}`" />

  <div class="max-w-3xl mx-auto p-4 md:p-8">
    <Link :href="urls.projects.show(project.id)" class="text-small text-dc-muted hover:text-dc-primary">
      ← Back to {{ project.title }}
    </Link>

    <!-- Step 2: Endorsements -->
    <div v-if="showEndorsements">
      <PageHeader
        title="Endorse your teammates' skills"
        subtitle="What skills did you actually witness? Only endorse what you saw."
        class="mt-4"
      />

      <div class="space-y-6 mb-8">
        <Card
          v-for="member in membersToEndorse"
          :key="member.user_id"
          class="p-6"
        >
          <!-- Avatar + name -->
          <div class="flex items-center gap-3 mb-4">
            <img
              v-if="member.user.avatar_url"
              :src="member.user.avatar_url"
              :alt="member.user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold flex-shrink-0"
            >
              {{ member.user.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="font-semibold text-dc-primary-dark dark:text-dc-primary-muted">
                {{ member.user.name }}
              </div>
              <div class="text-small text-dc-muted">
                {{ selectedSkills[member.user_id]?.length ?? 0 }} / 5 selected
              </div>
            </div>
          </div>

          <!-- Skill pills from their profile -->
          <div v-if="member.user.skills?.length" class="flex flex-wrap gap-2">
            <button
              v-for="skill in member.user.skills"
              :key="skill.skill_name"
              type="button"
              @click="toggleSkill(member.user_id, skill.skill_name)"
              :disabled="
                !isSkillSelected(member.user_id, skill.skill_name) &&
                (selectedSkills[member.user_id]?.length ?? 0) >= 5
              "
              :class="[
                'px-3 py-1.5 rounded-full text-small font-medium transition-colors',
                isSkillSelected(member.user_id, skill.skill_name)
                  ? 'bg-dc-primary text-white'
                  : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:bg-dc-primary-tint hover:text-dc-primary disabled:opacity-40 disabled:cursor-not-allowed'
              ]"
            >
              {{ skill.skill_name }}
            </button>
          </div>

          <!-- Free-text when member has no skills -->
          <div v-else>
            <p class="text-small text-dc-muted mb-2">This member has no listed skills — type one and press Enter:</p>
            <div class="flex flex-wrap gap-2 mb-2">
              <span
                v-for="s in (selectedSkills[member.user_id] ?? [])"
                :key="s"
                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-dc-primary text-white text-small font-medium"
              >
                {{ s }}
                <button type="button" @click="removeCustomSkill(member.user_id, s)" class="hover:opacity-70">×</button>
              </span>
            </div>
            <input
              v-if="(selectedSkills[member.user_id]?.length ?? 0) < 5"
              type="text"
              placeholder="Type a skill and press Enter…"
              class="w-full px-3 py-2 rounded-lg border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface text-small focus:outline-none focus:ring-2 focus:ring-dc-primary"
              @keydown.enter.prevent="addCustomSkill(member.user_id, $event)"
            />
          </div>
        </Card>
      </div>

      <div class="flex justify-end gap-3">
        <Link :href="urls.projects.show(project.id)">
          <Button variant="ghost" type="button">Skip</Button>
        </Link>
        <Button variant="primary" @click="saveEndorsements" :disabled="savingEndorsements">
          {{ savingEndorsements ? 'Saving…' : 'Save Endorsements' }}
        </Button>
      </div>
    </div>

    <!-- Step 1: Rating flow -->
    <template v-else>
    <PageHeader
      title="Rate your team"
      subtitle="Your ratings are anonymous"
      class="mt-4"
    />

    <!-- Already rated -->
    <Card v-if="alreadyRated" class="p-8 text-center">
      <div class="text-4xl mb-4">✓</div>
      <p class="text-h3 text-dc-primary-dark mb-4">You've already rated this team.</p>
      <Link :href="urls.projects.show(project.id)">
        <Button variant="primary">Back to project</Button>
      </Link>
    </Card>

    <!-- No teammates to rate -->
    <Card v-else-if="membersToRate.length === 0" class="p-8 text-center">
      <p class="text-body text-dc-muted mb-6">There are no teammates to rate for this project.</p>
      <Link :href="urls.projects.show(project.id)">
        <Button variant="ghost">Back to project</Button>
      </Link>
    </Card>

    <!-- Rating form -->
    <form v-else @submit.prevent="submit">
      <div class="space-y-6 mb-8">
        <Card
          v-for="(entry, index) in form"
          :key="entry.rated_id"
          class="p-6"
        >
          <!-- Avatar + name + role -->
          <div class="flex items-center gap-3 mb-6">
            <img
              v-if="membersToRate[index].user.avatar_url"
              :src="membersToRate[index].user.avatar_url"
              :alt="membersToRate[index].user.name"
              class="w-10 h-10 rounded-full object-cover"
            />
            <div
              v-else
              class="w-10 h-10 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold flex-shrink-0"
            >
              {{ membersToRate[index].user.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="font-semibold text-dc-primary-dark dark:text-dc-primary-muted">
                {{ membersToRate[index].user.name }}
              </div>
              <div class="text-small text-dc-muted capitalize">
                {{ membersToRate[index].role || membersToRate[index].user.role }}
              </div>
            </div>
          </div>

          <!-- Rating rows -->
          <div class="space-y-4">
            <div
              v-for="dim in DIMENSIONS"
              :key="dim.key"
              class="flex items-center justify-between gap-4"
            >
              <span class="text-body text-dc-body dark:text-dc-primary-muted w-32 flex-shrink-0">
                {{ dim.label }}
              </span>
              <div class="flex gap-1.5">
                <button
                  v-for="n in 5"
                  :key="n"
                  type="button"
                  @click="(entry as any)[dim.key] = n"
                  :class="[
                    'w-9 h-9 rounded-lg text-body font-semibold transition-colors',
                    (entry as any)[dim.key] === n
                      ? 'bg-dc-primary text-white'
                      : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:bg-dc-primary-tint hover:text-dc-primary',
                  ]"
                >
                  {{ n }}
                </button>
              </div>
            </div>
          </div>

          <!-- Comment -->
          <div class="mt-5">
            <Textarea
              v-model="entry.comment"
              placeholder="Anything you'd like to add?"
              :rows="3"
              maxlength="500"
            />
            <div class="text-right text-xs text-dc-muted mt-1">
              {{ entry.comment.length }}/500
            </div>
          </div>
        </Card>
      </div>

      <p v-if="errors.length > 0" class="text-small text-dc-danger mb-4">
        {{ errors[0] }}
      </p>

      <div class="flex justify-end gap-3">
        <Link :href="urls.projects.show(project.id)">
          <Button variant="ghost" type="button">Cancel</Button>
        </Link>
        <Button variant="primary" type="submit" :disabled="submitting">
          {{ submitting ? 'Submitting…' : 'Submit Ratings' }}
        </Button>
      </div>
    </form>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Textarea from '@/Components/Textarea.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

interface UserSkill {
  id: number
  user_id: number
  skill_name: string
  proficiency: string | null
}

interface ProjectMember {
  id: number
  user_id: number
  role: string | null
  user: {
    id: number
    name: string
    avatar_url: string | null
    role: string
    skills?: UserSkill[]
  }
}

interface RatingEntry {
  rated_id: number
  communication: number
  reliability: number
  contribution: number
  comment: string
}

const props = defineProps<{
  project: { id: number; title: string }
  membersToRate: ProjectMember[]
  alreadyRated: boolean
  showEndorsements: boolean
  membersToEndorse: ProjectMember[]
}>()

const DIMENSIONS = [
  { key: 'communication', label: 'Communication' },
  { key: 'reliability',   label: 'Reliability' },
  { key: 'contribution',  label: 'Contribution' },
]

const form = ref<RatingEntry[]>(
  props.membersToRate.map(m => ({
    rated_id:      m.user_id,
    communication: 3,
    reliability:   3,
    contribution:  3,
    comment:       '',
  }))
)

const submitting = ref(false)
const errors = ref<string[]>([])

function submit() {
  submitting.value = true
  errors.value = []

  router.post(
    urls.projects.ratings.store(props.project.id),
    { ratings: form.value },
    {
      onError: (errs) => {
        errors.value = Object.values(errs).flat() as string[]
        submitting.value = false
      },
      onFinish: () => {
        submitting.value = false
      },
    }
  )
}

// ── Endorsements (step 2) ─────────────────────────────────────────────────────
const selectedSkills = reactive<Record<number, string[]>>({})
const savingEndorsements = ref(false)

function isSkillSelected(userId: number, skill: string): boolean {
  return (selectedSkills[userId] ?? []).includes(skill)
}

function toggleSkill(userId: number, skill: string) {
  if (!selectedSkills[userId]) selectedSkills[userId] = []
  const idx = selectedSkills[userId].indexOf(skill)
  if (idx === -1) {
    if (selectedSkills[userId].length < 5) selectedSkills[userId].push(skill)
  } else {
    selectedSkills[userId].splice(idx, 1)
  }
}

function addCustomSkill(userId: number, event: Event) {
  const input = event.target as HTMLInputElement
  const skill = input.value.trim()
  if (!skill) return
  if (!selectedSkills[userId]) selectedSkills[userId] = []
  if (!selectedSkills[userId].includes(skill) && selectedSkills[userId].length < 5) {
    selectedSkills[userId].push(skill)
  }
  input.value = ''
}

function removeCustomSkill(userId: number, skill: string) {
  if (!selectedSkills[userId]) return
  const idx = selectedSkills[userId].indexOf(skill)
  if (idx !== -1) selectedSkills[userId].splice(idx, 1)
}

function saveEndorsements() {
  const batch = props.membersToEndorse
    .filter(m => (selectedSkills[m.user_id] ?? []).length > 0)
    .map(m => ({ endorsed_id: m.user_id, skills: selectedSkills[m.user_id] }))

  if (batch.length === 0) {
    router.visit(urls.projects.show(props.project.id))
    return
  }

  savingEndorsements.value = true
  router.post(
    `/projects/${props.project.id}/endorsements`,
    { endorsements: batch },
    { onFinish: () => { savingEndorsements.value = false } }
  )
}
</script>
