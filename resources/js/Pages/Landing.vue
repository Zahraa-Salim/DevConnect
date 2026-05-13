<template>
  <Head title="DevConnect LB — From Zero to Contributor" />

  <div class="landing-root">

    <!-- ── NAV ─────────────────────────────────────────────────────────────── -->
    <header :class="['landing-nav', scrolled && 'landing-nav--scrolled']">
      <div class="landing-nav__inner">
        <Logo />
        <div class="flex items-center gap-4">
          <Link href="/login">
            <Button variant="ghost">Sign in</Button>
          </Link>
          <Link href="/register">
            <Button variant="primary">Get started</Button>
          </Link>
        </div>
      </div>
    </header>

    <main>

      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 1 — HERO  (full viewport height)
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="hero">

        <!-- Animated grid background -->
        <div class="hero__grid" aria-hidden="true"></div>

        <!-- Floating orbs -->
        <div class="hero__orb hero__orb--1" aria-hidden="true"></div>
        <div class="hero__orb hero__orb--2" aria-hidden="true"></div>
        <div class="hero__orb hero__orb--3" aria-hidden="true"></div>

        <!-- DevBackground icons layer -->
        <DevBackground :opacity="0.10" :count="20" color="#534AB7" />

        <div class="hero__content">

          <!-- Eyebrow pill -->
          <div class="hero__eyebrow fade-up" style="--delay: 0ms">
            <span class="hero__eyebrow-dot"></span>
            Built for Lebanon's developer community
          </div>

          <!-- Headline -->
          <h1 class="hero__headline fade-up" style="--delay: 100ms">
            From zero to
            <span class="hero__headline-accent">contributor</span>
            <br />— together.
          </h1>

          <!-- Sub-headline -->
          <p class="hero__sub fade-up" style="--delay: 200ms">
            Discover projects, form teams, and build a professional track record
            through real collaborative work. No more starting from scratch alone.
          </p>

          <!-- CTA row -->
          <div class="hero__cta fade-up" style="--delay: 300ms">
            <Link href="/register">
              <Button variant="primary" size="lg">Start building free</Button>
            </Link>
            <Link href="/projects">
              <Button variant="ghost" size="lg">Browse open projects</Button>
            </Link>
          </div>

          <!-- Trust pills -->
          <div class="hero__trust fade-up" style="--delay: 400ms">
            <span class="hero__trust-pill">🇱🇧 Lebanon-first</span>
            <span class="hero__trust-pill">AI-powered matching</span>
            <span class="hero__trust-pill">Real workspaces</span>
            <span class="hero__trust-pill">Zero experience needed</span>
          </div>
        </div>

        <!-- Scroll cue -->
        <div class="hero__scroll-cue fade-up" style="--delay: 800ms" aria-hidden="true">
          <span class="hero__scroll-line"></span>
          <span class="hero__scroll-label">scroll</span>
        </div>
      </section>


      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 2 — SOCIAL PROOF NUMBERS
      ══════════════════════════════════════════════════════════════════════ -->
    <!--  <section class="stats-band reveal-section">
        <div class="stats-band__inner">
          <div v-for="stat in stats" :key="stat.label" class="stats-band__item">
            <div class="stats-band__number">{{ stat.value }}</div>
            <div class="stats-band__label">{{ stat.label }}</div>
          </div>
        </div>
      </section>
    -->

      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 3 — FOUR PATHS  (staggered cards)
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="paths reveal-section">
        <div class="section-wrap">
          <div class="section-header">
            <p class="section-eyebrow">Four ways in</p>
            <h2 class="section-title">Start wherever you are</h2>
            <p class="section-body">
              Whether you have an idea, a skill, or nothing yet — there's a path for you.
            </p>
          </div>

          <div class="paths__grid">
            <div
              v-for="(path, i) in paths"
              :key="path.title"
              class="path-card reveal-card"
              :style="`--card-delay: ${i * 80}ms`"
            >
              <h3 class="path-card__title">{{ path.title }}</h3>
              <p class="path-card__body">{{ path.body }}</p>
              <Link :href="path.href" class="path-card__link">
                {{ path.cta }} →
              </Link>
            </div>
          </div>
        </div>
      </section>


      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 4 — FEATURES  (alternating rows)
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="features reveal-section">
        <div class="section-wrap">
          <div class="section-header">
            <p class="section-eyebrow">What you get</p>
            <h2 class="section-title">Everything your team needs</h2>
          </div>

          <div class="features__list">
            <div
              v-for="(feat, i) in features"
              :key="feat.label"
              :class="['feature-row reveal-card', i % 2 === 1 && 'feature-row--flip']"
              :style="`--card-delay: 0ms`"
            >
              <!-- Visual panel -->
              <div class="feature-row__visual">
                <div :class="['feature-row__badge', `feature-row__badge--${feat.color}`]">
                  {{ feat.label }}
                </div>
                <div class="feature-row__mockup">
                  <div
                    v-for="line in feat.mockupLines"
                    :key="line.label"
                    :class="['mockup-line', line.accent && 'mockup-line--accent']"
                  >
                    <span
                      class="mockup-line__dot"
                      :style="line.accent ? 'background:#534AB7' : ''"
                    ></span>
                    <span class="mockup-line__text">{{ line.label }}</span>
                    <span v-if="line.tag" :class="['mockup-tag', `mockup-tag--${line.tagColor}`]">
                      {{ line.tag }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Text panel -->
              <div class="feature-row__text">
                <h3 class="feature-row__title">{{ feat.title }}</h3>
                <p class="feature-row__body">{{ feat.body }}</p>
                <ul class="feature-row__bullets">
                  <li v-for="b in feat.bullets" :key="b" class="feature-row__bullet">
                    <span class="bullet-check">✓</span> {{ b }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>


      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 5 — AI FEATURES STRIP
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="ai-strip reveal-section">
        <div class="section-wrap">
          <div class="section-header">
            <p class="section-eyebrow ai-strip__eyebrow">Powered by Claude</p>
            <h2 class="section-title">AI that works invisibly</h2>
            <p class="section-body">
              Six AI features embedded throughout the platform — not a chatbot,
              actual intelligence woven into every step.
            </p>
          </div>

          <div class="ai-strip__grid">
            <div
              v-for="(ai, i) in aiFeatures"
              :key="ai.title"
              class="ai-card reveal-card"
              :style="`--card-delay: ${i * 60}ms`"
            >
              <h4 class="ai-card__title">{{ ai.title }}</h4>
              <p class="ai-card__body">{{ ai.body }}</p>
            </div>
          </div>
        </div>
      </section>


      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 6 — HOW IT WORKS  (vertical timeline)
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="how-it-works reveal-section">
        <div class="section-wrap">
          <div class="section-header">
            <p class="section-eyebrow">The journey</p>
            <h2 class="section-title">Go from idea to shipped in 4 steps</h2>
          </div>

          <div class="timeline">
            <div class="timeline__line" aria-hidden="true"></div>
            <div
              v-for="(step, i) in steps"
              :key="step.title"
              class="timeline__step reveal-card"
              :style="`--card-delay: ${i * 100}ms`"
            >
              <div class="timeline__node">
                <span class="timeline__num">{{ i + 1 }}</span>
              </div>
              <div class="timeline__body">
                <h3 class="timeline__title">{{ step.title }}</h3>
                <p class="timeline__desc">{{ step.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>


      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 7 — TESTIMONIAL / QUOTE
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="quote-section reveal-section">
        <div class="section-wrap">
          <blockquote class="quote">
            <p class="quote__text">
              "Lebanese developers are talented. They just need a place to find
              each other, prove themselves, and build things that matter."
            </p>
            <footer class="quote__footer">
              <span class="quote__author">The DevConnect Team</span>
              <span class="quote__role">Beirut, Lebanon</span>
            </footer>
          </blockquote>
        </div>
      </section>


      <!-- ══════════════════════════════════════════════════════════════════════
           SECTION 8 — FINAL CTA  (dark, full-bleed)
      ══════════════════════════════════════════════════════════════════════ -->
      <section class="final-cta reveal-section">
        <DevBackground :opacity="0.08" :count="16" color="#AFA9EC" />
        <div class="final-cta__inner">
          <h2 class="final-cta__title">Ready to build something real?</h2>
          <p class="final-cta__sub">
            Join Lebanon's growing developer community. Free to start,
            free to grow.
          </p>
          <div class="final-cta__buttons">
            <Link href="/register">
              <Button size="lg" class="final-cta__btn-primary">
                Create your account
              </Button>
            </Link>
            <Link href="/ideas">
              <Button variant="ghost" size="lg" class="final-cta__btn-ghost">
                Explore project ideas
              </Button>
            </Link>
          </div>
        </div>
      </section>

    </main>

    <!-- ── FOOTER ────────────────────────────────────────────────────────── -->
    <footer class="landing-footer">
      <div class="landing-footer__inner">
        <div class="landing-footer__brand">
          <Logo />
          <span class="landing-footer__tagline">From Zero to Contributor — Together</span>
        </div>
        <div class="landing-footer__links">
          <Link href="/login" class="landing-footer__link">Sign in</Link>
          <Link href="/register" class="landing-footer__link">Register</Link>
          <Link href="/ideas" class="landing-footer__link">Ideas</Link>
          <Link href="/projects" class="landing-footer__link">Projects</Link>
        </div>
        <div class="landing-footer__copy">
          © {{ new Date().getFullYear() }} DevConnect LB. Built in Lebanon 🇱🇧
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import Logo from '@/Components/Logo.vue'
import Button from '@/Components/Button.vue'
import DevBackground from '@/Components/DevBackground.vue'

// ── Nav scroll state ──────────────────────────────────────────────────────────
const scrolled = ref(false)
function onScroll() { scrolled.value = window.scrollY > 40 }
onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })

  // Intersection Observer — reveal-section & reveal-card
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('is-visible')
        io.unobserve(e.target)
      }
    })
  }, { threshold: 0.12 })

  document.querySelectorAll('.reveal-section, .reveal-card').forEach(el => io.observe(el))
  onUnmounted(() => { window.removeEventListener('scroll', onScroll); io.disconnect() })
})

