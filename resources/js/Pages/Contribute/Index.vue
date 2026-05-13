<template>
  <Head title="Contribute" />

  <div class="max-w-7xl mx-auto p-4 md:p-8">
    <PageHeader
      title="Contribute to Open Source"
      subtitle="Find real issues that match your skills"
    />

    <!-- Filter bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <!-- Language tabs -->
      <div class="flex flex-wrap gap-2">
        <button
          @click="setLanguage(null)"
          :class="tabClass(! filters.language)"
        >
          All
        </button>
        <button
          v-for="lang in languages"
          :key="lang"
          @click="setLanguage(lang)"
          :class="tabClass(filters.language === lang)"
        >
          {{ capitalize(lang) }}
        </button>
      </div>

      <!-- AI toggle -->
      <div class="flex items-center gap-3">
        <Toggle
          :modelValue="aiRanked"
          @update:modelValue="toggleAiRank"
          label="AI-ranked for me"
        />
        <div v-if="aiRanked" class="flex items-center gap-1.5">
          <AIBadge />
          <span class="text-small text-dc-muted">Ranked by fit</span>
        </div>
      </div>
    </div>

    <!-- Tab bar -->
    <div class="flex gap-6 border-b border-dc-surface dark:border-dc-dark-border mb-6">
      <button
        v-for="tab in TABS"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="[
          'pb-3 text-body font-medium border-b-2 transition-colors -mb-px',
          activeTab === tab.key
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        {{ tab.label }}
        <span
          v-if="tab.key === 'mine' && myContributions.length > 0"
          class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-xs font-bold bg-dc-primary-tint text-dc-primary rounded-full"
        >
          {{ myContributions.length }}
        </span>
      </button>
    </div>

    <!-- ── Browse Issues tab ── -->
    <div v-if="activeTab === 'browse'">
      <EmptyState
        v-if="issues.length === 0"
        message="No open issues found. Try a different language filter or check back after the next sync."
      />

      <div v-else class="space-y-3">
        <Card v-for="issue in issues" :key="issue.id" class="p-5">
          <div class="flex items-start justify-between gap-4">
            <!-- Left: repo + title + labels -->
            <div class="min-w-0 flex-1">
              <div class="text-small text-dc-muted mb-1">
                {{ issue.repo_full_name }}
              </div>
              <a
                :href="issue.url"
                target="_blank"
                rel="noopener noreferrer"
                class="font-semibold text-dc-primary-dark dark:text-dc-primary-muted hover:underline leading-snug block"
              >
                {{ issue.title }}
              </a>

              <div v-if="issue.labels && issue.labels.length > 0" class="flex flex-wrap gap-1.5 mt-2">
                <span
                  v-for="label in issue.labels"
                  :key="label"
                  class="text-xs px-2 py-0.5 bg-dc-surface dark:bg-dc-dark-border rounded-full text-dc-muted"
                >
                  {{ label }}
                </span>
              </div>

              <!-- AI match box -->
              <div
                v-if="aiRanked && issue.ai_score !== undefined"
                class="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-dc-coral-tint rounded-md text-small"
              >
                <span class="font-semibold text-dc-coral">{{ issue.ai_score }}%</span>
                <span class="text-dc-coral-dark">— {{ issue.ai_reason }}</span>
              </div>
            </div>

            <!-- Right: badges + actions -->
            <div class="flex flex-col items-end gap-3 flex-shrink-0">
              <div class="flex items-center gap-2">
                <Badge v-if="issue.language" variant="open">
                  {{ capitalize(issue.language) }}
                </Badge>
                <Badge :variant="difficultyVariant(issue.difficulty)">
                  {{ issue.difficulty ?? 'unknown' }}
                </Badge>
              </div>

              <IssueActions
                :issue="issue"
                :log="localLogs[String(issue.id)] ?? null"
                :loading="loadingIds.has(String(issue.id))"
                @bookmark="bookmark(issue.id)"
                @start-working="advanceStatus($event, 'working')"
                @open-pr-modal="openPrModal"
                @mark-merged="advanceStatus($event, 'merged')"
              />
            </div>
          </div>
        </Card>
      </div>
    </div>

    <!-- ── My Contributions tab ── -->
    <div v-if="activeTab === 'mine'">
      <EmptyState
        v-if="myContributions.length === 0"
        message="No contributions tracked yet. Bookmark an issue to start."
      />

      <div v-else class="space-y-3">
        <Card v-for="issue in myContributions" :key="issue.id" class="p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <div class="text-small text-dc-muted mb-1">{{ issue.repo_full_name }}</div>
              <a
                :href="issue.url"
                target="_blank"
                rel="noopener noreferrer"
                class="font-semibold text-dc-primary-dark dark:text-dc-primary-muted hover:underline"
              >
                {{ issue.title }}
              </a>

              <!-- Status progress dots -->
              <div class="flex items-center gap-2 mt-3">
                <div
                  v-for="(step, i) in STATUS_STEPS"
                  :key="step.key"
                  class="flex items-center gap-2"
                >
                  <div
                    :class="[
                      'w-2.5 h-2.5 rounded-full transition-colors',
                      stepIndex(localLogs[String(issue.id)]?.status) >= i
                        ? 'bg-dc-primary'
                        : 'bg-dc-surface dark:bg-dc-dark-border'
                    ]"
                    :title="step.label"
                  />
                  <span
                    v-if="stepIndex(localLogs[String(issue.id)]?.status) === i"
                    class="text-xs text-dc-primary font-medium"
                  >
                    {{ step.label }}
                  </span>
                  <div v-if="i < STATUS_STEPS.length - 1" class="w-4 h-px bg-dc-surface dark:bg-dc-dark-border" />
                </div>
              </div>

              <!-- PR link -->
              <a
                v-if="localLogs[String(issue.id)]?.pr_url"
                :href="localLogs[String(issue.id)]!.pr_url!"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 inline-flex items-center gap-1 text-small text-dc-primary hover:underline"
              >
                View PR ↗
              </a>
            </div>

            <div class="flex flex-col items-end gap-3 flex-shrink-0">
              <Badge :variant="statusVariant(localLogs[String(issue.id)]?.status)">
                {{ statusLabel(localLogs[String(issue.id)]?.status) }}
              </Badge>

              <div class="flex flex-col gap-2">
                <IssueActions
                  :issue="issue"
                  :log="localLogs[String(issue.id)] ?? null"
                  :loading="loadingIds.has(String(issue.id))"
                  @bookmark="bookmark(issue.id)"
                  @start-working="advanceStatus($event, 'working')"
                  @open-pr-modal="openPrModal"
                  @mark-merged="advanceStatus($event, 'merged')"
                />
                <Button
                  v-if="localLogs[String(issue.id)]?.converted_project_id === null"
                  variant="ghost"
                  @click="convertToProject(localLogs[String(issue.id)]!.id)"
                >
                  Start a team project from this
                </Button>
              </div>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div>

  <!-- PR Submit Modal -->
  <Teleport to="body">
    <div
      v-if="prModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
      @click.self="prModal.open = false"
    >
      <Card class="w-full max-w-md p-6">
        <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Submit Pull Request
        </h2>
        <p class="text-small text-dc-muted mb-4">
          Paste your GitHub pull request URL to mark this issue as submitted.
        </p>
        <TextInput
          v-model="prModal.prUrl"
          placeholder="https://github.com/owner/repo/pull/123"
          @keydown.enter="submitPr"
        />
        <p v-if="prUrlError" class="mt-1 text-small text-dc-danger">{{ prUrlError }}</p>
        <div class="flex justify-end gap-3 mt-5">
          <Button variant="ghost" @click="prModal.open = false">Cancel</Button>
          <Button variant="primary" @click="submitPr">Submit</Button>
        </div>
      </Card>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Badge from '@/Components/Badge.vue'
