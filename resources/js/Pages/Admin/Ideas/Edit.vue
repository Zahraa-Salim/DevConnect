<script setup lang="ts">
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Select from '@/Components/Select.vue'
import SkillTag from '@/Components/SkillTag.vue'

defineOptions({ layout: AdminLayout })

interface Idea {
  id: number
  title: string
  description: string
  domain: string | null
  difficulty: string
  team_size: number | null
  suggested_roles: string[] | null
  requirements: string[] | null
  status: string
}

const props = defineProps<{ idea: Idea }>()

const form = useForm({
  title: props.idea.title,
  description: props.idea.description,
  domain: props.idea.domain ?? '',
  difficulty: props.idea.difficulty,
  team_size: props.idea.team_size ?? 3,
  suggested_roles: props.idea.suggested_roles ?? [],
  requirements: props.idea.requirements ?? [],
  status: props.idea.status,
})

const roleInput = ref('')
const reqInput = ref('')

function addRole() {
  const v = roleInput.value.trim()
  if (! v || form.suggested_roles.includes(v)) {
    roleInput.value = ''
    return
  }
  form.suggested_roles.push(v)
  roleInput.value = ''
}
function removeRole(i: number) { form.suggested_roles.splice(i, 1) }

function addReq() {
  const v = reqInput.value.trim()
  if (! v) return
  form.requirements.push(v)
  reqInput.value = ''
}
function removeReq(i: number) { form.requirements.splice(i, 1) }

function submit() {
  form.put(`/admin/ideas/${props.idea.id}`)
}
</script>

<template>
  <Head title="Admin · Edit Idea" />

  <div class="p-8 max-w-3xl mx-auto">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted">
        Edit Idea
      </h1>
      <p class="text-body text-dc-muted mt-1">Update a curated project idea.</p>
    </div>

    <div class="bg-white dark:bg-dc-dark-surface rounded-lg p-8 border border-dc-surface dark:border-dc-dark-border space-y-6">
      <TextInput v-model="form.title" label="Title" :error="form.errors.title" />
      <Textarea v-model="form.description" label="Description" :maxLength="1000" :error="form.errors.description" />

      <div class="grid grid-cols-2 gap-4">
        <TextInput v-model="form.domain" label="Domain (optional)" :error="form.errors.domain" />
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Difficulty</label>
          <Select
            v-model="form.difficulty"
            :options="[
              { value: 'beginner', label: 'Beginner' },
              { value: 'intermediate', label: 'Intermediate' },
              { value: 'advanced', label: 'Advanced' }
            ]"
          />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Team size</label>
          <input
            v-model.number="form.team_size"
            type="number" min="1" max="20"
            class="w-full p-2 border border-dc-surface dark:border-dc-dark-border rounded text-dc-body dark:text-dc-primary-muted bg-white dark:bg-dc-dark-surface"
          />
        </div>
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">Status</label>
          <Select
            v-model="form.status"
            :options="[
              { value: 'active', label: 'Active (visible)' },
              { value: 'featured', label: 'Featured' },
              { value: 'pending', label: 'Pending (hidden)' },
              { value: 'removed', label: 'Removed' }
            ]"
          />
        </div>
      </div>

      <div>
        <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
          Suggested roles
        </label>
        <div class="flex gap-2 mb-2 flex-wrap">
          <SkillTag
            v-for="(role, i) in form.suggested_roles"
            :key="role"
            @click="removeRole(i)"
            class="cursor-pointer"
          >
            {{ role }} ✕
          </SkillTag>
        </div>
        <TextInput
          v-model="roleInput"
          placeholder="Add a role (Enter to add)"
          @keydown.enter.prevent="addRole"
        />
      </div>

      <div>
        <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
          Requirements
        </label>
        <div class="space-y-2 mb-2">
          <div
            v-for="(req, i) in form.requirements"
            :key="req"
            class="flex items-center justify-between bg-dc-page-bg dark:bg-dc-dark-bg p-2 rounded"
          >
            <span class="text-body text-dc-body dark:text-dc-primary-muted">{{ req }}</span>
            <button @click="removeReq(i)" class="text-small text-dc-danger">Remove</button>
          </div>
        </div>
        <TextInput
          v-model="reqInput"
          placeholder="Add a requirement (Enter to add)"
          @keydown.enter.prevent="addReq"
        />
      </div>

      <div class="flex gap-3 pt-4">
        <Button variant="primary" @click="submit" :disabled="form.processing">
          Save changes
        </Button>
        <Button variant="secondary" @click="router.visit('/admin/ideas')">
          Cancel
        </Button>
      </div>
    </div>
  </div>
</template>
