<template>
  <Head title="Community Q&A" />

  <div class="max-w-6xl mx-auto p-4 md:p-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <PageHeader
        title="Community Q&A"
        subtitle="Ask the community, share answers, and jump into a DM when a thread needs focus."
        class="mb-0"
      />
      <Button @click="showForm = !showForm" variant="ghost">
        {{ showForm ? 'Cancel' : '+ Ask the community' }}
      </Button>
    </div>

    <Card v-if="showForm" class="p-6 mb-8">
      <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">Ask a question</h2>
      <form @submit.prevent="postRequest">
        <div class="mb-4">
          <label class="text-small font-medium text-dc-primary-dark dark:text-dc-primary-muted block mb-1">Title</label>
          <TextInput
            v-model="form.title"
            placeholder="e.g. How do I optimise this N+1 query in Laravel?"
            :class="formErrors.title ? 'border-dc-danger' : ''"
          />
          <p v-if="formErrors.title" class="text-small text-dc-danger mt-1">{{ formErrors.title }}</p>
        </div>

        <div class="mb-4">
          <label class="text-small font-medium text-dc-primary-dark dark:text-dc-primary-muted block mb-1">Description</label>
          <Textarea
            v-model="form.description"
            :rows="5"
            placeholder="Describe what you're trying to do, what you've tried, and where you're stuck..."
            :class="formErrors.description ? 'border-dc-danger' : ''"
          />
          <p v-if="formErrors.description" class="text-small text-dc-danger mt-1">{{ formErrors.description }}</p>
        </div>

        <div class="mb-6">
          <label class="text-small font-medium text-dc-primary-dark dark:text-dc-primary-muted block mb-2">Tech tags</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tag in allTags"
              :key="tag"
              type="button"
              @click="toggleTag(tag)"
              :class="tagPillClass(form.tech_tags.includes(tag))"
            >
              {{ tag }}
            </button>
          </div>
          <p v-if="formErrors.tech_tags" class="text-small text-dc-danger mt-1">{{ formErrors.tech_tags }}</p>
        </div>

        <div class="flex justify-end">
          <Button type="submit" :isLoading="posting">Post Request</Button>
        </div>
      </form>
    </Card>

    <div class="flex border-b border-dc-surface dark:border-dc-dark-border mb-6">
      <button
        v-for="tab in ['All Questions', 'My questions']"
        :key="tab"
        @click="activeTab = tab"
        :class="[
          'px-4 py-2 text-small font-medium border-b-2 -mb-px transition-colors',
          activeTab === tab
            ? 'border-dc-primary text-dc-primary'
            : 'border-transparent text-dc-muted hover:text-dc-primary'
        ]"
      >
        {{ tab }}
      </button>
    </div>

    <div v-if="activeTab === 'All Questions'" class="flex flex-wrap gap-2 mb-6">
      <button @click="setTag(null)" :class="tagPillClass(!activeTag)">All</button>
      <button
        v-for="tag in allTags"
        :key="tag"
        @click="setTag(tag)"
        :class="tagPillClass(activeTag === tag)"
      >
        {{ tag }}
      </button>
    </div>

    <div v-if="activeTab === 'All Questions'">
      <div v-if="filteredRequests.length === 0" class="text-body text-dc-muted text-center py-12">
        No open questions{{ activeTag ? ` tagged "${activeTag}"` : '' }}.
      </div>
      <div class="space-y-4">
        <HelpRequestCard
          v-for="req in filteredRequests"
          :key="req.id"
          :request="req"
          :is-approved-mentor="isApprovedMentor"
          :current-user-id="currentUserId"
          :claiming="claiming"
          :resolving="resolving"
          @open="openDetail"
          @message="messagePoster"
          @claim="claimRequest"
          @resolve="resolveRequest"
        />
      </div>

      <div v-if="requests.last_page > 1" class="flex flex-wrap justify-center gap-2 mt-8">
        <Button
          v-for="page in requests.last_page"
          :key="page"
          size="sm"
          :variant="page === requests.current_page ? 'primary' : 'ghost'"
          @click="goToPage(page)"
        >
          {{ page }}
        </Button>
      </div>
    </div>

    <div v-else>
      <div v-if="myRequests.length === 0" class="text-body text-dc-muted text-center py-12">
        You haven't posted any questions yet.
      </div>
      <div class="space-y-4">
        <HelpRequestCard
          v-for="req in normalizedMyRequests"
          :key="req.id"
          :request="req"
          :is-approved-mentor="isApprovedMentor"
          :current-user-id="currentUserId"
          :claiming="claiming"
          :resolving="resolving"
          @open="openDetail"
          @message="messagePoster"
          @claim="claimRequest"
          @resolve="resolveRequest"
        />
      </div>
    </div>
  </div>

  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="detailOpen"
        class="fixed inset-0 z-40 bg-dc-body/40 dark:bg-dc-dark-bg/70"
        @click="closeDetail"
      />
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      leave-active-class="transition-transform duration-200 ease-in"
      enter-from-class="translate-x-full"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="detailOpen && selectedRequest"
        class="fixed right-0 top-0 bottom-0 z-50 w-full max-w-xl bg-white dark:bg-dc-dark-surface border-l border-dc-surface dark:border-dc-dark-border shadow-xl flex flex-col"
      >
        <div class="flex items-start justify-between gap-4 p-5 border-b border-dc-surface dark:border-dc-dark-border">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <Badge :variant="statusVariant(selectedRequest.status)">{{ statusLabel(selectedRequest.status) }}</Badge>
              <Badge v-for="tag in selectedRequest.tech_tags ?? []" :key="tag" variant="skill">{{ tag }}</Badge>
            </div>
            <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted leading-tight">
              {{ selectedRequest.title }}
            </h2>
          </div>
          <button
            type="button"
            class="w-9 h-9 rounded-md text-dc-muted hover:text-dc-primary hover:bg-dc-surface dark:hover:bg-dc-dark-bg transition-colors"
            @click="closeDetail"
            aria-label="Close"
          >
            ×
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">
          <div class="flex items-center gap-3 mb-5">
            <Avatar :user="posterFor(selectedRequest)" />
            <div>
              <p class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
                {{ posterFor(selectedRequest)?.name ?? 'Unknown' }}
              </p>
              <p class="text-small text-dc-muted">{{ timeAgo(selectedRequest.created_at) }}</p>
            </div>
          </div>

          <p class="text-body text-dc-body dark:text-dc-primary-muted whitespace-pre-wrap mb-5">
            {{ selectedRequest.description }}
          </p>

          <div class="flex flex-wrap gap-2 mb-8">
            <Button
              v-if="!isOwnRequest(selectedRequest)"
              size="sm"
              variant="ghost"
              @click="messagePoster(selectedRequest.id)"
            >
              Message poster
            </Button>
            <Button
              v-if="isApprovedMentor && selectedRequest.status === 'open'"
              size="sm"
              :isLoading="claiming === selectedRequest.id"
              @click="claimRequest(selectedRequest.id)"
            >
              I can help
            </Button>
            <Button
              v-if="selectedRequest.status === 'open' || selectedRequest.status === 'in_progress'"
              size="sm"
              variant="ghost"
              :isLoading="resolving === selectedRequest.id"
              @click="resolveRequest(selectedRequest.id)"
            >
              Mark Resolved
            </Button>
          </div>

          <section>
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">Answers</h3>
              <span class="text-small text-dc-muted">{{ detailComments.length }} total</span>
            </div>

            <div v-if="commentsLoading" class="text-small text-dc-muted py-4">
              Loading answers...
            </div>
            <div v-else-if="detailComments.length === 0" class="text-body text-dc-muted py-6 text-center border border-dc-surface dark:border-dc-dark-border rounded-md">
              No answers yet.
            </div>
            <div v-else class="space-y-4">
              <article
                v-for="comment in detailComments"
                :key="comment.id"
                class="p-4 rounded-md border border-dc-surface dark:border-dc-dark-border bg-dc-surface/60 dark:bg-dc-dark-bg"
              >
                <div class="flex items-center gap-3 mb-2">
                  <Avatar :user="comment.user" size="sm" />
                  <div>
                    <p class="text-small font-medium text-dc-body dark:text-dc-primary-muted">
                      {{ comment.user?.name ?? 'Unknown' }}
                    </p>
                    <p class="text-xs text-dc-muted">{{ timeAgo(comment.created_at) }}</p>
                  </div>
                </div>
                <p class="text-body text-dc-body dark:text-dc-primary-muted whitespace-pre-wrap">{{ comment.body }}</p>
              </article>
            </div>
          </section>
        </div>

        <form class="p-5 border-t border-dc-surface dark:border-dc-dark-border" @submit.prevent="postComment">
          <Textarea
            v-model="commentBody"
            :rows="3"
            :maxLength="2000"
            placeholder="Write an answer..."
            :error="commentError"
          />
          <div class="flex justify-end mt-3">
            <Button type="submit" :isLoading="commentPosting" :disabled="!commentBody.trim()">
              Post Answer
            </Button>
          </div>
        </form>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<{
  requests: any
  myRequests: any[]
  isApprovedMentor: boolean
}>()