import Toggle from '@/Components/Toggle.vue'
import AIBadge from '@/Components/AIBadge.vue'
import PageHeader from '@/Components/PageHeader.vue'
import EmptyState from '@/Components/EmptyState.vue'
import TextInput from '@/Components/TextInput.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

// ── Inline sub-component for action buttons ──────────────────────────────────
// Defined as a local component to keep the template readable

const IssueActions = {
  props: ['issue', 'log', 'loading'],
  emits: ['bookmark', 'start-working', 'open-pr-modal', 'mark-merged'],
  template: `
    <div class="flex flex-wrap gap-2 justify-end">
      <template v-if="!log">
        <button @click="$emit('bookmark')" :disabled="loading"
          class="text-small font-medium px-3 py-1.5 rounded border border-dc-primary text-dc-primary hover:bg-dc-primary hover:text-white transition-colors disabled:opacity-50">
          Bookmark
        </button>
        <a :href="issue.url" target="_blank" rel="noopener noreferrer"
          class="text-small font-medium px-3 py-1.5 rounded border border-dc-surface dark:border-dc-dark-border text-dc-muted hover:text-dc-body transition-colors">
          View on GitHub ↗
        </a>
      </template>
      <template v-else-if="log.status === 'bookmarked'">
        <button @click="$emit('start-working', log.id)" :disabled="loading"
          class="text-small font-medium px-3 py-1.5 rounded bg-dc-primary text-white hover:bg-dc-primary-dark transition-colors disabled:opacity-50">
          Start Working
        </button>
        <a :href="issue.url" target="_blank" rel="noopener noreferrer"
          class="text-small font-medium px-3 py-1.5 rounded border border-dc-surface dark:border-dc-dark-border text-dc-muted hover:text-dc-body transition-colors">
          View on GitHub ↗
        </a>
      </template>
      <template v-else-if="log.status === 'working'">
        <button @click="$emit('open-pr-modal', log.id)" :disabled="loading"
          class="text-small font-medium px-3 py-1.5 rounded bg-dc-primary text-white hover:bg-dc-primary-dark transition-colors disabled:opacity-50">
          Submit PR
        </button>
        <a :href="issue.url" target="_blank" rel="noopener noreferrer"
          class="text-small font-medium px-3 py-1.5 rounded border border-dc-surface dark:border-dc-dark-border text-dc-muted hover:text-dc-body transition-colors">
          View on GitHub ↗
        </a>
      </template>
      <template v-else-if="log.status === 'pr_submitted'">
        <button @click="$emit('mark-merged', log.id)" :disabled="loading"
          class="text-small font-medium px-3 py-1.5 rounded bg-dc-success text-white hover:opacity-90 transition-colors disabled:opacity-50">
          Mark Merged
        </button>
        <a v-if="log.pr_url" :href="log.pr_url" target="_blank" rel="noopener noreferrer"
          class="text-small font-medium px-3 py-1.5 rounded border border-dc-success text-dc-success hover:bg-dc-success hover:text-white transition-colors">
          View PR ↗
        </a>
      </template>
    </div>
  `,
}

