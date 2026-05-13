<template>
  <Head title="Ideas" />

  <div class="max-w-7xl mx-auto p-4 md:p-8">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark mb-2">
        Browse project ideas
      </h1>
      <p class="text-body text-dc-muted">
        Find your next project to build and learn.
      </p>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-4 mb-8 border-b border-dc-surface dark:border-dc-dark-border">
      <button
        @click="activeTab = 'ai'"
        :class="[
          'px-4 py-3 font-medium text-small border-b-2 transition-colors',
          activeTab === 'ai'
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        AI generator
      </button>
      <button
        @click="activeTab = 'community'"
        :class="[
          'px-4 py-3 font-medium text-small border-b-2 transition-colors',
          activeTab === 'community'
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        Community ideas
      </button>
      <button
        @click="activeTab = 'curated'"
        :class="[
          'px-4 py-3 font-medium text-small border-b-2 transition-colors',
          activeTab === 'curated'
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        Curated library
      </button>
    </div>

    <!-- TAB 1: AI Generator -->
    <div v-if="activeTab === 'ai'" class="mb-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Form Column -->
        <Card class="p-6 md:p-8 h-fit bg-gradient-to-br from-dc-primary-tint/5 to-dc-blue-tint/5 dark:from-dc-primary-dark/10 dark:to-dc-blue-dark/10">
          <div class="mb-6">
            <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-2">
              Generate an Idea with AI
            </h2>
            <p class="text-body text-dc-muted">
              Tell Claude about your interests and let AI generate a personalized project idea tailored to your skills and available time.
            </p>
          </div>

          <form @submit.prevent="generateIdea" class="space-y-4">
            <div>
              <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                What domain interests you?
              </label>
              <TextInput
                v-model="generateForm.domain_interest"
                placeholder="e.g. fintech, social, education, AI"
                :disabled="isGenerating"
              />
            </div>

            <div>
              <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                Preferred team size
              </label>
              <Select
                v-model.number="generateForm.team_size"
                :options="[
                  { value: 1, label: '1 person (solo)' },
                  { value: 2, label: '2 people' },
                  { value: 3, label: '3 people' },
                  { value: 4, label: '4 people' },
                  { value: 5, label: '5 people' },
                  { value: 6, label: '6+ people' }
                ]"
                :disabled="isGenerating"
              />
            </div>

            <div>
              <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                Hours available per week
              </label>
              <Select
                v-model.number="generateForm.weekly_hours"
                :options="[
                  { value: 3, label: '~3 hours' },
                  { value: 5, label: '~5 hours' },
                  { value: 10, label: '~10 hours' },
                  { value: 15, label: '~15 hours' },
                  { value: 20, label: '20+ hours' }
                ]"
                :disabled="isGenerating"
              />
            </div>

            <div>
              <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                Any extra interests? (optional)
              </label>
              <TextInput
                v-model="generateForm.interests"
                placeholder="e.g. real-time features, mobile app, APIs"
                :disabled="isGenerating"
              />
            </div>

            <Button
              type="submit"
              class="w-full h-12 text-h3 font-bold"
              :disabled="isGenerating"
            >
              {{ isGenerating ? 'AI is crafting your idea...' : 'Generate idea' }}
            </Button>

            <div v-if="generateError" class="text-small text-dc-danger bg-dc-danger-tint/10 p-3 rounded">
              {{ generateError }}
            </div>
          </form>
        </Card>

        <!-- Result Column -->
        <div v-if="generatedIdea" class="space-y-4">
          <Card class="p-6 md:p-8">
            <div class="mb-4 flex items-center gap-2">
              <span class="text-xs px-2 py-1 rounded bg-dc-blue-tint text-dc-blue font-medium">
                AI Generated
              </span>
            </div>
            <h3 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-3">
              {{ generatedIdea.title }}
            </h3>
            <p class="text-body text-dc-body dark:text-dc-primary-muted mb-6 whitespace-pre-wrap">
              {{ generatedIdea.description }}
            </p>

            <!-- Difficulty & Domain -->
            <div class="flex gap-2 mb-6">
              <span
                :class="[
                  'text-xs px-2 py-1 rounded font-medium',
                  generatedIdea.difficulty === 'beginner'
                    ? 'bg-dc-success-tint text-dc-success'
                    : generatedIdea.difficulty === 'intermediate'
                    ? 'bg-dc-warning-tint text-dc-warning'
                    : 'bg-dc-danger-tint text-dc-danger'
                ]"
              >
                {{ generatedIdea.difficulty }}
              </span>
              <span
                v-if="generatedIdea.domain"
                class="text-xs px-2 py-1 rounded bg-dc-surface dark:bg-dc-dark-border text-dc-body dark:text-dc-primary-muted font-medium"
              >
                {{ generatedIdea.domain }}
              </span>
              <span v-if="generatedIdea.team_size" class="text-xs px-2 py-1 rounded bg-dc-primary-tint text-dc-primary font-medium">
                Team of {{ generatedIdea.team_size }}
              </span>
            </div>

            <!-- Suggested Roles -->
            <div v-if="generatedIdea.suggested_roles?.length" class="mb-6">
              <h4 class="text-small font-medium text-dc-body dark:text-dc-primary-muted mb-2">
                Suggested roles
              </h4>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="role in generatedIdea.suggested_roles"
                  :key="role"
                  class="px-3 py-1 rounded-full bg-dc-primary-tint text-dc-primary dark:bg-dc-primary-dark/20 dark:text-dc-primary-muted text-xs font-medium"
                >
                  {{ role }}
                </span>
              </div>
            </div>

            <!-- Requirements -->
            <div v-if="generatedIdea.requirements?.length" class="mb-6">
              <h4 class="text-small font-medium text-dc-body dark:text-dc-primary-muted mb-2">
                Requirements
              </h4>
              <ul class="space-y-1">
                <li
                  v-for="req in generatedIdea.requirements"
                  :key="req"
                  class="flex items-start gap-2 text-small text-dc-body dark:text-dc-primary-muted"
                >
                  <span class="text-dc-muted">✓</span>
                  <span>{{ req }}</span>
                </li>
              </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
              <Button
                @click="useGeneratedIdea"
                class="flex-1"
              >
                Use this idea
              </Button>
              <Button
                @click="resetGeneration"
                variant="ghost"
                class="flex-1"
              >
                Generate another
              </Button>
            </div>
          </Card>
        </div>
        <div v-else class="flex items-center justify-center">
          <div class="text-center text-dc-muted">
            <p class="text-body">Generate an idea to see the result here</p>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: Community Ideas -->
    <div v-if="activeTab === 'community'" class="mb-8">
      <!-- Submit Button (Sticky) -->
      <div class="sticky top-0 z-10 mb-6 flex justify-end">
        <Button
          @click="showSubmitModal = true"
          class="whitespace-nowrap"
        >
          + Submit an idea
        </Button>
      </div>

      <!-- Community Ideas Grid -->
      <div v-if="communityIdeas.length === 0" class="bg-white dark:bg-dc-dark-surface rounded-lg p-12 text-center border border-dc-surface dark:border-dc-dark-border">
        <div class="text-h2 text-dc-muted mb-2">
          No community ideas yet
        </div>
        <p class="text-body text-dc-muted">
          Be the first to submit an idea!
        </p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="idea in communityIdeas"
          :key="idea.id"
          class="flex flex-col"
        >
          <Link
            :href="`/ideas/${idea.id}`"
            class="block flex-grow"
          >
            <Card class="h-full hover:shadow-lg transition-shadow cursor-pointer">
              <div class="flex flex-col h-full">
                <div class="flex flex-wrap gap-2 mb-3">
                  <span class="text-xs px-2 py-1 rounded bg-dc-coral-tint text-dc-coral font-medium">
                    Community
                  </span>
                  <span
                    v-if="idea.status === 'converted'"
                    class="text-xs px-2 py-1 rounded bg-dc-success-tint text-dc-success font-medium"
                  >
                    Converted
                  </span>
                  <span
                    :class="[
                      'text-xs px-2 py-1 rounded font-medium',
                      idea.difficulty === 'beginner'
                        ? 'bg-dc-success-tint text-dc-success'
                        : idea.difficulty === 'intermediate'
                        ? 'bg-dc-warning-tint text-dc-warning'
                        : 'bg-dc-danger-tint text-dc-danger'
                    ]"
                  >
                    {{ idea.difficulty }}
                  </span>
                </div>

                <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-2 line-clamp-2">
                  {{ idea.title }}
                </h3>

                <p class="text-body text-dc-body dark:text-dc-primary-muted mb-4 flex-grow line-clamp-3">
                  {{ truncateText(idea.description, 120) }}
                </p>

                <div class="flex items-center justify-between pt-4 border-t border-dc-surface dark:border-dc-dark-border text-small text-dc-muted">
                  <div v-if="idea.team_size" class="flex items-center gap-1">
                    <span class="text-dc-muted" aria-hidden="true">○</span>
                    Team of {{ idea.team_size }}
                  </div>
                  <div class="flex items-center gap-2">
                    <span v-if="idea.upvotes" class="text-dc-muted">▲ {{ idea.upvotes }}</span>
                    <span v-if="idea.comments_count" class="text-dc-muted">□ {{ idea.comments_count }}</span>
                  </div>
                </div>
              </div>
            </Card>
          </Link>
          <div class="mt-2 space-y-2">
            <Link
              v-if="idea.status !== 'converted'"
              :href="urls.projects.create(idea.id)"
              @click.stop
              class="block w-full px-4 py-2 rounded bg-dc-primary-tint text-dc-primary hover:bg-dc-primary hover:text-white text-center text-small font-medium transition-colors"
            >
              Use this idea
            </Link>
            <div v-else class="block w-full px-4 py-2 rounded bg-dc-success-tint text-dc-success text-center text-small font-medium">
              Converted
            </div>

            <Button
              @click.prevent="toggleVote(idea)"
              :class="[
                'w-full text-small',
                userVotedIds.includes(idea.id)
                  ? 'bg-dc-primary text-white'
                  : 'bg-dc-surface dark:bg-dc-dark-border text-dc-body hover:bg-dc-primary-tint'
              ]"
            >
              {{ userVotedIds.includes(idea.id) ? '▲ Upvoted' : '▲ Upvote' }}
            </Button>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 3: Curated Library -->
    <div v-if="activeTab === 'curated'" class="mb-8">
      <!-- Filters -->
      <div class="bg-white dark:bg-dc-dark-surface rounded-lg p-6 mb-8 border border-dc-surface dark:border-dc-dark-border">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Difficulty
            </label>
            <Select
              :modelValue="curatedFilters.difficulty"
              @update:modelValue="updateCuratedFilter('difficulty', $event)"
              :options="[
                { value: '', label: 'All levels' },
                { value: 'beginner', label: 'Beginner' },
                { value: 'intermediate', label: 'Intermediate' },
                { value: 'advanced', label: 'Advanced' }
              ]"
            />
          </div>

          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Domain
            </label>
            <TextInput
              :modelValue="curatedFilters.domain"
              @update:modelValue="updateCuratedFilter('domain', $event)"
              placeholder="e.g. fintech, social"
            />
          </div>
        </div>
      </div>

      <!-- Curated Ideas Grid -->
      <div v-if="curatedIdeas.length === 0" class="bg-white dark:bg-dc-dark-surface rounded-lg p-12 text-center border border-dc-surface dark:border-dc-dark-border">
        <div class="text-h2 text-dc-muted mb-2">
          No curated ideas found
        </div>
        <p class="text-body text-dc-muted">
          Try adjusting your filters.
        </p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          v-for="idea in curatedIdeas"
          :key="idea.id"
          class="flex flex-col"
        >
          <Link
            :href="`/ideas/${idea.id}`"
            class="block flex-grow"
          >
            <Card class="h-full hover:shadow-lg transition-shadow cursor-pointer">
              <div class="flex flex-col h-full">
                <div class="flex flex-wrap gap-2 mb-3">
                  <span class="text-xs px-2 py-1 rounded bg-dc-primary-tint text-dc-primary font-medium">
                    Curated
                  </span>
                  <span
                    v-if="idea.status === 'featured'"
                    class="text-xs px-2 py-1 rounded bg-dc-coral-tint text-dc-coral font-medium"
                  >
                    Featured
                  </span>
                  <span
                    v-if="idea.status === 'converted'"
                    class="text-xs px-2 py-1 rounded bg-dc-success-tint text-dc-success font-medium"
                  >
                    Converted
                  </span>
                  <span
                    :class="[
                      'text-xs px-2 py-1 rounded font-medium',
                      idea.difficulty === 'beginner'
                        ? 'bg-dc-success-tint text-dc-success'
                        : idea.difficulty === 'intermediate'
                        ? 'bg-dc-warning-tint text-dc-warning'
                        : 'bg-dc-danger-tint text-dc-danger'
                    ]"
                  >
                    {{ idea.difficulty }}
                  </span>
                </div>

                <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-2 line-clamp-2">
                  {{ idea.title }}
                </h3>

                <p class="text-body text-dc-body dark:text-dc-primary-muted mb-4 flex-grow line-clamp-3">
                  {{ truncateText(idea.description, 120) }}
                </p>

                <div class="flex items-center justify-between pt-4 border-t border-dc-surface dark:border-dc-dark-border text-small text-dc-muted">
                  <div v-if="idea.team_size" class="flex items-center gap-1">
                    <span class="text-dc-muted" aria-hidden="true">○</span>
                    Team of {{ idea.team_size }}
                  </div>
                </div>
              </div>
            </Card>
          </Link>
          <div class="mt-2">
            <Link
              v-if="idea.status !== 'converted'"
              :href="urls.projects.create(idea.id)"
              @click.stop
              class="block w-full px-4 py-2 rounded bg-dc-primary-tint text-dc-primary hover:bg-dc-primary hover:text-white text-center text-small font-medium transition-colors"
            >
              Use this idea
            </Link>
            <div v-else class="block w-full px-4 py-2 rounded bg-dc-success-tint text-dc-success text-center text-small font-medium">
              Converted
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Submit Idea Modal -->
  <div v-if="showSubmitModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <Card class="w-full max-w-md max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted">
          Submit an idea
        </h2>
        <button
          @click="showSubmitModal = false"
          class="text-dc-muted hover:text-dc-body"
        >
          ✕
        </button>
      </div>

      <form @submit.prevent="submitIdea" class="space-y-4">
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Title *
          </label>
          <TextInput
            v-model="submitForm.title"
            placeholder="e.g. Build a weather app with Vue"
            @keydown.enter.prevent
          />
          <span v-if="submitForm.errors.title" class="text-xs text-dc-danger">
            {{ submitForm.errors.title }}
          </span>
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Description *
          </label>
          <Textarea
            v-model="submitForm.description"
            placeholder="Describe your project idea in detail..."
            :rows="4"
          />
          <span v-if="submitForm.errors.description" class="text-xs text-dc-danger">
            {{ submitForm.errors.description }}
          </span>
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Domain (optional)
          </label>
          <TextInput
            v-model="submitForm.domain"
            placeholder="e.g. fintech, social, education"
          />
          <span v-if="submitForm.errors.domain" class="text-xs text-dc-danger">
            {{ submitForm.errors.domain }}
          </span>
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Difficulty *
          </label>
          <Select
            v-model="submitForm.difficulty"
            :options="[
              { value: 'beginner', label: 'Beginner' },
              { value: 'intermediate', label: 'Intermediate' },
              { value: 'advanced', label: 'Advanced' }
            ]"
          />
          <span v-if="submitForm.errors.difficulty" class="text-xs text-dc-danger">
            {{ submitForm.errors.difficulty }}
          </span>
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Team size (optional)
          </label>
          <TextInput
            v-model="teamSizeInput"
            type="number"
            min="1"
            max="20"
            placeholder="e.g. 3"
          />
          <span v-if="submitForm.errors.team_size" class="text-xs text-dc-danger">
            {{ submitForm.errors.team_size }}
          </span>
        </div>

        <div class="flex gap-2 pt-4">
          <Button
            @click="showSubmitModal = false"
            type="button"
            class="flex-1 bg-dc-surface dark:bg-dc-dark-border text-dc-body"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            class="flex-1"
            :disabled="submitForm.processing"
          >
            {{ submitForm.processing ? 'Submitting...' : 'Submit idea' }}
          </Button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Select from '@/Components/Select.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