// ── Data ──────────────────────────────────────────────────────────────────────
const stats = [
  { value: '4',   label: 'Ways to start a project' },
  { value: '6',   label: 'AI-powered features' },
  { value: '39',  label: 'Database tables' },
  { value: '100%', label: 'Free to start' },
]

const paths = [
  {
    title: 'Post a project',
    body:  'Have an idea or a client? Define roles, recruit your team, and manage everything in one workspace.',
    cta:   'Start a project',
    href:  '/projects/create',
  },
  {
    title: 'Join a project',
    body:  'Browse open projects, apply for a role that matches your stack, and get AI-matched by compatibility.',
    cta:   'Browse projects',
    href:  '/projects',
  },
  {
    title: 'Contribute solo',
    body:  'AI scans GitHub and surfaces real open-source issues matched to your exact skill level and stack.',
    cta:   'Find issues',
    href:  '/contribute',
  },
  {
    title: 'Discover an idea',
    body:  'Let AI generate a full project brief from your interests, or browse community and curated libraries.',
    cta:   'Explore ideas',
    href:  '/ideas',
  },
]

const features = [
  {
    label: 'LIVING BOARD',
    color: 'indigo',
    title: 'A workspace your team will actually use',
    body:  'Every project gets a full workspace from day one — no setup needed. Kanban board, sprints, chat, files, and GitHub sync all live in one place.',
    bullets: [
      'Kanban + sprint board with story points',
      'Real-time group chat via WebSocket',
      'GitHub repo linked — commits and PRs visible inside',
      'Decision log and file sharing built in',
    ],
    mockupLines: [
      { label: 'Build auth system',        accent: false, tag: 'In progress', tagColor: 'blue' },
      { label: 'Design onboarding flow',   accent: true,  tag: 'Active',      tagColor: 'green' },
      { label: 'Set up database schema',   accent: false, tag: 'Done',        tagColor: 'gray' },
      { label: 'Write API endpoints',      accent: false, tag: 'Backlog',     tagColor: 'gray' },
    ],
  },
  {
    label: 'TRUST LAYER',
    color: 'coral',
    title: 'Real client work, handled professionally',
    body:  'Practice projects are open to all. Real client projects require NDA and team agreement signatures — so your work stays protected and professional.',
    bullets: [
      'Built-in NDA signing flow',
      'Milestone-gated workspace access levels',
      'Team agreement templates',
      'Verified Contributor badge after 2+ shipped projects',
    ],
    mockupLines: [
      { label: 'NDA signed',              accent: true,  tag: '✓',     tagColor: 'green' },
      { label: 'Team agreement signed',   accent: true,  tag: '✓',     tagColor: 'green' },
      { label: 'Milestone 1 unlocked',    accent: false, tag: 'Level 1', tagColor: 'blue' },
      { label: 'Milestone 2 — pending',   accent: false, tag: 'Locked', tagColor: 'gray' },
    ],
  },
  {
    label: 'REPUTATION',
    color: 'indigo',
    title: 'Build proof that speaks for itself',
    body:  'Every project you complete adds to your reputation score, earns skill endorsements from real teammates, and generates AI-written CV bullet points ready to use.',
    bullets: [
      'Reputation score from ratings + completed projects',
      'Skill endorsements from actual teammates',
      'AI-generated CV, portfolio, and LinkedIn post',
      'Contribution DNA — behavioral fingerprint across projects',
    ],
    mockupLines: [
      { label: 'Reputation score',   accent: true,  tag: '82',          tagColor: 'green' },
      { label: 'Vue.js endorsed',    accent: false, tag: '3 teammates',  tagColor: 'blue' },
      { label: 'Laravel endorsed',   accent: false, tag: '2 teammates',  tagColor: 'blue' },
      { label: 'Contribution DNA',   accent: false, tag: 'Builder',      tagColor: 'indigo' },
    ],
  },
]

