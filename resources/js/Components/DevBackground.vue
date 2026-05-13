<template>
  <div class="dev-bg" aria-hidden="true">
    <svg
      v-for="icon in icons"
      :key="icon.id"
      :class="['dev-icon', `dev-icon--${icon.id}`]"
      :style="{
        left: icon.x + '%',
        top: icon.y + '%',
        '--float-y': icon.floatY + 'px',
        '--float-duration': icon.duration + 's',
        '--float-delay': icon.delay + 's',
        '--draw-delay': icon.drawDelay + 's',
        '--size': icon.size + 'px',
        '--rotate': icon.rotate + 'deg',
      }"
      :width="icon.size"
      :height="icon.size"
      :viewBox="icon.viewBox"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- Each path gets the draw animation via stroke-dasharray/offset -->
      <path
        v-for="(path, pi) in icon.paths"
        :key="pi"
        :d="path.d"
        :stroke="strokeColor"
        :stroke-width="path.sw ?? 1.5"
        :stroke-linecap="path.lc ?? 'round'"
        :stroke-linejoin="path.lj ?? 'round'"
        :fill="path.fill ?? 'none'"
        pathLength="100"
        class="draw-path"
        :style="{ '--path-delay': (icon.drawDelay + (pi as number) * 0.08) + 's'}"
      />
    </svg>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  /** Stroke color for all icons. Defaults to the DevConnect muted gray. */
  color?: string
  /** Opacity 0–1. Defaults to 0.18 — very subtle. */
  opacity?: number
  /** How many icons to render. Defaults to 18. */
  count?: number
}

const props = withDefaults(defineProps<Props>(), {
  color: '#888780',
  opacity: 0.20,
  count: 18,
})

const strokeColor = computed(() => props.color)

// ─── Icon library ─────────────────────────────────────────────────────────────
// Each icon is defined as a viewBox + array of paths.
// All paths use stroke only (no fill) so they look hand-drawn.

