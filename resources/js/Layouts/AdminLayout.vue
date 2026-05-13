<template>
  <div class="min-h-screen flex bg-dc-page-bg dark:bg-dc-dark-bg">
    <!-- Sidebar -->
    <aside class="w-60 bg-dc-primary-tint dark:bg-dc-dark-surface border-r border-dc-surface dark:border-dc-dark-border p-6">
      <div class="mb-8">
        <Logo />
      </div>

      <nav class="space-y-1">
        <Link
          href="/dashboard"
          class="block px-3 py-2 rounded text-body text-dc-body dark:text-dc-primary-muted hover:bg-white/50 dark:hover:bg-dc-dark-border transition-colors"
        >
          ← Back to dashboard
        </Link>

        <div class="pt-6 mt-6 border-t border-dc-surface dark:border-dc-dark-border space-y-1">
          <div class="text-label-caps text-dc-muted px-3 mb-2">OVERVIEW</div>
          <Link href="/admin" :class="navClass('/admin', true)">Dashboard</Link>
          <Link href="/admin/users" :class="navClass('/admin/users')">Users</Link>
          <Link href="/admin/mentors" :class="navClass('/admin/mentors')">Mentors</Link>
          <Link href="/admin/analytics" :class="navClass('/admin/analytics')">Analytics</Link>
        </div>

        <div class="pt-6 mt-6 border-t border-dc-surface dark:border-dc-dark-border space-y-1">
          <div class="text-label-caps text-dc-muted px-3 mb-2">CONTENT</div>
          <Link href="/admin/ideas" :class="navClass('/admin/ideas')">Curated Ideas</Link>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden">
      <FlashMessages />
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Logo from '@/Components/Logo.vue'
import FlashMessages from '@/Components/FlashMessages.vue'

const page = usePage()

function isCurrent(path: string): boolean {
  return page.url.startsWith(path)
}

function navClass(path: string, exact = false): string {
  const active = exact ? page.url === path : page.url.startsWith(path)
  return [
    'block px-3 py-2 rounded text-body transition-colors',
    active
      ? 'bg-dc-primary text-white font-medium'
      : 'text-dc-body dark:text-dc-primary-muted hover:bg-white/50 dark:hover:bg-dc-dark-border',
  ].join(' ')
}
</script>
