<template>
  <nav class="bg-white dark:bg-dc-dark-surface border-b border-[#E2E1F5] dark:border-dc-dark-border sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <Link href="/" class="flex-shrink-0 flex items-center">
            <Logo />
          </Link>
          <div class="hidden md:ml-8 md:flex md:space-x-8 h-full">
            <Link
              v-for="link in navLinks"
              :key="link.name"
              :href="link.path"
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-body font-medium transition-colors h-full',
                isActive(link.path)
                  ? 'border-dc-primary text-dc-primary dark:border-dc-primary-muted dark:text-dc-primary-muted'
                  : 'border-transparent text-dc-muted hover:text-[#2E2C28] dark:hover:text-dc-primary-muted'
              ]"
            >
              {{ link.name }}
              <span
                v-if="link.name === 'Messages' && $page.props.unreadMessagesCount > 0"
                class="ml-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-dc-coral rounded-full"
              >
                {{ $page.props.unreadMessagesCount }}
              </span>
            </Link>
          </div>
        </div>
        <div class="hidden md:flex md:items-center md:space-x-6">
          <!-- Admin Link -->
          <Link
            v-if="user?.is_admin"
            href="/admin"
            class="text-small font-medium px-3 py-1 rounded bg-dc-coral-tint text-dc-coral hover:bg-dc-coral hover:text-white transition-colors"
          >
            Admin
          </Link>

          <!-- Dark Mode Toggle -->
          <button
            @click="toggleDarkMode()"
            class="relative w-10 h-5 rounded-full bg-dc-muted dark:bg-dc-primary transition-colors focus:outline-none"
            aria-label="Toggle dark mode"
          >
            <div
              :class="[
                'absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform',
                isDarkMode ? 'translate-x-5' : 'translate-x-0'
              ]"
            />
          </button>

          <!-- Notification Bell -->
          <div class="relative" ref="notifRef">
            <button
              @click="toggleNotifDropdown"
              class="relative p-1 text-dc-muted hover:text-dc-primary focus:outline-none"
              aria-label="Notifications"
            >
              <Bell class="w-6 h-6" />
              <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-dc-coral rounded-full ring-2 ring-white dark:ring-dc-dark-surface"
              >
                {{ unreadCount }}
              </span>
            </button>

            <div
              v-if="isNotifDropdownOpen"
              class="absolute right-0 mt-2 w-80 z-50 rounded-md shadow-lg bg-white dark:bg-dc-dark-surface ring-1 ring-black ring-opacity-5 dark:ring-dc-dark-border"
            >
              <!-- Header -->
              <div class="flex items-center justify-between px-4 py-3 border-b border-[#E2E1F5] dark:border-dc-dark-border">
                <span class="text-body font-semibold text-dc-body dark:text-dc-primary-muted">Notifications</span>
                <button
                  @click="markAllRead"
                  :disabled="markingNotificationsRead"
                  class="text-small text-dc-primary hover:underline focus:outline-none"
                >
                  {{ markingNotificationsRead ? 'Marking...' : 'Mark all read' }}
                </button>
              </div>

              <!-- List -->
              <div class="max-h-80 overflow-y-auto divide-y divide-[#E2E1F5] dark:divide-dc-dark-border">
                <div v-if="notificationsLoading" class="px-4 py-6 text-center text-small text-dc-muted">
                  Loading notifications...
                </div>
                <div v-else-if="notificationsError" class="px-4 py-6 text-center text-small text-dc-danger">
                  {{ notificationsError }}
                </div>
                <template v-else-if="notifications.length > 0">
                  <button
                    v-for="n in notifications.slice(0, 10)"
                    :key="n.id"
                    @click="handleNotifClick(n)"
                    :class="[
                      'w-full text-left px-4 py-3 hover:bg-dc-surface dark:hover:bg-dc-dark-bg transition-colors',
                      !n.read_at ? 'bg-dc-surface dark:bg-dc-dark-bg' : ''
                    ]"
                  >
                    <p class="text-small font-medium text-dc-body dark:text-dc-primary-muted truncate">{{ n.data.title }}</p>
                    <p class="text-xs text-dc-muted mt-0.5 truncate">{{ n.data.message }}</p>
                    <p class="text-xs text-dc-muted mt-0.5">{{ timeAgo(n.created_at) }}</p>
                  </button>
                </template>
                <div v-else class="px-4 py-6 text-center text-small text-dc-muted">
                  No notifications
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-[#E2E1F5] dark:border-dc-dark-border px-4 py-2 text-center">
                <Link href="/notifications" class="text-small text-dc-primary hover:underline" @click="isNotifDropdownOpen = false">
                  View all
                </Link>
              </div>
            </div>
          </div>

          <!-- Profile Dropdown -->
          <div class="relative">
            <button
              @click="isProfileDropdownOpen = !isProfileDropdownOpen"
              class="flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dc-primary"
            >
              <AvatarInitials initials="JD" size="md" />
            </button>

            <div
              v-if="isProfileDropdownOpen"
              class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white dark:bg-dc-dark-surface ring-1 ring-black ring-opacity-5 dark:ring-dc-dark-border focus:outline-none"
            >
              <Link
                :href="`/profile/${user?.id}`"
                class="block px-4 py-2 text-body text-dc-body dark:text-dc-primary-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
              >
                Profile
              </Link>
              <Link
                href="/projects?mine=1"
                class="block px-4 py-2 text-body text-dc-body dark:text-dc-primary-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
                @click="isProfileDropdownOpen = false"
              >
                My Projects
              </Link>
              <Link
                href="/help-requests?tab=mine"
                class="block px-4 py-2 text-body text-dc-body dark:text-dc-primary-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
                @click="isProfileDropdownOpen = false"
              >
                My Q&A Posts
              </Link>
              <Link
                href="/mentor/dashboard"
                class="block px-4 py-2 text-body text-dc-body dark:text-dc-primary-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
              >
                Mentor Dashboard
              </Link>
              <Link
                href="/settings"
                class="block px-4 py-2 text-body text-dc-body dark:text-dc-primary-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
              >
                Settings
              </Link>
              <button
                @click="handleLogout"
                class="w-full text-left block px-4 py-2 text-body text-dc-body dark:text-dc-primary-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
              >
                Logout
              </button>
            </div>
          </div>
        </div>

        <!-- Mobile menu button -->
        <div class="flex items-center md:hidden">
          <button
            @click="isMobileMenuOpen = !isMobileMenuOpen"
            class="inline-flex items-center justify-center p-2 rounded-md text-dc-muted hover:text-dc-primary focus:outline-none"
          >
            <div class="w-6 h-5 flex flex-col justify-between">
              <div class="w-full h-0.5 bg-current rounded-full" />
              <div class="w-full h-0.5 bg-current rounded-full" />
              <div class="w-full h-0.5 bg-current rounded-full" />
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="isMobileMenuOpen" class="md:hidden border-t border-[#E2E1F5] dark:border-dc-dark-border">
      <div class="pt-2 pb-3 space-y-1">
        <Link
          v-for="link in navLinks"
          :key="link.name"
          :href="link.path"
          :class="[
            'flex items-center justify-between pl-3 pr-4 py-2 border-l-4 text-body font-medium',
            isActive(link.path)
              ? 'border-dc-primary text-dc-primary bg-dc-primary-tint dark:bg-dc-dark-bg dark:text-dc-primary-muted'
              : 'border-transparent text-dc-muted hover:bg-dc-surface hover:text-[#2E2C28] dark:hover:bg-dc-dark-bg dark:hover:text-dc-primary-muted'
          ]"
        >
          <span>{{ link.name }}</span>
          <span
            v-if="link.name === 'Messages' && $page.props.unreadMessagesCount > 0"
            class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-dc-coral rounded-full"
          >
            {{ $page.props.unreadMessagesCount }}
          </span>
        </Link>
      </div>
      <div class="pt-4 pb-3 border-t border-[#E2E1F5] dark:border-dc-dark-border">
        <div class="flex items-center px-4 justify-between">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <AvatarInitials initials="JD" size="md" />
            </div>
            <div class="ml-3">
              <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
                John Doe
              </div>
            </div>
          </div>
          <button
            @click="toggleDarkMode()"
            class="relative w-10 h-5 rounded-full bg-dc-muted dark:bg-dc-primary transition-colors focus:outline-none"
          >
            <div
              :class="[
                'absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform',
                isDarkMode ? 'translate-x-5' : 'translate-x-0'
              ]"
            />
          </button>
        </div>
        <div class="mt-3 space-y-1">
          <Link
            href="/profile"
            class="block px-4 py-2 text-body font-medium text-dc-muted hover:text-[#2E2C28] hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
          >
            Profile
          </Link>
          <Link
            href="/projects?mine=1"
            class="block px-4 py-2 text-body font-medium text-dc-muted hover:text-[#2E2C28] hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
          >
            My Projects
          </Link>
          <Link
            href="/help-requests?tab=mine"
            class="block px-4 py-2 text-body font-medium text-dc-muted hover:text-[#2E2C28] hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
          >
            My Help Requests
          </Link>
          <Link
            href="/settings"
            class="block px-4 py-2 text-body font-medium text-dc-muted hover:text-[#2E2C28] hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
          >
            Settings
          </Link>
          <button
            @click="handleLogout"
            class="w-full text-left block px-4 py-2 text-body font-medium text-dc-muted hover:text-[#2E2C28] hover:bg-dc-surface dark:hover:bg-dc-dark-bg"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'
