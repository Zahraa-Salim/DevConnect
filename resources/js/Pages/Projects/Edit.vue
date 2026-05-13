<template>
  <Head :title="`Edit ${project.title}`" />

  <div class="max-w-2xl mx-auto p-4 md:p-8">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark mb-2">
        Edit project
      </h1>
      <p class="text-body text-dc-muted">
        Update your project details.
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
                    v-model.number="role.slots"
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

        <!-- Submit -->
        <div class="pt-6 border-t border-dc-surface dark:border-dc-dark-border">
          <div class="flex gap-3">
            <Link
              :href="urls.projects.show(project.id)"
              class="flex-1 px-4 py-3 rounded border border-dc-surface text-dc-body hover:bg-dc-surface text-center text-small font-medium"
            >
              Cancel
            </Link>
            <Button
              type="submit"
              class="flex-1"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Saving...' : 'Save changes' }}
            </Button>
          </div>
        </div>
      </form>
    </Card>

    <!-- Readiness Checklist -->
    <Card class="p-8 mt-6">
      <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">Readiness Checklist</h3>

      <div v-if="loadingReadiness" class="py-4">
        <Spinner :size="48" message="Checking readiness..." />
      </div>

      <div v-else class="space-y-3 mb-6">
        <div
          v-for="check in readiness.checks"
          :key="check.key"
          class="flex items-center gap-3"
        >
          <span
            :class="check.pass ? 'text-dc-success font-bold' : 'text-dc-danger font-bold'"
            class="w-4 flex-shrink-0"
          >
            {{ check.pass ? '✓' : '✗' }}
          </span>
          <span :class="check.pass ? 'text-dc-body dark:text-dc-primary-muted' : 'text-dc-muted line-through'">
            {{ check.label }}
          </span>
        </div>
      </div>

      <p v-if="readinessError" class="text-small text-dc-danger mb-4">{{ readinessError }}</p>

      <div v-if="project.status === 'archived'" class="flex items-center gap-3 flex-wrap">
        <Button
          variant="primary"
          :disabled="!readiness.ready || publishingStatus"
          @click="publishProject"
        >
          {{ publishingStatus ? 'Publishing…' : 'Publish Project' }}
        </Button>
        <span v-if="!readiness.ready && !loadingReadiness" class="text-small text-dc-muted">
          Complete all checks above to publish
        </span>
      </div>

      <p v-else-if="project.status === 'open'" class="text-small text-dc-success font-medium">
        Project is live and accepting applications.
      </p>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Select from '@/Components/Select.vue'
import Spinner from '@/Components/Spinner.vue'
import { urls } from '@/lib/routes'

defineOptions({ layout: AuthenticatedLayout })

interface ProjectRole {
  id: number | null
  role_name: string
  slots: number
  description: string | null
}

interface Project {
  id: number
  title: string
  description: string
  type: string
  domain: string | null
  tech_stack: string[] | null
  max_members: number
  estimated_duration: string | null
  status: string
  repo_url: string | null
  roles: ProjectRole[]
}

interface Props {
  project: Project
}

const props = defineProps<Props>()

// ── Readiness Checklist ───────────────────────────────────────────────────────
interface ReadinessCheck { key: string; label: string; pass: boolean }
const loadingReadiness = ref(true)
const readiness = ref<{ checks: ReadinessCheck[]; ready: boolean }>({ checks: [], ready: false })
const readinessError = ref('')
const publishingStatus = ref(false)

async function fetchReadiness() {
  try {
    const res = await fetch(urls.projects.readiness(props.project.id))
    readiness.value = await res.json()
  } catch {
    // silently ignore
  } finally {
    loadingReadiness.value = false
  }
}

onMounted(fetchReadiness)

function publishProject() {
  publishingStatus.value = true
  readinessError.value = ''
  router.patch(
    urls.projects.updateStatus(props.project.id),
    { status: 'open' },
    {
      onError: (errs) => {
        readinessError.value = errs.readiness ?? errs.status ?? 'An error occurred.'
        fetchReadiness()
      },
      onFinish: () => { publishingStatus.value = false },
    }
  )
}

// ── Form ──────────────────────────────────────────────────────────────────────
const techStackInput = ref('')

const form = useForm({
  title: props.project.title,
  description: props.project.description,
  type: props.project.type,
  domain: props.project.domain || '',
  tech_stack: props.project.tech_stack || [],
  max_members: props.project.max_members,
  estimated_duration: props.project.estimated_duration || '',
  roles: props.project.roles.map(r => ({
    id: r.id,
    role_name: r.role_name,
    slots: r.slots,
    description: r.description || '',
  })) as Array<{ id: number | null; role_name: string; slots: number; description: string }>,
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
    id: null,
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
  form.put(urls.projects.update(props.project.id))
}
</script>
