<template>
  <Head title="Messages" />

  <div class="flex flex-col md:flex-row h-[calc(100vh-64px)] bg-dc-page-bg dark:bg-dc-dark-bg">
    <!-- Left Sidebar - Conversation List -->
    <div class="w-full md:w-[280px] border-r border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface flex flex-col h-1/3 md:h-full">
      <!-- Header -->
      <div class="p-4 border-b border-dc-surface dark:border-dc-dark-border space-y-3">
        <div class="flex justify-between items-center">
          <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted">
            Messages
          </h2>
          <button
            @click="showNewDmModal = true"
            class="text-dc-primary hover:underline text-small font-medium"
          >
            + New
          </button>
        </div>
        <TextInput
          v-model="searchQuery"
          placeholder="Search conversations..."
          class="w-full"
        />

        <!-- Filter Tabs -->
        <div class="flex space-x-1">
          <button
            v-for="tab in ['All', 'DMs', 'Groups']"
            :key="tab"
            @click="filterType = tab"
            :class="[
              'px-3 py-1 text-small rounded-full transition-colors',
              filterType === tab ? 'bg-dc-primary text-white' : 'text-dc-muted hover:bg-dc-surface dark:hover:bg-dc-dark-bg'
            ]"
          >
            {{ tab }}
          </button>
        </div>
      </div>

      <!-- Conversation List -->
      <div class="flex-1 overflow-y-auto">
        <div
          v-for="conv in conversations"
          :key="conv.id"
          @click="selectConversation(conv.id)"
          :class="[
            'p-4 flex items-start space-x-3 cursor-pointer transition-colors border-b border-dc-surface dark:border-dc-dark-border md:border-none',
            activeConversationId === conv.id
              ? 'bg-dc-primary-tint dark:bg-dc-primary-dark'
              : 'hover:bg-dc-page-bg dark:hover:bg-dc-dark-bg'
          ]"
        >
          <AvatarInitials :initials="conv.initials" size="md" />
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-baseline">
              <span
                :class="[
                  'truncate',
                  conv.unread ? 'font-medium text-dc-body dark:text-white' : 'text-dc-body dark:text-dc-primary-muted'
                ]"
              >
                {{ conv.name }}
              </span>
              <span class="text-small text-dc-muted whitespace-nowrap ml-2">
                {{ conv.timestamp }}
              </span>
            </div>
            <p class="text-small text-dc-muted truncate mt-0.5">
              {{ conv.preview }}
            </p>
          </div>
          <div v-if="conv.unread" class="w-2 h-2 rounded-full bg-dc-primary mt-2 flex-shrink-0" />
        </div>
      </div>
    </div>

    <!-- Right Panel - Active Chat -->
    <div class="flex-1 flex flex-col h-2/3 md:h-full bg-dc-page-bg dark:bg-dc-dark-bg">
      <template v-if="activeConversation">
        <!-- Header -->
        <div class="p-4 bg-white dark:bg-dc-dark-surface border-b border-dc-surface dark:border-dc-dark-border flex items-center space-x-3 shadow-sm z-10">
          <AvatarInitials
            :initials="activeConversation.initials || 'U'"
            size="sm"
          />
          <span class="font-medium text-dc-body dark:text-dc-primary-muted">
            {{ activeConversation.name }}
          </span>
        </div>

        <!-- Messages Area -->
        <div
          ref="messagesContainer"
          class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6"
        >
          <div
            v-for="message in messages"
            :key="message.id"
            :class="[
              'flex',
              message.sender.id === $page.props.auth.user.id ? 'flex-row-reverse space-x-reverse' : 'space-x-3'
            ]"
          >
            <AvatarInitials
              :initials="message.sender.initials || message.sender.name.substring(0, 2)"
              size="md"
            />
            <div :class="message.sender.id === $page.props.auth.user.id ? 'flex flex-col items-end' : ''">
              <div
                :class="[
                  'flex items-baseline',
                  message.sender.id === $page.props.auth.user.id ? 'flex-row-reverse space-x-reverse space-x-2' : 'space-x-2'
                ]"
              >
                <span class="font-medium text-dc-body dark:text-dc-primary-muted text-small">
                  {{ message.sender.id === $page.props.auth.user.id ? `${message.sender.name} (You)` : message.sender.name }}
                </span>
                <span class="text-small text-dc-muted">
                  {{ formatTime(message.created_at) }}
                </span>
              </div>
              <div
                v-if="!message.deleted_at"
                :class="[
                  'p-3 rounded-lg mt-1 shadow-sm',
                  message.sender.id === $page.props.auth.user.id
                    ? 'bg-dc-primary-tint dark:bg-dc-primary-dark rounded-br-none text-dc-primary-dark dark:text-white'
                    : 'bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border rounded-tl-none'
                ]"
              >
                <p class="text-body">{{ message.body }}</p>
                <p
                  v-if="message.edited_at"
                  class="text-small italic text-dc-muted mt-1"
                >
                  (edited)
                </p>
              </div>
              <div
                v-else
                class="p-3 rounded-lg mt-1 italic text-dc-muted text-small"
              >
                This message was deleted
              </div>
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="typingUser" class="flex space-x-3">
            <AvatarInitials
              :initials="typingUser.name.substring(0, 2)"
              size="md"
            />
            <div class="bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border p-3 rounded-lg rounded-tl-none mt-1 shadow-sm">
              <ThreeDotBounce />
            </div>
          </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white dark:bg-dc-dark-surface border-t border-dc-surface dark:border-dc-dark-border flex items-end space-x-3">
          <div class="flex-1">
            <p v-if="messageError" class="text-small text-dc-danger mb-2">
              {{ messageError }}
            </p>
            <Textarea
              v-model="newMessage"
              @keydown="handleKeydown"
              @input="handleInput"
              placeholder="Type a message..."
              rows="1"
              class="min-h-[44px] resize-none"
            />
          </div>
          <Button
            variant="primary"
            :disabled="isSending || !newMessage.trim()"
            :isLoading="isSending"
            @click="sendMessage"
          >
            Send
          </Button>
        </div>
      </template>

      <template v-else>
        <div class="flex-1 flex items-center justify-center p-8">
          <EmptyState message="Select a conversation or start a new one." />
        </div>
      </template>
    </div>
  </div>

  <!-- New DM Modal -->
  <div
    v-if="showNewDmModal"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    @click.self="showNewDmModal = false"
  >
    <div class="bg-white dark:bg-dc-dark-surface rounded-lg p-6 w-96 shadow-lg">
      <h3 class="text-h2 mb-4">Start a new message</h3>
      <TextInput
        v-model="userSearchQuery"
        @input="searchUsers"
        placeholder="Search users..."
        class="w-full mb-4"
      />
      <div class="space-y-2 max-h-64 overflow-y-auto">
        <div v-if="dmSearchLoading" class="text-small text-dc-muted text-center py-4">
          Searching...
        </div>
        <div v-else-if="dmError" class="text-small text-dc-danger text-center py-4">
          {{ dmError }}
        </div>
        <div
          v-else-if="userSearchQuery.length >= 2 && searchResults.length === 0"
          class="text-small text-dc-muted text-center py-4"
        >
          No users found.
        </div>
        <template v-else>
          <button
            v-for="user in searchResults"
            :key="user.id"
            @click="startDm(user)"
            class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-dc-page-bg dark:hover:bg-dc-dark-bg transition-colors"
          >
            <AvatarInitials :initials="(user.name || '').substring(0, 2)" size="md" />
            <div class="text-left flex-1">
              <p class="font-medium text-dc-body dark:text-dc-primary-muted">{{ user.name }}</p>
              <p class="text-small text-dc-muted">{{ user.role }}</p>
            </div>
          </button>
        </template>
      </div>
      <Button
        variant="ghost"
        class="w-full mt-4"
        @click="showNewDmModal = false"
      >
        Cancel
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AvatarInitials from '@/Components/AvatarInitials.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import ThreeDotBounce from '@/Components/ThreeDotBounce.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

