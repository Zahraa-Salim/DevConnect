<template>
  <Head title="Projects" />

  <div class="max-w-7xl mx-auto p-4 md:p-8">
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-display text-dc-primary-dark mb-2">
          Browse projects
        </h1>
        <p class="text-body text-dc-muted">
          Find a project to join or post one of your own.
        </p>
      </div>
      <Link
        :href="urls.projects.create()"
        class="whitespace-nowrap"
      >
        <Button>Post a project</Button>
      </Link>
    </div>

    <!-- Ownership tabs -->
    <div class="flex gap-4 mb-6 border-b border-dc-surface dark:border-dc-dark-border">
      <button
        type="button"
        @click="updateFilter('mine', '')"
        :class="[
          'px-4 py-3 font-medium text-small border-b-2 transition-colors',
          !filters.mine
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        All Projects
      </button>
      <button
        type="button"
        @click="updateFilter('mine', '1')"
        :class="[
          'px-4 py-3 font-medium text-small border-b-2 transition-colors',
          filters.mine
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-body'
        ]"
      >
        My Projects
      </button>
    </div>

    <!-- Filter Bar -->
    <Card class="mb-8 p-6">
      <div class="flex items-center justify-between gap-4 mb-4">
        <div class="flex items-center gap-3">
          <Toggle
            :modelValue="aiMatchEnabled"
            @update:modelValue="toggleAiMatch"
            label="AI match for me"
          />
          <div v-if="aiMatchEnabled" class="flex items-center gap-1.5">
            <AIBadge />
            <span class="text-small text-dc-muted">Sorted by AI match</span>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Stack
          </label>
          <TextInput
            :modelValue="filters.search || ''"
            @update:modelValue="updateFilter('search', $event)"
            placeholder="e.g. React, Vue"
            @input="debounceSearch"
          />
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Role needed
          </label>
          <Select
            :modelValue="filters.role || ''"
            @update:modelValue="updateFilter('role', $event)"
            :options="[
              { value: '', label: 'All roles' },
              { value: 'frontend', label: 'Frontend' },
              { value: 'backend', label: 'Backend' },
              { value: 'designer', label: 'Designer' }
            ]"
          />
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Domain
          </label>
          <TextInput
            :modelValue="filters.domain || ''"
            @update:modelValue="updateFilter('domain', $event)"
            placeholder="e.g. education"
          />
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Type
          </label>
          <Select
            :modelValue="filters.type || ''"
            @update:modelValue="updateFilter('type', $event)"
            :options="[
              { value: '', label: 'All types' },
              { value: 'practice', label: 'Practice' },
              { value: 'real_client', label: 'Real Client' }
            ]"
          />
        </div>

        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Sort by
          </label>
          <Select
            :modelValue="sortBy"
            @update:modelValue="updateSort"
            :options="[
              { value: 'newest', label: 'Newest' },
              { value: 'active', label: 'Most active' }
            ]"
          />
        </div>
      </div>
    </Card>

    <!-- Projects List -->
    <div v-if="projects.data.length === 0" class="bg-white dark:bg-dc-dark-surface rounded-lg p-12 text-center border border-dc-surface dark:border-dc-dark-border">
      <div class="text-h2 text-dc-muted mb-2">
        No projects found
      </div>
      <p class="text-body text-dc-muted">
        Try adjusting your filters or post a new project.
      </p>
    </div>

    <div v-else class="space-y-4 mb-8">
      <Link
        v-for="project in projects.data"
        :key="project.id"
        :href="urls.projects.show(project.id)"
        class="block hover:no-underline"
      >
        <Card class="hover:shadow-lg transition-shadow cursor-pointer">
          <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-2 min-w-0">
              <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted hover:underline">
                {{ project.title }}
              </h3>
              <span
                v-if="project.status === 'at_risk'"
                class="w-2 h-2 rounded-full bg-red-500 animate-pulse flex-shrink-0"
                title="At Risk — no recent activity"
              ></span>
            </div>
            <Badge
              :variant="project.type === 'practice' ? 'skill' : 'open'"
              class="flex-shrink-0"
            >
              {{ project.type === 'practice' ? 'Practice' : 'Real Client' }}
            </Badge>
          </div>

          <p class="text-body text-dc-body dark:text-dc-muted mb-4 line-clamp-2">
            {{ project.description }}
          </p>

          <div
            v-if="aiMatchEnabled && project.ai_score && project.ai_score > 0"
            class="mb-4 inline-flex items-center gap-2 px-3 py-1 bg-dc-coral-tint rounded-md text-small"
          >
            <span class="font-semibold text-dc-coral">{{ project.ai_score }}% match</span>
            <span class="text-dc-coral-dark">— {{ project.ai_reason }}</span>
          </div>

          <div v-if="project.tech_stack && project.tech_stack.length > 0" class="flex flex-wrap gap-2 mb-4">
            <SkillTag
              v-for="tech in project.tech_stack"
              :key="tech"
              :label="tech"
            />
          </div>

          <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-dc-surface dark:border-dc-dark-border">
            <div class="flex items-center gap-6">
              <div class="text-small text-dc-body dark:text-dc-muted">
                <span class="font-medium">Open roles: </span>
                {{ openRolesText(project) }}
              </div>
              <div class="text-small text-dc-muted">
                {{ membersText(project) }}
              </div>
              <div class="flex items-center gap-2 text-small text-dc-muted">
                <div
                  v-if="!project.owner.avatar_url"
                  class="w-6 h-6 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold text-xs"
                >
                  {{ project.owner.name?.charAt(0).toUpperCase() }}
                </div>
                <img
                  v-else
                  :src="project.owner.avatar_url"
                  :alt="project.owner.name"
                  class="w-6 h-6 rounded-full"
                />
                <span>
                  {{ project.owner.name }} · {{ formatDate(project.created_at) }}
                </span>
              </div>
            </div>
            <div class="flex gap-3">
              <Button
                v-if="!isProjectOwner(project)"
                variant="ghost"
                @click.prevent="messageOwner(project)"
              >
                Message owner
              </Button>
              <Button
                v-if="!isProjectOwner(project)"
                variant="primary"
                @click.prevent="applyProject(project)"
              >
                Apply
              </Button>
            </div>
          </div>
        </Card>
      </Link>
    </div>

    <!-- Pagination -->
    <div v-if="projects.last_page > 1" class="flex justify-center gap-2">
      <Link
        v-for="page in paginationLinks"
        :key="page.label"
        :href="page.url"
        :class="[
          'px-3 py-2 rounded border text-small font-medium transition-colors',
          page.active
            ? 'bg-dc-primary text-white border-dc-primary'
            : 'border-dc-surface text-dc-body hover:border-dc-primary'
        ]"
      >
        {{ page.label }}
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Badge from '@/Components/Badge.vue'
import SkillTag from '@/Components/SkillTag.vue'
import TextInput from '@/Components/TextInput.vue'
import Select from '@/Components/Select.vue'
import Toggle from '@/Components/Toggle.vue'
import AIBadge from '@/Components/AIBadge.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