import Logo from './Logo.vue'
import AvatarInitials from './AvatarInitials.vue'

const isDarkMode = ref(false)
const isMobileMenuOpen = ref(false)
const isProfileDropdownOpen = ref(false)
const isNotifDropdownOpen = ref(false)
const notifications = ref<any[]>([])
const notificationsLoading = ref(false)
const notificationsError = ref('')
const markingNotificationsRead = ref(false)
const notifRef = ref<HTMLElement | null>(null)

const page = usePage()
const user = page.props.auth.user
const unreadCount = computed(() => (page.props as any).notifications?.unread_count ?? 0)

const navLinks = [
  { name: 'Dashboard', path: '/dashboard' },
  { name: 'Projects', path: '/projects' },
  { name: 'Ideas', path: '/ideas' },
  { name: 'Contribute', path: '/contribute' },
  { name: 'Messages', path: '/messages' },
  { name: 'Mentors', path: '/mentors' },
  { name: 'Community Q&A', path: '/help-requests' },
]

const isActive = (path: string) => {
  if (path === '/dashboard') return page.url === '/dashboard'
  return page.url.startsWith(path)
}

router.on('navigate', () => {
  isMobileMenuOpen.value = false
  isProfileDropdownOpen.value = false
  isNotifDropdownOpen.value = false
})