const aiFeatures = [
  { title: 'Idea Generator',   body: 'Tell Claude your interests and available time. Get a full project brief with roles, requirements, and difficulty.' },
  { title: 'Task Breakdown',   body: 'Claude reads your project description and auto-creates 8–15 starter tasks so your team hits the ground running.' },
  { title: 'Project Matching', body: 'Browse projects ranked by how well they fit your skills, role, and working style — scored by Claude.' },
  { title: 'Issue Ranking',    body: 'Hundreds of GitHub issues ranked by fit. Claude picks the ones at the right level for you.' },
  { title: 'Team Chemistry',   body: 'When a new member joins, Claude scores compatibility and flags alignment or friction with the existing team.' },
  { title: 'Profile Writer',   body: 'On project completion, Claude turns your contribution data into CV bullets, a portfolio blurb, and a LinkedIn post.' },
]

const steps = [
  { title: 'Register and connect GitHub',     desc: 'Create an account, take the Role Discovery Quiz, and link your GitHub repositories so the platform understands your stack.' },
  { title: 'Discover or post a project',      desc: 'Use the AI idea generator, browse the community library, or post your own project and define the roles you need.' },
  { title: 'Form your team',                  desc: 'Review applications with AI chemistry scores, accept members, and watch the workspace auto-populate with starter tasks.' },
  { title: 'Build together and grow',         desc: 'Ship the project in your workspace, rate your teammates, earn verified badges, and get AI-written career assets ready to use.' },
]
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════════════════════════
   ROOT
