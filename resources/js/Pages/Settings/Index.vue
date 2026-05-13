<template>
  <Head title="Settings" />

  <div class="max-w-2xl mx-auto p-4 md:p-8">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark dark:text-white mb-1">Settings</h1>
      <p class="text-body text-dc-muted">Update your profile and working preferences.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-8">

      <!-- GitHub -->
      <Card class="p-6">
        <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">GitHub</h2>
        <div v-if="profile.github_username" class="flex items-center gap-3">
          <span class="text-body text-dc-body dark:text-dc-primary-muted">
            Connected as <strong>@{{ profile.github_username }}</strong>
          </span>
          <Badge variant="open">Connected</Badge>
        </div>
        <div v-else class="flex items-center justify-between gap-4">
          <p class="text-body text-dc-muted">Connect GitHub to unlock AI project matching.</p>
          <a href="/auth/github">
            <Button type="button" variant="ghost" size="sm">Connect GitHub</Button>
          </a>
        </div>
      </Card>

      <!-- Basic Info -->
      <Card class="p-6 space-y-5">
        <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">Basic info</h2>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Display name *</label>
          <TextInput v-model="form.name" placeholder="Your name" />
          <span v-if="form.errors.name" class="text-xs text-dc-danger block mt-1">{{ form.errors.name }}</span>
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Bio</label>
          <Textarea v-model="form.bio" :rows="3" :maxlength="1000" placeholder="Tell others what you're about…" />
          <div class="flex justify-between mt-1">
            <span v-if="form.errors.bio" class="text-xs text-dc-danger">{{ form.errors.bio }}</span>
            <span class="text-xs text-dc-muted ml-auto">{{ form.bio.length }} / 1000</span>
          </div>
        </div>
      </Card>

      <!-- Role -->
      <Card class="p-6">
        <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">Your role</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <button
            v-for="opt in ROLES"
            :key="opt.value"
            type="button"
            @click="form.role = opt.value"
            :class="[
              'p-3 rounded-lg border text-center text-body font-medium transition-colors',
              form.role === opt.value
                ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20 text-dc-primary-dark dark:text-dc-primary-muted'
                : 'border-dc-surface dark:border-dc-dark-border text-dc-muted hover:border-dc-primary',
            ]"
          >
            {{ opt.label }}
          </button>
        </div>
      </Card>

      <!-- Skills -->
      <Card class="p-6">
        <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">Skills</h2>
        <div class="flex flex-wrap gap-2 mb-3">
          <span
            v-for="(skill, i) in form.skills"
            :key="i"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-dc-primary-tint text-dc-primary text-small font-medium"
          >
            {{ skill }}
            <button type="button" @click="removeSkill(i)" class="hover:opacity-70 leading-none">×</button>
          </span>
        </div>
        <TextInput
          v-model="skillInput"
          placeholder="Type a skill and press Enter…"
          @keydown.enter.prevent="addSkill"
        />
        <p class="text-xs text-dc-muted mt-1">Examples: Vue, Laravel, Figma, PostgreSQL</p>
      </Card>

      <!-- Working Style -->
      <Card class="p-6 space-y-6">
        <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">Working style</h2>

        <!-- Communication -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-3">Communication preference</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="opt in COMM_OPTS"
              :key="opt.value"
              type="button"
              @click="form.communication_pref = opt.value"
              :class="styleOptionClass(form.communication_pref === opt.value)"
            >
              <div class="font-medium text-dc-body dark:text-dc-primary-muted">{{ opt.label }}</div>
              <div class="text-xs text-dc-muted mt-0.5">{{ opt.desc }}</div>
            </button>
          </div>
        </div>

        <!-- Feedback -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-3">Feedback style</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="opt in FEEDBACK_OPTS"
              :key="opt.value"
              type="button"
              @click="form.feedback_style = opt.value"
              :class="styleOptionClass(form.feedback_style === opt.value)"
            >
              <div class="font-medium text-dc-body dark:text-dc-primary-muted">{{ opt.label }}</div>
              <div class="text-xs text-dc-muted mt-0.5">{{ opt.desc }}</div>
            </button>
          </div>
        </div>

        <!-- Conflict approach -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-3">How do you handle team disagreements?</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              v-for="opt in CONFLICT_OPTS"
              :key="opt.value"
              type="button"
              @click="form.conflict_approach = opt.value"
              :class="styleOptionClass(form.conflict_approach === opt.value)"
            >
              <div class="font-medium text-dc-body dark:text-dc-primary-muted">{{ opt.label }}</div>
              <div class="text-xs text-dc-muted mt-0.5">{{ opt.desc }}</div>
            </button>
          </div>
        </div>

        <!-- Weekly hours + work hours -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Hours / week available</label>
            <TextInput
              v-model.number="form.weekly_hours"
              type="number"
              min="1"
              max="80"
              placeholder="e.g. 10"
            />
            <span v-if="form.errors.weekly_hours" class="text-xs text-dc-danger block mt-1">{{ form.errors.weekly_hours }}</span>
          </div>
          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Online from</label>
            <input
              v-model="form.work_hours_start"
              type="time"
              class="w-full px-3 py-2 rounded-lg border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface text-dc-body dark:text-dc-primary-muted text-small focus:outline-none focus:ring-2 focus:ring-dc-primary"
            />
          </div>
          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Online until</label>
            <input
              v-model="form.work_hours_end"
              type="time"
              class="w-full px-3 py-2 rounded-lg border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface text-dc-body dark:text-dc-primary-muted text-small focus:outline-none focus:ring-2 focus:ring-dc-primary"
            />
          </div>
        </div>
      </Card>

      <!-- Save -->
      <div class="flex justify-end">
        <Button type="submit" variant="primary" :disabled="form.processing">
          {{ form.processing ? 'Saving…' : 'Save settings' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Badge from '@/Components/Badge.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'

defineOptions({ layout: AuthenticatedLayout })

interface WorkingStyle {
  communication_pref: string | null
  feedback_style: string | null
  conflict_approach: string | null
  weekly_hours: number | null
  work_hours_start: string | null
  work_hours_end: string | null
}

interface Profile {
  name: string
  bio: string
  role: string
  github_username: string | null
  skills: string[]
  working_style: WorkingStyle | null
}

const props = defineProps<{ profile: Profile }>()

const ws = props.profile.working_style

const form = useForm({
  name:               props.profile.name,
  bio:                props.profile.bio,
  role:               props.profile.role,
  skills:             [...props.profile.skills],
  communication_pref: ws?.communication_pref ?? 'mixed',
  feedback_style:     ws?.feedback_style ?? 'gentle',
  conflict_approach:  ws?.conflict_approach ?? 'discuss',
  weekly_hours:       ws?.weekly_hours ?? (null as number | null),
  work_hours_start:   ws?.work_hours_start ?? '09:00',
  work_hours_end:     ws?.work_hours_end ?? '17:00',
})

const skillInput = ref('')

function addSkill() {
  const v = skillInput.value.trim()
  if (!v || form.skills.includes(v)) { skillInput.value = ''; return }
  form.skills.push(v)
  skillInput.value = ''
}

function removeSkill(i: number) {
  form.skills.splice(i, 1)
}

function submit() {
  form.post('/settings')
}

function styleOptionClass(active: boolean): string {
  return [
    'p-3 rounded-lg border text-left text-small transition-colors',
    active
      ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20'
      : 'border-dc-surface dark:border-dc-dark-border hover:border-dc-primary',
  ].join(' ')
}

const ROLES = [
  { value: 'dev',       label: 'Developer' },
  { value: 'designer',  label: 'Designer' },
  { value: 'pm',        label: 'Product Manager' },
  { value: 'mentor',    label: 'Mentor' },
  { value: 'exploring', label: 'Exploring' },
]

const COMM_OPTS = [
  { value: 'async', label: 'Async',  desc: 'Messages, no rush' },
  { value: 'sync',  label: 'Sync',   desc: 'Calls and live chat' },
  { value: 'mixed', label: 'Mixed',  desc: 'A bit of both' },
]

const FEEDBACK_OPTS = [
  { value: 'direct',     label: 'Direct',     desc: 'Tell me straight' },
  { value: 'gentle',     label: 'Gentle',     desc: 'Soften it a bit' },
  { value: 'structured', label: 'Structured', desc: 'Clear examples' },
]

const CONFLICT_OPTS = [
  { value: 'discuss', label: 'Discuss', desc: 'Talk it through' },
  { value: 'vote',    label: 'Vote',    desc: 'Majority rules' },
  { value: 'defer',   label: 'Defer',   desc: 'Escalate to owner' },
]
</script>