interface Idea {
  id: number
  title: string
  description: string | null
  domain: string | null
  difficulty: string
  team_size: number | null
  source: string
  status: string
  upvotes: number
  comments_count: number
  suggested_roles?: string[] | null
  requirements?: string[] | null
  submitter?: { id: number; name: string; avatar_url: string | null } | null
}

interface PaginatedIdeas {
  data: Idea[]
  links: any[]
  current_page: number
  last_page: number
  path: string
}

const props = defineProps<{
  ideas: PaginatedIdeas
  filters: { source?: string; difficulty?: string; domain?: string }
  userVotedIds: number[]
}>()

const activeTab = ref<'ai' | 'community' | 'curated'>('ai')
const showSubmitModal = ref(false)
const generatedIdea = ref<Idea | null>(null)
const isGenerating = ref(false)

const generateError = ref('')

const curatedFilters = ref({
  difficulty: props.filters.difficulty || '',
  domain: props.filters.domain || '',
})

const submitForm = useForm({
  title: '',
  description: '',
  domain: '',
  difficulty: 'beginner',
  team_size: null as number | null,
  suggested_roles: [],
  requirements: [],
})

const teamSizeInput = computed({
  get: () => submitForm.team_size?.toString() ?? '',
  set: (value: string) => {
    submitForm.team_size = value === '' ? null : Number(value)
  },
})

