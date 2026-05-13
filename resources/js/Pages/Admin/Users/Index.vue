<template>
  <Head title="Admin · Users" />

  <div class="p-8">
    <div class="mb-6">
      <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted">Users</h1>
      <p class="text-body text-dc-muted mt-1">{{ users.total }} total users</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <input
        v-model="search"
        type="text"
        placeholder="Search by name or email…"
        class="flex-1 px-4 py-2 rounded-lg border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface text-body text-dc-body dark:text-dc-primary-muted placeholder:text-dc-muted focus:outline-none focus:ring-2 focus:ring-dc-primary"
        @input="onSearch"
      />
      <select
        v-model="role"
        class="px-4 py-2 rounded-lg border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface text-body text-dc-body dark:text-dc-primary-muted focus:outline-none focus:ring-2 focus:ring-dc-primary"
        @change="applyFilters"
      >
        <option value="">All roles</option>
        <option value="dev">Dev</option>
        <option value="designer">Designer</option>
        <option value="pm">PM</option>
        <option value="mentor">Mentor</option>
        <option value="exploring">Exploring</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-dc-dark-surface rounded-xl border border-dc-surface dark:border-dc-dark-border overflow-x-auto mb-6">
      <table class="w-full text-small">
        <thead>
          <tr class="border-b border-dc-surface dark:border-dc-dark-border text-dc-muted uppercase text-label-caps">
            <th class="px-4 py-3 text-left">User</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-right">Rep</th>
            <th class="px-4 py-3 text-center">Verified</th>
            <th class="px-4 py-3 text-left">Joined</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in users.data"
            :key="user.id"
            class="border-b border-dc-surface dark:border-dc-dark-border last:border-0 hover:bg-dc-surface/40 dark:hover:bg-dc-dark-border/20 transition-colors"
          >
            <!-- User -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <img
                  v-if="user.avatar_url"
                  :src="user.avatar_url"
                  :alt="user.name"
                  class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                />
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold text-xs flex-shrink-0"
                >
                  {{ user.name?.charAt(0).toUpperCase() }}
                </div>
                <span class="font-medium text-dc-primary-dark dark:text-dc-primary-muted whitespace-nowrap">
                  {{ user.name }}
                </span>
              </div>
            </td>

            <!-- Email -->
            <td class="px-4 py-3 text-dc-muted">{{ user.email }}</td>

            <!-- Role -->
            <td class="px-4 py-3">
              <span class="capitalize text-dc-body dark:text-dc-primary-muted">{{ user.role }}</span>
            </td>

            <!-- Rep -->
            <td class="px-4 py-3 text-right font-mono text-dc-primary-dark dark:text-dc-primary-muted">
              {{ Number(user.reputation_score ?? 0).toFixed(0) }}
            </td>

            <!-- Verified -->
            <td class="px-4 py-3 text-center">
              <span v-if="user.is_verified" class="text-dc-success text-base">✓</span>
              <span v-else class="text-dc-muted">—</span>
            </td>

            <!-- Joined -->
            <td class="px-4 py-3 text-dc-muted whitespace-nowrap">
              {{ formatDate(user.created_at) }}
            </td>

            <!-- Status -->
            <td class="px-4 py-3 text-center">
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded-full text-label-caps uppercase font-medium',
                  user.suspended_at
                    ? 'bg-dc-danger-tint text-dc-danger-dark'
                    : 'bg-dc-success-tint text-dc-success-dark'
                ]"
              >
                {{ user.suspended_at ? 'Suspended' : 'Active' }}
              </span>
            </td>

            <!-- Actions -->
            <td class="px-4 py-3 text-right">
              <button
                v-if="!user.suspended_at"
                class="text-small text-dc-danger hover:underline disabled:opacity-50"
                :disabled="acting === user.id"
                @click="suspend(user)"
              >
                Suspend
              </button>
              <button
                v-else
                class="text-small text-dc-success hover:underline disabled:opacity-50"
                :disabled="acting === user.id"
                @click="unsuspend(user)"
              >
                Unsuspend
              </button>
            </td>
          </tr>

          <tr v-if="users.data.length === 0">
            <td colspan="8" class="px-4 py-10 text-center text-dc-muted">No users found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="users.last_page > 1" class="flex justify-center gap-1">
      <button
        v-for="page in users.last_page"
        :key="page"
        @click="goToPage(page)"
        :class="[
          'px-3 py-1.5 rounded text-small font-medium transition-colors',
          page === users.current_page
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
  users: {
    data: any[]
    current_page: number
    last_page: number
    total: number
  }
  filters: { search?: string; role?: string }
}>()

const search = ref(props.filters.search ?? '')
const role   = ref(props.filters.role ?? '')
const acting = ref<number | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onSearch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 350)
}

function applyFilters() {
  router.get('/admin/users', { search: search.value, role: role.value }, { preserveScroll: true, replace: true })
}

function goToPage(page: number) {
  router.get('/admin/users', { search: search.value, role: role.value, page }, { preserveScroll: true })
}

function suspend(user: any) {
  if (!confirm(`Suspend ${user.name}? They will not be able to log in.`)) return
  acting.value = user.id
  router.post(`/admin/users/${user.id}/suspend`, {}, { onFinish: () => { acting.value = null } })
}

function unsuspend(user: any) {
  acting.value = user.id
  router.post(`/admin/users/${user.id}/unsuspend`, {}, { onFinish: () => { acting.value = null } })
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>
