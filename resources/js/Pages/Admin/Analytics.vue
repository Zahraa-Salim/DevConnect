<template>
  <Head title="Admin · Analytics" />

  <div class="p-8 max-w-5xl">
    <div class="mb-8">
      <h1 class="text-display text-dc-primary-dark dark:text-dc-primary-muted">Analytics</h1>
      <p class="text-body text-dc-muted mt-1">AI usage, platform activity and reputation data.</p>
    </div>

    <!-- Section 1: AI Usage -->
    <section class="mb-10">
      <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-1">AI Usage</h2>

      <div class="flex gap-6 mb-4">
        <div class="p-4 rounded-xl border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface">
          <div class="text-2xl font-bold text-dc-primary">{{ totalTokens.toLocaleString() }}</div>
          <div class="text-small text-dc-muted uppercase tracking-wider">Total Tokens</div>
        </div>
        <div class="p-4 rounded-xl border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface">
          <div class="text-2xl font-bold text-dc-coral">${{ estimatedCost }}</div>
          <div class="text-small text-dc-muted uppercase tracking-wider">Est. Cost</div>
        </div>
        <div class="p-4 rounded-xl border border-dc-surface dark:border-dc-dark-border bg-white dark:bg-dc-dark-surface">
          <div class="text-2xl font-bold text-dc-primary-dark dark:text-dc-primary-muted">{{ totalCalls.toLocaleString() }}</div>
          <div class="text-small text-dc-muted uppercase tracking-wider">Total Calls</div>
        </div>
      </div>

      <div class="bg-white dark:bg-dc-dark-surface rounded-xl border border-dc-surface dark:border-dc-dark-border p-6">
        <p v-if="ai_usage.length === 0" class="text-dc-muted text-body text-center py-8">No AI calls recorded yet.</p>
        <svg v-else :viewBox="`0 0 ${SVG_W} ${SVG_H}`" class="w-full" :style="{ height: SVG_H + 'px' }">
          <!-- Y grid lines -->
          <line
            v-for="(y, i) in yGridLines(aiMax)"
            :key="'ay' + i"
            :x1="PAD_L" :y1="yPos(y, aiMax)"
            :x2="SVG_W - PAD_R" :y2="yPos(y, aiMax)"
            stroke="currentColor" stroke-opacity="0.08" stroke-width="1"
          />
          <!-- Y labels -->
          <text
            v-for="(y, i) in yGridLines(aiMax)"
            :key="'ayl' + i"
            :x="PAD_L - 8" :y="yPos(y, aiMax) + 4"
            text-anchor="end" font-size="11" fill="currentColor" opacity="0.45"
          >{{ y }}</text>

          <!-- Bars -->
          <g v-for="(row, i) in ai_usage" :key="row.feature">
            <rect
              :x="barX(i, ai_usage.length)"
              :y="yPos(row.calls, aiMax)"
              :width="barW(ai_usage.length)"
              :height="CHART_H - yPos(row.calls, aiMax) + PAD_T"
              rx="4" ry="4"
              fill="#534AB7"
            />
            <!-- Value label above bar -->
            <text
              :x="barX(i, ai_usage.length) + barW(ai_usage.length) / 2"
              :y="yPos(row.calls, aiMax) - 5"
              text-anchor="middle" font-size="11" font-weight="600"
              fill="currentColor" opacity="0.7"
            >{{ row.calls }}</text>
            <!-- X label -->
            <text
              :x="barX(i, ai_usage.length) + barW(ai_usage.length) / 2"
              :y="SVG_H - 6"
              text-anchor="middle" font-size="11" fill="currentColor" opacity="0.5"
            >{{ row.feature }}</text>
          </g>

          <!-- Baseline -->
          <line
            :x1="PAD_L" :y1="CHART_H + PAD_T"
            :x2="SVG_W - PAD_R" :y2="CHART_H + PAD_T"
            stroke="currentColor" stroke-opacity="0.15" stroke-width="1"
          />
        </svg>
      </div>
    </section>

    <!-- Section 2: Platform Activity -->
    <section class="mb-10">
      <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-4">Platform Activity</h2>

      <div class="bg-white dark:bg-dc-dark-surface rounded-xl border border-dc-surface dark:border-dc-dark-border p-6">
        <p v-if="projects_per_week.length === 0" class="text-dc-muted text-body text-center py-8">No project data for the past 8 weeks.</p>
        <svg v-else :viewBox="`0 0 ${SVG_W} ${SVG_H}`" class="w-full" :style="{ height: SVG_H + 'px' }">
          <!-- Y grid lines -->
          <line
            v-for="(y, i) in yGridLines(weekMax)"
            :key="'wy' + i"
            :x1="PAD_L" :y1="yPos(y, weekMax)"
            :x2="SVG_W - PAD_R" :y2="yPos(y, weekMax)"
            stroke="currentColor" stroke-opacity="0.08" stroke-width="1"
          />
          <text
            v-for="(y, i) in yGridLines(weekMax)"
            :key="'wyl' + i"
            :x="PAD_L - 8" :y="yPos(y, weekMax) + 4"
            text-anchor="end" font-size="11" fill="currentColor" opacity="0.45"
          >{{ y }}</text>

          <!-- Fill area under line -->
          <polygon
            :points="lineAreaPoints"
            fill="#534AB7" fill-opacity="0.08"
          />

          <!-- Line -->
          <polyline
            :points="linePoints"
            fill="none" stroke="#534AB7" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"
          />

          <!-- Dots + value labels -->
          <g v-for="(row, i) in projects_per_week" :key="row.label">
            <circle
              :cx="lineX(i, projects_per_week.length)"
              :cy="yPos(row.count, weekMax)"
              r="4" fill="#534AB7"
            />
            <text
              v-if="row.count > 0"
              :x="lineX(i, projects_per_week.length)"
              :y="yPos(row.count, weekMax) - 8"
              text-anchor="middle" font-size="11" font-weight="600"
              fill="currentColor" opacity="0.7"
            >{{ row.count }}</text>
            <!-- X label -->
            <text
              :x="lineX(i, projects_per_week.length)"
              :y="SVG_H - 6"
              text-anchor="middle" font-size="10" fill="currentColor" opacity="0.45"
            >{{ shortWeekLabel(row.label) }}</text>
          </g>

          <!-- Baseline -->
          <line
            :x1="PAD_L" :y1="CHART_H + PAD_T"
            :x2="SVG_W - PAD_R" :y2="CHART_H + PAD_T"
            stroke="currentColor" stroke-opacity="0.15" stroke-width="1"
          />
        </svg>
      </div>
    </section>

    <!-- Section 3: Reputation Distribution -->
    <section class="mb-10">
      <h2 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-4">Reputation Distribution</h2>

      <div class="bg-white dark:bg-dc-dark-surface rounded-xl border border-dc-surface dark:border-dc-dark-border p-6">
        <p v-if="repMax === 0" class="text-dc-muted text-body text-center py-8">No reputation data yet.</p>
        <svg v-else :viewBox="`0 0 ${SVG_W} ${SVG_H}`" class="w-full" :style="{ height: SVG_H + 'px' }">
          <!-- Y grid lines -->
          <line
            v-for="(y, i) in yGridLines(repMax)"
            :key="'ry' + i"
            :x1="PAD_L" :y1="yPos(y, repMax)"
            :x2="SVG_W - PAD_R" :y2="yPos(y, repMax)"
            stroke="currentColor" stroke-opacity="0.08" stroke-width="1"
          />
          <text
            v-for="(y, i) in yGridLines(repMax)"
            :key="'ryl' + i"
            :x="PAD_L - 8" :y="yPos(y, repMax) + 4"
            text-anchor="end" font-size="11" fill="currentColor" opacity="0.45"
          >{{ y }}</text>

          <!-- Bars -->
          <g v-for="(row, i) in reputation_distribution" :key="row.label">
            <rect
              :x="barX(i, reputation_distribution.length)"
              :y="yPos(row.count, repMax)"
              :width="barW(reputation_distribution.length)"
              :height="CHART_H - yPos(row.count, repMax) + PAD_T"
              rx="4" ry="4"
              :fill="repBarColor(row.label)"
            />
            <text
              v-if="row.count > 0"
              :x="barX(i, reputation_distribution.length) + barW(reputation_distribution.length) / 2"
              :y="yPos(row.count, repMax) - 5"
              text-anchor="middle" font-size="11" font-weight="600"
              fill="currentColor" opacity="0.7"
            >{{ row.count }}</text>
            <text
              :x="barX(i, reputation_distribution.length) + barW(reputation_distribution.length) / 2"
              :y="SVG_H - 6"
              text-anchor="middle" font-size="12" fill="currentColor" opacity="0.5"
            >{{ row.label }}</text>
          </g>

          <!-- Baseline -->
          <line
            :x1="PAD_L" :y1="CHART_H + PAD_T"
            :x2="SVG_W - PAD_R" :y2="CHART_H + PAD_T"
            stroke="currentColor" stroke-opacity="0.15" stroke-width="1"
          />
        </svg>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
  projects_per_week: { label: string; count: number }[]
  ai_usage: { feature: string; calls: number; tokens: number }[]
  reputation_distribution: { label: string; count: number }[]
}>()

