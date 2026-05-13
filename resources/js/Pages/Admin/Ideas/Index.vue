<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Button from '@/Components/Button.vue'

defineOptions({ layout: AdminLayout })

interface Idea {
  id: number
  title: string
  status: string
  difficulty: string
  created_at: string
}

defineProps<{
  ideas: { data: Idea[]; current_page: number; last_page: number }
}>()

const deleteForm = useForm({})

function deleteIdea(id: number) {
  if (! confirm('Delete this idea? This cannot be undone.')) return
  deleteForm.delete(`/admin/ideas/${id}`)
}
</script>

<template>
  <Head title="Admin · Curated Ideas" />

  <div class="p-8 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted">
          Curated Ideas
        </h1>
        <p class="text-body text-dc-muted mt-1">
          Hand-picked project ideas for the community.
        </p>
      </div>
      <Link href="/admin/ideas/create">
        <Button variant="primary">+ New Idea</Button>
      </Link>
    </div>

    <div
      v-if="ideas.data.length === 0"
      class="bg-white dark:bg-dc-dark-surface rounded-lg p-12 text-center border border-dc-surface dark:border-dc-dark-border"
    >
      <div class="text-h2 text-dc-muted mb-2">No curated ideas yet</div>
      <p class="text-body text-dc-muted">
        Click "+ New Idea" to add the first one.
      </p>
    </div>

    <div
      v-else
      class="bg-white dark:bg-dc-dark-surface rounded-lg overflow-hidden border border-dc-surface dark:border-dc-dark-border"
    >
      <table class="w-full">
        <thead class="bg-dc-page-bg dark:bg-dc-dark-bg border-b border-dc-surface dark:border-dc-dark-border">
          <tr>
            <th class="text-left text-label-caps text-dc-muted px-6 py-3">Title</th>
            <th class="text-left text-label-caps text-dc-muted px-6 py-3">Status</th>
            <th class="text-left text-label-caps text-dc-muted px-6 py-3">Difficulty</th>
            <th class="text-right text-label-caps text-dc-muted px-6 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="idea in ideas.data"
            :key="idea.id"
            class="border-b border-dc-surface dark:border-dc-dark-border hover:bg-dc-page-bg/50 dark:hover:bg-dc-dark-bg/50"
          >
            <td class="px-6 py-4 text-body text-dc-primary-dark dark:text-dc-primary-muted font-medium">
              {{ idea.title }}
            </td>
            <td class="px-6 py-4">
              <span
                :class="[
                  'text-small px-2 py-1 rounded',
                  idea.status === 'featured' ? 'bg-dc-coral-tint text-dc-coral' :
                  idea.status === 'active' ? 'bg-dc-success-tint text-dc-success' :
                  'bg-dc-surface text-dc-muted'
                ]"
              >
                {{ idea.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-body text-dc-body dark:text-dc-primary-muted capitalize">
              {{ idea.difficulty }}
            </td>
            <td class="px-6 py-4 text-right space-x-3">
              <Link
                :href="`/admin/ideas/${idea.id}/edit`"
                class="text-small text-dc-primary hover:underline"
              >
                Edit
              </Link>
              <button
                @click="deleteIdea(idea.id)"
                class="text-small text-dc-danger hover:underline"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
