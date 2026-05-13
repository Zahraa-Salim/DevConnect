<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { CheckCircle } from 'lucide-vue-next'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

const user = usePage().props.auth.user

interface Stats {
  reputation_score: number
  is_verified: boolean
  active_project_count: number
  completed_project_count: number
  dna_label: string | null
  tasks_done: number
  prs_merged: number
}

interface DashboardProject {
  id: number
  title: string
  status: string
  type: string
  domain: string | null
  tech_stack: string[]
  member_count: number
  max_members: number
  open_roles: string[]
}

interface Props {
  stats: Stats
  myProjects: DashboardProject[]
}

const props = defineProps<Props>()

const reputationColor = computed(() => {
  const s = props.stats.reputation_score
  if (s >= 80) return 'text-dc-success'
  if (s >= 60) return 'text-dc-primary'
  if (s >= 40) return 'text-dc-warning'
  return 'text-dc-muted'
})

function projectStatusClass(status: string): string {
  const classes: Record<string, string> = {
    open: 'bg-dc-success-tint text-dc-success',
    active: 'bg-dc-primary-tint text-dc-primary',
    at_risk: 'bg-dc-danger-tint text-dc-danger',
  }

  return classes[status] ?? 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted'
}

function statusLabel(status: string): string {
  return status.replace(/_/g, ' ')
}
</script>

<template>
  <Head title="Dashboard" />

  <div class="max-w-5xl mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center gap-3 flex-wrap">
        <h1 class="text-display text-dc-primary-dark dark:text-white">
          Welcome back, {{ user?.name?.split(' ')[0] || 'friend' }}.
        </h1>
        <CheckCircle
          v-if="stats.is_verified"
          class="w-6 h-6 text-blue-500 flex-shrink-0"
        />
      </div>
      <p class="text-body text-dc-muted mt-1">Here's where you stand on DevConnect.</p>
    </div>

    <!-- Stat cards row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <!-- Reputation -->
      <Card class="p-5">
        <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-2">Reputation</div>
        <div :class="['text-3xl font-bold mb-1', reputationColor]">
          {{ stats.reputation_score > 0 ? stats.reputation_score : '—' }}
        </div>
        <div class="flex items-center gap-1.5">
          <Badge v-if="stats.is_verified" variant="open" class="text-xs">Verified Contributor</Badge>
          <span v-else class="text-xs text-dc-muted">Rate projects to build score</span>
        </div>
      </Card>

      <!-- Active projects -->
      <Card class="p-5">
        <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-2">Active Projects</div>
        <div class="text-3xl font-bold text-dc-primary-dark dark:text-white mb-1">
          {{ stats.active_project_count }}
        </div>
        <Link :href="urls.projects.index()" class="text-xs text-dc-primary hover:underline">
          Browse projects →
        </Link>
      </Card>

      <!-- Completed projects -->
      <Card class="p-5">
        <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-2">Completed</div>
        <div class="text-3xl font-bold text-dc-primary-dark dark:text-white mb-1">
          {{ stats.completed_project_count }}
        </div>
        <span class="text-xs text-dc-muted">projects shipped</span>
      </Card>

      <!-- Contribution DNA -->
      <Card class="p-5">
        <div class="text-xs text-dc-muted uppercase tracking-wider font-medium mb-2">Contribution DNA</div>
        <div class="text-2xl font-bold text-dc-primary-dark dark:text-white mb-1">
          {{ stats.dna_label ?? '—' }}
        </div>
        <span class="text-xs text-dc-muted">
          {{ stats.tasks_done }} tasks · {{ stats.prs_merged }} PRs merged
        </span>
      </Card>
    </div>

    <!-- My projects -->
    <div class="mb-8">
      <div class="flex items-center justify-between gap-4 mb-4">
        <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted">My Projects</h2>
        <Link href="/projects?mine=1" class="text-small text-dc-primary hover:underline">
          View all →
        </Link>
      </div>

      <div v-if="myProjects.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Link
          v-for="p in myProjects"
          :key="p.id"
          :href="`/projects/${p.id}`"
          class="block"
        >
          <Card class="p-5 h-full hover:border-dc-primary transition-colors cursor-pointer">
            <div class="flex items-start justify-between gap-3 mb-3">
              <span :class="['px-2 py-1 rounded-full text-xs font-medium capitalize', projectStatusClass(p.status)]">
                {{ statusLabel(p.status) }}
              </span>
              <span class="text-xs text-dc-muted">{{ p.type }}</span>
            </div>

            <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-1 line-clamp-2">
              {{ p.title }}
            </h3>
            <p v-if="p.domain" class="text-small text-dc-muted mb-3">{{ p.domain }}</p>

            <div class="flex flex-wrap gap-1.5 mb-4">
              <span
                v-for="tech in p.tech_stack.slice(0, 3)"
                :key="tech"
                class="px-2 py-0.5 rounded-full bg-dc-primary-tint text-dc-primary text-small"
              >
                {{ tech }}
              </span>
            </div>

            <div class="flex items-center justify-between gap-3 pt-4 border-t border-dc-surface dark:border-dc-dark-border text-small text-dc-muted">
              <span>{{ p.member_count }}/{{ p.max_members }} members</span>
              <span>{{ p.open_roles.length }} open roles</span>
            </div>
          </Card>
        </Link>
      </div>

      <Card v-else class="p-6 text-center">
        <p class="text-body text-dc-muted mb-3">You're not in any active projects yet.</p>
        <div class="flex items-center justify-center gap-4">
          <Link href="/projects/create" class="text-small text-dc-primary hover:underline">Post one</Link>
          <Link href="/projects" class="text-small text-dc-primary hover:underline">Browse to join</Link>
        </div>
      </Card>
    </div>

    <!-- Quick links -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <Link :href="urls.projects.index()">
        <Card class="p-5 hover:border-dc-primary transition-colors cursor-pointer group">
          <div class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted group-hover:text-dc-primary mb-1">
            Find a project
          </div>
          <p class="text-small text-dc-muted">Browse open projects and apply to join a team.</p>
        </Card>
      </Link>

      <Link :href="urls.projects.create()">
        <Card class="p-5 hover:border-dc-primary transition-colors cursor-pointer group">
          <div class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted group-hover:text-dc-primary mb-1">
            Start a project
          </div>
          <p class="text-small text-dc-muted">Post a new project and recruit your team.</p>
        </Card>
      </Link>

      <Link href="/settings">
        <Card class="p-5 hover:border-dc-primary transition-colors cursor-pointer group">
          <div class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted group-hover:text-dc-primary mb-1">
            Complete your profile
          </div>
          <p class="text-small text-dc-muted">Add skills and working style to get better matches.</p>
        </Card>
      </Link>
    </div>
  </div>
</template>