// ── SVG layout constants ──────────────────────────────────────────────────────
const SVG_W  = 640
const SVG_H  = 240
const PAD_L  = 40   // left  (y-axis labels)
const PAD_R  = 16   // right
const PAD_T  = 16   // top
const PAD_B  = 30   // bottom (x-axis labels)
const CHART_W = SVG_W - PAD_L - PAD_R
const CHART_H = SVG_H - PAD_T - PAD_B

// ── Derived values ────────────────────────────────────────────────────────────
const totalTokens   = computed(() => props.ai_usage.reduce((s, r) => s + r.tokens, 0))
const totalCalls    = computed(() => props.ai_usage.reduce((s, r) => s + r.calls, 0))
const estimatedCost = computed(() => ((totalTokens.value / 1000) * 0.003).toFixed(4))

const aiMax   = computed(() => Math.max(...props.ai_usage.map(r => r.calls), 1))
const weekMax = computed(() => Math.max(...props.projects_per_week.map(r => r.count), 1))
const repMax  = computed(() => Math.max(...props.reputation_distribution.map(r => r.count), 1))

// ── Chart helpers ─────────────────────────────────────────────────────────────
function yPos(value: number, max: number): number {
  return PAD_T + CHART_H * (1 - value / max)
}

function yGridLines(max: number): number[] {
  const step = max <= 5 ? 1 : max <= 20 ? Math.ceil(max / 4) : Math.ceil(max / 4 / 5) * 5
  const lines: number[] = []
  for (let v = 0; v <= max; v += step) lines.push(v)
  return lines
}

