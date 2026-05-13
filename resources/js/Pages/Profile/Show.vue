<template>
  <Head :title="user.name" />

  <div class="max-w-4xl mx-auto p-4 md:p-8">
    <!-- Profile Header -->
    <div class="bg-white dark:bg-dc-dark-surface rounded-xl border border-dc-surface dark:border-dc-dark-border p-6 mb-6">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-center gap-4">
          <img
            v-if="user.avatar_url"
            :src="user.avatar_url"
            :alt="user.name"
            class="w-16 h-16 rounded-full object-cover"
          />
          <div
            v-else
            class="w-16 h-16 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold text-2xl"
          >
            {{ user.name?.charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <h1 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted">{{ user.name }}</h1>
              <CheckCircle v-if="user.is_verified" class="w-5 h-5 text-blue-500" />
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <Badge variant="skill">{{ user.role }}</Badge>
              <Badge v-if="user.is_verified" variant="open">Verified Contributor</Badge>
            </div>
            <a
              v-if="user.github_username"
              :href="`https://github.com/${user.github_username}`"
              target="_blank"
              rel="noreferrer"
              class="text-small text-dc-muted hover:text-dc-primary mt-1 inline-flex items-center gap-1"
            >
              @{{ user.github_username }}
              <ExternalLink class="w-3 h-3" />
            </a>
          </div>
        </div>

        <div class="flex items-center gap-6">
          <!-- Reputation Score -->
          <div class="text-center">
            <div class="text-3xl font-bold text-dc-primary">{{ user.reputation_score ?? 0 }}</div>
            <div class="text-xs text-dc-muted uppercase tracking-wider font-medium">Reputation</div>
          </div>
          <Link v-if="isOwner" href="/settings">
            <Button variant="ghost" size="sm">Edit Profile</Button>
          </Link>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-dc-surface dark:border-dc-dark-border mb-6 overflow-x-auto">
      <button
        v-for="tab in visibleTabs"
        :key="tab"
        @click="activeTab = tab"
        :class="[
          'px-4 py-3 font-medium text-small border-b-2 transition-colors whitespace-nowrap',
          activeTab === tab
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        {{ tab }}
      </button>
    </div>

    <!-- TAB: Overview -->
    <div v-if="activeTab === 'Overview'" class="space-y-6">
      <!-- Bio -->
      <div v-if="user.bio">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-2">About</h3>
        <p class="text-body text-dc-body dark:text-dc-muted whitespace-pre-wrap">{{ user.bio }}</p>
      </div>

      <!-- Skills -->
      <div v-if="user.skills?.length">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-3">Skills</h3>
        <div class="flex flex-wrap gap-2">
          <SkillTag v-for="s in user.skills" :key="s.id" :label="s.skill_name" />
        </div>
      </div>

      <!-- Working Style -->
      <div v-if="user.working_style">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-3">Working Style</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-dc-surface dark:bg-dc-dark-border rounded-lg p-4">
            <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-1">Communication</div>
            <div class="text-body text-dc-body dark:text-dc-primary-muted capitalize">
              {{ user.working_style.communication_pref ?? '—' }}
            </div>
          </div>
          <div class="bg-dc-surface dark:bg-dc-dark-border rounded-lg p-4">
            <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-1">Feedback style</div>
            <div class="text-body text-dc-body dark:text-dc-primary-muted capitalize">
              {{ user.working_style.feedback_style ?? '—' }}
            </div>
          </div>
          <div class="bg-dc-surface dark:bg-dc-dark-border rounded-lg p-4">
            <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-1">Hours / week</div>
            <div class="text-body text-dc-body dark:text-dc-primary-muted">
              {{ user.working_style.weekly_hours ?? '—' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Skill Endorsements -->
      <div v-if="endorsements.length > 0">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-3">Skill Endorsements</h3>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="e in endorsements"
            :key="e.skill"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-dc-primary-tint border border-dc-primary/20"
          >
            <span class="text-small font-medium text-dc-primary-dark dark:text-dc-primary-muted">{{ e.skill }}</span>
            <span class="text-xs font-semibold text-dc-primary bg-white/70 dark:bg-dc-dark-surface/70 rounded-full px-1.5 py-0.5">
              {{ e.count }}
            </span>
          </div>
        </div>
      </div>

      <!-- Contribution DNA -->
      <div>
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-3">Contribution DNA</h3>
        <div v-if="user.contribution_dna" class="bg-dc-surface dark:bg-dc-dark-border rounded-lg p-4">
          <div class="flex items-center gap-3 mb-3">
            <Badge variant="open" class="text-base px-3 py-1">
              {{ dnaLabel }}
            </Badge>
          </div>
          <div class="text-small text-dc-body dark:text-dc-primary-muted mb-2">
            <span v-if="dnaStats.tasks_done != null">{{ dnaStats.tasks_done }} tasks done</span>
            <span v-if="dnaStats.tasks_done != null && dnaStats.prs_merged != null"> · </span>
            <span v-if="dnaStats.prs_merged != null">{{ dnaStats.prs_merged }} PRs merged</span>
            <span v-if="dnaStats.prs_merged != null && dnaStats.projects_completed != null"> · </span>
            <span v-if="dnaStats.projects_completed != null">{{ dnaStats.projects_completed }} projects</span>
          </div>
          <div class="text-xs text-dc-muted">
            Updated {{ formatDate(user.contribution_dna.updated_at) }}
          </div>
        </div>
        <div v-else class="text-small text-dc-muted">
          Complete a project to see your DNA
        </div>
      </div>
    </div>

    <!-- TAB: Projects -->
    <div v-if="activeTab === 'Projects'" class="space-y-4">
      <template v-if="completedMemberships.length">
        <Card
          v-for="m in completedMemberships"
          :key="m.id"
          class="p-4"
        >
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
            <div>
              <h4 class="text-body font-medium text-dc-body dark:text-dc-primary-muted mb-1">
                {{ m.project.title }}
              </h4>
              <div class="text-small text-dc-muted mb-2">Role: {{ m.role }}</div>
              <div v-if="m.project.tech_stack?.length" class="flex flex-wrap gap-1">
                <SkillTag v-for="tech in m.project.tech_stack" :key="tech" :label="tech" />
              </div>
            </div>
            <div class="text-small text-dc-muted whitespace-nowrap">
              Completed {{ formatDate(m.project.updated_at) }}
            </div>
          </div>
        </Card>
      </template>
      <div v-else class="text-small text-dc-muted">No completed projects yet.</div>
    </div>

    <!-- TAB: Contributions -->
    <div v-if="activeTab === 'Contributions'" class="space-y-3">
      <div
        v-for="log in displayedContributions"
        :key="log.id"
        class="flex items-center justify-between gap-4 bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border rounded-lg p-4"
      >
        <div>
          <div class="text-small font-medium text-dc-body dark:text-dc-primary-muted">
            {{ log.issue?.repo_full_name ?? '—' }}
          </div>
          <div class="text-small text-dc-muted">{{ log.issue?.title ?? '—' }}</div>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
          <Badge variant="open">Merged</Badge>
          <a
            v-if="log.pr_url"
            :href="log.pr_url"
            target="_blank"
            rel="noreferrer"
            class="text-small text-dc-primary hover:underline inline-flex items-center gap-1"
          >
            PR <ExternalLink class="w-3 h-3" />
          </a>
        </div>
      </div>
    </div>

    <!-- TAB: Suggestions (owner only) -->
    <div v-if="activeTab === 'Suggestions'" class="space-y-4">
      <p class="text-small text-dc-muted italic">Only visible to you</p>
      <Card
        v-for="s in displayedSuggestions"
        :key="s.id"
        class="p-4"
      >
        <div class="flex items-center justify-between gap-4 mb-3">
          <Badge variant="skill">{{ s.source_type }}</Badge>
          <div class="flex gap-2">
            <Button size="sm" variant="ghost" @click="copyText(s.cv_text)">CV</Button>
            <Button size="sm" variant="ghost" @click="copyText(s.portfolio_text)">Portfolio</Button>
            <Button size="sm" variant="ghost" @click="copyText(s.linkedin_text)">LinkedIn</Button>
          </div>
        </div>
        <p class="text-small text-dc-muted line-clamp-2">{{ s.cv_text }}</p>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { CheckCircle, ExternalLink } from 'lucide-vue-next'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Badge from '@/Components/Badge.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import SkillTag from '@/Components/SkillTag.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<{
  user: any
  isOwner: boolean
  endorsements: { skill: string; count: number }[]
}>()

const allTabs = ['Overview', 'Projects', 'Contributions', 'Suggestions']
const visibleTabs = computed(() =>
  props.isOwner ? allTabs : allTabs.filter(t => t !== 'Suggestions')
)

const activeTab = ref('Overview')

const completedMemberships = computed(() =>
  (props.user.project_memberships ?? []).filter(
    (m: any) => m.project?.status === 'completed'
  )
)

const dnaLabel = computed(() => props.user.contribution_dna?.label ?? 'Contributor')

const dnaStats = computed(() => {
  const dna = props.user.contribution_dna
  if (!dna) return {}
  return {
    tasks_done: dna.tasks_done ?? null,
    prs_merged: dna.prs_merged ?? null,
    projects_completed: dna.projects_completed ?? null,
  }
})

const formatDate = (d: string | null) => {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
}

const copyText = (text: string | null) => {
  if (text) navigator.clipboard.writeText(text)
}

const MOCK_CONTRIBUTIONS = [
  {
    id: 'm1',
    pr_url: 'https://github.com/laravel/framework/pull/51284',
    issue: { repo_full_name: 'laravel/framework', title: 'Fix null pointer exception in Eloquent model hydration' },
  },
  {
    id: 'm2',
    pr_url: 'https://github.com/vuejs/core/pull/10392',
    issue: { repo_full_name: 'vuejs/core', title: 'Improve TypeScript inference for defineProps with generic components' },
  },
  {
    id: 'm3',
    pr_url: 'https://github.com/inertiajs/inertia/pull/1873',
    issue: { repo_full_name: 'inertiajs/inertia', title: 'Add missing return type annotation for usePage composable' },
  },
]

const MOCK_SUGGESTIONS = [
  {
    id: 'm1',
    source_type: 'project',
    cv_text: 'Led development of a full-stack developer collaboration platform (DevConnect LB) using Laravel 12, Vue 3, and Inertia.js. Implemented real-time WebSocket communication via Laravel Reverb, an AI-powered project idea generator using the Anthropic SDK, and a Kanban task management system — resulting in a portfolio-ready, production-grade application.',
    portfolio_text: 'DevConnect LB — A developer collaboration hub built for Lebanese CS students. Architected and shipped the real-time team chat, AI idea generation feature, and sprint-based Kanban board. Stack: Laravel 12, Vue 3, Inertia.js, Tailwind CSS v4, Laravel Reverb.',
    linkedin_text: "Excited to share that I shipped a major feature at DevConnect LB — an AI-powered project idea generator that personalises suggestions based on a developer's skills, available time, and domain interests. Built with Laravel + Anthropic SDK on the backend, Vue 3 on the frontend. Always happy to connect with other builders!",
  },
]

const displayedContributions = computed(() =>
  props.user.contribution_logs?.length ? props.user.contribution_logs : MOCK_CONTRIBUTIONS
)

const displayedSuggestions = computed(() =>
  props.user.ai_suggestions?.length ? props.user.ai_suggestions : MOCK_SUGGESTIONS
)
</script>
