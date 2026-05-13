<template>
  <Head title="Login" />
  <div class="min-h-screen flex items-center justify-center p-4">
    <Card class="w-full max-w-[480px] p-[32px]">
      <div class="flex flex-col items-center mb-8">
        <Logo class="mb-6" />
        <h2 class="text-h2 mb-2">Welcome back.</h2>
        <p class="text-body text-dc-primary-muted">Sign in to your account.</p>
      </div>

      <Button variant="primary" fullWidth class="mb-6" @click="loginWithGitHub">
        Continue with GitHub
      </Button>

      <div class="relative flex items-center justify-center mb-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-[#E2E1F5] dark:border-dc-dark-border"></div>
        </div>
        <div class="relative bg-white dark:bg-dc-dark-surface px-4 text-small text-dc-muted">
          or
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4 mb-6">
        <TextInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="john@example.com"
          :error="form.errors.email"
        />

        <div>
          <TextInput
            v-model="form.password"
            label="Password"
            type="password"
            placeholder="••••••••"
            :error="form.errors.password"
          />

          <div class="flex justify-end mt-1">
            <!-- TODO: /forgot-password not available in Stage 3A -->
            <!-- <Link
              href="/forgot-password"
              class="text-small text-dc-primary hover:underline"
            >
              Forgot your password?
            </Link> -->
          </div>
        </div>

        <div class="pt-2">
          <Button variant="primary" fullWidth :disabled="form.processing">
            Sign in
          </Button>
        </div>
      </form>

      <div class="text-center">
        <span class="text-body text-dc-body dark:text-dc-primary-muted">
          Don't have an account?
        </span>
        <Link
          :href="registerHref"
          class="text-body text-dc-primary hover:underline ml-1"
        >
          Get started
        </Link>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'

const form = useForm({
  email: '',
  password: '',
  remember: false,
  redirect: redirectPath(),
})

const registerHref = redirectPath()
  ? `/register?redirect=${encodeURIComponent(redirectPath())}`
  : '/register'

function handleSubmit() {
  form.clearErrors()

  let hasErrors = false

  if (!form.email || !form.email.includes('@')) {
    form.setError('email', 'Please enter a valid email')
    hasErrors = true
  }

  if (!form.password || form.password.length < 1) {
    form.setError('password', 'Password is required')
    hasErrors = true
  }

  if (hasErrors) return

  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}

function loginWithGitHub() {
  window.location.href = '/auth/github'
}

function redirectPath(): string {
  const redirect = new URLSearchParams(window.location.search).get('redirect') || ''
  return redirect.startsWith('/') && !redirect.startsWith('//') ? redirect : ''
}
</script>
