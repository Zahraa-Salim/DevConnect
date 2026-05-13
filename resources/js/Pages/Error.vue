<template>
  <Head :title="`${props.status} - ${error.title}`" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-dc-page-bg dark:bg-dc-dark-bg">
    <Card class="w-full max-w-[520px] p-8 text-center">
      <Logo size="lg" class="mb-8 justify-center" />

      <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-dc-surface dark:bg-dc-dark-bg border border-dc-surface dark:border-dc-dark-border text-dc-muted text-h2">
        {{ error.icon }}
      </div>

      <div class="text-[56px] leading-none font-semibold text-dc-muted/40 mb-4">
        {{ props.status }}
      </div>

      <h1 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-3">
        {{ error.title }}
      </h1>

      <p class="text-body text-dc-muted mb-8">
        {{ error.description }}
      </p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <Button variant="ghost" type="button" @click="goBack">
          Go back
        </Button>
        <Link href="/dashboard">
          <Button variant="primary" type="button">
            Go home
          </Button>
        </Link>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Logo from '@/Components/Logo.vue'

const props = defineProps<{
  status: number
}>()

const errorMessages: Record<number, { title: string; description: string; icon: string }> = {
  403: {
    title: 'Access Denied',
    description: "You don't have permission to access this page.",
    icon: '403',
  },
  404: {
    title: 'Page Not Found',
    description: "The page you're looking for doesn't exist or has been moved.",
    icon: '404',
  },
  409: {
    title: 'Conflict',
    description: 'This action has already been completed or conflicts with the current state.',
    icon: '409',
  },
  419: {
    title: 'Session Expired',
    description: 'Your session has expired. Please refresh the page and try again.',
    icon: '419',
  },
  422: {
    title: 'Invalid Request',
    description: 'The submitted data was invalid. Please check your input and try again.',
    icon: '422',
  },
  429: {
    title: 'Too Many Requests',
    description: "You're doing that too fast. Please wait a moment and try again.",
    icon: '429',
  },
  500: {
    title: 'Server Error',
    description: 'Something went wrong on our end. Please try again later.',
    icon: '500',
  },
  503: {
    title: 'Service Unavailable',
    description: "We're currently performing maintenance. Please check back shortly.",
    icon: '503',
  },
}

const error = computed(() => {
  return errorMessages[props.status] || {
    title: 'Something went wrong',
    description: 'An unexpected error occurred. Please try again.',
    icon: String(props.status || 'Error'),
  }
})

function goBack() {
  window.history.back()
}
</script>