const iconDefs = [
  // < > code brackets
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M8 6L2 12L8 18' },
      { d: 'M16 6L22 12L16 18' },
    ],
  },
  // { } curly braces
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M7 4C5.5 4 5 4.5 5 6V10.5C5 11.5 4 12 4 12C4 12 5 12.5 5 13.5V18C5 19.5 5.5 20 7 20' },
      { d: 'M17 4C18.5 4 19 4.5 19 6V10.5C19 11.5 20 12 20 12C20 12 19 12.5 19 13.5V18C19 19.5 18.5 20 17 20' },
    ],
  },
  // Terminal / command prompt
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M4 6C4 4.9 4.9 4 6 4H18C19.1 4 20 4.9 20 6V18C20 19.1 19.1 20 18 20H6C4.9 20 4 19.1 4 18V6Z' },
      { d: 'M8 9L11 12L8 15' },
      { d: 'M13 15H16' },
    ],
  },
  // Git branch
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M6 3V15' },
      { d: 'M18 9C18 10.657 16.657 12 15 12C13.343 12 12 10.657 12 9C12 7.343 13.343 6 15 6C16.657 6 18 7.343 18 9Z' },
      { d: 'M6 3C6 4.657 7.343 6 9 6C10.657 6 12 4.657 12 3C12 1.343 10.657 0 9 0C7.343 0 6 1.343 6 3Z', sw: 1.2 },
      { d: 'M6 18C6 19.657 7.343 21 9 21C10.657 21 12 19.657 12 18C12 16.343 10.657 15 9 15C7.343 15 6 16.343 6 18Z', sw: 1.2 },
      { d: 'M6 15C6 12 9 12 12 9' },
    ],
  },
  // Database / cylinder
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M12 4C16.418 4 20 5.343 20 7C20 8.657 16.418 10 12 10C7.582 10 4 8.657 4 7C4 5.343 7.582 4 12 4Z' },
      { d: 'M4 7V17C4 18.657 7.582 20 12 20C16.418 20 20 18.657 20 17V7' },
      { d: 'M20 12C20 13.657 16.418 15 12 15C7.582 15 4 13.657 4 12' },
    ],
  },
  // Cloud upload / deploy
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M12 16V8M9 11L12 8L15 11' },
      { d: 'M7 18C5.343 18 4 16.657 4 15C4 13.343 5.343 12 7 12C7 9.791 8.791 8 11 8C12.304 8 13.457 8.63 14.197 9.603C14.461 9.534 14.726 9.5 15 9.5C16.933 9.5 18.5 11.067 18.5 13C18.5 13.034 18.499 13.067 18.498 13.1C19.393 13.568 20 14.514 20 15.6C20 17.478 18.433 19 16.5 19' },
    ],
  },
  // Wifi / signal
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M1.5 8.5C5.5 4.5 10.5 2.5 12 2.5C13.5 2.5 18.5 4.5 22.5 8.5' },
      { d: 'M5 12C7.5 9.5 9.5 8 12 8C14.5 8 16.5 9.5 19 12' },
      { d: 'M8.5 15.5C9.5 14.5 10.5 13.5 12 13.5C13.5 13.5 14.5 14.5 15.5 15.5' },
      { d: 'M12 19.5C12 19.5 12 19.5 12 19.5', sw: 2.5, lc: 'round' },
    ],
  },
  // Bug / octagon
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M8 8H16M8 12H16M8 16H13' },
      { d: 'M12 22C8.134 22 5 18.866 5 15V9L8 6H16L19 9V15C19 18.866 15.866 22 12 22Z' },
      { d: 'M9 6V4.5C9 3.672 9.672 3 10.5 3H13.5C14.328 3 15 3.672 15 4.5V6' },
      { d: 'M5 10H2M19 10H22M5 14H2M19 14H22' },
    ],
  },
  // Layers / stack
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M2 12L12 17L22 12' },
      { d: 'M2 7L12 12L22 7L12 2L2 7Z' },
      { d: 'M2 17L12 22L22 17' },
    ],
  },
  // CPU / chip
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M9 4H15V20H9V4Z', sw: 1 },
      { d: 'M7 7H17V17H7V7Z' },
      { d: 'M10 10H14V14H10V10Z', sw: 1 },
      { d: 'M9 2V4M12 2V4M15 2V4M9 20V22M12 20V22M15 20V22' },
      { d: 'M4 9H7M4 12H7M4 15H7M17 9H20M17 12H20M17 15H20' },
    ],
  },
  // API plug / connector
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M14 3V10H18L12 21V14H8L14 3Z' },
    ],
  },
  // Puzzle piece / integration
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M4 4H10V7C10 8.105 10.895 9 12 9C13.105 9 14 8.105 14 7V4H20V10H17C15.895 10 15 10.895 15 12C15 13.105 15.895 14 17 14H20V20H14V17C14 15.895 13.105 15 12 15C10.895 15 10 15.895 10 17V20H4V14H7C8.105 14 9 13.105 9 12C9 10.895 8.105 10 7 10H4V4Z' },
    ],
  },
  // Function / lambda
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M6 4L10 16L14 8L18 20' },
      { d: 'M4 12H8' },
    ],
  },
  // Package / box
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M12 2L2 7V17L12 22L22 17V7L12 2Z' },
      { d: 'M2 7L12 12M12 12L22 7M12 12V22' },
      { d: 'M7 4.5L17 9.5' },
    ],
  },
  // Lock / security
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M6 11V7C6 4.239 8.239 2 11 2H13C15.761 2 18 4.239 18 7V11' },
      { d: 'M4 11H20V22H4V11Z' },
      { d: 'M12 16V18' },
    ],
  },
  // Compass / navigation
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2C6.477 2 2 6.477 2 12C2 17.523 6.477 22 12 22Z' },
      { d: 'M16.24 7.76L14.12 14.12L7.76 16.24L9.88 9.88L16.24 7.76Z' },
    ],
  },
  // Sliders / settings
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M4 6H20M4 12H20M4 18H20' },
      { d: 'M8 4V8M8 6H4M8 6H12' },
      { d: 'M16 10V14M16 12H12M16 12H20' },
      { d: 'M10 16V20M10 18H6M10 18H14' },
    ],
  },
  // Rocket / launch
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M12 2C12 2 7 6 7 12V15L9 17H15L17 15V12C17 6 12 2 12 2Z' },
      { d: 'M9 17L7 21H17L15 17' },
      { d: 'M12 9V12M12 14H12.01' },
      { d: 'M7 14L5 16M17 14L19 16' },
    ],
  },
  // Webhook / circular arrows
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3' },
      { d: 'M18 3L21 6L18 9' },
      { d: 'M21 6H15' },
      { d: 'M9 12H15M12 9V15' },
    ],
  },
  // Variables / dollar
  {
    viewBox: '0 0 24 24',
    paths: [
      { d: 'M7 8H15C16.657 8 18 9.343 18 11C18 12.657 16.657 14 15 14H9C7.343 14 6 15.343 6 17C6 18.657 7.343 20 9 20H18' },
      { d: 'M12 6V8M12 20V22' },
    ],
  },
]

