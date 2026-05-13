<template>
  <Head title="Profile Setup" />
  <div class="relative min-h-screen bg-dc-page-bg dark:bg-dc-dark-bg flex flex-col items-center py-12 p-4 overflow-hidden">
    <DevBackground :opacity="0.20" :count="18" />
    <div class="w-full max-w-[640px]">
      <PageHeader title="Set up your profile" />

      <div class="space-y-8 mt-6">
        <!-- GitHub Connection -->
        <section>
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            GitHub connection
          </h3>
          <Card v-if="user?.github_username" class="bg-dc-primary-tint dark:bg-dc-primary-dark/20 border-dc-primary-light">
            <div class="flex items-center justify-between">
              <div>
                <div class="font-semibold text-dc-primary-dark dark:text-dc-primary-muted">
                  @{{ user.github_username }}
                </div>
                <div class="text-small text-dc-primary mt-1">
                  Connected
                </div>
              </div>
            </div>
          </Card>
          <Card v-else>
            <div class="flex items-center justify-between gap-4">
              <div class="text-body text-dc-body dark:text-dc-primary-muted">
                Connect your GitHub to get better AI matches.
              </div>
              <a href="/auth/github">
                <Button variant="primary">Connect GitHub</Button>
              </a>
            </div>
          </Card>
        </section>

        <hr class="border-dc-surface dark:border-dc-dark-border" />

        <!-- Basic Info -->
        <section>
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            Basic info
          </h3>
          <div class="space-y-4">
            <TextInput
              v-model="form.name"
              label="Display name"
              placeholder="Your name"
              :error="form.errors.name"
            />
            <Textarea
              v-model="form.bio"
              label="Bio"
              :maxLength="280"
              placeholder="Tell us about yourself..."
              :error="form.errors.bio"
            />
          </div>
        </section>

        <hr class="border-dc-surface dark:border-dc-dark-border" />

        <!-- Your Role -->
        <section>
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            Your role
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <div
              v-for="option in roleOptions"
              :key="option.value"
              @click="form.role = option.value"
              :class="[
                'p-4 rounded-lg border cursor-pointer transition-colors text-center',
                form.role === option.value
                  ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20 text-dc-primary-dark dark:text-dc-primary-muted font-semibold'
                  : 'border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface text-dc-body dark:text-dc-primary-muted hover:border-dc-primary-light'
              ]"
            >
              {{ option.label }}
            </div>
          </div>
          <div v-if="form.errors.role" class="text-small text-dc-danger mt-2">
            {{ form.errors.role }}
          </div>
        </section>

        <hr class="border-dc-surface dark:border-dc-dark-border" />

        <!-- Tech Stack -->
        <section>
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            Your tech stack
          </h3>
          <div class="flex gap-2 mb-4 flex-wrap">
            <SkillTag
              v-for="(tech, idx) in form.skills"
              :key="tech"
              @click="removeSkill(idx)"
              class="cursor-pointer"
            >
              {{ tech }} ✕
            </SkillTag>
          </div>
          <TextInput
            v-model="skillInput"
            placeholder="Add a skill (press Enter)"
            @keydown.enter.prevent="addSkill"
          />
          <div class="text-small text-dc-muted mt-1">
            Click a skill to remove. Examples: Vue, Laravel, Figma, MySQL
          </div>
        </section>

        <hr class="border-dc-surface dark:border-dc-dark-border" />

        <!-- Working Style -->
        <section>
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-1">
            How you like to work
          </h3>
          <p class="text-small text-dc-muted mb-6">
            These help us match you with teammates whose style fits yours.
          </p>

          <div class="space-y-6">
            <!-- Communication -->
            <div>
              <label class="text-body font-medium text-dc-body dark:text-dc-primary-muted flex items-center gap-2 mb-3">
                How do you prefer to communicate?
                <InfoTooltip
                  text="Async = written messages, on your own time (chat, email). Sync = live calls and real-time conversations. Mixed = comfortable with both, depending on the situation."
                  position="right"
                />
              </label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="opt in [
                    { value: 'async', label: 'Async', desc: 'Messages, no rush' },
                    { value: 'sync', label: 'Sync', desc: 'Calls and live chat' },
                    { value: 'mixed', label: 'Mixed', desc: 'A bit of both' }
                  ]"
                  :key="opt.value"
                  type="button"
                  @click="form.communication_pref = opt.value"
                  :class="[
                    'p-3 rounded-lg border text-left transition-colors',
                    form.communication_pref === opt.value
                      ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20'
                      : 'border-dc-surface dark:border-dc-dark-border hover:border-dc-primary-light'
                  ]"
                >
                  <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
                    {{ opt.label }}
                  </div>
                  <div class="text-small text-dc-muted mt-0.5">
                    {{ opt.desc }}
                  </div>
                </button>
              </div>
            </div>

            <!-- Feedback -->
            <div>
              <label class="text-body font-medium text-dc-body dark:text-dc-primary-muted flex items-center gap-2 mb-3">
                How do you like to receive feedback?
                <InfoTooltip
                  text="Direct = blunt and to the point — tell me what's wrong. Gentle = wrap critique in encouragement. Structured = use a clear format like 'Situation, Behavior, Impact' so feedback is concrete and actionable."
                  position="right"
                />
              </label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="opt in [
                    { value: 'direct', label: 'Direct', desc: 'Tell me straight' },
                    { value: 'gentle', label: 'Gentle', desc: 'Soften it a bit' },
                    { value: 'structured', label: 'Structured', desc: 'Clear examples' }
                  ]"
                  :key="opt.value"
                  type="button"
                  @click="form.feedback_style = opt.value"
                  :class="[
                    'p-3 rounded-lg border text-left transition-colors',
                    form.feedback_style === opt.value
                      ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20'
                      : 'border-dc-surface dark:border-dc-dark-border hover:border-dc-primary-light'
                  ]"
                >
                  <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
                    {{ opt.label }}
                  </div>
                  <div class="text-small text-dc-muted mt-0.5">
                    {{ opt.desc }}
                  </div>
                </button>
              </div>
            </div>

            <!-- Work hours -->
            <div>
              <label class="text-body font-medium text-dc-body dark:text-dc-primary-muted flex items-center gap-2 mb-3">
                When are you usually online?
                <InfoTooltip
                  text="Your typical hours of availability for live collaboration (Beirut time). We use this to suggest realistic call windows when scheduling with teammates."
                  position="right"
                />
              </label>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="text-small text-dc-muted block mb-1">Start</label>
                  <input
                    v-model="form.work_hours_start"
                    type="time"
                    class="w-full p-2 border border-dc-surface dark:border-dc-dark-border rounded text-dc-body dark:text-dc-primary-muted bg-white dark:bg-dc-dark-surface"
                  />
                </div>
                <div>
                  <label class="text-small text-dc-muted block mb-1">End</label>
                  <input
                    v-model="form.work_hours_end"
                    type="time"
                    class="w-full p-2 border border-dc-surface dark:border-dc-dark-border rounded text-dc-body dark:text-dc-primary-muted bg-white dark:bg-dc-dark-surface"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        <div class="mt-8">
          <Button
            variant="primary"
            fullWidth
            :disabled="form.processing"
            @click="saveProfile"
          >
            Save and continue
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import DevBackground from '@/Components/DevBackground.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import SkillTag from '@/Components/SkillTag.vue'
import InfoTooltip from '@/Components/InfoTooltip.vue'

const page = usePage()
const user = page.props.auth.user

const roleOptions = [
  { value: 'dev', label: 'Developer' },
  { value: 'designer', label: 'Designer' },
  { value: 'pm', label: 'Product Manager' },
  { value: 'exploring', label: 'Exploring' },
]

const form = useForm({
  name: user?.name ?? '',
  bio: '',
  role: user?.role ?? 'exploring',
  skills: [] as string[],
  communication_pref: 'mixed',
  feedback_style: 'gentle',
  work_hours_start: '09:00',
  work_hours_end: '17:00',
})

const skillInput = ref('')

function addSkill() {
  const value = skillInput.value.trim()
  if (! value) return
  if (form.skills.includes(value)) {
    skillInput.value = ''
    return
  }
  form.skills.push(value)
  skillInput.value = ''
}

function removeSkill(index: number) {
  form.skills.splice(index, 1)
}

function saveProfile() {
  form.post('/onboarding/profile')
}

onMounted(() => {
  // Populate name from user if available
  if (user?.name && !form.name) {
    form.name = user.name
  }
})
</script>
