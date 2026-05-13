<template>
  <Head title="Sign Team Agreement" />

  <div class="min-h-screen flex items-center justify-center p-4 bg-dc-page-bg dark:bg-dc-dark-bg">
    <div class="w-full max-w-[600px]">
      <Card class="p-6 sm:p-8">
        <div class="text-center mb-8">
          <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-2">
            Team agreement.
          </h2>
          <p class="text-body text-dc-muted">
            Review the team responsibilities before continuing in this real client project.
          </p>
        </div>

        <div v-if="alreadySigned" class="p-4 rounded-lg bg-dc-primary-tint/10 border border-dc-primary-tint text-center">
          <p class="text-body text-dc-body dark:text-dc-primary-muted">
            You have already signed this team agreement.
          </p>
          <Link :href="urls.projects.show(project.id)" class="mt-3 inline-block text-dc-primary hover:underline">
            Back to project
          </Link>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-6">
          <div class="bg-dc-surface dark:bg-dc-dark-surface p-4 rounded-lg border border-dc-muted/20">
            <h3 class="text-label-caps text-dc-muted mb-4">
              Team Agreement
            </h3>

            <Textarea
              label="Role responsibilities & work split"
              :model-value="agreementText || 'No team agreement text has been added yet.'"
              :rows="8"
              readonly
              class="mb-4"
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <TextInput label="Full name" :model-value="user.name" readonly />
              <TextInput label="Date" :model-value="currentDate" readonly />
            </div>

            <Checkbox
              v-model="form.agreement_agreed"
              label="I agree to the team responsibilities outlined above."
            />
          </div>

          <div class="pt-2 flex flex-col items-center gap-4">
            <Button
              variant="primary"
              fullWidth
              type="submit"
              :disabled="!form.agreement_agreed || form.processing"
              :class="{ 'opacity-50 cursor-not-allowed': !form.agreement_agreed || form.processing }"
            >
              {{ form.processing ? 'Signing...' : 'Sign team agreement' }}
            </Button>
            <Link :href="urls.projects.show(project.id)" class="text-dc-primary hover:underline text-body">
              Cancel
            </Link>
          </div>
        </form>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Checkbox from '@/Components/Checkbox.vue'
import Button from '@/Components/Button.vue'
import { urls } from '@/lib/routes'

const props = defineProps<{
  project: { id: number; title: string; description: string; type: string }
  agreementText: string | null
  user: { name: string; email: string }
  currentDate: string
  alreadySigned: boolean
}>()

const form = useForm({
  agreement_agreed: false,
  document_text: props.agreementText ?? '',
})

function submit() {
  if (!form.agreement_agreed) return
  form.post(urls.projects.agreementSign(props.project.id))
}
</script>