type HelpRequest = {
  id: number
  title: string
  description: string
  status: string
  tech_tags?: string[]
  created_at: string
  user_id: number
  user?: User
  comments_count?: number
}

type User = {
  id: number
  name: string
  avatar_url?: string | null
  role?: string | null
}

type HelpRequestComment = {
  id: number
  body: string
  created_at: string
  user?: User
}

const allTags = [
  'JavaScript', 'TypeScript', 'Python', 'PHP', 'Go', 'Rust', 'Java',
  'React', 'Vue', 'Angular', 'Laravel', 'Django', 'Node.js',
  'PostgreSQL', 'MySQL', 'MongoDB', 'Redis',
  'DevOps', 'Docker', 'AWS', 'UI/UX', 'Mobile', 'Security',
]

const page = usePage()
const currentUser = computed(() => (page.props.auth as any)?.user as User | null)
const currentUserId = computed(() => currentUser.value?.id ?? null)
const urlParams = new URLSearchParams(window.location.search)
const activeTab = ref(urlParams.get('tab') === 'mine' ? 'My questions' : 'All Questions')
const activeTag = ref<string | null>(null)
const showForm = ref(false)
const posting = ref(false)
const claiming = ref<number | null>(null)
const resolving = ref<number | null>(null)
const detailOpen = ref(false)
const selectedRequest = ref<HelpRequest | null>(null)
const detailComments = ref<HelpRequestComment[]>([])
const commentsLoading = ref(false)
const commentBody = ref('')
const commentError = ref('')
const commentPosting = ref(false)

