<template>
  <Head :title="`Profile Suggestions — ${project.title}`" />

  <div class="max-w-7xl mx-auto p-4 md:p-8">
    <Link :href="urls.projects.show(project.id)" class="text-small text-dc-muted hover:text-dc-primary">
      ← Back to {{ project.title }}
    </Link>

    <PageHeader
      title="Profile Suggestions"
      :subtitle="`AI-generated from your work on ${project.title}`"
      class="mt-4"
    />

    <!-- Project not yet complete -->
    <Card v-if="project.status !== 'completed'" class="p-8 text-center">
      <p class="text-body text-dc-muted">
        Profile suggestions are available once the project is marked complete.
      </p>
    </Card>

    <template v-else>
      <!-- Error banner -->
      <div
        v-if="error"
        class="mb-6 p-4 rounded-lg bg-dc-danger-tint border border-dc-danger-tint text-small text-dc-danger"
      >
        Generation failed — the AI service may be temporarily unavailable. Please try again.
      </div>

      <!-- Empty state -->
      <Card v-if="!localSuggestion" class="p-10 text-center">
        <p class="text-h3 text-dc-primary-dark mb-2">Generate career-ready content from this project</p>
        <p class="text-body text-dc-muted mb-8 max-w-md mx-auto">
          Claude will analyse your role, tasks, and the tech stack to produce
          content ready for your CV, portfolio, and LinkedIn.
        </p>
        <Button variant="primary" :disabled="generating" @click="generate">
          <span v-if="generating" class="flex items-center gap-2">
            <Spinner class="w-4 h-4" />Generating…
          </span>
          <span v-else>Generate with AI</span>
        </Button>
      </Card>

      <!-- Three suggestion cards -->
      <template v-else>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div
            v-for="card in CARDS"
            :key="card.key"
            class="flex flex-col rounded-xl border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface p-5"
          >
            <!-- Card header -->
            <div class="mb-3">
              <p class="text-label-caps text-dc-muted mb-1">{{ card.label }}</p>
              <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">{{ card.title }}</h3>
            </div>

            <!-- Content -->
            <div class="flex-1 mb-4">
              <p
                :class="[
                  'text-dc-body dark:text-dc-primary-muted whitespace-pre-wrap leading-relaxed',
                  card.mono ? 'font-mono text-small' : 'text-body',
                ]"
              >
                {{ localSuggestion[card.field] }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mt-auto">
              <button
                @click="copy(card.key, card.field)"
                class="flex-1 text-small font-medium px-3 py-1.5 rounded border border-dc-surface dark:border-dc-dark-border text-dc-muted hover:text-dc-primary hover:border-dc-primary transition-colors"
              >
                {{ copiedKey === card.key ? 'Copied!' : 'Copy' }}
              </button>
              <button
                @click="generate"
                :disabled="generating"
                class="flex-1 text-small font-medium px-3 py-1.5 rounded border border-dc-surface dark:border-dc-dark-border text-dc-muted hover:text-dc-primary hover:border-dc-primary transition-colors disabled:opacity-50"
              >
                <span v-if="generating && regeneratingFrom === card.key" class="flex items-center justify-center gap-1">
                  <Spinner class="w-3 h-3" />
                </span>
                <span v-else>Regenerate</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer disclaimer -->
        <p class="text-small text-dc-muted italic text-center">
          These are suggestions. Edit them to match your voice before using.
        </p>
      </template>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Spinner from '@/Components/Spinner.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

// ── Types ─────────────────────────────────────────────────────────────────────

interface Suggestion {
  cv_text: string
  portfolio_text: string
  linkedin_text: string
}

type SuggestionField = keyof Suggestion

interface CardDef {
  key: string
  title: string
  label: string
  field: SuggestionField
  mono: boolean
}

const props = defineProps<{
  project: { id: number; title: string; status: string }
  suggestion: Suggestion | null
}>()

// ── Card definitions ──────────────────────────────────────────────────────────

const CARDS: CardDef[] = [
  {
    key:   'cv',
    title: 'CV Bullet',
    label: 'Add to your resume',
    field: 'cv_text',
    mono:  true,
  },
  {
    key:   'portfolio',
    title: 'Portfolio Description',
    label: 'For your portfolio or README',
    field: 'portfolio_text',
    mono:  false,
  },
  {
    key:   'linkedin',
    title: 'LinkedIn Post',
    label: 'Announce your project',
    field: 'linkedin_text',
    mono:  false,
  },
]

// ── State ─────────────────────────────────────────────────────────────────────

const localSuggestion = ref<Suggestion | null>(props.suggestion)
const generating      = ref(false)
const regeneratingFrom = ref<string | null>(null)
const error           = ref(false)
const copiedKey       = ref<string | null>(null)

// ── Actions ───────────────────────────────────────────────────────────────────

function copy(cardKey: string, field: SuggestionField) {
  const text = localSuggestion.value?.[field] ?? ''
  navigator.clipboard.writeText(text).then(() => {
    copiedKey.value = cardKey
    setTimeout(() => (copiedKey.value = null), 2000)
  })
}

async function generate(event?: Event) {
  // Track which card's Regenerate button was clicked (if any)
  const btn = (event?.target as HTMLElement)?.closest('button')
  const cardKey = btn?.closest('[data-card-key]')?.getAttribute('data-card-key') ?? null
  regeneratingFrom.value = cardKey

  generating.value = true
  error.value = false

  try {
    const { data } = await axios.post<{
      cv_bullet: string
      portfolio_description: string
      linkedin_post: string
      error: boolean
    }>(urls.projects.suggestions.generate(props.project.id))

    if (data.error) {
      error.value = true
      return
    }

    localSuggestion.value = {
      cv_text:        data.cv_bullet,
      portfolio_text: data.portfolio_description,
      linkedin_text:  data.linkedin_post,
    }
  } catch {
    error.value = true
  } finally {
    generating.value = false
    regeneratingFrom.value = null
  }
}
</script>