const generateForm = useForm({
  domain_interest: '',
  team_size: 3,
  weekly_hours: 5,
  interests: '',
})

const communityIdeas = computed(() => {
  return props.ideas.data.filter(idea => idea.source === 'community')
})

const curatedIdeas = computed(() => {
  return props.ideas.data.filter(idea => {
    if (idea.source !== 'curated') return false
    if (curatedFilters.value.difficulty && idea.difficulty !== curatedFilters.value.difficulty) return false
    if (curatedFilters.value.domain && !(idea.domain ?? '').toLowerCase().includes(curatedFilters.value.domain.toLowerCase())) return false
    return true
  })
})

function truncateText(value: string | null, length: number): string {
  const text = value ?? ''
  return text.length > length ? `${text.substring(0, length)}...` : text
}

function updateCuratedFilter(key: string, value: string) {
  curatedFilters.value[key as keyof typeof curatedFilters.value] = value
}

function submitIdea() {
  submitForm.post(urls.ideas.store(), {
    onSuccess: () => {
      submitForm.reset()
      showSubmitModal.value = false
    },
  })
}

function toggleVote(idea: Idea) {
  router.post(urls.ideas.vote(idea.id), {}, { preserveScroll: true })
}

async function generateIdea() {
  isGenerating.value = true
  generateError.value = ''

  await new Promise(resolve => setTimeout(resolve, 3000))

  generatedIdea.value = buildMockIdea(
    generateForm.domain_interest,
    generateForm.weekly_hours,
    generateForm.team_size,
  )
  isGenerating.value = false
}