══════════════════════════════════════════════════════════════════════════════ */
.landing-root {
  background: #F8F8FC;
  color: #444441;
  overflow-x: hidden;
}

/* ══════════════════════════════════════════════════════════════════════════════
   NAV
══════════════════════════════════════════════════════════════════════════════ */
.landing-nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  transition: background 0.3s, box-shadow 0.3s, backdrop-filter 0.3s;
}
.landing-nav--scrolled {
  background: rgba(248, 248, 252, 0.85);
  backdrop-filter: blur(12px);
  box-shadow: 0 1px 0 rgba(83, 74, 183, 0.08);
}
.landing-nav__inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* ══════════════════════════════════════════════════════════════════════════════
   HERO — full viewport
══════════════════════════════════════════════════════════════════════════════ */
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 120px 2rem 80px;
}

/* Grid lines background */
.hero__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(83, 74, 183, 0.06) 1px, transparent 1px),
    linear-gradient(90deg, rgba(83, 74, 183, 0.06) 1px, transparent 1px);
  background-size: 60px 60px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
}

/* Soft glow orbs */
.hero__orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.hero__orb--1 {
  width: 600px; height: 600px;
  background: rgba(83, 74, 183, 0.12);
  top: -100px; right: -100px;
  animation: orb-drift 12s ease-in-out infinite alternate;
}
.hero__orb--2 {
  width: 400px; height: 400px;
  background: rgba(216, 90, 48, 0.08);
  bottom: 0; left: -80px;
  animation: orb-drift 9s ease-in-out infinite alternate-reverse;
}
.hero__orb--3 {
  width: 300px; height: 300px;
  background: rgba(83, 74, 183, 0.07);
  top: 40%; left: 60%;
  animation: orb-drift 15s ease-in-out infinite alternate;
}
@keyframes orb-drift {
  from { transform: translate(0, 0) scale(1); }
  to   { transform: translate(30px, -30px) scale(1.08); }
}

