<template>
  <Head title="Admin · Dashboard" />

  <div class="p-8">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted">Dashboard</h1>
      <p class="text-body text-dc-muted mt-1">Platform overview at a glance.</p>
    </div>

    <!-- Row 1: Users & Projects -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
      <div v-for="card in row1" :key="card.label" :class="statCard(card.accent)">
        <div :class="['text-4xl font-bold mb-1', card.accent ? 'text-dc-primary' : 'text-dc-primary-dark dark:text-dc-primary-muted']">
          {{ card.value }}
        </div>
        <div class="text-small text-dc-muted uppercase tracking-wider font-medium">{{ card.label }}</div>
      </div>
    </div>

    <!-- Row 2: AI & Mentors -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
      <div v-for="card in row2" :key="card.label" :class="statCard(card.accent)">
        <div :class="['text-4xl font-bold mb-1', card.accent ? 'text-dc-primary' : 'text-dc-primary-dark dark:text-dc-primary-muted']">
          {{ card.value }}
        </div>
        <div class="text-small text-dc-muted uppercase tracking-wider font-medium">{{ card.label }}</div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <Link
        v-for="link in quickLinks"
        :key="link.href"
        :href="link.href"
        class="block p-5 rounded-xl border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface hover:border-dc-primary transition-colors"
      >
        <div class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted font-semibold mb-1">{{ link.title }}</div>
        <p class="text-small text-dc-muted">{{ link.desc }}</p>
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  stats: {
    total_users: number
    new_users_week: number
    total_projects: number
    active_projects: number
    ai_calls_month: number
    tokens_used_month: number
    pending_mentors: number
  }
}>()

function fmt(n: number): string {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}

const row1 = computed(() => [
  { label: 'Total Users',     value: props.stats.total_users,     accent: false },
  { label: 'New This Week',   value: props.stats.new_users_week,  accent: true  },
  { label: 'Total Projects',  value: props.stats.total_projects,  accent: false },
  { label: 'Active Projects', value: props.stats.active_projects, accent: true  },
])

const row2 = computed(() => [
  { label: 'AI Calls This Month', value: props.stats.ai_calls_month,               accent: false },
  { label: 'Tokens Used',         value: fmt(props.stats.tokens_used_month),        accent: false },
  { label: 'Pending Mentors',     value: props.stats.pending_mentors,               accent: props.stats.pending_mentors > 0 },
  { label: 'Est. AI Cost',        value: '$' + ((props.stats.tokens_used_month / 1000) * 0.003).toFixed(2), accent: false },
])

const quickLinks = [
  { href: '/admin/users',                  title: 'Manage Users',   desc: 'Search, suspend or review any user account.' },
  { href: '/admin/mentors?status=pending', title: 'Review Mentors', desc: 'Approve or reject pending mentor applications.' },
  { href: '/admin/analytics',              title: 'Analytics',      desc: 'AI usage, project activity and reputation data.' },
]

function statCard(accent: boolean): string {
  return [
    'p-5 rounded-xl border bg-white dark:bg-dc-dark-surface',
    accent
      ? 'border-dc-primary/40 dark:border-dc-primary/30'
      : 'border-dc-surface dark:border-dc-dark-border',
  ].join(' ')
}
</script>
