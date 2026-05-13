<template>
  <Head title="Project invite" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-dc-page-bg dark:bg-dc-dark-bg">
    <Card class="w-full max-w-[600px] p-6 sm:p-8">
      <div class="flex justify-center mb-8">
        <Logo size="lg" />
      </div>

      <div v-if="isExpired || isMaxed" class="text-center">
        <div class="text-[40px] mb-4">{{ isExpired ? '!' : 'i' }}</div>
        <h1 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-3">
          {{ isExpired ? 'This invite link has expired' : 'This invite link has reached its usage limit' }}
        </h1>
        <p class="text-body text-dc-muted mb-6">
          This project invite is no longer available.
        </p>
        <Link href="/projects">
          <Button variant="ghost">Browse other projects</Button>
        </Link>
      </div>

      <div v-else>
        <p class="text-label-caps text-dc-muted mb-2">Project invite</p>
        <h1 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-6">
          You're invited to join a project
        </h1>

        <div class="border border-dc-surface dark:border-dc-dark-border rounded-lg p-4 mb-6">
          <div class="flex items-start justify-between gap-3 mb-3">
            <h2 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">
              {{ invite.project.title }}
            </h2>
            <Badge :variant="invite.project.type === 'practice' ? 'skill' : 'open'">
              {{ invite.project.type === 'practice' ? 'Practice project' : 'Real Client Project' }}
            </Badge>
          </div>

          <p class="text-body text-dc-body dark:text-dc-primary-muted mb-4 line-clamp-3">
            {{ invite.project.description }}
          </p>

          <div class="flex items-center gap-2 text-small text-dc-muted mb-4">
            <AvatarInitials :initials="ownerInitials" size="sm" />
            <span>Posted by {{ invite.project.owner.name }}</span>
          </div>

          <div v-if="invite.project.tech_stack?.length" class="flex flex-wrap gap-2 mb-4">
            <SkillTag v-for="tech in invite.project.tech_stack" :key="tech">
              {{ tech }}
            </SkillTag>
          </div>

          <div class="bg-dc-primary-tint/10 border border-dc-primary-tint rounded-md p-3">
            <div v-if="invite.role" class="flex flex-wrap items-center gap-2">
              <span class="text-small text-dc-body dark:text-dc-primary-muted">
                You're invited as:
              </span>
              <Badge variant="skill">{{ invite.role.role_name }}</Badge>
            </div>
            <p v-else class="text-small text-dc-body dark:text-dc-primary-muted">
              You'll join as Undecided and choose a role later.
            </p>
          </div>
        </div>

        <div v-if="isLoggedIn" class="space-y-3">
          <form @submit.prevent="acceptInvite">
            <Button
              variant="primary"
              fullWidth
              type="submit"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Joining...' : 'Join this project' }}
            </Button>
          </form>
          <Link
            :href="`/projects/${invite.project.id}`"
            class="block text-center text-small text-dc-primary hover:underline"
          >
            View project details
          </Link>
        </div>

        <div v-else class="space-y-3">
          <Link :href="loginHref">
            <Button variant="primary" fullWidth>Login to join</Button>
          </Link>
          <Link
            :href="registerHref"
            class="block text-center text-small text-dc-primary hover:underline"
          >
            Register to join
          </Link>
          <p class="text-small text-dc-muted text-center">
            You need a DevConnect account to join projects.
          </p>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Badge from '@/Components/Badge.vue'
import SkillTag from '@/Components/SkillTag.vue'
import AvatarInitials from '@/Components/AvatarInitials.vue'

interface Invite {
  id: number
  token: string
  project: {
    id: number
    title: string
    description: string
    type: string
    tech_stack: string[] | null
    owner: { id: number; name: string; avatar_url: string | null }
  }
  role: { id: number; role_name: string } | null
}

const props = defineProps<{
  invite: Invite
  isExpired: boolean
  isMaxed: boolean
}>()

const page = usePage()
const form = useForm({})

const isLoggedIn = computed(() => Boolean((page.props.auth as any)?.user))
const currentInvitePath = computed(() => `/invite/${props.invite.token}`)
const loginHref = computed(() => `/login?redirect=${encodeURIComponent(currentInvitePath.value)}`)
const registerHref = computed(() => `/register?redirect=${encodeURIComponent(currentInvitePath.value)}`)
const ownerInitials = computed(() => {
  return props.invite.project.owner.name
    .split(' ')
    .map((part) => part.charAt(0))
    .join('')
    .slice(0, 2)
})

function acceptInvite() {
  form.post(`/invite/${props.invite.token}/accept`)
}
</script>