interface Project {
  id: number
  title: string
  description: string
  type: string
  domain: string | null
  tech_stack: string[] | null
  max_members: number
  status: string
  created_at: string
  owner: { id: number; name: string; avatar_url: string | null }
  members: Array<{ id: number }>
  roles: Array<{ id: number; role_name: string; is_open: boolean }>
  ai_score?: number
  ai_reason?: string
}

interface PaginatedProjects {
  data: Project[]
  links: any[]
  current_page: number
  last_page: number
}

const props = defineProps<{
  projects: PaginatedProjects
  filters: Record<string, string | boolean | undefined>
  aiMatchEnabled: boolean
}>()

const page = usePage()
const sortBy = ref('newest')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const paginationLinks = computed(() => {
  return props.projects.links.map((link: any) => ({
    label: link.label.replace(/&laquo;|&raquo;/g, '').trim(),
    url: link.url,
    active: link.active,
  }))
})

function updateFilter(key: string, value: string) {
  const newFilters = {
    ...props.filters,
    [key]: value || undefined,
  }
  router.get(urls.projects.index(), newFilters, { preserveState: true, preserveScroll: true })
}

function debounceSearch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    // Debounce is handled by input event, actual filtering happens on blur
  }, 300)
}

function updateSort(value: string) {
  sortBy.value = value
  // TODO: Implement sorting in backend
}

function toggleAiMatch(value: boolean) {
  router.get(urls.projects.index(), {
    ...props.filters,
    ai_match: value ? 'true' : undefined,
  }, { preserveState: true, preserveScroll: true })
}

function openRolesText(project: Project): string {
  const openRoles = project.roles.filter(r => r.is_open).map(r => r.role_name)
  return openRoles.length > 0 ? openRoles.join(' · ') : 'No open roles'
}

function membersText(project: Project): string {
  return `${project.members.length} of ${project.max_members} members`
}

function isProjectOwner(project: Project): boolean {
  return project.owner.id === page.props.auth.user?.id
}

function formatDate(date: string): string {
  const d = new Date(date)
  const now = new Date()
  const diffMs = now.getTime() - d.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return 'today'
  if (diffDays === 1) return 'yesterday'
  if (diffDays < 7) return `${diffDays}d ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`
  return `${Math.floor(diffDays / 30)}m ago`
}

async function messageOwner(project: Project) {
  try {
    const res = await fetch('/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ recipient_id: project.owner.id }),
    })
    if (res.redirected) {
      window.location.href = res.url
      return
    }
    const data = await res.json()
    if (data?.id) router.visit(`/messages/${data.id}`)
  } catch (e) {
    console.error('messageOwner error', e)
  }
}

function applyProject(project: Project) {
  router.visit(urls.projects.show(project.id))
}
</script>
