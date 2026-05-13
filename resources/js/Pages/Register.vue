<template>
  <Head title="Register" />
  <div class="relative min-h-screen flex items-center justify-center p-4 overflow-hidden">
    <DevBackground :opacity="0.20" :count="18" />
    <Card class="w-full max-w-[480px] p-[32px]">
      <div class="flex flex-col items-center mb-8">
        <Logo class="mb-6" />
        <h2 class="text-h2 mb-2">Create your account</h2>
        <p class="text-body text-dc-primary-muted">
          Join Lebanon's developer community.
        </p>
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
          v-model="form.name"
          label="Full name"
          placeholder="John Doe"
          :error="form.errors.name"
        />
        <TextInput
          v-model="form.email"
          label="Email"
          type="email"
          placeholder="john@example.com"
          :error="form.errors.email"
        />
        <TextInput
          v-model="form.password"
          label="Password"
          type="password"
          placeholder="••••••••"
          :error="form.errors.password"
        />
        <TextInput
          v-model="form.password_confirmation"
          label="Confirm password"
          type="password"
          placeholder="••••••••"
          :error="form.errors.password_confirmation"
        />

        <div class="pt-2">
          <Button variant="primary" fullWidth :disabled="form.processing">
            Create account
          </Button>
        </div>
      </form>

      <div class="text-center mb-6">
        <span class="text-body text-dc-body dark:text-dc-primary-muted">
          Already have an account?
        </span>
        <Link
          :href="loginHref"
          class="text-body text-dc-primary hover:underline ml-1"
        >
          Sign in
        </Link>
      </div>

      <p class="text-[12px] text-dc-muted text-center">
        By registering you agree to our Terms of Service and Privacy Policy.
      </p>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import DevBackground from '@/Components/DevBackground.vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  redirect: redirectPath(),
})

const loginHref = redirectPath()
  ? `/login?redirect=${encodeURIComponent(redirectPath())}`
  : '/login'

function handleSubmit() {
  form.clearErrors()

  let hasErrors = false

  if (!form.name || form.name.trim().length < 2) {
    form.setError('name', 'Full name is required (at least 2 characters)')
    hasErrors = true
  }

  if (!form.email || !form.email.includes('@') || !form.email.includes('.')) {
    form.setError('email', 'A valid email address is required')
    hasErrors = true
  }

  if (!form.password || form.password.length < 8) {
    form.setError('password', 'Password must be at least 8 characters')
    hasErrors = true
  }

  if (form.password !== form.password_confirmation) {
    form.setError('password_confirmation', 'Passwords do not match')
    hasErrors = true
  }

  if (hasErrors) return

  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
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
