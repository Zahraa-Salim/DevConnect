<template>
  <Head title="Notifications" />

  <div class="max-w-3xl mx-auto p-4 md:p-8">
    <div class="flex items-start gap-3 mb-6">
      <button
        type="button"
        class="mt-1 inline-flex h-9 w-9 items-center justify-center rounded-md text-dc-muted hover:bg-dc-surface hover:text-dc-primary focus:outline-none focus:ring-2 focus:ring-dc-primary dark:hover:bg-dc-dark-surface"
        aria-label="Go back"
        @click="goBack"
      >
        <ArrowLeft class="h-5 w-5" />
      </button>
      <div>
        <h1 class="text-h1">Notifications</h1>
        <p class="text-body text-dc-muted mt-1">
          Recent activity and updates from your projects.
        </p>
      </div>
    </div>

    <Card v-if="notifications.length === 0" class="p-8 mt-6">
      <EmptyState message="No notifications yet." />
    </Card>

    <div v-else class="mt-6 space-y-3">
      <Card
        v-for="notification in notifications"
        :key="notification.id"
        :class="[
          'p-4 transition-colors hover:border-dc-primary',
          !notification.read_at ? 'bg-dc-primary-tint/40 dark:bg-dc-primary-dark/20' : ''
        ]"
      >
        <button
          type="button"
          class="block w-full text-left"
          @click="markUnread(notification)"
        >
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-body font-medium text-dc-primary-dark dark:text-dc-primary-muted">
                {{ notification.data?.title ?? 'Notification' }}
              </p>
              <p class="text-small text-dc-muted mt-1">
                {{ notification.data?.message ?? 'You have a new update.' }}
              </p>
              <p class="text-xs text-dc-muted mt-2">{{ timeAgo(notification.created_at) }}</p>
            </div>
            <span
              v-if="!notification.read_at"
              class="mt-1 h-2.5 w-2.5 rounded-full bg-dc-coral flex-shrink-0"
              aria-label="Unread"
            />
          </div>
        </button>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'

defineOptions({ layout: AuthenticatedLayout })

interface AppNotification {
  id: string
  read_at: string | null
  created_at: string
  data?: {
    title?: string
    message?: string
    url?: string
  }
}

defineProps<{
  notifications: AppNotification[]
}>()

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back()
    return
  }

  router.visit('/dashboard')
}

function markUnread(notification: AppNotification) {
  if (!notification.read_at) return

  fetch(`/notifications/${notification.id}/unread`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken(),
      'Accept': 'application/json',
    },
  }).then(() => {
    router.reload({ only: ['notifications'] })
  }).catch(() => {})
}

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.floor(hrs / 24)}d ago`
}
</script>