const page = usePage()
const props = defineProps({
  conversations: Array,
  activeConversation: Object,
  messages: Array,
})

const activeConversationId = ref(props.activeConversation?.id || null)
const newMessage = ref('')
const isSending = ref(false)
const messageError = ref('')
const showNewDmModal = ref(false)
const userSearchQuery = ref('')
const searchQuery = ref('')
const searchResults = ref([])
const dmSearchLoading = ref(false)
const dmError = ref('')
const typingUser = ref(null)
const messagesContainer = ref(null)
const filterType = ref('All')
let typingTimeout = null
let lastTypingSent = 0

const messages = ref(props.messages || [])

const conversations = computed(() => {
  if (!props.conversations) return []

  let result = props.conversations

  // Apply type filter
  if (filterType.value === 'DMs') {
    result = result.filter(c => c.type === 'dm')
  } else if (filterType.value === 'Groups') {
    result = result.filter(c => c.type === 'group')
  }

  // Apply search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(c => c.name.toLowerCase().includes(q))
  }

  return result
})

const activeConversation = computed(() => {
  if (!activeConversationId.value || !props.conversations) return null
  return props.conversations.find(c => c.id === activeConversationId.value)
})

const selectConversation = (conversationId) => {
  activeConversationId.value = conversationId
  router.visit(urls.messages.show(conversationId), {
    preserveState: true,
    only: ['conversations', 'activeConversation', 'messages', 'unreadMessagesCount'],
  })
}

