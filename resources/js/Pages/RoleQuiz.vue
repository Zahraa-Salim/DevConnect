<template>
  <Head title="Role Quiz" />
  <div v-if="isComplete" class="min-h-screen bg-dc-page-bg dark:bg-dc-dark-bg flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-[600px]">
      <Card class="text-center">
        <div class="text-center space-y-4 mt-8">
          <p class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">
            Based on your answers, you might enjoy:
          </p>
          <div class="text-display text-dc-coral font-semibold">
            {{ roleLabels[suggestedRole] }}
          </div>
          <p class="text-body text-dc-muted">
            You can change your role any time later.
          </p>

          <div class="flex flex-col gap-3 mt-6">
            <Button
              variant="primary"
              fullWidth
              :disabled="submitForm.processing"
              @click="finalize(suggestedRole)"
            >
              Start as {{ roleLabels[suggestedRole] }}
            </Button>

            <details class="text-left">
              <summary class="text-small text-dc-primary cursor-pointer hover:underline text-center py-2">
                Pick a different role instead
              </summary>
              <div class="grid grid-cols-3 gap-2 mt-3">
                <Button
                  v-for="r in ['dev', 'designer', 'pm']"
                  :key="r"
                  variant="secondary"
                  :disabled="submitForm.processing"
                  @click="finalize(r)"
                >
                  {{ roleLabels[r] }}
                </Button>
              </div>
            </details>

            <Button
              variant="ghost"
              fullWidth
              :disabled="submitForm.processing"
              @click="finalize('exploring')"
            >
              I'm still exploring — let me decide later
            </Button>
          </div>
        </div>
      </Card>
    </div>
  </div>

  <div v-else class="min-h-screen bg-dc-page-bg dark:bg-dc-dark-bg flex flex-col items-center pt-12 p-4">
    <div class="w-full max-w-[600px]">
      <!-- Progress Bar -->
      <div class="w-full h-1 bg-dc-surface dark:bg-dc-dark-border rounded-full mb-8 overflow-hidden">
        <div
          class="h-full bg-dc-primary transition-all duration-300"
          :style="{ width: `${progress}%` }"
        />
      </div>

      <div class="text-center mb-8">
        <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-2">
          Let's find your fit.
        </h2>
        <p class="text-body text-dc-muted">
          Answer a few quick questions and we'll suggest a starting role. You
          can always change it later.
        </p>
      </div>

      <div class="mb-8">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          {{ questions[currentStep].title }}
        </h3>
        <div class="flex flex-col gap-3">
          <div
            v-for="(opt, idx) in questions[currentStep].options"
            :key="idx"
            @click="handleSelect(idx)"
            :class="[
              'p-4 rounded-lg border cursor-pointer transition-colors',
              isSelected(idx)
                ? 'border-dc-primary bg-dc-primary-tint dark:bg-dc-primary-dark/20'
                : 'border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface hover:border-dc-primary-light'
            ]"
          >
            <div
              :class="[
                'font-semibold mb-1',
                isSelected(idx)
                  ? 'text-dc-primary-dark dark:text-dc-primary-muted'
                  : 'text-dc-body dark:text-dc-primary-muted'
              ]"
            >
              {{ opt.label }}
            </div>
            <div class="text-small text-dc-muted">{{ opt.desc }}</div>
          </div>
        </div>
      </div>

      <div class="flex justify-between items-center">
        <Button
          variant="ghost"
          @click="handleBack"
          :disabled="currentStep === 0"
        >
          Back
        </Button>
        <Button
          variant="primary"
          @click="handleNext"
          :disabled="answers[currentStep] === undefined"
        >
          {{ currentStep === questions.length - 1 ? 'See my suggested role' : 'Next' }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'

const questions = [
  {
    title: 'When you join a project, what feels most natural to you?',
    options: [
      { label: 'Writing and testing code', desc: 'You like building the logic and structure.', weights: { dev: 3, designer: 0, pm: 0 } },
      { label: 'Designing screens and flows', desc: 'You focus on user experience and visuals.', weights: { dev: 0, designer: 3, pm: 0 } },
      { label: 'Organizing tasks and people', desc: 'You keep the team aligned and moving.', weights: { dev: 0, designer: 0, pm: 3 } },
      { label: "I'm not sure yet", desc: 'You want to explore different areas.', weights: { dev: 1, designer: 1, pm: 1 } }
    ]
  },
  {
    title: 'What kind of work energizes you most?',
    options: [
      { label: 'Solving technical problems', desc: 'Debugging and optimizing systems.', weights: { dev: 3, designer: 0, pm: 0 } },
      { label: 'Making things look and feel right', desc: 'Polishing UI and interactions.', weights: { dev: 0, designer: 3, pm: 0 } },
      { label: 'Keeping a team on track', desc: 'Planning sprints and managing scope.', weights: { dev: 0, designer: 0, pm: 3 } },
      { label: 'I want to explore', desc: 'Open to trying whatever is needed.', weights: { dev: 1, designer: 1, pm: 1 } }
    ]
  },
  {
    title: 'How comfortable are you with code right now?',
    options: [
      { label: 'Very comfortable', desc: 'Can build features independently.', weights: { dev: 3, designer: 0, pm: 0 } },
      { label: 'Learning actively', desc: 'Can contribute with some guidance.', weights: { dev: 2, designer: 0, pm: 0 } },
      { label: 'Minimal experience', desc: 'Just starting to learn the basics.', weights: { dev: 1, designer: 2, pm: 1 } },
      { label: 'None yet', desc: 'Focusing on non-code contributions.', weights: { dev: 0, designer: 2, pm: 2 } }
    ]
  },
  {
    title: 'Which tools do you reach for first?',
    options: [
      { label: 'Code editor / terminal', desc: 'VS Code, Git, command line.', weights: { dev: 3, designer: 0, pm: 0 } },
      { label: 'Design tools', desc: 'Figma, Sketch, Adobe.', weights: { dev: 0, designer: 3, pm: 0 } },
      { label: 'Project management tools', desc: 'Jira, Trello, Notion.', weights: { dev: 0, designer: 0, pm: 3 } },
      { label: 'Still figuring out', desc: 'Learning the landscape.', weights: { dev: 1, designer: 1, pm: 1 } }
    ]
  },
  {
    title: "What's your goal on DevConnect?",
    options: [
      { label: 'Build portfolio projects', desc: 'Get real experience to show employers.', weights: { dev: 2, designer: 2, pm: 0 } },
      { label: 'Learn new tech', desc: 'Practice with unfamiliar stacks.', weights: { dev: 2, designer: 1, pm: 0 } },
      { label: 'Lead / manage projects', desc: 'Practice leadership and coordination.', weights: { dev: 0, designer: 0, pm: 3 } },
      { label: 'Find my path', desc: 'Discover what role fits me best.', weights: { dev: 1, designer: 1, pm: 1 } }
    ]
  }
]

const currentStep = ref(0)
const answers = ref<Record<number, number>>({})

const roleLabels: Record<string, string> = {
  dev: 'Developer',
  designer: 'Designer',
  pm: 'Product Manager',
  exploring: 'Exploring',
}

const suggestedRole = computed<string>(() => {
  const tally: Record<string, number> = { dev: 0, designer: 0, pm: 0 }

  for (const [questionIdx, optionIdx] of Object.entries(answers.value)) {
    const question = questions[Number(questionIdx)]
    const option = question?.options[optionIdx]
    if (option?.weights) {
      for (const [role, points] of Object.entries(option.weights)) {
        tally[role] = (tally[role] || 0) + (points as number)
      }
    }
  }

  let topRole = 'dev'
  let topScore = -1
  for (const [role, score] of Object.entries(tally)) {
    if (score > topScore) {
      topScore = score
      topRole = role
    }
  }
  return topRole
})

const isComplete = computed(() => currentStep.value === questions.length)
const progress = computed(() => (currentStep.value / questions.length) * 100)

const isSelected = (idx: number) => answers.value[currentStep.value] === idx

const handleSelect = (optionIndex: number) => {
  answers.value[currentStep.value] = optionIndex
}

const handleNext = () => {
  if (currentStep.value < questions.length) {
    currentStep.value++
  }
}

const handleBack = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const submitForm = useForm({
  answers: {},
  suggested_role: 'exploring',
})

function finalize(role: string) {
  submitForm.answers = answers.value
  submitForm.suggested_role = role
  submitForm.post('/onboarding/quiz')
}
</script>