const fetchNotifications = async () => {
  notificationsLoading.value = true
  notificationsError.value = ''
  try {
    const res = await fetch('/notifications', { headers: { 'Accept': 'application/json' } })
    if (!res.ok) throw new Error('Failed to load notifications')
    notifications.value = await res.json()
  } catch {
    notificationsError.value = 'Could not load notifications.'
  } finally {
    notificationsLoading.value = false
  }
}

const toggleNotifDropdown = () => {
  isNotifDropdownOpen.value = !isNotifDropdownOpen.value
  if (isNotifDropdownOpen.value) {
    fetchNotifications()
  }
}

const markAllRead = async () => {
  markingNotificationsRead.value = true
  notificationsError.value = ''
  try {
    const res = await fetch('/notifications/read-all', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'Accept': 'application/json',
      },
    })
    if (!res.ok) throw new Error('Failed to mark notifications read')
    router.reload({ only: ['notifications'] })
    isNotifDropdownOpen.value = false
  } catch {
    notificationsError.value = 'Could not mark notifications as read.'
  } finally {
    markingNotificationsRead.value = false
  }
}

const handleNotifClick = async (n: any) => {
  if (!n.read_at) {
    await fetch(`/notifications/${n.id}/read`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        'Accept': 'application/json',
      },
    })
  }
  isNotifDropdownOpen.value = false
  router.visit(n.data.url)
}

const timeAgo = (dateStr: string): string => {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h ago`
  return `${Math.floor(hrs / 24)}d ago`
}

const handleOutsideClick = (e: MouseEvent) => {
  if (notifRef.value && !notifRef.value.contains(e.target as Node)) {
    isNotifDropdownOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleOutsideClick))
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick))

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
}

const handleLogout = () => {
  router.post('/logout')
}
</script>