// ─── Place icons on a grid with jitter ────────────────────────────────────────
function seededRandom(seed: number) {
  let s = seed
  return () => {
    s = (s * 1664525 + 1013904223) & 0xffffffff
    return (s >>> 0) / 0xffffffff
  }
}

const icons = computed(() => {
  const rand = seededRandom(42)
  const count = Math.min(props.count, 24)
  const cols = 5
  const rows = Math.ceil(count / cols)
  const result = []

  for (let i = 0; i < count; i++) {
    const def = iconDefs[i % iconDefs.length]
    const col = i % cols
    const row = Math.floor(i / cols)

    // Base grid position with jitter
    const baseX = (col / (cols - 1)) * 85 + 5
    const baseY = (row / Math.max(rows - 1, 1)) * 80 + 8
    const jitterX = (rand() - 0.5) * 14
    const jitterY = (rand() - 0.5) * 14

    result.push({
      id: i,
      x: Math.max(2, Math.min(92, baseX + jitterX)),
      y: Math.max(2, Math.min(90, baseY + jitterY)),
      size: 34 + rand() * 18,                // 34–52px
      rotate: (rand() - 0.5) * 30,            // ±15 deg
      floatY: 12 + rand() * 14,               // 12–26px vertical travel
      duration: 3 + rand() * 3,               // 3–6s float period
      delay: rand() * 1.5,                    // stagger start (max 1.5s)
      drawDelay: i * 0.06,                    // staggered draw
      viewBox: def.viewBox,
      paths: def.paths,
    })
  }
  return result
})
</script>

<style scoped>
/* ── Container ─────────────────────────────────────────────────────────────── */
.dev-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
  opacity: v-bind(opacity);
}

/* ── Each icon wrapper ──────────────────────────────────────────────────────── */
.dev-icon {
  position: absolute;
  transform:
    translate(-50%, -50%)
    rotate(var(--rotate));
  width: var(--size);
  height: var(--size);
  /* float animation starts after draw finishes */
  animation: float var(--float-duration) ease-in-out infinite alternate;
  animation-delay: calc(var(--draw-delay) + 1.2s + var(--float-delay));
  animation-fill-mode: both;
}

/* ── Draw animation: each path traces itself ────────────────────────────────── */
.draw-path {
  /* pathLength="100" is set on every <path>, so dasharray/offset in [0,100] space */
  stroke-dasharray: 100;
  stroke-dashoffset: 100;
  animation: draw 0.9s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  animation-delay: var(--path-delay);
}

@keyframes draw {
  to {
    stroke-dashoffset: 0;
  }
}

/* ── Floating animation ─────────────────────────────────────────────────────── */
@keyframes float {
  from {
    transform:
      translate(-50%, -50%)
      rotate(var(--rotate))
      translateY(0px);
  }
  to {
    transform:
      translate(-50%, -50%)
      rotate(var(--rotate))
      translateY(calc(var(--float-y) * -1));
  }
}

/* ── Respect reduced motion preference ──────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
  .draw-path {
    animation: none;
    stroke-dashoffset: 0;
  }
  .dev-icon {
    animation: none;
  }
}
</style>