.hero__content {
  position: relative;
  z-index: 2;
  max-width: 780px;
  text-align: center;
  margin: 0 auto;
}

/* Eyebrow */
.hero__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(83, 74, 183, 0.08);
  border: 1px solid rgba(83, 74, 183, 0.2);
  border-radius: 100px;
  padding: 6px 16px;
  font-size: 13px;
  font-weight: 500;
  color: #534AB7;
  margin-bottom: 28px;
}
.hero__eyebrow-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #D85A30;
  animation: pulse-dot 2s ease-in-out infinite;
}
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.6; transform: scale(0.8); }
}

/* Headline */
.hero__headline {
  font-size: clamp(2.6rem, 6vw, 4.2rem);
  font-weight: 600;
  line-height: 1.15;
  letter-spacing: -0.03em;
  color: #3C3489;
  margin-bottom: 24px;
}
.hero__headline-accent {
  position: relative;
  color: #534AB7;
}
.hero__headline-accent::after {
  content: '';
  position: absolute;
  left: 0; right: 0;
  bottom: -4px;
  height: 4px;
  background: linear-gradient(90deg, #D85A30, #534AB7);
  border-radius: 2px;
  transform: scaleX(0);
  transform-origin: left;
  animation: underline-grow 0.8s 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
@keyframes underline-grow {
  to { transform: scaleX(1); }
}

/* Sub */
.hero__sub {
  font-size: clamp(1rem, 2vw, 1.2rem);
  line-height: 1.65;
  color: #888780;
  max-width: 560px;
  margin: 0 auto 36px;
}

/* CTA row */
.hero__cta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 40px;
}

/* Trust pills */
.hero__trust {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}
.hero__trust-pill {
  font-size: 12px;
  color: #888780;
  background: rgba(255,255,255,0.7);
  border: 1px solid rgba(83,74,183,0.12);
  border-radius: 100px;
  padding: 4px 14px;
  backdrop-filter: blur(4px);
}

/* Scroll cue */
.hero__scroll-cue {
  position: absolute;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  z-index: 2;
}
.hero__scroll-line {
  width: 1px;
  height: 48px;
  background: linear-gradient(to bottom, rgba(83,74,183,0.5), transparent);
  animation: scroll-line 2s ease-in-out infinite;
}
@keyframes scroll-line {
  0%   { transform: scaleY(0); transform-origin: top; opacity: 0; }
  50%  { transform: scaleY(1); opacity: 1; }
  100% { transform: scaleY(0); transform-origin: bottom; opacity: 0; }
}
.hero__scroll-label {
  font-size: 10px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: #888780;
}

/* ── Entrance animations ────────────────────────────────────────────────────── */
.fade-up {
  opacity: 0;
  transform: translateY(24px);
  animation: fade-up-in 0.7s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  animation-delay: var(--delay, 0ms);
}
@keyframes fade-up-in {
  to { opacity: 1; transform: translateY(0); }
}

/* ══════════════════════════════════════════════════════════════════════════════
   STATS BAND
══════════════════════════════════════════════════════════════════════════════ */
.stats-band {
  background: #534AB7;
  padding: 40px 2rem;
}
.stats-band__inner {
  max-width: 900px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
  text-align: center;
}
@media (max-width: 640px) {
  .stats-band__inner { grid-template-columns: repeat(2, 1fr); }
}
.stats-band__number {
  font-size: 2.4rem;
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.03em;
}
.stats-band__label {
  font-size: 12px;
  color: #AFA9EC;
  margin-top: 4px;
  font-weight: 500;
}

/* ══════════════════════════════════════════════════════════════════════════════
   SECTION UTILITIES
══════════════════════════════════════════════════════════════════════════════ */
.section-wrap {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
}
.section-header {
  text-align: center;
  margin-bottom: 56px;
}
.section-eyebrow {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #D85A30;
  margin-bottom: 12px;
}
.section-title {
  font-size: clamp(1.7rem, 3.5vw, 2.4rem);
  font-weight: 600;
  color: #3C3489;
  letter-spacing: -0.02em;
  line-height: 1.2;
  margin-bottom: 16px;
}
.section-body {
  font-size: 1rem;
  color: #888780;
  max-width: 520px;
  margin: 0 auto;
  line-height: 1.65;
}

/* ══════════════════════════════════════════════════════════════════════════════
   PATHS
══════════════════════════════════════════════════════════════════════════════ */
.paths {
  padding: 100px 0;
  background: #fff;
}
.paths__grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}
@media (max-width: 900px) {
  .paths__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
  .paths__grid { grid-template-columns: 1fr; }
}