// ── Types ─────────────────────────────────────────────────────────────────────

interface GithubIssue {
  id: number
  repo_full_name: string
  issue_number: number
  title: string
  body: string | null
  url: string
  labels: string[]
  language: string | null
  difficulty: 'beginner' | 'intermediate' | 'advanced' | null
  state: 'open' | 'closed'
  fetched_at: string
  ai_score?: number
  ai_reason?: string
}

interface ContributionLog {
  id: number
  user_id: number
  github_issue_id: number
  status: 'bookmarked' | 'working' | 'pr_submitted' | 'merged'
  pr_url: string | null
  converted_project_id: number | null
  created_at: string
  updated_at: string
}

const props = defineProps<{
  issues: GithubIssue[]
  languages: string[]
  userLogs: Record<string, ContributionLog>
  aiRanked: boolean
  filters: { language?: string }
}>()

// ── State ─────────────────────────────────────────────────────────────────────

const activeTab = ref<'browse' | 'mine'>('browse')
const localLogs = ref<Record<string, ContributionLog>>({ ...props.userLogs })
const loadingIds = ref(new Set<string>())

const prModal = ref<{ open: boolean; logId: number | null; prUrl: string }>({
  open: false,
  logId: null,
  prUrl: '',
})
const prUrlError = ref('')