function barW(n: number): number {
  return (CHART_W / n) * 0.55
}

function barX(i: number, n: number): number {
  const slot = CHART_W / n
  return PAD_L + i * slot + (slot - barW(n)) / 2
}

function lineX(i: number, n: number): number {
  if (n <= 1) return PAD_L + CHART_W / 2
  return PAD_L + (i / (n - 1)) * CHART_W
}

const linePoints = computed(() => {
  const data = props.projects_per_week
  const max  = weekMax.value
  return data.map((row, i) =>
    `${lineX(i, data.length)},${yPos(row.count, max)}`
  ).join(' ')
})

const lineAreaPoints = computed(() => {
  const data     = props.projects_per_week
  const max      = weekMax.value
  const baseline = CHART_H + PAD_T
  const first    = `${lineX(0, data.length)},${baseline}`
  const points   = data.map((row, i) => `${lineX(i, data.length)},${yPos(row.count, max)}`).join(' ')
  const last     = `${lineX(data.length - 1, data.length)},${baseline}`
  return `${first} ${points} ${last}`
})

function shortWeekLabel(label: string): string {
  // "Week of May 5" → "May 5"
  return label.replace('Week of ', '')
}

function repBarColor(label: string): string {
  const map: Record<string, string> = {
    '0–20':   '#E5E3F7',
    '21–40':  '#B8B3EC',
    '41–60':  '#8B84E0',
    '61–80':  '#6158CE',
    '81–100': '#534AB7',
  }
  return map[label] ?? '#534AB7'
}
</script>