.path-card {
  background: #F8F8FC;
  border: 1px solid rgba(83,74,183,0.1);
  border-radius: 16px;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
}
.path-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(83,74,183,0.1);
  border-color: rgba(83,74,183,0.3);
}
.path-card__title { font-size: 1.1rem; font-weight: 600; color: #3C3489; }
.path-card__body  { font-size: 0.875rem; color: #888780; line-height: 1.6; flex: 1; }
.path-card__link  { font-size: 0.8125rem; font-weight: 600; color: #534AB7; }

/* ══════════════════════════════════════════════════════════════════════════════
   FEATURES
══════════════════════════════════════════════════════════════════════════════ */
.features {
  padding: 100px 0;
  background: #F8F8FC;
}
.features__list { display: flex; flex-direction: column; gap: 80px; }

.feature-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}
.feature-row--flip { direction: rtl; }
.feature-row--flip > * { direction: ltr; }

@media (max-width: 768px) {
  .feature-row { grid-template-columns: 1fr; }
  .feature-row--flip { direction: ltr; }
}

/* Visual panel */
.feature-row__visual {
  background: #fff;
  border: 1px solid rgba(83,74,183,0.1);
  border-radius: 20px;
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: 0 4px 24px rgba(83,74,183,0.06);
}
.feature-row__badge {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 4px 12px;
  border-radius: 100px;
  display: inline-flex;
  align-self: flex-start;
}
.feature-row__badge--indigo { background: #EEEDFE; color: #534AB7; }
.feature-row__badge--coral  { background: #FAECE7; color: #D85A30; }

/* Mockup lines */
.feature-row__mockup { display: flex; flex-direction: column; gap: 12px; }
.mockup-line {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  background: #F8F8FC;
  border-radius: 10px;
  border: 1px solid rgba(83,74,183,0.07);
}
.mockup-line--accent {
  background: #EEEDFE;
  border-color: rgba(83,74,183,0.2);
}
.mockup-line__dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #D3D1C7;
  flex-shrink: 0;
}
.mockup-line__text { font-size: 13px; color: #444441; flex: 1; }
.mockup-tag {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 100px;
}
.mockup-tag--blue   { background: #E6F1FB; color: #185FA5; }
.mockup-tag--green  { background: #E1F5EE; color: #0F6E56; }
.mockup-tag--gray   { background: #F1EFE8; color: #5F5E5A; }
.mockup-tag--indigo { background: #EEEDFE; color: #534AB7; }

/* Text panel */
.feature-row__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #3C3489;
  letter-spacing: -0.02em;
  margin-bottom: 14px;
}
.feature-row__body {
  font-size: 0.9375rem;
  color: #888780;
  line-height: 1.65;
  margin-bottom: 20px;
}
.feature-row__bullets { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.feature-row__bullet  { font-size: 0.875rem; color: #444441; display: flex; align-items: flex-start; gap: 8px; }
.bullet-check         { color: #1D9E75; font-weight: 700; flex-shrink: 0; }

/* ══════════════════════════════════════════════════════════════════════════════
   AI STRIP
══════════════════════════════════════════════════════════════════════════════ */
.ai-strip {
  padding: 100px 0;
  background: #14121F;
}
.ai-strip__eyebrow { color: #D85A30; }
.ai-strip .section-title { color: #EEEDFE; }
.ai-strip .section-body  { color: #AFA9EC; }

.ai-strip__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
@media (max-width: 900px) {
  .ai-strip__grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
  .ai-strip__grid { grid-template-columns: 1fr; }
}

.ai-card {
  background: #1E1A2E;
  border: 1px solid #2E2848;
  border-radius: 16px;
  padding: 28px 24px;
  transition: transform 0.25s, border-color 0.25s;
}
.ai-card:hover {
  transform: translateY(-4px);
  border-color: rgba(83,74,183,0.5);
}
.ai-card__title { font-size: 1rem; font-weight: 600; color: #EEEDFE; margin-bottom: 8px; }
.ai-card__body  { font-size: 0.875rem; color: #AFA9EC; line-height: 1.6; }

/* ══════════════════════════════════════════════════════════════════════════════
   HOW IT WORKS — timeline
══════════════════════════════════════════════════════════════════════════════ */
.how-it-works {
  padding: 100px 0;
  background: #fff;
}

.timeline {
  position: relative;
  max-width: 640px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.timeline__line {
  position: absolute;
  left: 24px;
  top: 0; bottom: 0;
  width: 1px;
  background: linear-gradient(to bottom, #534AB7, rgba(83,74,183,0.1));
}
.timeline__step {
  display: flex;
  gap: 32px;
  padding: 32px 0;
  position: relative;
}
.timeline__node {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #534AB7;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  z-index: 1;
}
.timeline__num  { font-size: 1rem; font-weight: 700; color: #fff; }
.timeline__body { padding-top: 10px; }
.timeline__title { font-size: 1.1rem; font-weight: 600; color: #3C3489; margin-bottom: 8px; }
.timeline__desc  { font-size: 0.9rem; color: #888780; line-height: 1.65; }

/* ══════════════════════════════════════════════════════════════════════════════
   QUOTE
══════════════════════════════════════════════════════════════════════════════ */
.quote-section {
  padding: 80px 0;
  background: #EEEDFE;
}
.quote {
  max-width: 700px;
  margin: 0 auto;
  text-align: center;
  padding: 0 2rem;
}
.quote__text {
  font-size: clamp(1.2rem, 2.5vw, 1.6rem);
  font-weight: 500;
  color: #3C3489;
  line-height: 1.5;
  font-style: italic;
  margin-bottom: 24px;
}
.quote__footer {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.quote__author { font-weight: 600; color: #534AB7; font-size: 0.9rem; }
.quote__role   { font-size: 0.8rem; color: #888780; }

/* ══════════════════════════════════════════════════════════════════════════════
   FINAL CTA
══════════════════════════════════════════════════════════════════════════════ */
.final-cta {
  position: relative;
  background: #14121F;
  padding: 120px 2rem;
  text-align: center;
  overflow: hidden;
}
.final-cta__inner { position: relative; z-index: 2; max-width: 640px; margin: 0 auto; }
.final-cta__title {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 700;
  color: #fff;
  letter-spacing: -0.03em;
  margin-bottom: 16px;
}
.final-cta__sub {
  font-size: 1rem;
  color: #AFA9EC;
  margin-bottom: 40px;
  line-height: 1.65;
}
.final-cta__buttons {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px;
}
.final-cta__btn-ghost { color: #AFA9EC !important; border-color: #2E2848 !important; }
.final-cta__btn-ghost:hover { color: #fff !important; }

/* ══════════════════════════════════════════════════════════════════════════════
   FOOTER
══════════════════════════════════════════════════════════════════════════════ */
.landing-footer {
  background: #0D0B17;
  padding: 48px 0;
}
.landing-footer__inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 24px;
}
.landing-footer__brand {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.landing-footer__tagline { font-size: 12px; color: #AFA9EC; }
.landing-footer__links {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
}
.landing-footer__link { font-size: 13px; color: #AFA9EC; transition: color 0.2s; }
.landing-footer__link:hover { color: #fff; }
.landing-footer__copy { font-size: 12px; color: #5F5E5A; }

/* ══════════════════════════════════════════════════════════════════════════════
   SCROLL REVEAL
══════════════════════════════════════════════════════════════════════════════ */
.reveal-section {
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 0.7s cubic-bezier(0.4,0,0.2,1), transform 0.7s cubic-bezier(0.4,0,0.2,1);
}
.reveal-section.is-visible { opacity: 1; transform: none; }

.reveal-card {
  opacity: 0;
  transform: translateY(24px);
  transition:
    opacity 0.6s cubic-bezier(0.4,0,0.2,1) var(--card-delay, 0ms),
    transform 0.6s cubic-bezier(0.4,0,0.2,1) var(--card-delay, 0ms);
}
.is-visible .reveal-card { opacity: 1; transform: none; }

/* ── Reduced motion ─────────────────────────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
  .fade-up, .reveal-section, .reveal-card { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
  .hero__orb { animation: none; }
  .hero__scroll-line { animation: none; }
  .hero__eyebrow-dot { animation: none; }
  .hero__headline-accent::after { transform: scaleX(1); animation: none; }
}
</style>