const handleKeydown = (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

const handleInput = () => {
  const now = Date.now()
  if (now - lastTypingSent > 3000) {
    lastTypingSent = now
    if (activeConversationId.value) {
      fetch(urls.messages.typing(activeConversationId.value), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
          'Accept': 'application/json',
        },
      }).catch(() => {})
    }
  }
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value || !activeConversationId.value) return

  const body = newMessage.value
  newMessage.value = ''
  isSending.value = true
  messageError.value = ''

  const tempMessage = {
    id: 'temp-' + Date.now(),
    conversation_id: activeConversationId.value,
    sender: {
      id: page.props.auth.user.id,
      name: page.props.auth.user.name,
      avatar_url: page.props.auth.user.avatar_url,
      initials: (page.props.auth.user.name || '').substring(0, 2),
    },
    body,
    edited_at: null,
    deleted_at: null,
    created_at: new Date().toISOString(),
  }

  messages.value.push(tempMessage)
  await nextTick()
  scrollToBottom()

  try {
    const response = await fetch(urls.messages.send(activeConversationId.value), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ body }),
    })

    if (!response.ok) throw new Error('Failed to send message')

    const data = await response.json()
    const idx = messages.value.findIndex(m => m.id === tempMessage.id)
    if (idx !== -1) {
      messages.value[idx] = data.message
      await nextTick()
      scrollToBottom()
    }
  } catch (err) {
    console.error('Error sending message:', err)
    messages.value = messages.value.filter(m => m.id !== tempMessage.id)
    messageError.value = 'Message failed to send. Please try again.'
    newMessage.value = body
  } finally {
    isSending.value = false
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    nextTick(() => {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    })
  }
}

const formatTime = (dateTime) => {
  const date = new Date(dateTime)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (messageDate.getTime() === today.getTime()) {
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
  }

  if (messageDate.getTime() === yesterday.getTime()) {
    return 'Yesterday'
  }

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const searchUsers = async () => {
  if (userSearchQuery.value.length < 2) {
    searchResults.value = []
    dmError.value = ''
    return
  }

  dmSearchLoading.value = true
  dmError.value = ''
  searchResults.value = []
  try {
    const response = await fetch(urls.messages.usersSearch(userSearchQuery.value))
    if (!response.ok) throw new Error('Failed to search users')
    searchResults.value = await response.json()
  } catch (err) {
    console.error('Error searching users:', err)
    dmError.value = 'Could not search users. Please try again.'
  } finally {
    dmSearchLoading.value = false
  }
}

const startDm = async (user) => {
  dmError.value = ''
  try {
    const response = await fetch(urls.messages.store(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ recipient_id: user.id }),
    })

    if (response.status === 303 || response.status === 302) {
      const location = response.headers.get('location')
      if (location) {
        window.location.href = location
        return
      }
    }

    const data = await response.json()
    if (data.id) {
      selectConversation(data.id)
    }
  } catch (err) {
    console.error('Error creating DM:', err)
    dmError.value = 'Could not start this conversation. Please try again.'
    return
  }

  showNewDmModal.value = false
  userSearchQuery.value = ''
  searchResults.value = []
}

onMounted(() => {
  scrollToBottom()

  if (activeConversationId.value && window.Echo) {
    window.Echo.private(`conversation.${activeConversationId.value}`)
      .listen('.message.sent', (e) => {
        if (e.sender.id !== page.props.auth.user.id) {
          messages.value.push(e)
          nextTick(() => scrollToBottom())
        }
      })
      .listen('.message.updated', (e) => {
        const idx = messages.value.findIndex(m => m.id === e.id)
        if (idx !== -1) {
          messages.value[idx] = { ...messages.value[idx], body: e.body, edited_at: e.edited_at }
        }
      })
      .listen('.message.deleted', (e) => {
        const idx = messages.value.findIndex(m => m.id === e.id)
        if (idx !== -1) {
          messages.value[idx].deleted_at = new Date().toISOString()
        }
      })
      .listen('.user.typing', (e) => {
        if (e.user.id !== page.props.auth.user.id) {
          typingUser.value = e.user
          clearTimeout(typingTimeout)
          typingTimeout = setTimeout(() => {
            typingUser.value = null
          }, 3000)
        }
      })
  }
})

onUnmounted(() => {
  if (activeConversationId.value && window.Echo) {
    window.Echo.leave(`conversation.${activeConversationId.value}`)
  }
})

watch(() => props.activeConversation, (newVal) => {
  if (newVal?.id) {
    activeConversationId.value = newVal.id
  }
})

watch(() => props.messages, (newVal) => {
  messages.value = newVal || []
  scrollToBottom()
})
</script>