// ── Constants ─────────────────────────────────────────────────────────────────

const TABS = [
  { key: 'browse' as const, label: 'Browse Issues' },
  { key: 'mine' as const, label: 'My Contributions' },
]

const STATUS_STEPS = [
  { key: 'bookmarked', label: 'Bookmarked' },
  { key: 'working', label: 'Working' },
  { key: 'pr_submitted', label: 'PR Submitted' },
  { key: 'merged', label: 'Merged' },
]

const STATUS_ORDER: Record<string, number> = {
  bookmarked: 0,
  working: 1,
  pr_submitted: 2,
  merged: 3,
}

// ── Computed ──────────────────────────────────────────────────────────────────

const myContributions = computed(() =>
  props.issues.filter(issue => localLogs.value[String(issue.id)])
)

// ── Helpers ───────────────────────────────────────────────────────────────────

function capitalize(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function tabClass(active: boolean): string {
  return [
    'px-3 py-1.5 rounded-full text-small font-medium transition-colors',
    active
      ? 'bg-dc-primary text-white'
      : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:text-dc-body',
  ].join(' ')
}

function difficultyVariant(difficulty: string | null): 'completed' | 'pending' | 'declined' {
  if (difficulty === 'beginner') return 'completed'
  if (difficulty === 'advanced') return 'declined'
  return 'pending'
}

function statusVariant(status?: string): 'open' | 'in-progress' | 'completed' | 'pending' | 'declined' {
  const map: Record<string, 'open' | 'in-progress' | 'completed' | 'pending' | 'declined'> = {
    bookmarked: 'pending',
    working: 'in-progress',
    pr_submitted: 'open',
    merged: 'completed',
  }
  return map[status ?? ''] ?? 'pending'
}

function statusLabel(status?: string): string {
  const map: Record<string, string> = {
    bookmarked: 'Bookmarked',
    working: 'Working',
    pr_submitted: 'PR Submitted',
    merged: 'Merged ✓',
  }
  return map[status ?? ''] ?? status ?? ''
}

function stepIndex(status?: string): number {
  return STATUS_ORDER[status ?? ''] ?? -1
}

// ── Navigation ────────────────────────────────────────────────────────────────

function toggleAiRank(value: boolean) {
  router.get(urls.contribute.index(), {
    ...props.filters,
    ai_ranked: value ? 'true' : undefined,
  })
}

function setLanguage(lang: string | null) {
  router.get(urls.contribute.index(), {
    language: lang ?? undefined,
    ai_ranked: props.aiRanked ? 'true' : undefined,
  })
}

// ── Actions ───────────────────────────────────────────────────────────────────

async function bookmark(issueId: number) {
  const key = String(issueId)
  loadingIds.value.add(key)
  try {
    const { data } = await axios.post<ContributionLog>(urls.contribute.logs.store(), {
      github_issue_id: issueId,
    })
    localLogs.value[key] = data
  } finally {
    loadingIds.value.delete(key)
  }
}

async function advanceStatus(logId: number, status: string, prUrl?: string) {
  const log = Object.values(localLogs.value).find(l => l.id === logId)
  if (! log) return

  const key = String(log.github_issue_id)
  loadingIds.value.add(key)
  try {
    const { data } = await axios.patch<ContributionLog>(urls.contribute.logs.update(logId), {
      status,
      pr_url: prUrl,
    })
    localLogs.value[key] = data
  } finally {
    loadingIds.value.delete(key)
  }
}

function openPrModal(logId: number) {
  prModal.value = { open: true, logId, prUrl: '' }
  prUrlError.value = ''
}

async function submitPr() {
  if (! prModal.value.logId) return

  const url = prModal.value.prUrl.trim()
  if (! /github\.com\/.+\/pull\/\d+/.test(url)) {
    prUrlError.value = 'Must be a GitHub PR URL (github.com/.../pull/123)'
    return
  }

  await advanceStatus(prModal.value.logId, 'pr_submitted', url)
  prModal.value.open = false
}

function convertToProject(logId: number) {
  router.post(urls.contribute.logs.convert(logId))
}
</script>