const form = reactive({
  title: '',
  description: '',
  tech_tags: [] as string[],
})
const formErrors = reactive<Record<string, string>>({})

const filteredRequests = computed<HelpRequest[]>(() => {
  const data = props.requests?.data ?? []
  if (!activeTag.value) return data
  return data.filter((r: HelpRequest) => (r.tech_tags ?? []).includes(activeTag.value as string))
})

const normalizedMyRequests = computed<HelpRequest[]>(() => {
  return props.myRequests.map((request) => ({
    ...request,
    user: request.user ?? currentUser.value,
  }))
})

const Avatar = defineComponent({
  props: {
    user: { type: Object, required: false },
    size: { type: String, default: 'md' },
  },
  setup(avatarProps) {
    return () => {
      const user = avatarProps.user as User | undefined
      const sizeClass = avatarProps.size === 'sm' ? 'w-8 h-8 text-xs' : 'w-10 h-10 text-small'
      if (user?.avatar_url) {
        return h('img', {
          src: user.avatar_url,
          alt: user.name,
          class: `${sizeClass} rounded-full object-cover flex-shrink-0`,
        })
      }

      return h(
        'div',
        {
          class: `${sizeClass} rounded-full bg-dc-primary-tint text-dc-primary-dark dark:text-dc-primary-muted flex items-center justify-center font-semibold flex-shrink-0`,
        },
        initials(user?.name)
      )
    }
  },
})

const HelpRequestCard = defineComponent({
  components: { Avatar, Badge, Button, Card },
  props: {
    request: { type: Object, required: true },
    isApprovedMentor: { type: Boolean, required: true },
    currentUserId: { type: Number, required: false },
    claiming: { type: Number, required: false },
    resolving: { type: Number, required: false },
  },
  emits: ['open', 'message', 'claim', 'resolve'],
  setup(cardProps, { emit }) {
    const req = cardProps.request as HelpRequest
    const isOwn = computed(() => req.user_id === cardProps.currentUserId)

    return {
      req,
      isOwn,
      emit,
      statusVariant,
      statusLabel,
      timeAgo,
      posterFor,
    }
  },
  template: `
    <Card
      class="p-5 cursor-pointer hover:border-dc-primary transition-colors"
      role="button"
      tabindex="0"
      @click="emit('open', req)"
      @keydown.enter.prevent="emit('open', req)"
    >
      <div class="flex items-start justify-between gap-3 mb-3">
        <h3 class="text-body font-semibold text-dc-primary-dark dark:text-dc-primary-muted leading-snug">
          {{ req.title }}
        </h3>
        <Badge :variant="statusVariant(req.status)">{{ statusLabel(req.status) }}</Badge>
      </div>
      <p class="text-small text-dc-muted mb-3 line-clamp-3">{{ req.description }}</p>
      <div class="flex flex-wrap gap-1.5 mb-4">
        <Badge v-for="tag in (req.tech_tags ?? [])" :key="tag" variant="skill">{{ tag }}</Badge>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <Avatar :user="posterFor(req)" size="sm" />
          <div class="min-w-0">
            <p class="text-small font-medium text-dc-body dark:text-dc-primary-muted truncate">
              {{ posterFor(req)?.name ?? 'Unknown' }}
            </p>
            <p class="text-xs text-dc-muted">{{ timeAgo(req.created_at) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <span class="px-2 py-1 rounded-full bg-dc-surface dark:bg-dc-dark-border text-xs text-dc-muted">
            {{ req.comments_count ?? 0 }} answers
          </span>
          <button
            v-if="!isOwn"
            type="button"
            class="text-small text-dc-primary hover:underline"
            @click.stop="emit('message', req.id)"
          >
            💬 Message
          </button>
          <Button
            v-if="isApprovedMentor && req.status === 'open'"
            size="sm"
            :isLoading="claiming === req.id"
            @click.stop="emit('claim', req.id)"
          >
            I can help
          </Button>
          <Button
            v-if="req.status === 'open' || req.status === 'in_progress'"
            size="sm"
            variant="ghost"
            :isLoading="resolving === req.id"
            @click.stop="emit('resolve', req.id)"
          >
            Resolve
          </Button>
        </div>
      </div>
    </Card>
  `,
})