function buildMockIdea(domainInput: string, weeklyHours: number, teamSize: number): Idea {
  const domain = domainInput.trim() || 'technology'
  const difficulty = weeklyHours >= 15 ? 'advanced' : weeklyHours >= 8 ? 'intermediate' : 'beginner'

  type IdeaTemplate = { title: string; description: string; domain: string; suggested_roles: string[]; requirements: string[] }
  const pool: Record<string, IdeaTemplate> = {
    fintech: {
      title: 'LebPay — Peer-to-Peer Expense Tracker for Lebanese Students',
      description: 'Problem: Lebanese students face difficulty managing shared expenses due to currency volatility and limited banking access.\n\nWhat to build: A web app that lets students split bills, track IOUs, and settle debts in LBP and USD. Features include expense groups, payment history, and visualized balance summaries.\n\nWhy it matters: Financial transparency is critical in Lebanon\'s current economic climate — this tool addresses a real daily need while teaching payment-system fundamentals.\n\nExpected outcomes: A production-ready app with multi-currency support, real-time sync across devices, and shareable expense reports.',
      domain: 'Fintech',
      suggested_roles: ['Full-Stack Developer', 'UI/UX Designer', 'Backend Developer', 'QA Engineer'],
      requirements: ['REST API design with Laravel', 'JWT authentication', 'Multi-currency conversion logic', 'Data visualisation (Chart.js)'],
    },
    social: {
      title: 'DevMeet LB — Tech Event Aggregator for Lebanese Developers',
      description: 'Problem: Lebanese developers miss local meetups and hackathons because event info is scattered across WhatsApp groups and social media.\n\nWhat to build: A curated event platform where organizers post tech events and developers can RSVP, get reminders, and connect with other attendees beforehand.\n\nWhy it matters: Building community infrastructure strengthens Lebanon\'s tech ecosystem and helps developers grow their professional networks.\n\nExpected outcomes: A responsive web app with event creation, RSVP management, email notifications, and an attendee community feed.',
      domain: 'Social',
      suggested_roles: ['Frontend Developer', 'Backend Developer', 'Product Manager', 'Designer'],
      requirements: ['User authentication & profiles', 'Email notification system', 'Calendar integration (iCal export)', 'Event search & filtering'],
    },
    education: {
      title: 'CodePath LB — Structured Learning Roadmap Platform for CS Students',
      description: 'Problem: Lebanese CS students lack structured guidance on what to learn and in what order, leading to skill gaps and slow career progression.\n\nWhat to build: A platform where students follow curated learning roadmaps (frontend, backend, AI, etc.), track progress, and share achievements with peers and employers.\n\nWhy it matters: Structured learning paths dramatically improve skill acquisition speed and give students a clear direction in their technical journey.\n\nExpected outcomes: A full-featured roadmap app with progress tracking, milestone badges, resource links, and a public portfolio showcase.',
      domain: 'Education',
      suggested_roles: ['Frontend Developer', 'Backend Developer', 'Content Curator', 'Designer'],
      requirements: ['Progress tracking system', 'User profiles & badges', 'Curated resource management', 'Search and filtering'],
    },
    ai: {
      title: 'Arabic Study Assistant — AI-Powered Tutor for Lebanese CS Students',
      description: 'Problem: Arabic-speaking CS students struggle to find AI tools that support Arabic content, since most LLMs are English-centric.\n\nWhat to build: A study assistant that accepts Arabic text input, summarises lecture notes, generates quiz questions, and explains concepts in Arabic using a multilingual LLM API.\n\nWhy it matters: Localising AI education tools empowers the Arab developer community and makes quality CS education more accessible across the region.\n\nExpected outcomes: A working web app integrating a multilingual AI API with full Arabic (RTL) UI, note summarisation, and auto-generated quizzes.',
      domain: 'AI / EdTech',
      suggested_roles: ['ML Engineer', 'Frontend Developer', 'NLP Researcher', 'UI Designer'],
      requirements: ['LLM API integration (Anthropic / OpenAI)', 'Arabic RTL layout support', 'Quiz generation logic', 'Secure server-side API key management'],
    },
    mobile: {
      title: 'CampusNav LB — Indoor Navigation App for Lebanese University Campuses',
      description: 'Problem: New students at large Lebanese universities like AUB or LU waste significant time navigating unfamiliar buildings to find classrooms, offices, and labs.\n\nWhat to build: A mobile-friendly web app with interactive indoor maps, room search, and step-by-step directions generated from a graph-based pathfinding algorithm.\n\nWhy it matters: A well-designed navigation tool reduces daily friction for hundreds of students and demonstrates real applied algorithms work.\n\nExpected outcomes: A PWA with campus map rendering, room database, search, and shortest-path navigation — deployable without an app store.',
      domain: 'Mobile / Maps',
      suggested_roles: ['Frontend Developer', 'Backend Developer', 'UI/UX Designer'],
      requirements: ['SVG or canvas-based map rendering', 'Graph-based pathfinding (Dijkstra / A*)', 'Room & schedule database', 'PWA offline support'],
    },
  }

  const domainLower = domain.toLowerCase()
  const matched = Object.entries(pool).find(([key]) => domainLower.includes(key))
  let base: IdeaTemplate

  if (matched) {
    base = matched[1]
  } else {
    base = {
      title: `${domain.charAt(0).toUpperCase() + domain.slice(1)} Collaboration Hub for Lebanese Developers`,
      description: `Problem: Lebanese CS students working in the ${domain} space lack a dedicated space to find collaborators with the right skills and availability.\n\nWhat to build: A project-matching platform tailored to the ${domain} domain, where developers post project ideas, define roles needed, and recruit teammates with verified skill profiles.\n\nWhy it matters: Collaboration on real projects is the fastest path to career-ready skills, and targeted matching makes it far more likely teams will succeed together.\n\nExpected outcomes: A working matching platform with skill-based search, project boards, team chat, and milestone progress tracking.`,
      domain: domain.charAt(0).toUpperCase() + domain.slice(1),
      suggested_roles: ['Full-Stack Developer', 'UI/UX Designer', 'Project Manager', 'Backend Developer'],
      requirements: ['User authentication & profiles', 'Skill-based matching algorithm', 'Real-time team messaging', 'Project status tracking'],
    }
  }

  return {
    id: Date.now(),
    title: base.title,
    description: base.description,
    domain: base.domain,
    difficulty,
    team_size: teamSize,
    source: 'ai',
    status: 'active',
    upvotes: 0,
    comments_count: 0,
    suggested_roles: base.suggested_roles.slice(0, Math.max(2, teamSize)),
    requirements: base.requirements,
  }
}

function useGeneratedIdea() {
  if (generatedIdea.value) {
    router.visit(urls.projects.create(generatedIdea.value.id))
  }
}

function resetGeneration() {
  generatedIdea.value = null
  generateForm.reset()
}
</script>
