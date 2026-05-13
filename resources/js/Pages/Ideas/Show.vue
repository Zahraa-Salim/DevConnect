<template>
  <Head :title="`${idea.title} - Ideas`" />

  <div class="max-w-4xl mx-auto p-4 md:p-8">
    <!-- Back Button -->
    <Link
      href="/ideas"
      class="inline-flex items-center text-body text-dc-primary hover:underline mb-8"
    >
      ← All ideas
    </Link>

    <!-- Header -->
    <div class="mb-8">
      <div class="flex flex-wrap gap-2 mb-4">
        <span
          :class="[
            'text-xs px-3 py-1 rounded font-medium',
            idea.source === 'curated'
              ? 'bg-dc-primary-tint text-dc-primary'
              : idea.source === 'community'
              ? 'bg-dc-coral-tint text-dc-coral'
              : 'bg-dc-blue-tint text-dc-blue'
          ]"
        >
          {{ idea.source }}
        </span>
        <span
          :class="[
            'text-xs px-3 py-1 rounded font-medium',
            idea.difficulty === 'beginner'
              ? 'bg-dc-success-tint text-dc-success'
              : idea.difficulty === 'intermediate'
              ? 'bg-dc-warning-tint text-dc-warning'
              : 'bg-dc-danger-tint text-dc-danger'
          ]"
        >
          {{ idea.difficulty }}
        </span>
        <span
          v-if="idea.domain"
          class="text-xs px-3 py-1 rounded bg-dc-surface dark:bg-dc-dark-border text-dc-body dark:text-dc-primary-muted font-medium"
        >
          {{ idea.domain }}
        </span>
      </div>

      <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted mb-4">
        {{ idea.title }}
      </h1>

      <div v-if="idea.team_size" class="text-body text-dc-muted mb-6">
        👥 Suggested team size: <strong>{{ idea.team_size }} people</strong>
      </div>
    </div>

    <!-- Description Card -->
    <Card class="mb-8 p-6 md:p-8">
      <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
        About this idea
      </h2>
      <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap text-body text-dc-body dark:text-dc-primary-muted">
        {{ idea.description }}
      </div>
    </Card>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column (wider) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Comments Section -->
        <Card class="p-6">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4 flex items-center justify-between">
            <span>Discussion ({{ idea.comments?.length || 0 }})</span>
          </h3>

          <!-- Comments List -->
          <div v-if="idea.comments && idea.comments.length > 0" class="space-y-4 mb-6">
            <div
              v-for="comment in idea.comments"
              :key="comment.id"
              class="flex gap-3 pb-4 border-b border-dc-surface dark:border-dc-dark-border last:border-b-0 last:pb-0"
            >
              <div class="flex-shrink-0">
                <div
                  v-if="!comment.user?.avatar_url"
                  class="w-8 h-8 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold text-xs"
                >
                  {{ comment.user?.name?.charAt(0).toUpperCase() }}
                </div>
                <img
                  v-else
                  :src="comment.user.avatar_url"
                  :alt="comment.user?.name"
                  class="w-8 h-8 rounded-full"
                />
              </div>
              <div class="flex-grow">
                <div class="flex items-center justify-between mb-1">
                  <div class="text-small font-medium text-dc-primary-dark dark:text-dc-primary-muted">
                    {{ comment.user?.name }}
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-dc-muted">
                      {{ formatDate(comment.created_at) }}
                    </span>
                    <button
                      v-if="isOwnComment(comment)"
                      @click="deleteComment(comment.id)"
                      class="text-xs text-dc-danger hover:text-dc-danger-dark"
                    >
                      Delete
                    </button>
                  </div>
                </div>
                <p class="text-body text-dc-body dark:text-dc-primary-muted whitespace-pre-wrap">
                  {{ comment.body }}
                </p>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8 text-dc-muted">
            <p>No comments yet. Be the first!</p>
          </div>

          <!-- Comment Form -->
          <div class="pt-4 border-t border-dc-surface dark:border-dc-dark-border mt-6">
            <form @submit.prevent="submitComment" class="space-y-3">
              <Textarea
                v-model="commentForm.body"
                placeholder="Share your thoughts about this idea..."
                :rows="3"
              />
              <div class="flex gap-2">
                <Button
                  type="submit"
                  class="flex-1"
                  :disabled="commentForm.processing || !commentForm.body.trim()"
                >
                  {{ commentForm.processing ? 'Posting...' : 'Post comment' }}
                </Button>
              </div>
              <span v-if="commentForm.errors.body" class="text-xs text-dc-danger">
                {{ commentForm.errors.body }}
              </span>
            </form>
          </div>
        </Card>

        <!-- Suggested Roles -->
        <Card v-if="idea.suggested_roles?.length" class="p-6">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            Suggested roles
          </h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="role in idea.suggested_roles"
              :key="role"
              class="px-3 py-2 rounded-full bg-dc-primary-tint text-dc-primary dark:bg-dc-primary-dark/20 dark:text-dc-primary-muted text-small font-medium"
            >
              {{ role }}
            </span>
          </div>
        </Card>

        <!-- Requirements -->
        <Card v-if="idea.requirements?.length" class="p-6">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            Requirements
          </h3>
          <ul class="space-y-2">
            <li
              v-for="req in idea.requirements"
              :key="req"
              class="flex items-start gap-3 text-body text-dc-body dark:text-dc-primary-muted"
            >
              <span class="text-dc-coral mt-1">✓</span>
              <span>{{ req }}</span>
            </li>
          </ul>
        </Card>
      </div>

      <!-- Right Column (sidebar) -->
      <div class="space-y-6">
        <!-- AI Generation Info (for AI-generated ideas) -->
        <Card v-if="idea.source === 'ai' && aiUsage" class="p-6 bg-dc-blue-tint/5 dark:bg-dc-blue-dark/10">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-3">
            ✨ Generated by AI
          </h3>
          <div class="space-y-2 text-small text-dc-muted mb-4">
            <div>{{ aiUsage.tokens }} tokens · ~${aiUsage.cost }}</div>
            <div>Model: {{ aiUsage.model }}</div>
          </div>
          <Button
            v-if="idea.submitted_by === $page.props.auth.user?.id"
            @click="regenerate"
            variant="ghost"
            fullWidth
            :disabled="isRegenerating"
            class="text-center"
          >
            {{ isRegenerating ? '⏳ Regenerating...' : '↻ Generate another' }}
          </Button>
        </Card>

        <!-- Submitter Info -->
        <Card v-if="idea.submitter && ['community', 'ai'].includes(idea.source)" class="p-6">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
            {{ idea.source === 'ai' ? 'Generated for' : 'Submitted by' }}
          </h3>
          <div class="flex items-center gap-3">
            <img
              v-if="idea.submitter.avatar_url"
              :src="idea.submitter.avatar_url"
              :alt="idea.submitter.name"
              class="w-12 h-12 rounded-full"
            />
            <div v-else class="w-12 h-12 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold">
              {{ idea.submitter.name?.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="text-body font-medium text-dc-primary-dark dark:text-dc-primary-muted">
                {{ idea.submitter.name }}
              </div>
              <div class="text-small text-dc-muted">
                {{ idea.source === 'ai' ? 'AI Generation' : 'Community member' }}
              </div>
            </div>
          </div>
        </Card>

        <!-- Vote Section -->
        <Card class="p-6">
          <div class="text-center">
            <Button
              @click="toggleVote"
              :class="[
                'w-full h-12 text-h3 font-bold',
                hasVoted
                  ? 'bg-dc-primary text-white'
                  : 'bg-dc-surface dark:bg-dc-dark-border text-dc-body hover:bg-dc-primary-tint'
              ]"
            >
              {{ hasVoted ? '▲' : '▲' }} {{ idea.upvotes }}
            </Button>
            <p class="text-small text-dc-muted mt-2">
              {{ hasVoted ? 'You upvoted this' : 'Upvote this idea' }}
            </p>
          </div>
        </Card>

        <!-- CTA Button -->
        <Button
          v-if="idea.status !== 'converted'"
          variant="primary"
          fullWidth
          @click="useIdea"
          class="h-12"
        >
          Use this idea
        </Button>
        <Button
          v-else
          variant="ghost"
          disabled
          fullWidth
          class="h-12"
        >
          Already converted to project
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Textarea from '@/Components/Textarea.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

interface IdeaComment {
  id: number
  body: string
  created_at: string
  user?: { id: number; name: string; avatar_url: string | null }
}

interface Props {
  idea: {
    id: number
    title: string
    description: string
    domain: string | null
    difficulty: string
    team_size: number | null
    source: string
    status: string
    upvotes: number
    comments_count: number
    submitted_by: number
    suggested_roles: string[] | null
    requirements: string[] | null
    submitter: { id: number; name: string; avatar_url: string | null } | null
    comments: IdeaComment[] | null
  }
  hasVoted: boolean
  aiUsage?: { tokens: number; cost: string; model: string } | null
}

const props = defineProps<Props>()
const isRegenerating = ref(false)
const page = usePage()
const currentUserId = computed(() => page.props.auth.user?.id)

const commentForm = useForm({
  body: '',
})

function useIdea() {
  router.visit(urls.projects.create(props.idea.id))
}

function toggleVote() {
  router.post(urls.ideas.vote(props.idea.id), {}, { preserveScroll: true })
}

function submitComment() {
  commentForm.post(urls.ideas.comments.store(props.idea.id), {
    onSuccess: () => {
      commentForm.reset()
    },
  })
}

function deleteComment(commentId: number) {
  if (confirm('Are you sure you want to delete this comment?')) {
    router.delete(urls.ideas.comments.destroy(props.idea.id, commentId), {
      preserveScroll: true,
    })
  }
}

function isOwnComment(comment: IdeaComment): boolean {
  return comment.user?.id === currentUserId.value
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

function regenerate() {
  if (isRegenerating.value) return

  isRegenerating.value = true

  router.post(urls.ideas.generate(), {
    domain_interest: props.idea.domain || '',
    team_size: props.idea.team_size || 3,
    weekly_hours: 5, // Default since we don't store the original value
    interests: '',
  }, {
    onFinish: () => {
      isRegenerating.value = false
    },
  })
}
</script>
