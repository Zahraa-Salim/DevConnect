<template>
  <div class="flex flex-col items-center justify-center gap-3">
    <div
      class="relative flex items-center justify-center"
      :style="{
        width: props.size,
        height: props.size
      }"
    >
      <svg
        viewBox="0 0 100 100"
        class="spinner-arc w-full h-full"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <!-- Indigo Arc -->
        <path
          d="M 50 10 A 40 40 0 0 1 88.5 63.6"
          stroke="currentColor"
          class="text-dc-primary dark:text-dc-primary-muted"
          stroke-width="8"
          stroke-linecap="round"
        />

        <!-- Coral Arc -->
        <path
          d="M 50 90 A 40 40 0 0 1 11.5 36.4"
          stroke="currentColor"
          class="text-dc-coral"
          stroke-width="8"
          stroke-linecap="round"
        />
      </svg>
      <div
        class="spinner-dot absolute rounded-full bg-dc-primary dark:bg-dc-primary-muted"
        :style="{
          width: props.size * 0.18 + 'px',
          height: props.size * 0.18 + 'px',
          '--dot-amplitude': (props.size * 0.09) + 'px'
        }"
      />
    </div>
    <span v-if="props.message" class="text-small text-dc-muted">{{ props.message }}</span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  size?: 80 | 64 | 48 | 28
  message?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 48
})
</script>

<style scoped>
.spinner-arc {
  animation: swing-arc 2200ms cubic-bezier(0.45, 0, 0.55, 1) alternate infinite;
  transform-origin: center;
}

.spinner-dot {
  animation: bounce-dot 2200ms cubic-bezier(0.45, 0, 0.55, 1) alternate infinite;
}

@keyframes swing-arc {
  from { transform: rotate(-120deg); }
  to   { transform: rotate(120deg); }
}

@keyframes bounce-dot {
  from { transform: translateY(0px); }
  to   { transform: translateY(calc(var(--dot-amplitude) * -1)); }
}
</style>