function tagPillClass(active: boolean): string {
  return [
    'px-3 py-1.5 rounded-full text-small font-medium transition-colors',
    active
      ? 'bg-dc-primary text-white'
      : 'bg-dc-surface dark:bg-dc-dark-border text-dc-muted hover:text-dc-primary hover:bg-dc-primary-tint',
  ].join(' ')
}

function setTag(tag: string | null) {
  activeTag.value = tag
}

function statusVariant(status: string): 'open' | 'in-progress' | 'completed' | 'declined' | 'pending' | 'skill' {
  const map: Record<string, any> = {
    open: 'open',
    in_progress: 'in-progress',
    resolved: 'completed',
    closed: 'declined',
  }
  return map[status] ?? 'skill'
}

function statusLabel(status: string): string {
  return status.replace(/_/g, ' ')
}

function timeAgo(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return 'just now'
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

function initials(name?: string): string {
  return (name ?? '?')
    .split(' ')
    .map(part => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

function posterFor(request: HelpRequest): User | null {
  if (request.user) return request.user
  if (request.user_id === currentUser.value?.id) return currentUser.value
  return null
}

function isOwnRequest(request: HelpRequest): boolean {
  return request.user_id === currentUserId.value
}

function toggleTag(tag: string) {
  const idx = form.tech_tags.indexOf(tag)
  if (idx === -1) form.tech_tags.push(tag)
  else form.tech_tags.splice(idx, 1)
}

function validateForm(): boolean {
  Object.keys(formErrors).forEach(k => delete formErrors[k])
  let ok = true
  if (!form.title.trim()) { formErrors.title = 'Title is required.'; ok = false }
  if (!form.description.trim()) { formErrors.description = 'Description is required.'; ok = false }
  if (form.tech_tags.length === 0) { formErrors.tech_tags = 'Select at least one tag.'; ok = false }
  return ok
}

function postRequest() {
  if (!validateForm()) return
  posting.value = true
  router.post('/help-requests', form, {
    preserveScroll: true,
    onSuccess: () => {
      showForm.value = false
      form.title = ''
      form.description = ''
      form.tech_tags = []
    },
    onError: (errors) => { Object.assign(formErrors, errors) },
    onFinish: () => { posting.value = false },
  })
}

function openDetail(request: HelpRequest) {
  selectedRequest.value = request
  detailOpen.value = true
  commentBody.value = ''
  commentError.value = ''
  loadComments(request.id)
}

function closeDetail() {
  detailOpen.value = false
}

async function loadComments(id: number) {
  commentsLoading.value = true
  try {
    const response = await fetch(`/help-requests/${id}/comments`, {
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) throw new Error('Unable to load comments.')
    detailComments.value = await response.json()
  } catch {
    detailComments.value = []
    commentError.value = 'Unable to load answers right now.'
  } finally {
    commentsLoading.value = false
  }
}

function postComment() {
  if (!selectedRequest.value || !commentBody.value.trim()) return

  commentPosting.value = true
  commentError.value = ''
  router.post(`/help-requests/${selectedRequest.value.id}/comments`, { body: commentBody.value }, {
    preserveScroll: true,
    onSuccess: () => {
      commentBody.value = ''
      if (selectedRequest.value) {
        selectedRequest.value.comments_count = (selectedRequest.value.comments_count ?? 0) + 1
        loadComments(selectedRequest.value.id)
      }
    },
    onError: (errors) => {
      commentError.value = (errors.body as string) ?? 'Unable to post this answer.'
    },
    onFinish: () => { commentPosting.value = false },
  })
}

function messagePoster(id: number) {
  router.post(`/help-requests/${id}/dm`, {}, { preserveScroll: true })
}

function claimRequest(id: number) {
  claiming.value = id
  router.post(`/help-requests/${id}/claim`, {}, {
    preserveScroll: true,
    onFinish: () => { claiming.value = null },
  })
}

function resolveRequest(id: number) {
  resolving.value = id
  router.post(`/help-requests/${id}/resolve`, {}, {
    preserveScroll: true,
    onFinish: () => { resolving.value = null },
  })
}

function goToPage(page: number) {
  router.get('/help-requests', { page, tag: activeTag.value }, { preserveScroll: true })
}
</script>
