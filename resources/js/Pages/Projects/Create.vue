<template>
  <Head title="Post a project" />

  <div class="max-w-2xl mx-auto p-4 md:p-8">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark mb-2">
        Post a project
      </h1>
      <p class="text-body text-dc-muted">
        Share your project idea and find collaborators to build with.
      </p>
    </div>

    <Card class="p-8 space-y-8">
      <form @submit.prevent="submit" class="space-y-8">
        <!-- Project Title -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Project title *
          </label>
          <TextInput
            v-model="form.title"
            placeholder="e.g. LearnAR - Augmented Reality Flashcards"
          />
          <span v-if="form.errors.title" class="text-xs text-dc-danger block mt-1">
            {{ form.errors.title }}
          </span>
        </div>

        <!-- Description -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Description *
          </label>
          <Textarea
            v-model="form.description"
            placeholder="Describe what you're building, the problem it solves, and what you need from your team."
            :rows="4"
          />
          <span v-if="form.errors.description" class="text-xs text-dc-danger block mt-1">
            {{ form.errors.description }}
          </span>
        </div>

        <!-- Project Type -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-3">
            Project type *
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              @click="form.type = 'practice'"
              :class="[
                'p-4 rounded-lg border cursor-pointer transition-colors',
                form.type === 'practice'
                  ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20'
                  : 'border-dc-surface dark:border-dc-dark-border hover:border-dc-primary'
              ]"
            >
              <div
                :class="[
                  'font-semibold mb-1',
                  form.type === 'practice'
                    ? 'text-dc-primary-dark dark:text-dc-primary-muted'
                    : 'text-dc-body dark:text-dc-muted'
                ]"
              >
                Practice project
              </div>
              <div class="text-small text-dc-muted">
                Open to all, no NDA required, great for learning.
              </div>
            </div>

            <div
              @click="form.type = 'real_client'"
              :class="[
                'p-4 rounded-lg border cursor-pointer transition-colors',
                form.type === 'real_client'
                  ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20'
                  : 'border-dc-surface dark:border-dc-dark-border hover:border-dc-primary'
              ]"
            >
              <div
                :class="[
                  'font-semibold mb-1',
                  form.type === 'real_client'
                    ? 'text-dc-primary-dark dark:text-dc-primary-muted'
                    : 'text-dc-body dark:text-dc-muted'
                ]"
              >
                Real client project
              </div>
              <div class="text-small text-dc-muted">
                Requires NDA, milestone-based access, for actual client work.
              </div>
            </div>
          </div>
          <span v-if="form.errors.type" class="text-xs text-dc-danger block mt-2">
            {{ form.errors.type }}
          </span>
        </div>

        <!-- Domain & Tech Stack -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Domain
            </label>
            <Select
              v-model="form.domain"
              :options="[
                { value: '', label: 'Select a domain' },
                { value: 'education', label: 'Education' },
                { value: 'health', label: 'Health' },
                { value: 'finance', label: 'Finance' },
                { value: 'social', label: 'Social' },
                { value: 'tools', label: 'Tools' },
                { value: 'other', label: 'Other' }
              ]"
            />
            <span v-if="form.errors.domain" class="text-xs text-dc-danger block mt-1">
              {{ form.errors.domain }}
            </span>
          </div>

          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Tech stack
            </label>
            <TextInput
              v-model="techStackInput"
              placeholder="Type and press Enter"
              @keydown.enter="addTechStack"
            />
            <div v-if="form.tech_stack.length > 0" class="flex flex-wrap gap-2 mt-2">
              <div
                v-for="(tech, idx) in form.tech_stack"
                :key="idx"
                class="bg-dc-primary-tint text-dc-primary px-3 py-1 rounded-full text-small font-medium flex items-center gap-2"
              >
                {{ tech }}
                <button
                  type="button"
                  @click="removeTechStack(idx)"
                  class="text-dc-primary hover:text-dc-primary-dark"
                >
                  ✕
                </button>
              </div>
            </div>
            <span v-if="form.errors.tech_stack" class="text-xs text-dc-danger block mt-1">
              {{ form.errors.tech_stack }}
            </span>
          </div>
        </div>

        <!-- Max Members & Duration -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Max team members
            </label>
            <TextInput
              v-model.number="form.max_members"
              type="number"
              min="1"
              max="20"
              placeholder="Default: 5"
            />
            <span v-if="form.errors.max_members" class="text-xs text-dc-danger block mt-1">
              {{ form.errors.max_members }}
            </span>
          </div>

          <div>
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Estimated duration
            </label>
            <TextInput
              v-model="form.estimated_duration"
              placeholder="e.g. 4 weeks, 8 weeks"
            />
            <span v-if="form.errors.estimated_duration" class="text-xs text-dc-danger block mt-1">
              {{ form.errors.estimated_duration }}
            </span>
          </div>
        </div>

        <!-- Required Roles -->
        <div>
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-4">
            Required roles *
          </label>
          <div class="space-y-6">
            <div
              v-for="(role, idx) in form.roles"
              :key="idx"
              class="p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg relative bg-dc-page-bg dark:bg-dc-dark-bg"
            >
              <button
                v-if="form.roles.length > 1"
                type="button"
                @click="removeRole(idx)"
                class="absolute top-4 right-4 text-small text-dc-danger hover:underline"
              >
                Remove
              </button>

              <div class="grid grid-cols-3 gap-4 mb-4 pr-16">
                <div class="col-span-2">
                  <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                    Role name
                  </label>
                  <TextInput
                    v-model="role.role_name"
                    placeholder="e.g. Backend Developer"
                  />
                  <span v-if="form.errors[`roles.${idx}.role_name`]" class="text-xs text-dc-danger block mt-1">
                    {{ form.errors[`roles.${idx}.role_name`] }}
                  </span>
                </div>
                <div>
                  <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                    Slots
                  </label>
                  <Select
                    v-model="role.slots"
                    :options="[
                      { value: 1, label: '1' },
                      { value: 2, label: '2' },
                      { value: 3, label: '3' },
                      { value: 4, label: '4' },
                      { value: 5, label: '5' }
                    ]"
                  />
                  <span v-if="form.errors[`roles.${idx}.slots`]" class="text-xs text-dc-danger block mt-1">
                    {{ form.errors[`roles.${idx}.slots`] }}
                  </span>
                </div>
              </div>

              <div>
                <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
                  Description
                </label>
                <Textarea
                  v-model="role.description"
                  placeholder="What will this person do?"
                  :rows="2"
                />
                <span v-if="form.errors[`roles.${idx}.description`]" class="text-xs text-dc-danger block mt-1">
                  {{ form.errors[`roles.${idx}.description`] }}
                </span>
              </div>
            </div>
          </div>

          <button
            type="button"
            @click="addRole"
            class="text-dc-primary hover:underline text-small mt-4 font-medium"
          >
            + Add another role
          </button>
        </div>

        <!-- Idea source (optional, if converting from idea) -->
        <input
          v-if="idea"
          v-model="form.idea_id"
          type="hidden"
          name="idea_id"
        />

        <div v-if="form.errors.idea_id" class="bg-dc-danger-tint border border-dc-danger rounded-lg p-3">
          <p class="text-small text-dc-danger">{{ form.errors.idea_id }}</p>
        </div>

        <!-- Submit -->
        <div class="pt-6 border-t border-dc-surface dark:border-dc-dark-border">
          <div class="flex gap-3">
            <Link
              href="/ideas"
              class="flex-1 px-4 py-3 rounded border border-dc-surface text-dc-body hover:bg-dc-surface text-center text-small font-medium"
            >
              Cancel
            </Link>
            <Button
              type="submit"
              class="flex-1"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Creating...' : 'Post project' }}
            </Button>
          </div>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Select from '@/Components/Select.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

interface Idea {
  id: number
  title: string
  description: string
  domain: string | null
  team_size: number | null
}

interface Props {
  idea?: Idea | null
}

const props = defineProps<Props>()

const techStackInput = ref('')

const form = useForm({
  title: props.idea?.title ?? '',
  description: props.idea?.description ?? '',
  type: 'practice',
  domain: props.idea?.domain ?? '',
  tech_stack: [] as string[],
  max_members: props.idea?.team_size ?? 5,
  estimated_duration: '',
  idea_id: props.idea?.id ?? null,
  roles: [
    {
      role_name: '',
      slots: 1,
      description: '',
    }
  ] as Array<{ role_name: string; slots: number; description: string }>,
})

function addTechStack() {
  if (techStackInput.value.trim()) {
    form.tech_stack.push(techStackInput.value.trim())
    techStackInput.value = ''
  }
}

function removeTechStack(idx: number) {
  form.tech_stack.splice(idx, 1)
}

function addRole() {
  form.roles.push({
    role_name: '',
    slots: 1,
    description: '',
  })
}

function removeRole(idx: number) {
  if (form.roles.length > 1) {
    form.roles.splice(idx, 1)
  }
}

function submit() {
  form.post(urls.projects.store())
}
</script>
