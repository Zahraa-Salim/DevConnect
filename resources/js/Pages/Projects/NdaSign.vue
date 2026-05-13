<template>
  <Head title="Sign NDA" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-dc-page-bg dark:bg-dc-dark-bg">
    <div class="w-full max-w-[600px]">
      <Card class="p-6 sm:p-8">
        <div class="text-center mb-8">
          <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-2">
            Before you join.
          </h2>
          <p class="text-body text-dc-muted">
            This is a real client project. Please read and sign the following agreement before accessing the workspace.
          </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <div class="bg-dc-surface dark:bg-dc-dark-surface p-4 rounded-lg border border-dc-muted/20">
            <h3 class="text-label-caps text-dc-muted mb-4">
              Confidentiality Agreement
            </h3>

            <div class="max-h-[200px] overflow-y-auto p-3 mb-4 bg-white dark:bg-dc-dark-bg border border-dc-muted/30 rounded text-[13px] font-mono text-dc-body dark:text-dc-primary-muted whitespace-pre-wrap">
              {{ ndaText }}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <TextInput label="Full name" :model-value="user.name" readonly />
              <TextInput label="Date" :model-value="currentDate" readonly />
            </div>

            <Checkbox
              v-model="form.nda_agreed"
              label="I have read and agree to the confidentiality terms."
            />
            <div v-if="form.errors.nda_agreed" class="text-small text-dc-danger mt-2">
              {{ form.errors.nda_agreed }}
            </div>
          </div>

          <div v-if="agreementText" class="bg-dc-surface dark:bg-dc-dark-surface p-4 rounded-lg border border-dc-muted/20">
            <h3 class="text-label-caps text-dc-muted mb-4">
              Team Agreement
            </h3>

            <Textarea
              label="Role responsibilities & work split"
              :model-value="agreementText"
              :rows="6"
              readonly
              class="mb-4"
            />

            <Checkbox
              v-model="form.agreement_agreed"
              label="I agree to the team responsibilities outlined above."
            />
            <div v-if="form.errors.agreement_agreed" class="text-small text-dc-danger mt-2">
              {{ form.errors.agreement_agreed }}
            </div>
          </div>

          <div class="pt-2 flex flex-col items-center gap-4">
            <Button
              variant="primary"
              fullWidth
              type="submit"
              :disabled="!isFormValid || form.processing"
              :class="{ 'opacity-50 cursor-not-allowed': !isFormValid || form.processing }"
            >
              {{ form.processing ? 'Signing...' : 'Sign and join project' }}
            </Button>
            <Link
              :href="urls.projects.show(project.id)"
              class="text-dc-primary hover:text-dc-primary-dark dark:hover:text-dc-primary-light text-body"
            >
              Cancel
            </Link>
          </div>
        </form>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Checkbox from '@/Components/Checkbox.vue'
import Button from '@/Components/Button.vue'
import { urls } from '@/lib/routes'

const props = defineProps<{
  project: {
    id: number
    title: string
    description: string
    type: string
    owner: { name: string }
  }
  ndaText: string
  agreementText: string | null
  user: { name: string; email: string }
  currentDate: string
}>()

const form = useForm({
  nda_agreed: false,
  agreement_agreed: false,
  document_text: props.ndaText,
})

const isFormValid = computed(() => {
  return form.nda_agreed && (!props.agreementText || form.agreement_agreed)
})

function submit() {
  if (!isFormValid.value) return
  form.post(urls.projects.ndaSign(props.project.id))
}
</script>
