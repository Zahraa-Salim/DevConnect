<template>
  <Head title="Admin · Mentors" />

  <div class="p-8">
    <div class="mb-6">
      <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted">Mentor Applications</h1>
      <p class="text-body text-dc-muted mt-1">{{ mentors.total }} total applications</p>
    </div>

    <!-- Status filter tabs -->
    <div class="flex gap-2 mb-6 border-b border-dc-surface dark:border-dc-dark-border">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="setStatus(tab.value)"
        :class="[
          'px-4 py-2 text-small font-medium border-b-2 -mb-px transition-colors',
          activeStatus === tab.value
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-primary'
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-dc-dark-surface rounded-xl border border-dc-surface dark:border-dc-dark-border overflow-x-auto mb-6">
      <table class="w-full text-small">
        <thead>
          <tr class="border-b border-dc-surface dark:border-dc-dark-border text-dc-muted uppercase text-label-caps">
            <th class="px-4 py-3 text-left">Applicant</th>
            <th class="px-4 py-3 text-left">GitHub</th>
            <th class="px-4 py-3 text-right">Score</th>
            <th class="px-4 py-3 text-left">Focus Areas</th>
            <th class="px-4 py-3 text-left">Applied</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="mentor in mentors.data"
            :key="mentor.id"
            :class="[
              'border-b border-dc-surface dark:border-dc-dark-border last:border-0 transition-colors',
              mentor.status === 'pending'
                ? 'border-l-4 border-l-amber-400 hover:bg-amber-50/40 dark:hover:bg-amber-900/10'
                : 'hover:bg-dc-surface/40 dark:hover:bg-dc-dark-border/20'
            ]"
          >
            <!-- Applicant -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img
                  v-if="mentor.user?.avatar_url"
                  :src="mentor.user.avatar_url"
                  :alt="mentor.user.name"
                  class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                />
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold text-xs flex-shrink-0"
                >
                  {{ mentor.user?.name?.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-medium text-dc-primary-dark dark:text-dc-primary-muted">
                    {{ mentor.user?.name ?? '—' }}
                  </div>
                  <div class="text-dc-muted">{{ mentor.user?.email }}</div>
                </div>
              </div>
            </td>

            <!-- GitHub -->
            <td class="px-4 py-3">
              <a
                v-if="mentor.user?.github_username"
                :href="`https://github.com/${mentor.user.github_username}`"
                target="_blank"
                rel="noreferrer"
                class="text-dc-primary hover:underline"
              >
                @{{ mentor.user.github_username }}
              </a>
              <span v-else class="text-dc-muted">—</span>
            </td>

            <!-- Score -->
            <td class="px-4 py-3 text-right">
              <span
                :class="[
                  'font-mono font-semibold',
                  (mentor.github_score ?? 0) >= 60 ? 'text-dc-success' : 'text-dc-warning-dark'
                ]"
              >
                {{ mentor.github_score ?? 0 }}
              </span>
            </td>

            <!-- Focus Areas -->
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-1 max-w-xs">
                <span
                  v-for="area in (mentor.focus_areas ?? []).slice(0, 3)"
                  :key="area"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase bg-dc-primary-tint text-dc-primary-dark"
                >
                  {{ area }}
                </span>
                <span
                  v-if="(mentor.focus_areas ?? []).length > 3"
                  class="text-dc-muted text-xs self-center"
                >
                  +{{ mentor.focus_areas.length - 3 }}
                </span>
              </div>
            </td>

            <!-- Applied -->
            <td class="px-4 py-3 text-dc-muted whitespace-nowrap">
              {{ formatDate(mentor.created_at) }}
            </td>

            <!-- Status -->
            <td class="px-4 py-3 text-center">
              <span :class="statusClass(mentor.status)">
                {{ mentor.status }}
              </span>
            </td>

            <!-- Actions -->
            <td class="px-4 py-3 text-right">
              <template v-if="mentor.status === 'pending'">
                <div class="flex items-center justify-end gap-2">
                  <button
                    class="px-3 py-1 rounded text-small font-medium bg-dc-success text-white hover:opacity-80 transition-opacity disabled:opacity-50"
                    :disabled="acting === mentor.id"
                    @click="approve(mentor)"
                  >
                    Approve
                  </button>
                  <button
                    class="px-3 py-1 rounded text-small font-medium bg-dc-danger text-white hover:opacity-80 transition-opacity disabled:opacity-50"
                    :disabled="acting === mentor.id"
                    @click="reject(mentor)"
                  >
                    Reject
                  </button>
                </div>
              </template>
              <span v-else class="text-dc-muted">—</span>
            </td>
          </tr>

          <tr v-if="mentors.data.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-dc-muted">No applications found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="mentors.last_page > 1" class="flex justify-center gap-1">
      <button
        v-for="page in mentors.last_page"
        :key="page"
        @click="goToPage(page)"
        :class="[
          'px-3 py-1.5 rounded text-small font-medium transition-colors',
          page === mentors.current_page
            ? 'bg-dc-primary text-white'
            : 'bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border text-dc-muted hover:text-dc-primary'
        ]"
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  mentors: {
    data: any[]
    current_page: number
    last_page: number
    total: number
  }
  filters: { status?: string }
}>()

const tabs = [
  { label: 'All',      value: '' },
  { label: 'Pending',  value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

const activeStatus = ref(props.filters.status ?? '')
const acting = ref<number | null>(null)

function setStatus(status: string) {
  activeStatus.value = status
  router.get('/admin/mentors', { status }, { preserveScroll: true, replace: true })
}

function goToPage(page: number) {
  router.get('/admin/mentors', { status: activeStatus.value, page }, { preserveScroll: true })
}

function approve(mentor: any) {
  acting.value = mentor.id
  router.post(`/admin/mentors/${mentor.id}/approve`, {}, { onFinish: () => { acting.value = null } })
}

function reject(mentor: any) {
  if (!confirm(`Reject ${mentor.user?.name ?? 'this applicant'}?`)) return
  acting.value = mentor.id
  router.post(`/admin/mentors/${mentor.id}/reject`, {}, { onFinish: () => { acting.value = null } })
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

function statusClass(status: string): string {
  const map: Record<string, string> = {
    pending:  'inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase bg-dc-warning-tint text-dc-warning-dark',
    approved: 'inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase bg-dc-success-tint text-dc-success-dark',
    rejected: 'inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase bg-dc-danger-tint text-dc-danger-dark',
  }
  return map[status] ?? 'text-dc-muted'
}
</script>
