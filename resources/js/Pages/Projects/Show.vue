
Copy

<template>
  <Head :title="project.title" />
 
  <div class="max-w-7xl mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div>
          <h1 class="text-display text-dc-primary-dark mb-2">
            {{ project.title }}
          </h1>
          <div class="flex items-center gap-2 flex-wrap">
            <Badge :variant="project.type === 'practice' ? 'skill' : 'open'">
              {{ project.type === 'practice' ? 'Practice' : 'Real Client' }}
            </Badge>
            <Badge variant="skill">
              {{ statusBadgeText(project.status) }}
            </Badge>
            <div v-if="pulseStatus" class="flex items-center space-x-1.5 ml-1">
              <span
                class="w-2.5 h-2.5 rounded-full"
                :class="{
                  'bg-green-500': pulseStatus === 'resolved',
                  'bg-yellow-500': pulseStatus === 'nudge_sent',
                  'bg-red-500 animate-pulse': pulseStatus === 'at_risk',
                }"
              ></span>
              <span class="text-xs text-dc-muted">
                {{ pulseStatus === 'nudge_sent' ? 'Nudge sent' : pulseStatus === 'at_risk' ? 'At Risk' : 'Healthy' }}
              </span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="isOwner" class="flex gap-2">
        <Link :href="urls.projects.edit(project.id)">
          <Button variant="ghost">Edit project</Button>
        </Link>
        <Button variant="ghost" @click="showStatusMenu = !showStatusMenu">
          ⋯ Status
        </Button>
      </div>
    </div>
 
    <!-- Status transition menu -->
    <div v-if="showStatusMenu && isOwner" class="mb-8 bg-white dark:bg-dc-dark-surface rounded-lg p-4 border border-dc-surface dark:border-dc-dark-border">
      <div class="flex gap-2 flex-wrap">
        <Button
          v-for="transition in validTransitions"
          :key="transition.status"
          variant="ghost"
          size="sm"
          @click="updateStatus(transition.status)"
        >
          {{ transition.label }}
        </Button>
      </div>
    </div>
 
    <!-- Completion banner -->
    <div
      v-if="project.status === 'completed' && (isMember || isOwner)"
      class="flex items-center justify-between gap-4 bg-dc-success-tint border border-dc-success-tint rounded-lg p-4 mb-6"
    >
      <p class="text-body font-semibold text-dc-success-dark">This project is complete 🎉</p>
      <div class="flex gap-2 flex-shrink-0">
        <span v-if="hasRated" class="text-small font-medium text-dc-success-dark px-3 py-1.5">
          ✓ Team rated
        </span>
        <Link v-else :href="urls.projects.rate(project.id)">
          <Button variant="primary" size="sm">Rate your team →</Button>
        </Link>
        <Link :href="urls.projects.suggestions.index(project.id)">
          <Button variant="ghost" size="sm">Generate profile suggestions →</Button>
        </Link>
      </div>
    </div>
 
    <div v-if="ndaRequired" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <p class="text-body font-medium text-yellow-800">NDA signature required</p>
      <p class="text-small text-yellow-600 mt-1">
        You need to sign the Non-Disclosure Agreement before accessing this workspace.
      </p>
      <Link :href="urls.projects.nda(project.id)" class="mt-3 inline-block">
        <Button variant="primary" size="sm">Sign NDA now</Button>
      </Link>
    </div>
 
    <!-- Tab Navigation -->
    <div class="flex mb-8 border-b border-dc-surface dark:border-dc-dark-border">
      <div class="flex gap-4 flex-1 overflow-x-auto">
        <button
          v-for="tab in displayedTabs"
          :key="tab"
          @click="activeTab = tab"
          :class="[
            'px-4 py-3 font-medium text-small border-b-2 transition-colors whitespace-nowrap',
            activeTab === tab
              ? 'border-dc-primary text-dc-primary'
              : 'border-transparent text-dc-muted hover:text-dc-body'
          ]"
        >
          {{ tabDisplayLabel(tab) }}
        </button>
      </div>
      <div v-if="(isMember || isOwner) && !ndaRequired" class="flex items-center pb-2 pl-4 flex-shrink-0">
        <button
          @click="sendAliveSignal"
          :disabled="aliveDisabled"
          :class="[
            'px-3 py-1.5 text-small rounded-lg border border-dc-surface dark:border-dc-dark-border transition-colors',
            aliveDisabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-dc-surface dark:hover:bg-dc-dark-surface'
          ]"
        >
          <span v-if="!justSignaled" class="text-dc-body dark:text-dc-primary-muted">👋 I'm Alive</span>
          <span v-else class="text-green-600">✓ Checked in</span>
        </button>
      </div>
    </div>
 
    <!-- OVERVIEW TAB -->
    <div v-if="activeTab === 'Overview'" class="space-y-8 mb-8">
      <div
        v-if="pulseStatus === 'at_risk'"
        class="mb-4 flex items-start gap-3 p-4 rounded-xl bg-dc-danger-tint border border-dc-danger/20"
      >
        <span class="text-dc-danger text-lg flex-shrink-0">⚠️</span>
        <div>
          <p class="text-body font-semibold text-dc-danger-dark">Project health warning</p>
          <p class="text-small text-dc-danger-dark/80">
            This project hasn't had recent activity. Post a message in the chat or update a task to keep the momentum going.
          </p>
        </div>
      </div>
 
      <!-- Pending Applications (owner only) -->
      <div v-if="isOwner && pendingApplications.length > 0" class="space-y-4 mb-8">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Applicants ({{ pendingApplications.length }})
        </h3>
        <Card v-for="app in pendingApplications" :key="app.id" class="p-5 space-y-4">
          <!-- ROW 1: Applicant header -->
          <div class="flex items-center gap-3">
            <AvatarInitials :initials="getInitials(app.user.name)" size="md" />
            <div class="flex-1 min-w-0">
              <p class="text-body font-medium text-dc-body dark:text-dc-primary-muted">{{ app.user.name }}</p>
              <p class="text-small text-dc-muted capitalize">{{ app.user.role ?? 'Developer' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span class="text-small text-dc-muted">★ {{ app.user.reputation_score ?? 0 }}</span>
              <span class="text-xs px-2 py-1 rounded bg-dc-primary-tint text-dc-primary">{{ app.role?.role_name ?? 'Undecided' }}</span>
            </div>
          </div>
          <!-- ROW 2: Message -->
          <p v-if="app.message" class="text-small text-dc-muted italic">{{ app.message }}</p>
          <!-- ROW 3: Chemistry -->
          <div v-if="app.chemistry" class="border border-dc-surface dark:border-dc-dark-border rounded-lg p-4 bg-dc-surface dark:bg-dc-dark-bg">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-small font-semibold text-dc-body dark:text-dc-primary-muted">Team Chemistry</span>
              <AIBadge />
            </div>
            <template v-if="app.chemistry.label !== 'Unknown'">
              <div class="flex items-center gap-2 mb-2">
                <span
                  :class="[
                    'inline-block px-2 py-0.5 rounded text-xs font-semibold',
                    app.chemistry.label === 'Strong Fit'   ? 'bg-green-100 text-green-700' :
                    app.chemistry.label === 'Good Fit'     ? 'bg-blue-100 text-blue-700' :
                    app.chemistry.label === 'Possible Fit' ? 'bg-amber-100 text-amber-700' :
                                                             'bg-red-100 text-red-700'
                  ]"
                >{{ app.chemistry.label }}</span>
              </div>
              <p class="text-small text-dc-muted mb-3">{{ app.chemistry.summary }}</p>
              <div :class="['grid gap-4', app.chemistry.friction?.length ? 'grid-cols-2' : 'grid-cols-1']">
                <div v-if="app.chemistry.alignment?.length">
                  <div class="text-xs font-semibold text-dc-muted uppercase tracking-wider mb-1">✓ Alignment</div>
                  <ul class="space-y-0.5">
                    <li v-for="(item, i) in app.chemistry.alignment" :key="i" class="text-small text-dc-body dark:text-dc-primary-muted">{{ item }}</li>
                  </ul>
                </div>
                <div v-if="app.chemistry.friction?.length">
                  <div class="text-xs font-semibold text-dc-muted uppercase tracking-wider mb-1">⚠ Friction</div>
                  <ul class="space-y-0.5">
                    <li v-for="(item, i) in app.chemistry.friction" :key="i" class="text-small text-dc-body dark:text-dc-primary-muted">{{ item }}</li>
                  </ul>
                </div>
              </div>
            </template>
            <p v-else class="text-small text-dc-muted">Chemistry analysis unavailable</p>
          </div>
          <!-- ROW 4: Action buttons -->
          <div class="flex gap-2">
            <Button size="sm" variant="primary" @click="acceptApplication(app)">Accept</Button>
            <Button size="sm" variant="ghost" class="text-dc-danger" @click="declineApplication(app)">Decline</Button>
          </div>
        </Card>
      </div>
      <p v-else-if="isOwner" class="text-small text-dc-muted mb-8">No pending applications.</p>
      <!-- Description -->
      <div>
        <p class="text-body text-dc-body dark:text-dc-muted whitespace-pre-wrap mb-4">
          {{ project.description }}
        </p>
      </div>
 
      <!-- Tech Stack -->
      <div v-if="project.tech_stack && project.tech_stack.length > 0">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-3">
          Tech Stack
        </h3>
        <div class="flex flex-wrap gap-2">
          <SkillTag
            v-for="tech in project.tech_stack"
            :key="tech"
            :label="tech"
          />
        </div>
      </div>
 
      <!-- Project Info -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card class="p-4 bg-dc-surface dark:bg-dc-dark-border">
          <div class="text-small text-dc-muted uppercase tracking-wider font-medium mb-2">
            Domain
          </div>
          <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
            {{ project.domain || 'Not specified' }}
          </div>
        </Card>
        <Card class="p-4 bg-dc-surface dark:bg-dc-dark-border">
          <div class="text-small text-dc-muted uppercase tracking-wider font-medium mb-2">
            Duration
          </div>
          <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
            {{ project.estimated_duration || 'Not specified' }}
          </div>
        </Card>
        <Card class="p-4 bg-dc-surface dark:bg-dc-dark-border">
          <div class="text-small text-dc-muted uppercase tracking-wider font-medium mb-2">
            Team Size
          </div>
          <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted">
            {{ project.max_members }} people
          </div>
        </Card>
      </div>
 
      <!-- Progress bar (shows when there are tasks) -->
      <div v-if="totalTaskCount > 0">
        <div class="flex items-center justify-between mb-1">
          <span class="text-small text-dc-muted">Progress</span>
          <span class="text-small font-medium text-dc-primary-dark dark:text-dc-primary-muted">
            {{ taskCounts.done }} / {{ totalTaskCount }} tasks done
          </span>
        </div>
        <div class="w-full h-2 bg-dc-surface dark:bg-dc-dark-border rounded-full overflow-hidden">
          <div
            class="h-full bg-dc-primary rounded-full transition-all duration-300"
            :style="{ width: `${Math.round((taskCounts.done / totalTaskCount) * 100)}%` }"
          />
        </div>
      </div>
 
      <!-- Owner -->
      <Card class="p-4 bg-dc-surface dark:bg-dc-dark-border">
        <div class="text-small text-dc-muted uppercase tracking-wider font-medium mb-3">
          Project Owner
        </div>
        <Link
          :href="`/profile/${project.owner_id}`"
          class="inline-flex items-center gap-3 group"
        >
          <img
            v-if="project.owner?.avatar_url"
            :src="project.owner.avatar_url"
            :alt="project.owner.name"
            class="w-11 h-11 rounded-full object-cover"
          />
          <div
            v-else
            class="w-11 h-11 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold"
          >
            {{ project.owner?.name?.charAt(0).toUpperCase() || '?' }}
          </div>
          <div>
            <div class="text-body font-medium text-dc-body dark:text-dc-primary-muted group-hover:text-dc-primary transition-colors">
              {{ project.owner?.name || 'Project owner' }}
            </div>
            <div class="text-small text-dc-muted">
              {{ project.owner?.role || 'Owner' }}
            </div>
          </div>
        </Link>
      </Card>
 
      <!-- Non-member actions -->
      <Card v-if="!isMember && !isOwner" class="p-5 bg-dc-primary-tint/10 dark:bg-dc-primary-dark/10 border-dc-primary-tint">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-1">
              Interested in joining?
            </h3>
            <p v-if="hasApplied" class="text-small text-dc-muted">
              Application pending — the owner will review your request.
            </p>
            <p v-else class="text-small text-dc-muted">
              Apply to join the team or message the owner to ask a question first.
            </p>
          </div>
          <div class="flex flex-col sm:flex-row gap-2">
            <Button
              v-if="!hasApplied && ['open', 'active'].includes(project.status)"
              variant="primary"
              @click="applyingUndecided = true; applyingRole = null; applyMessage = ''"
            >
              Apply
            </Button>
            <Button
              v-if="project.owner_id !== $page.props.auth.user?.id"
              variant="ghost"
              @click="messageProjectOwner"
              :disabled="messagingOwner"
            >
              {{ messagingOwner ? 'Opening...' : 'Message owner' }}
            </Button>
          </div>
        </div>
      </Card>
 
      <!-- Trust Layer (for real client projects) -->
      <div v-if="project.type === 'real_client'">
        <Card class="p-6 bg-dc-surface dark:bg-dc-dark-surface border-dc-surface dark:border-dc-dark-border">
          <div class="text-label-caps text-dc-muted mb-3">
            Real Client Project
          </div>
          <div class="space-y-3 text-body text-dc-body dark:text-dc-muted mb-4">
            <div class="flex items-center justify-between gap-3">
              <span>NDA agreement required before joining</span>
              <span v-if="userNdaSigned" class="text-small font-medium text-dc-success">
                ✓ NDA signed{{ userNdaSignedAt ? ` on ${formatFullDate(userNdaSignedAt)}` : '' }}
              </span>
              <Link v-else :href="urls.projects.nda(project.id)" class="text-small font-medium text-dc-warning hover:underline">
                ! NDA not yet signed
              </Link>
            </div>
            <div class="flex items-center justify-between gap-3">
              <span>Team agreement signed before work begins</span>
              <span v-if="userAgreementSigned" class="text-small font-medium text-dc-success">
                ✓ Team agreement signed
              </span>
              <span v-else class="text-small font-medium text-dc-warning">
                ! Team agreement not yet signed
              </span>
            </div>
            <div class="flex items-center justify-between gap-3">
              <span>Milestone-based repository access</span>
              <span class="text-small font-medium text-dc-body dark:text-dc-primary-muted">
                Your access: Level {{ userAccessLevel }}
              </span>
            </div>
            <div class="text-small text-dc-muted">
              {{ completedMilestonesCount }} of {{ milestones.length }} milestones completed
            </div>
          </div>
          <Button variant="ghost" @click="showTrustLayer = !showTrustLayer">
            {{ showTrustLayer ? 'Hide' : 'View' }} terms
          </Button>
          <div v-if="showTrustLayer" class="mt-4 p-4 bg-white dark:bg-dc-dark-bg border border-dc-surface dark:border-dc-dark-border rounded-md text-small text-dc-muted h-32 overflow-y-auto font-mono">
            CONFIDENTIALITY AGREEMENT — This Non-Disclosure Agreement ("Agreement") is entered into by and between the project owner ("Disclosing Party") and the team member ("Receiving Party"). The Receiving Party agrees to hold all proprietary information in strict confidence...
          </div>
        </Card>
      </div>
 
      <!-- Milestones -->
      <div v-if="project.type === 'real_client'">
        <div class="flex items-center justify-between gap-3 mb-4">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">
            Milestones & Access
          </h3>
          <Badge variant="skill">Level {{ userAccessLevel }}</Badge>
        </div>
 
        <div v-if="milestones.length > 0" class="space-y-4">
          <div
            v-for="milestone in milestones"
            :key="milestone.id"
            class="flex gap-4"
          >
            <div
              :class="[
                'mt-1 w-4 h-4 rounded-full border-2 shrink-0',
                milestone.completed_at ? 'bg-dc-success border-dc-success' : 'bg-white border-dc-muted'
              ]"
            />
            <Card class="p-4 flex-1">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                <div>
                  <h4 class="font-medium text-dc-body dark:text-dc-primary-muted">
                    {{ milestone.title }}
                  </h4>
                  <p v-if="milestone.description" class="text-small text-dc-muted mt-1">
                    {{ milestone.description }}
                  </p>
                  <p v-if="milestone.completed_at" class="text-small text-dc-success mt-2">
                    Completed on {{ formatFullDate(milestone.completed_at) }}
                  </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <Badge variant="skill">Unlocks Level {{ milestone.unlocks_access_level }} access</Badge>
                  <Button
                    v-if="isOwner && !milestone.completed_at"
                    size="sm"
                    variant="ghost"
                    @click="completeMilestone(milestone)"
                  >
                    Mark complete
                  </Button>
                </div>
              </div>
            </Card>
          </div>
        </div>
        <div v-else class="p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg text-small text-dc-muted">
          No milestones have been added yet.
        </div>
 
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="p-3 rounded-lg bg-dc-surface dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border">
            <div class="font-medium text-dc-body dark:text-dc-primary-muted">Level 1</div>
            <div class="text-small text-dc-muted">Limited - Read-only access to project files</div>
          </div>
          <div class="p-3 rounded-lg bg-dc-surface dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border">
            <div class="font-medium text-dc-body dark:text-dc-primary-muted">Level 2</div>
            <div class="text-small text-dc-muted">Standard - Can push to feature branches</div>
          </div>
          <div class="p-3 rounded-lg bg-dc-surface dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border">
            <div class="font-medium text-dc-body dark:text-dc-primary-muted">Level 3</div>
            <div class="text-small text-dc-muted">Full - Full repository access and deployment</div>
          </div>
        </div>
      </div>
 
      <!-- Open Roles Section (non-members only) -->

      <div v-if="!isMember && !isOwner">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Open Roles
        </h3>
 
        <!-- Show message if user has applied -->
        <div v-if="hasApplied" class="mb-4 bg-dc-primary-tint/10 dark:bg-dc-primary-dark/10 p-4 rounded-lg border border-dc-primary-tint">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-body font-medium text-dc-primary-dark dark:text-dc-primary-muted">
                Application pending
              </p>
              <p class="text-small text-dc-muted">
                Application pending — the owner will review your request.
              </p>
              <p class="text-xs text-dc-muted mt-1">
                {{ userApplication?.role?.role_name || 'Undecided' }} role · Submitted {{ formatDate(userApplication.created_at) }}
              </p>
            </div>
            <Button
              variant="ghost"
              size="sm"
              @click="withdrawApplication"
              class="text-dc-danger"
            >
              Withdraw
            </Button>
          </div>
        </div>
 
        <!-- Application blocked message -->
        <div v-else-if="!['open', 'active'].includes(project.status)" class="mb-4 bg-dc-surface p-4 rounded-lg border border-dc-surface">
          <p class="text-body text-dc-muted">
            This project is no longer accepting applications.
          </p>
        </div>
 
        <!-- Roles list -->
        <div v-else class="space-y-4 mb-6">
          <Card v-for="role in project.roles" :key="role.id" class="p-6">
            <div class="flex justify-between items-start mb-3">
              <h4 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">
                {{ role.role_name }}
              </h4>
              <span class="text-small text-dc-muted">
                {{ Math.max(0, role.slots - (role.filled || 0)) }} slot{{ Math.max(0, role.slots - (role.filled || 0)) !== 1 ? 's' : '' }} open
              </span>
            </div>
            <p class="text-body text-dc-body dark:text-dc-muted mb-4">
              {{ role.description || 'No description provided' }}
            </p>
 
            <!-- Apply form (inline) -->
            <div v-if="applyingRole === role.id" class="pt-4 border-t border-dc-surface dark:border-dc-dark-border">
              <Textarea
                v-model="applyMessage"
                placeholder="Tell the owner why you'd be a good fit (optional)"
                :rows="3"
                class="mb-3"
              />
              <div class="flex gap-2">
                <Button
                  variant="primary"
                  @click="submitApplication(role.id)"
                  :disabled="isSubmittingApplication"
                >
                  {{ isSubmittingApplication ? 'Submitting...' : 'Submit application' }}
                </Button>
                <Button
                  variant="ghost"
                  @click="applyingRole = null"
                >
                  Cancel
                </Button>
              </div>
            </div>
 
            <!-- Apply button -->
            <Button
              v-else
              variant="ghost"
              @click="applyingRole = role.id; applyMessage = ''"
            >
              Apply as {{ role.role_name }}
            </Button>
          </Card>
        </div>
 
        <!-- Apply as Undecided -->
        <div v-if="['open', 'active'].includes(project.status) && !applyingUndecided && !hasApplied">
          <button
            @click="applyingUndecided = true; applyMessage = ''"
            class="text-dc-primary hover:underline text-small font-medium"
          >
            Apply as Undecided
          </button>
        </div>
 
        <!-- Undecided apply form -->
        <div v-if="applyingUndecided && ['open', 'active'].includes(project.status)" class="mt-4 p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg bg-dc-page-bg dark:bg-dc-dark-bg">
          <p class="text-body font-medium text-dc-body dark:text-dc-primary-muted mb-3">
            Apply as Undecided
          </p>
          <p class="text-small text-dc-muted mb-3">
            Not sure which role? Apply and explore what might be a good fit.
          </p>
          <Textarea
            v-model="applyMessage"
            placeholder="Tell the owner why you'd be a good fit (optional)"
            :rows="3"
            class="mb-3"
          />
          <div class="flex gap-2">
            <Button
              variant="primary"
              @click="submitApplication(null)"
              :disabled="isSubmittingApplication"
            >
              {{ isSubmittingApplication ? 'Submitting...' : 'Submit application' }}
            </Button>
            <Button
              variant="ghost"
              @click="applyingUndecided = false"
            >
              Cancel
            </Button>
          </div>
        </div>
      </div>
 
      <!-- Idea Source -->
      <div v-if="project.idea" class="bg-dc-primary-tint/10 dark:bg-dc-primary-dark/10 p-4 rounded-lg border border-dc-primary-tint">
        <p class="text-small text-dc-body dark:text-dc-primary-muted">
          Created from idea: <strong>{{ project.idea.title }}</strong>
        </p>
      </div>
    </div>
 
    <!-- MEMBERS TAB -->
    <div v-if="activeTab === 'Members'" class="space-y-6 mb-8">
      <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">
        Team Members ({{ project.members.length }})
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card v-for="member in project.members" :key="member.id" class="p-6 flex flex-col items-center text-center">
          <Link
            :href="`/profile/${member.user.id}`"
            class="flex flex-col items-center gap-3 mb-1 hover:text-dc-primary transition-colors"
          >
            <div
              v-if="!member.user.avatar_url"
              class="w-12 h-12 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary font-bold"
            >
              {{ member.user.name?.charAt(0).toUpperCase() }}
            </div>
            <img
              v-else
              :src="member.user.avatar_url"
              :alt="member.user.name"
              class="w-12 h-12 rounded-full"
            />
            <h4 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted flex items-center justify-center gap-1">
              {{ member.user.name }}
              <CheckCircle v-if="member.user.is_verified" class="w-4 h-4 text-blue-500 flex-shrink-0" />
            </h4>
          </Link>
          <Badge variant="skill" class="mb-2">
            {{ member.role }}
          </Badge>
          <div class="text-xs text-dc-muted mb-1">
            {{ member.user.reputation_score ?? 0 }} rep
          </div>
          <div v-if="member.user.id === project.owner_id" class="text-small text-dc-muted mb-1">
            Owner
          </div>
          <div v-else class="text-small text-dc-muted mb-1">
            Joined {{ formatDate(member.joined_at) }}
          </div>
          <div class="flex items-center justify-center space-x-1 mb-3">
            <span
              class="w-2 h-2 rounded-full"
              :class="{
                'bg-green-500': getActivityForUser(member.user.id) === 'active',
                'bg-yellow-500': getActivityForUser(member.user.id) === 'away',
                'bg-red-500': getActivityForUser(member.user.id) === 'inactive',
                'bg-gray-300': getActivityForUser(member.user.id) === 'never',
              }"
            ></span>
            <span class="text-xs text-dc-muted">{{ getActivityLabel(member.user.id) }}</span>
          </div>
          <div v-if="isOwner && member.user.id !== project.owner_id" class="mt-auto">
            <Button
              size="sm"
              variant="ghost"
              class="text-dc-danger"
              @click="removeMember(member)"
            >
              Remove
            </Button>
          </div>
        </Card>
      </div>
 
      <!-- Leave button (for members) -->
      <div v-if="isMember && !isOwner" class="pt-4 border-t border-dc-surface dark:border-dc-dark-border">
        <Button
          variant="ghost"
          class="text-dc-danger"
          @click="leaveProject"
        >
          Leave project
        </Button>
      </div>
    </div>
 
    <!-- SETTINGS TAB (owner only) -->
    <div v-if="activeTab === 'Settings' && isOwner" class="space-y-6 mb-8">
      <div>
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Project Settings
        </h3>
        <Link :href="urls.projects.edit(project.id)">
          <Button>Edit project</Button>
        </Link>
      </div>
 
      <!-- Manage Roles -->
      <div>
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Manage Roles
        </h3>
        <div class="space-y-3 mb-4">
          <Card v-for="role in project.roles" :key="role.id" class="p-4 flex items-center justify-between">
            <div>
              <h4 class="font-medium text-dc-body dark:text-dc-primary-muted">
                {{ role.role_name }}
              </h4>
              <p class="text-small text-dc-muted">
                {{ role.filled || 0 }} of {{ role.slots }} filled · {{ role.is_open ? 'Open' : 'Closed' }}
              </p>
              <p v-if="role.description" class="text-small text-dc-body dark:text-dc-primary-muted mt-1">
                {{ role.description }}
              </p>
            </div>
            <div class="flex gap-2">
              <Button
                size="sm"
                variant="ghost"
                @click="editRole(role)"
              >
                Edit
              </Button>
              <Button
                size="sm"
                variant="ghost"
                class="text-dc-danger"
                :disabled="(role.filled || 0) > 0"
                @click="deleteRole(role)"
              >
                Delete
              </Button>
            </div>
          </Card>
        </div>
        <Button
          variant="ghost"
          @click="showAddRole = !showAddRole"
        >
          + Add new role
        </Button>
 
        <!-- Add role form -->
        <div v-if="showAddRole" class="mt-4 p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg bg-dc-page-bg dark:bg-dc-dark-bg">
          <div class="mb-3">
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Role name
            </label>
            <TextInput
              v-model="newRoleForm.role_name"
              placeholder="e.g. Frontend Developer"
            />
          </div>
          <div class="mb-3">
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Slots
            </label>
            <Select
              v-model.number="newRoleForm.slots"
              :options="Array.from({ length: 10 }, (_, i) => ({ value: i + 1, label: String(i + 1) }))"
            />
          </div>
          <div class="mb-3">
            <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
              Description
            </label>
            <Textarea
              v-model="newRoleForm.description"
              placeholder="What will this person do?"
              :rows="2"
            />
          </div>
          <div class="flex gap-2">
            <Button
              variant="primary"
              @click="addRole"
              :disabled="!newRoleForm.role_name"
            >
              Create role
            </Button>
            <Button
              variant="ghost"
              @click="showAddRole = false"
            >
              Cancel
            </Button>
          </div>
        </div>
      </div>
 
      <!-- Team Agreement Text -->
      <div v-if="project.type === 'real_client'">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Team Agreement
        </h3>
        <div class="p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg bg-dc-page-bg dark:bg-dc-dark-bg">
          <Textarea
            v-model="teamAgreementTextForm"
            label="Role responsibilities & work split"
            placeholder="Describe team responsibilities, working rhythms, and review expectations."
            :rows="5"
            class="mb-3"
          />
          <Button
            variant="primary"
            :disabled="!teamAgreementTextForm.trim()"
            @click="saveTeamAgreementText"
          >
            Save team agreement
          </Button>
        </div>
      </div>
 
      <!-- Manage Milestones -->
      <div v-if="project.type === 'real_client'">
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-4">
          Manage Milestones
        </h3>
 
        <div class="mb-4 p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg bg-dc-page-bg dark:bg-dc-dark-bg">
          <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_180px] gap-3 mb-3">
            <TextInput
              v-model="newMilestoneForm.title"
              label="Title"
              placeholder="e.g. MVP Demo"
            />
            <TextInput
              v-model="newMilestoneForm.description"
              label="Description"
              placeholder="What does this milestone unlock?"
            />
            <Select
              v-model="newMilestoneForm.unlocks_access_level"
              label="Unlocks access"
              :options="[
                { value: '1', label: 'Level 1' },
                { value: '2', label: 'Level 2' },
                { value: '3', label: 'Level 3' }
              ]"
            />
          </div>
          <Button
            variant="primary"
            :disabled="!newMilestoneForm.title.trim()"
            @click="addMilestone"
          >
            Add milestone
          </Button>
        </div>
 
        <div class="space-y-3">
          <Card v-for="milestone in milestones" :key="milestone.id" class="p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
              <div>
                <h4 class="font-medium text-dc-body dark:text-dc-primary-muted">
                  {{ milestone.title }}
                </h4>
                <p class="text-small text-dc-muted">
                  Level {{ milestone.unlocks_access_level }} access ·
                  {{ milestone.completed_at ? `Completed ${formatFullDate(milestone.completed_at)}` : 'Pending' }}
                </p>
              </div>
              <div class="flex gap-2">
                <Button
                  v-if="!milestone.completed_at"
                  size="sm"
                  variant="ghost"
                  @click="completeMilestone(milestone)"
                >
                  Mark complete
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  class="text-dc-danger"
                  :disabled="Boolean(milestone.completed_at)"
                  @click="deleteMilestone(milestone)"
                >
                  Delete
                </Button>
              </div>
            </div>
          </Card>
          <div v-if="milestones.length === 0" class="p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg text-small text-dc-muted">
            No milestones yet.
          </div>
        </div>
      </div>
 
      <!-- Invite Links -->
      <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">
            Invite Links
          </h3>
          <Button variant="ghost" @click="showInviteForm = !showInviteForm">
            {{ showInviteForm ? 'Cancel' : 'Generate new link' }}
          </Button>
        </div>
 
        <div v-if="showInviteForm" class="mb-4 p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg bg-dc-page-bg dark:bg-dc-dark-bg">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <Select
              v-model="inviteForm.role_id"
              label="Role"
              :options="inviteRoleOptions"
            />
            <Select
              v-model="inviteForm.expires_in"
              label="Expires in"
              :options="inviteExpiryOptions"
            />
            <TextInput
              v-model="inviteForm.max_uses"
              label="Max uses"
              type="number"
              min="1"
              max="100"
              placeholder="Unlimited"
            />
          </div>
          <Button
            variant="primary"
            :disabled="isGeneratingInvite"
            @click="generateInviteLink"
          >
            {{ isGeneratingInvite ? 'Generating...' : 'Generate link' }}
          </Button>
        </div>
 
        <div v-if="generatedInviteUrl" class="mb-4 p-4 border border-dc-primary-tint rounded-lg bg-dc-primary-tint/10">
          <label class="text-small font-medium text-dc-body dark:text-dc-primary-muted block mb-2">
            Share invite link
          </label>
          <div class="flex flex-col sm:flex-row gap-2 mb-3">
            <TextInput
              :model-value="generatedInviteUrl"
              readonly
              class="flex-1"
            />
            <Button variant="primary" @click="copyInviteUrl(generatedInviteUrl, 'generated')">
              {{ copiedInviteKey === 'generated' ? 'Copied!' : 'Copy' }}
            </Button>
          </div>
          <div class="flex flex-wrap gap-3 text-small">
            <a
              :href="whatsAppShareUrl(generatedInviteUrl)"
              target="_blank"
              rel="noreferrer"
              class="text-dc-primary hover:underline"
            >
              Share on WhatsApp
            </a>
            <a
              :href="linkedInShareUrl(generatedInviteUrl)"
              target="_blank"
              rel="noreferrer"
              class="text-dc-primary hover:underline"
            >
              Share on LinkedIn
            </a>
          </div>
        </div>
 
        <div v-if="inviteLinksList.length > 0" class="space-y-3">
          <Card v-for="link in inviteLinksList" :key="link.id" class="p-4">
            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_1fr_0.8fr_0.9fr_0.9fr_auto] gap-3 lg:items-center">
              <div>
                <div class="text-small text-dc-muted mb-1">Role</div>
                <div class="font-medium text-dc-body dark:text-dc-primary-muted">
                  {{ link.role?.role_name || 'Any role' }}
                </div>
              </div>
              <div>
                <div class="text-small text-dc-muted mb-1">URL</div>
                <div class="font-mono text-small text-dc-body dark:text-dc-primary-muted">
                  {{ truncateToken(link.token) }}
                </div>
              </div>
              <div>
                <div class="text-small text-dc-muted mb-1">Status</div>
                <Badge :variant="link.is_valid ? 'open' : 'declined'">
                  {{ inviteStatus(link) }}
                </Badge>
              </div>
              <div>
                <div class="text-small text-dc-muted mb-1">Uses</div>
                <div class="text-small text-dc-body dark:text-dc-primary-muted">
                  {{ formatInviteUses(link) }}
                </div>
              </div>
              <div>
                <div class="text-small text-dc-muted mb-1">Expires</div>
                <div class="text-small text-dc-body dark:text-dc-primary-muted">
                  {{ formatInviteExpiry(link.expires_at) }}
                </div>
              </div>
              <div class="flex gap-2">
                <Button size="sm" variant="ghost" @click="copyInviteUrl(link.full_url, `link-${link.id}`)">
                  {{ copiedInviteKey === `link-${link.id}` ? 'Copied!' : 'Copy' }}
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  class="text-dc-danger"
                  @click="revokeInviteLink(link)"
                >
                  Revoke
                </Button>
              </div>
            </div>
          </Card>
        </div>
 
        <div v-else class="p-4 border border-dc-surface dark:border-dc-dark-border rounded-lg text-small text-dc-muted">
          No invite links yet.
        </div>
      </div>
 
      <!-- Project Pulse History -->
      <div class="mt-8">
        <h4 class="text-label-caps text-dc-muted mb-3">PROJECT PULSE HISTORY</h4>
        <div v-if="pulseHistory.length === 0" class="text-small text-dc-muted">No pulse checks recorded yet.</div>
        <div v-else class="space-y-2">
          <div v-for="entry in pulseHistory" :key="entry.id" class="flex items-center space-x-3 text-small">
            <span
              class="w-2 h-2 rounded-full flex-shrink-0"
              :class="{
                'bg-yellow-500': entry.status === 'nudge_sent',
                'bg-red-500': entry.status === 'at_risk',
                'bg-green-500': entry.status === 'resolved',
              }"
            ></span>
            <span class="text-dc-body dark:text-dc-primary-muted">{{ entry.status === 'nudge_sent' ? 'Nudge sent' : entry.status === 'at_risk' ? 'Marked at risk' : 'Resolved' }}</span>
            <span class="text-dc-muted">{{ formatDate(entry.triggered_at) }}</span>
          </div>
        </div>
      </div>
 
      <!-- Danger Zone -->
      <div class="bg-dc-danger-tint/10 dark:bg-dc-danger/10 border border-dc-danger-tint rounded-lg p-6">
        <h4 class="text-h3 text-dc-danger mb-3">Danger Zone</h4>
        <Button
          variant="ghost"
          class="text-dc-danger hover:bg-dc-danger-tint"
          @click="confirmDelete = true"
        >
          Delete project
        </Button>
      </div>
 
      <!-- Delete confirmation -->
      <div v-if="confirmDelete" class="bg-white dark:bg-dc-dark-surface border border-dc-surface dark:border-dc-dark-border rounded-lg p-6">
        <p class="text-body text-dc-body dark:text-dc-primary-muted mb-4">
          Are you sure you want to delete this project? This action cannot be undone.
        </p>
        <div class="flex gap-3">
          <Button variant="ghost" @click="confirmDelete = false">
            Cancel
          </Button>
          <Button
            variant="ghost"
            class="text-dc-danger hover:bg-dc-danger-tint"
            @click="deleteProject"
          >
            Delete permanently
          </Button>
        </div>
      </div>
    </div>
 
    <!-- BOARD TAB -->
    <div v-if="activeTab === 'Board' && !ndaRequired">
      <!-- Sub-nav -->
      <div class="flex items-center justify-between border-b border-dc-surface dark:border-dc-dark-border mb-0">
        <div class="flex">
          <button
            v-for="view in (['sprint', 'backlog', 'velocity'] as const)"
            :key="view"
            @click="boardView = view"
            :class="[
              'px-4 py-3 text-small font-medium border-b-2 transition-colors capitalize',
              boardView === view
                ? 'border-dc-primary text-dc-primary'
                : 'border-transparent text-dc-muted hover:text-dc-body'
            ]"
          >
            {{ view === 'sprint' ? 'Sprint Board' : view === 'backlog' ? 'Product Backlog' : 'Velocity' }}
          </button>
        </div>
        <div class="flex items-center gap-3 px-4">
          <Button v-if="isOwner && !activeSprint" variant="primary" size="sm" @click="showCreateSprint = true">
            + New Sprint
          </Button>
          <Button v-else-if="isOwner && activeSprint" variant="ghost" size="sm" @click="showCreateSprint = true">
            Plan Next Sprint
          </Button>
          <Button variant="ghost" size="sm" @click="openAddTask">+ Add Task</Button>
          <span class="text-small text-dc-muted">{{ totalTaskCount }} tasks</span>
        </div>
      </div>
 
      <Card
        v-if="totalTaskCount === 0 && isOwner && !sprintBoardLoading && !backlogLoading"
        class="m-4 p-8 text-center"
      >
        <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted mb-2">No tasks yet</h3>
        <p class="text-body text-dc-muted mb-6">
          Start by generating starter tasks with AI, or add your first task manually.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-3">
          <Button variant="primary" @click="generateAiTasks" :isLoading="isGenerating">
            ✨ Generate tasks with AI
          </Button>
          <Button variant="ghost" @click="openAddTask('todo')">
            + Add first task
          </Button>
        </div>
      </Card>
 
      <!-- SPRINT BOARD VIEW -->
      <div v-if="boardView === 'sprint'">
        <div v-if="!activeSprint" class="text-center py-16">
          <h3 class="text-h3 mb-2">No active sprint</h3>
          <p class="text-body text-dc-muted mb-6">Create a sprint and move backlog tasks into it to get started.</p>
          <div class="flex justify-center gap-3">
            <Button v-if="isOwner" variant="primary" @click="showCreateSprint = true">+ Create Sprint</Button>
            <Button variant="ghost" @click="boardView = 'backlog'">View Backlog</Button>
          </div>
        </div>
 
        <template v-else>
          <!-- Sprint header -->
          <div class="bg-dc-primary/5 dark:bg-dc-primary/10 border border-dc-primary/20 rounded-lg p-4 m-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
              <div>
                <h3 class="text-h3 font-semibold">{{ activeSprint.name }}</h3>
                <p v-if="activeSprint.goal" class="text-small text-dc-muted mt-0.5">{{ activeSprint.goal }}</p>
                <p class="text-small text-dc-muted mt-0.5">
                  {{ activeSprint.start_date }} — {{ activeSprint.end_date }}
                </p>
              </div>
              <div class="flex items-center gap-6 text-small">
                <div class="text-center">
                  <div class="text-h3 font-bold text-dc-primary">{{ activeSprint.days_remaining }}</div>
                  <div class="text-dc-muted">days left</div>
                </div>
                <div class="text-center">
                  <div class="text-h3 font-bold text-dc-primary">{{ activeSprint.completed_points }}/{{ activeSprint.total_points }}</div>
                  <div class="text-dc-muted">points done</div>
                </div>
                <div class="flex flex-col items-center">
                  <div class="w-24 bg-dc-surface dark:bg-dc-dark-border rounded-full h-2 mb-1">
                    <div class="bg-dc-primary rounded-full h-2 transition-all" :style="{ width: sprintProgress + '%' }"></div>
                  </div>
                  <span class="text-dc-muted">{{ sprintProgress }}%</span>
                </div>
                <Button v-if="isOwner" variant="ghost" size="sm" @click="showCompleteSprint = true">
                  Complete Sprint
                </Button>
              </div>
            </div>
          </div>
 
          <!-- Filter bar -->
          <div class="flex items-center gap-3 px-4 pb-3 flex-wrap">
            <select v-model="filterRole" class="text-small px-2 py-1.5 border border-dc-surface rounded-md bg-dc-surface dark:bg-dc-dark-surface dark:text-dc-primary-muted">
              <option value="">All roles</option>
              <option v-for="r in project.roles" :key="r.id" :value="r.role_name">{{ r.role_name }}</option>
            </select>
            <select v-model="filterEnergy" class="text-small px-2 py-1.5 border border-dc-surface rounded-md bg-dc-surface dark:bg-dc-dark-surface dark:text-dc-primary-muted">
              <option value="">All energy</option>
              <option value="quick_win">Quick Win</option>
              <option value="deep_work">Deep Work</option>
              <option value="blocked">Blocked</option>
            </select>
            <select v-model="filterAssignee" class="text-small px-2 py-1.5 border border-dc-surface rounded-md bg-dc-surface dark:bg-dc-dark-surface dark:text-dc-primary-muted">
              <option value="">All members</option>
              <option v-for="m in project.members" :key="m.user.id" :value="String(m.user.id)">{{ m.user.name }}</option>
            </select>
            <button v-if="filterRole || filterEnergy || filterAssignee" @click="clearFilters" class="text-small text-dc-primary hover:underline">
              Clear filters
            </button>
          </div>
 
          <div v-if="sprintBoardLoading" class="py-10">
            <Spinner :size="48" message="Loading sprint board..." />
          </div>
 
          <!-- 3 Kanban columns -->
          <div v-else class="grid grid-cols-3 gap-4 px-4 pb-4">
            <div v-for="col in sprintColumns" :key="col.status" class="flex flex-col min-h-[300px]">
              <div class="flex items-center gap-2 mb-3">
                <h4 class="text-small font-semibold text-dc-muted uppercase tracking-wider">{{ col.label }}</h4>
                <span :class="col.countClass" class="text-small font-bold">{{ filteredByStatus(col.status).length }}</span>
              </div>
              <draggable
                v-model="sprintTasks[col.status as keyof typeof sprintTasks]"
                group="sprint-tasks"
                item-key="id"
                class="flex-1 space-y-2 min-h-[80px] rounded-lg transition-colors"
                ghost-class="opacity-40"
                @end="(evt: any) => onDragEnd(evt, col.status)"
              >
                <template #item="{ element }">
                  <TaskCard
                    v-if="passesFilter(element)"
                    :task="element"
                    showQuickComplete
                    @click="openTaskDetail"
                    @quickComplete="quickCompleteTask"
                  />
                </template>
              </draggable>
              <button @click="openAddTask(col.status)" class="mt-2 text-small text-dc-muted hover:text-dc-primary text-left p-2 rounded hover:bg-dc-surface/50 transition-colors">
                + Add task
              </button>
            </div>
          </div>
        </template>
      </div>
 
      <!-- PRODUCT BACKLOG VIEW -->
      <div v-if="boardView === 'backlog'" class="p-4">
        <div v-if="backlogLoading" class="py-8">
          <Spinner :size="48" message="Loading backlog..." />
        </div>
 
        <div v-else-if="backlogTasks.length === 0" class="text-center py-16">
          <h3 class="text-h3 mb-2">Product Backlog is empty</h3>
          <p class="text-body text-dc-muted mb-6">Generate tasks with AI or add them manually.</p>
          <div class="flex justify-center gap-3">
            <Button v-if="isOwner" variant="primary" @click="generateAiTasks" :isLoading="isGenerating">
              ✨ Generate tasks with AI
            </Button>
            <Button variant="ghost" @click="openAddTask()">Add task</Button>
          </div>
        </div>
 
        <template v-else>
          <div class="flex items-center justify-between mb-4">
            <div class="flex gap-2">
              <Button v-if="isOwner" variant="ghost" size="sm" @click="generateAiTasks" :isLoading="isGenerating">
                ✨ AI Generate
              </Button>
              <Button variant="ghost" size="sm" @click="openAddTask()">+ Add task</Button>
            </div>
            <span class="text-small text-dc-muted">{{ backlogTasks.length }} tasks · {{ backlogTotalPoints }} SP</span>
          </div>
 
          <div class="overflow-x-auto rounded-lg border border-dc-surface dark:border-dc-dark-border">
            <table class="w-full">
              <thead>
                <tr class="border-b border-dc-surface bg-dc-surface/50 dark:bg-dc-dark-surface">
                  <th class="py-2 pl-3 w-6"></th>
                  <th class="py-2 text-left text-small font-semibold text-dc-muted uppercase tracking-wider">Task</th>
                  <th class="py-2 w-14 text-center text-small font-semibold text-dc-muted uppercase tracking-wider">Pts</th>
                  <th class="py-2 w-24 text-center text-small font-semibold text-dc-muted uppercase tracking-wider">Priority</th>
                  <th class="py-2 w-28 text-left text-small font-semibold text-dc-muted uppercase tracking-wider">Role</th>
                  <th class="py-2 w-24 text-left text-small font-semibold text-dc-muted uppercase tracking-wider">Energy</th>
                  <th class="py-2 w-28 text-left text-small font-semibold text-dc-muted uppercase tracking-wider">Assignee</th>
                  <th v-if="activeSprint" class="py-2 w-24 pr-3"></th>
                </tr>
              </thead>
              <draggable v-model="backlogTasks" tag="tbody" item-key="id" handle=".drag-handle" @end="onBacklogReorder">
                <template #item="{ element }">
                  <tr class="border-b border-dc-surface/50 hover:bg-dc-surface/30 cursor-pointer" @click="openTaskDetail(element)">
                    <td class="py-2 pl-3">
                      <span class="drag-handle cursor-grab text-dc-muted select-none text-lg leading-none">⠿</span>
                    </td>
                    <td class="py-2 pr-4">
                      <span class="text-small font-medium">{{ element.title }}</span>
                      <span v-if="element.subtasks?.length" class="text-xs text-dc-muted ml-2">
                        ({{ element.subtasks.filter((s: any) => s.status === 'done').length }}/{{ element.subtasks.length }})
                      </span>
                    </td>
                    <td class="py-2 pr-2 text-center">
                      <span v-if="element.story_points" class="bg-dc-surface dark:bg-dc-dark-border text-dc-muted text-xs font-mono px-2 py-0.5 rounded font-semibold">
                        {{ element.story_points }}
                      </span>
                    </td>
                    <td class="py-2 pr-2 text-center">
                      <span :class="priorityClasses[element.priority ?? 'medium']" class="text-xs px-2 py-0.5 rounded-full font-medium">
                        {{ element.priority ?? 'medium' }}
                      </span>
                    </td>
                    <td class="py-2 pr-2">
                      <span class="text-xs text-dc-muted">{{ element.role_tag ?? '—' }}</span>
                    </td>
                    <td class="py-2 pr-2">
                      <span v-if="element.energy" :class="energyBadges[element.energy]?.class" class="text-xs px-2 py-0.5 rounded-full font-medium">
                        {{ energyBadges[element.energy]?.label }}
                      </span>
                    </td>
                    <td class="py-2 pr-2">
                      <span v-if="element.assignee" class="text-xs">{{ element.assignee.name?.split(' ')[0] }}</span>
                      <span v-else class="text-xs text-dc-muted">—</span>
                    </td>
                    <td v-if="activeSprint" class="py-2 pr-3" @click.stop>
                      <Button size="sm" variant="ghost" @click="assignTaskToActiveSprint(element)">→ Sprint</Button>
                    </td>
                  </tr>
                </template>
              </draggable>
            </table>
          </div>
        </template>
      </div>
 
      <!-- VELOCITY VIEW -->
      <div v-if="boardView === 'velocity'" class="p-6 max-w-2xl mx-auto">
        <h3 class="text-h3 mb-6">Sprint Velocity</h3>
        <div v-if="completedSprints.length === 0" class="text-center py-16">
          <p class="text-body text-dc-muted">No completed sprints yet. Velocity will be tracked after your first sprint.</p>
        </div>
        <div v-else>
          <div class="flex items-end gap-4 h-48 mb-6 px-2 border-b border-dc-surface">
            <div v-for="sprint in completedSprints" :key="sprint.id" class="flex-1 flex flex-col items-center">
              <span class="text-small font-semibold mb-1 text-dc-primary">{{ sprint.velocity ?? 0 }}</span>
              <div
                class="w-full bg-dc-primary rounded-t transition-all"
                :style="{ height: maxVelocity > 0 ? ((sprint.velocity ?? 0) / maxVelocity * 160) + 'px' : '4px' }"
              ></div>
              <span class="text-xs text-dc-muted mt-2 truncate w-full text-center" :title="sprint.name">{{ sprint.name }}</span>
            </div>
          </div>
          <div class="text-center py-4">
            <span class="text-body text-dc-muted">Average velocity: </span>
            <span class="text-h3 font-bold text-dc-primary">{{ averageVelocity }}</span>
            <span class="text-body text-dc-muted"> points/sprint</span>
          </div>
          <div class="mt-4 space-y-3">
            <div v-for="sprint in completedSprints" :key="'r-' + sprint.id" class="p-4 bg-dc-surface/50 dark:bg-dc-dark-surface rounded-lg">
              <div class="flex items-center justify-between mb-1">
                <span class="text-small font-medium">{{ sprint.name }}</span>
                <span class="text-dc-primary font-bold">{{ sprint.velocity ?? 0 }} pts</span>
              </div>
              <div v-if="sprint.retro_good || sprint.retro_improve" class="text-small text-dc-muted space-y-1 mt-2">
                <div v-if="sprint.retro_good"><strong>Went well:</strong> {{ sprint.retro_good }}</div>
                <div v-if="sprint.retro_improve"><strong>Improve:</strong> {{ sprint.retro_improve }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
 
    <!-- ── CREATE SPRINT MODAL ── -->
    <div v-if="showCreateSprint" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white dark:bg-dc-dark-surface rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-h3 font-semibold mb-4">Create Sprint</h3>
        <div class="space-y-4">
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">Sprint name</label>
            <TextInput v-model="newSprintForm.name" placeholder="Sprint 1" />
          </div>
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">Goal (optional)</label>
            <Textarea v-model="newSprintForm.goal" :rows="2" placeholder="What should the team achieve this sprint?" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-small font-medium text-dc-muted block mb-1">Start date</label>
              <input type="date" v-model="newSprintForm.start_date" class="w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-border border border-dc-muted rounded-md text-body focus:outline-none focus:ring-2 focus:ring-dc-primary" />
            </div>
            <div>
              <label class="text-small font-medium text-dc-muted block mb-1">End date</label>
              <input type="date" v-model="newSprintForm.end_date" class="w-full px-3 py-2 bg-dc-surface dark:bg-dc-dark-border border border-dc-muted rounded-md text-body focus:outline-none focus:ring-2 focus:ring-dc-primary" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <Button variant="primary" @click="createSprint" :disabled="!newSprintForm.name.trim() || isSavingSprint">
            {{ isSavingSprint ? 'Creating...' : 'Create Sprint' }}
          </Button>
          <Button variant="ghost" @click="showCreateSprint = false">Cancel</Button>
        </div>
      </div>
    </div>
 
    <!-- ── START SPRINT CONFIRM ── -->
    <div v-if="showStartSprint" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white dark:bg-dc-dark-surface rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-h3 font-semibold mb-3">Start {{ sprintToStart?.name }}?</h3>
        <p class="text-body text-dc-muted mb-2">
          {{ sprintToStart?.task_counts?.todo ?? 0 }} tasks in backlog.
          Once started, this sprint becomes the active sprint.
        </p>
        <div class="flex gap-3 mt-6">
          <Button variant="primary" @click="startSprint" :disabled="isSavingSprint">
            {{ isSavingSprint ? 'Starting...' : 'Start Sprint' }}
          </Button>
          <Button variant="ghost" @click="showStartSprint = false">Cancel</Button>
        </div>
      </div>
    </div>
 
    <!-- ── COMPLETE SPRINT MODAL (Retrospective) ── -->
    <div v-if="showCompleteSprint && activeSprint" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white dark:bg-dc-dark-surface rounded-xl shadow-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-h3 font-semibold mb-2">Complete {{ activeSprint.name }}</h3>
 
        <!-- Summary -->
        <div class="bg-dc-surface/50 dark:bg-dc-dark-border rounded-lg p-4 mb-4 text-small space-y-1">
          <div><strong>Points completed:</strong> {{ activeSprint.completed_points }} / {{ activeSprint.total_points }}</div>
          <div><strong>Tasks done:</strong> {{ sprintTasks.done?.length ?? 0 }}</div>
          <div><strong>Incomplete tasks:</strong> {{ (sprintTasks.todo?.length ?? 0) + (sprintTasks.in_progress?.length ?? 0) }} (will return to backlog)</div>
        </div>
 
        <p class="text-small text-dc-muted mb-4">Incomplete tasks will be moved back to the Product Backlog.</p>
 
        <div class="space-y-4">
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">What went well?</label>
            <Textarea v-model="retroForm.retro_good" :rows="2" placeholder="Team collaboration, completed features..." />
          </div>
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">What could be improved?</label>
            <Textarea v-model="retroForm.retro_improve" :rows="2" placeholder="Estimation accuracy, communication..." />
          </div>
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">Action items for next sprint</label>
            <Textarea v-model="retroForm.retro_actions" :rows="2" placeholder="Daily standups, better task breakdown..." />
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <Button variant="primary" @click="completeSprint" :disabled="isSavingSprint">
            {{ isSavingSprint ? 'Completing...' : 'Complete Sprint' }}
          </Button>
          <Button variant="ghost" @click="showCompleteSprint = false">Cancel</Button>
        </div>
      </div>
    </div>
 
    <!-- ── ADD TASK MODAL ── -->
    <div v-if="showAddTask" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="bg-white dark:bg-dc-dark-surface rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-h3 font-semibold mb-4">Add Task</h3>
        <div class="space-y-3">
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">Title *</label>
            <TextInput v-model="addTaskForm.title" placeholder="Task title" />
          </div>
          <div>
            <label class="text-small font-medium text-dc-muted block mb-1">Description</label>
            <Textarea v-model="addTaskForm.description" :rows="2" placeholder="What needs to be done?" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-small font-medium text-dc-muted block mb-1">Story Points</label>
              <Select v-model="addTaskForm.story_points" :options="storyPointOptions" />
            </div>
            <div>
              <label class="text-small font-medium text-dc-muted block mb-1">Priority</label>
              <Select v-model="addTaskForm.priority" :options="priorityOptions" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-small font-medium text-dc-muted block mb-1">Sprint</label>
              <Select v-model="addTaskForm.sprint_id" :options="addTaskSprintOptions" />
            </div>
            <div>
              <label class="text-small font-medium text-dc-muted block mb-1">Status</label>
              <Select v-model="addTaskForm.status" :options="statusOptions" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <Button variant="primary" @click="saveAddTask" :disabled="!addTaskForm.title.trim() || isSavingTask">
            {{ isSavingTask ? 'Adding...' : 'Add Task' }}
          </Button>
          <Button variant="ghost" @click="showAddTask = false">Cancel</Button>
        </div>
      </div>
    </div>
 
    <!-- ── TASK DETAIL PANEL ── -->
    <TaskDetailPanel
      v-if="selectedTask"
      :task="selectedTask"
      :project-id="project.id"
      :is-owner="isOwner"
      :members="project.members"
      :roles="project.roles"
      :sprints="allSprints"
      @close="selectedTask = null"
      @updated="onTaskUpdated"
      @deleted="onTaskDeleted"
    />
 
    <!-- CHAT TAB -->
    <div v-if="activeTab === 'Chat' && !ndaRequired" class="flex h-[calc(100vh-200px)]">
      <!-- LEFT COLUMN: chat -->
      <div class="flex flex-col flex-1 min-w-0">
      <!-- Chat header -->
      <div class="p-3 border-b border-dc-surface dark:border-dc-dark-surface flex items-center justify-between">
        <div>
          <h3 class="text-h3">Team Chat</h3>
          <p class="text-small text-dc-muted">{{ chatParticipants.length }} members</p>
        </div>
      </div>
 
      <!-- Messages area -->
      <div
        ref="chatMessagesContainer"
        class="flex-1 overflow-y-auto p-4 space-y-4"
      >
        <div v-if="chatLoading" class="py-4">
          <Spinner :size="28" message="Loading messages..." />
        </div>
 
        <template v-else>
          <div
            v-for="msg in chatMessages"
            :key="msg.id"
            class="flex"
          >
            <div v-if="msg.deleted_at" class="text-center text-small text-dc-muted italic py-1 w-full">
              Message deleted
            </div>
            <template v-else>
              <div v-if="msg.sender.id === $page.props.auth.user.id" class="flex justify-end w-full">
                <!-- Sent message: right-aligned -->
                <div class="max-w-[70%]">
                  <div class="bg-dc-primary text-white p-3 rounded-lg rounded-br-none">
                    {{ msg.body }}
                  </div>
                  <div class="text-right text-small text-dc-muted mt-1">
                    {{ formatChatTime(msg.created_at) }}
                    <span v-if="msg.edited_at" class="italic"> (edited)</span>
                  </div>
                </div>
              </div>
              <div v-else class="flex space-x-2 w-full">
                <!-- Received message: left-aligned with avatar -->
                <div class="flex-shrink-0">
                  <div
                    class="w-[32px] h-[32px] rounded-full flex items-center justify-center bg-dc-primary-tint text-dc-primary-dark text-small font-medium"
                  >
                    {{ getInitials(msg.sender.name) }}
                  </div>
                </div>
                <div class="max-w-[70%]">
                  <div class="flex items-baseline space-x-2">
                    <span class="text-small font-medium">{{ msg.sender.name }}</span>
                    <span class="text-small text-dc-muted">{{ formatChatTime(msg.created_at) }}</span>
                  </div>
                  <div class="bg-dc-surface dark:bg-dc-dark-surface p-3 rounded-lg rounded-tl-none mt-1">
                    {{ msg.body }}
                  </div>
                </div>
              </div>
            </template>
          </div>
 
          <!-- Typing indicator -->
          <div v-if="typingUser" class="flex space-x-2">
            <div class="flex-shrink-0">
              <div
                class="w-[32px] h-[32px] rounded-full flex items-center justify-center bg-dc-primary-tint text-dc-primary-dark text-small font-medium"
              >
                {{ getInitials(typingUser.name) }}
              </div>
            </div>
            <div class="bg-dc-surface dark:bg-dc-dark-surface p-3 rounded-lg rounded-tl-none mt-1">
              <div class="flex space-x-1 items-center h-5">
                <span class="w-1.5 h-1.5 bg-dc-primary rounded-full animate-bounce" style="animation-delay: 0ms" />
                <span class="w-1.5 h-1.5 bg-dc-coral rounded-full animate-bounce" style="animation-delay: 200ms" />
                <span class="w-1.5 h-1.5 bg-dc-primary rounded-full animate-bounce" style="animation-delay: 400ms" />
              </div>
            </div>
          </div>
        </template>
      </div>
 
      <!-- Input area -->
      <div class="p-3 border-t border-dc-surface dark:border-dc-dark-border flex items-end space-x-3">
        <Textarea
          v-model="chatInput"
          placeholder="Type a message..."
          :rows="1"
          class="flex-1 min-h-[44px] resize-none"
          @keydown="handleChatKeydown"
          @input="handleChatTyping"
        />
        <Button
          variant="primary"
          :disabled="!chatInput.trim() || chatLoading"
          @click="sendChatMessage"
        >
          Send
        </Button>
      </div>
      </div>
      <!-- RIGHT COLUMN: members sidebar -->
      <div class="hidden md:flex flex-col w-56 border-l border-dc-surface dark:border-dc-dark-border p-4 overflow-y-auto">
        <h4 class="text-small font-medium text-dc-muted mb-3">Team members</h4>
        <div class="space-y-2">
          <div v-for="member in chatParticipants" :key="member.id" class="flex items-center gap-2">
            <AvatarInitials :initials="getInitials(member.name)" size="sm" />
            <span class="text-small text-dc-body flex-1 truncate">{{ member.name }}</span>
            <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
          </div>
        </div>
      </div>
    </div>
 
    <!-- VOICE TAB -->
    <div v-if="activeTab === 'Voice' && !ndaRequired" class="flex flex-col items-center justify-center py-24 text-center gap-5">
      <div class="w-16 h-16 rounded-full bg-dc-primary-tint flex items-center justify-center">
        <svg class="w-8 h-8 text-dc-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
        </svg>
      </div>
      <div>
        <h3 class="text-h2 text-dc-primary-dark dark:text-dc-primary-muted mb-2">Voice Channel</h3>
        <p class="text-body text-dc-muted max-w-sm">Team voice calls are coming soon. Jump into a live audio session with your project team directly from the workspace.</p>
      </div>
      <span class="inline-flex items-center px-3 py-1 rounded-full bg-dc-warning-tint text-dc-warning text-small font-medium">Coming soon</span>
    </div>
 
    <!-- FILES TAB -->
    <div v-if="activeTab === 'Files' && !ndaRequired" class="p-4">
      <!-- Upload area -->
      <div class="mb-6">
        <div
          class="border-2 border-dashed border-dc-surface dark:border-dc-dark-border rounded-lg p-8 text-center hover:border-dc-primary transition-colors cursor-pointer"
          :class="{ 'border-dc-primary bg-dc-primary/5': isDragging }"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleFileDrop"
          @click="fileInput?.click()"
        >
          <input ref="fileInput" type="file" class="hidden" @change="handleFileSelect" multiple />
          <div v-if="!uploading">
            <p class="text-body text-dc-muted mb-1">Drop files here or click to upload</p>
            <p class="text-small text-dc-muted">Max 10 MB per file · PDF, Images, Docs, Archives</p>
          </div>
          <div v-else>
            <Spinner :size="28" message="Uploading..." />
          </div>
        </div>
      </div>
 
      <!-- Loading -->
      <div v-if="filesLoading" class="py-8">
        <Spinner :size="48" message="Loading files..." />
      </div>
 
      <!-- Empty state -->
      <div v-else-if="projectFiles.length === 0" class="text-center py-12 text-dc-muted">
        No files shared yet. Upload designs, docs, or assets for your team.
      </div>
 
      <!-- File list -->
      <div v-else class="space-y-2">
        <div
          v-for="file in projectFiles"
          :key="file.id"
          class="flex items-center justify-between p-3 bg-white dark:bg-dc-dark-surface rounded-lg border border-dc-surface dark:border-dc-dark-border hover:shadow-sm transition-shadow"
        >
          <div class="flex items-center space-x-3 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-lg bg-dc-surface dark:bg-dc-dark-border flex items-center justify-center text-lg flex-shrink-0">
              <span v-if="file.icon === 'image'">🖼️</span>
              <span v-else-if="file.icon === 'pdf'">📄</span>
              <span v-else-if="file.icon === 'doc'">📝</span>
              <span v-else-if="file.icon === 'spreadsheet'">📊</span>
              <span v-else-if="file.icon === 'presentation'">📈</span>
              <span v-else-if="file.icon === 'archive'">📦</span>
              <span v-else-if="file.icon === 'video'">🎬</span>
              <span v-else-if="file.icon === 'audio'">🎵</span>
              <span v-else-if="file.icon === 'text'">📃</span>
              <span v-else>📎</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-small font-medium text-dc-body dark:text-dc-primary-muted truncate">{{ file.file_name }}</p>
              <div class="flex items-center space-x-2 text-xs text-dc-muted">
                <span>{{ file.size_formatted }}</span>
                <span>·</span>
                <span>{{ file.uploader?.name }}</span>
                <span>·</span>
                <span>{{ formatDate(file.created_at) }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center space-x-3 flex-shrink-0 ml-4">
            <a
              :href="urls.projects.files.download(project.id, file.id)"
              class="text-dc-primary hover:underline text-small font-medium"
            >
              Download
            </a>
            <button
              v-if="file.uploaded_by === $page.props.auth.user?.id || isOwner"
              @click="deleteFile(file.id)"
              class="text-dc-muted hover:text-red-500 text-small"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
 
    <!-- GITHUB TAB -->
    <div v-if="activeTab === 'GitHub' && !ndaRequired">
 
      <!-- No repo linked -->
      <div v-if="!project.repo_url" class="p-6 text-center">
        <h3 class="text-h3 text-dc-body dark:text-dc-primary-muted mb-2">Link a GitHub Repository</h3>
        <p class="text-body text-dc-muted mb-6">Connect your project's GitHub repo to see commits, pull requests, and contributors.</p>
        <div v-if="isOwner" class="max-w-md mx-auto flex space-x-3">
          <input
            v-model="linkRepoUrl"
            placeholder="https://github.com/username/repo"
            class="flex-1 px-3 py-2 border border-dc-surface dark:border-dc-dark-border rounded-lg text-body bg-white dark:bg-dc-dark-surface text-dc-body dark:text-dc-primary-muted focus:outline-none focus:ring-2 focus:ring-dc-primary"
          />
          <Button variant="primary" @click="linkRepo" :disabled="!linkRepoUrl.trim() || isLinkingRepo">
            {{ isLinkingRepo ? 'Linking...' : 'Link' }}
          </Button>
        </div>
        <p v-else class="text-small text-dc-muted">Ask the project owner to link the GitHub repository.</p>
      </div>
 
      <!-- Repo linked -->
      <div v-else class="p-4">
 
        <!-- Repo header -->
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-900 text-white rounded-lg flex items-center justify-center text-sm font-bold flex-shrink-0">
              GH
            </div>
            <div>
              <a :href="project.repo_url" target="_blank" rel="noreferrer" class="text-h3 text-dc-primary dark:text-dc-primary-muted hover:underline">
                {{ repoDisplayName }}
              </a>
              <p class="text-small text-dc-muted">Last commit {{ githubCommits[0]?.date ? formatDate(githubCommits[0].date) : 'N/A' }}</p>
            </div>
          </div>
          <Button v-if="isOwner" variant="ghost" size="sm" @click="unlinkRepo">Unlink</Button>
        </div>
 
        <!-- Error state -->
        <div v-if="githubError" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-small text-red-600">
          {{ githubError }}. The repository may be private or the URL may be incorrect.
        </div>
 
        <!-- Loading -->
        <div v-if="githubLoading" class="py-12">
          <Spinner :size="64" message="Loading GitHub data..." />
        </div>
 
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 
          <!-- Recent Commits -->
          <div>
            <h4 class="text-label-caps text-dc-muted mb-3">RECENT COMMITS</h4>
            <div v-if="githubCommits.length === 0" class="text-small text-dc-muted">No commits found</div>
            <div v-else class="space-y-3">
              <div v-for="commit in githubCommits.slice(0, 10)" :key="commit.full_sha" class="flex space-x-3">
                <img v-if="commit.author_avatar" :src="commit.author_avatar" class="w-6 h-6 rounded-full mt-0.5 flex-shrink-0" />
                <div v-else class="w-6 h-6 rounded-full bg-dc-surface dark:bg-dc-dark-border mt-0.5 flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                  <a :href="commit.url" target="_blank" rel="noreferrer" class="text-small font-medium text-dc-body dark:text-dc-primary-muted hover:text-dc-primary truncate block">
                    {{ commit.message }}
                  </a>
                  <div class="flex items-center space-x-2 text-xs text-dc-muted flex-wrap">
                    <span>{{ commit.author_login || commit.author_name }}</span>
                    <span>·</span>
                    <code class="bg-dc-surface dark:bg-dc-dark-border px-1 rounded font-mono">{{ commit.sha }}</code>
                    <span>·</span>
                    <span>{{ formatDate(commit.date) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
 
          <!-- Open Pull Requests -->
          <div>
            <h4 class="text-label-caps text-dc-muted mb-3">OPEN PULL REQUESTS</h4>
            <div v-if="githubPRs.length === 0" class="text-small text-dc-muted">No open pull requests</div>
            <div v-else class="space-y-3">
              <div v-for="pr in githubPRs" :key="pr.number" class="flex space-x-3 items-start">
                <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs mt-0.5 flex-shrink-0 font-bold">
                  PR
                </div>
                <div class="flex-1 min-w-0">
                  <a :href="pr.url" target="_blank" rel="noreferrer" class="text-small font-medium text-dc-body dark:text-dc-primary-muted hover:text-dc-primary">
                    {{ pr.title }}<span class="text-dc-muted font-normal"> #{{ pr.number }}</span>
                  </a>
                  <div class="flex items-center space-x-2 text-xs text-dc-muted mt-0.5 flex-wrap">
                    <img v-if="pr.author_avatar" :src="pr.author_avatar" class="w-4 h-4 rounded-full" />
                    <span>{{ pr.author_login }}</span>
                    <span>·</span>
                    <span>{{ formatDate(pr.created_at) }}</span>
                    <span v-if="pr.draft" class="bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-1.5 py-0.5 rounded text-xs">Draft</span>
                    <span v-for="label in pr.labels" :key="label" class="bg-dc-primary/10 text-dc-primary px-1.5 py-0.5 rounded text-xs">
                      {{ label }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
 
          <!-- Contributors -->
          <div class="lg:col-span-2">
            <h4 class="text-label-caps text-dc-muted mb-3">CONTRIBUTORS</h4>
            <div v-if="githubContributors.length === 0" class="text-small text-dc-muted">No contributors found</div>
            <div v-else class="flex flex-wrap gap-4">
              <a
                v-for="c in githubContributors"
                :key="c.login"
                :href="c.url"
                target="_blank"
                rel="noreferrer"
                class="flex items-center space-x-2 bg-dc-surface/50 dark:bg-dc-dark-surface rounded-lg px-3 py-2 hover:shadow-sm transition-shadow"
              >
                <img :src="c.avatar_url" :alt="c.login" class="w-8 h-8 rounded-full" />
                <div>
                  <span class="text-small font-medium text-dc-body dark:text-dc-primary-muted">{{ c.login }}</span>
                  <span class="text-xs text-dc-muted block">{{ c.contributions }} commits</span>
                </div>
              </a>
            </div>
          </div>
 
        </div>
      </div>
    </div>
 
    <!-- DECISION LOG TAB -->
    <div v-if="activeTab === 'Decision log' && !ndaRequired" class="p-4">
      <!-- Add decision form -->
      <div class="mb-6">
        <div v-if="!showDecisionForm" class="flex items-center justify-between">
          <h3 class="text-h3 text-dc-primary-dark dark:text-dc-primary-muted">Decision Log</h3>
          <Button variant="primary" size="sm" @click="showDecisionForm = true">+ Log a decision</Button>
        </div>
        <div v-else class="bg-dc-surface/30 dark:bg-dc-dark-surface rounded-lg p-4 border border-dc-surface dark:border-dc-dark-border">
          <p class="text-label-caps text-dc-muted mb-3">NEW DECISION</p>
          <input
            v-model="newDecision.decision"
            placeholder="What was decided?"
            class="w-full px-3 py-2 bg-white dark:bg-dc-dark-bg border border-dc-surface dark:border-dc-dark-border rounded-lg text-body text-dc-body dark:text-dc-primary-muted mb-3 focus:outline-none focus:ring-2 focus:ring-dc-primary"
            maxlength="500"
          />
          <Textarea
            v-model="newDecision.reason"
            placeholder="Why was this decided? (optional)"
            :rows="3"
            class="mb-3"
          />
          <div class="flex space-x-2">
            <Button variant="primary" size="sm" @click="saveDecision" :disabled="!newDecision.decision.trim()">Save decision</Button>
            <Button variant="ghost" size="sm" @click="showDecisionForm = false; newDecision = { decision: '', reason: '' }">Cancel</Button>
          </div>
        </div>
      </div>
 
      <!-- Loading -->
      <div v-if="decisionLoading" class="py-8">
        <Spinner :size="48" message="Loading decisions..." />
      </div>
 
      <!-- Empty state -->
      <div v-else-if="decisions.length === 0" class="text-center py-12 text-dc-muted">
        No decisions logged yet. Start documenting your team's choices so new members can get up to speed.
      </div>
 
      <!-- Decision timeline -->
      <div v-else class="space-y-4">
        <div v-for="entry in decisions" :key="entry.id" class="flex space-x-4">
          <!-- Timeline dot + line -->
          <div class="flex flex-col items-center">
            <div class="w-3 h-3 rounded-full bg-dc-primary mt-1.5 flex-shrink-0"></div>
            <div class="w-0.5 flex-1 bg-dc-surface dark:bg-dc-dark-border mt-1"></div>
          </div>
          <!-- Decision content -->
          <div class="flex-1 pb-6">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <p class="text-body font-medium text-dc-body dark:text-dc-primary-muted">{{ entry.decision }}</p>
                <p v-if="entry.reason" class="text-small text-dc-muted mt-1 italic">{{ entry.reason }}</p>
              </div>
              <button
                v-if="entry.user_id === $page.props.auth.user?.id || isOwner"
                @click="deleteDecision(entry.id)"
                class="text-dc-muted hover:text-red-500 text-small flex-shrink-0"
              >
                Delete
              </button>
            </div>
            <div class="flex items-center space-x-2 mt-2">
              <div
                v-if="!entry.user?.avatar_url"
                class="w-5 h-5 rounded-full bg-dc-primary-tint flex items-center justify-center text-dc-primary text-xs font-bold"
              >
                {{ entry.user?.name?.charAt(0) }}
              </div>
              <img v-else :src="entry.user.avatar_url" :alt="entry.user.name" class="w-5 h-5 rounded-full" />
              <span class="text-small text-dc-muted">{{ entry.user?.name }}</span>
              <span class="text-small text-dc-muted">·</span>
              <span class="text-small text-dc-muted">{{ formatDate(entry.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
 
<script setup lang="ts">
import { ref, computed, watch, nextTick, reactive, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { CheckCircle } from 'lucide-vue-next'
import draggable from 'vuedraggable'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Card from '@/Components/Card.vue'
import Button from '@/Components/Button.vue'
import Badge from '@/Components/Badge.vue'
import AIBadge from '@/Components/AIBadge.vue'
import SkillTag from '@/Components/SkillTag.vue'
import TextInput from '@/Components/TextInput.vue'
import Textarea from '@/Components/Textarea.vue'
import Select from '@/Components/Select.vue'
import TaskCard from '@/Components/TaskCard.vue'
import TaskDetailPanel from '@/Components/TaskDetailPanel.vue'
import Spinner from '@/Components/Spinner.vue'
import AvatarInitials from '@/Components/AvatarInitials.vue'
import { urls } from '@/lib/routes'
 
const $page = usePage()
 
defineOptions({ layout: AuthenticatedLayout })
 
interface Chemistry {
  label: string
  alignment: string[]
  friction: string[]
  summary: string
}
 
interface Application {
  id: number
  user_id: number
  role_id: number | null
  message: string | null
  status: string
  created_at: string
  user: { id: number; name: string; avatar_url: string | null; role: string; reputation_score?: number }
  role: { id: number; role_name: string } | null
  chemistry?: Chemistry
}
 
interface ProjectMember {
  id: number
  user_id: number
  role: string
  status: string
  joined_at: string
  user: { id: number; name: string; avatar_url: string | null; role: string }
}
 
interface ProjectRole {
  id: number
  role_name: string
  slots: number
  filled: number
  description: string | null
  is_open: boolean
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
  owner_id: number
  repo_url: string | null
  created_at: string
  idea: { id: number; title: string } | null
  members: ProjectMember[]
  roles: ProjectRole[]
  owner: { id: number; name: string; avatar_url: string | null; role: string }
}
 
interface PulseEntry {
  id: number
  status: string
  triggered_at: string
  signals: Record<string, any>
}
 
interface InviteLink {
  id: number
  project_id: number
  role_id: number | null
  token: string
  expires_at: string | null
  max_uses: number | null
  uses: number
  full_url: string
  is_valid: boolean
  role: { id: number; role_name: string } | null
}
 
interface Milestone {
  id: number
  project_id: number
  title: string
  description: string | null
  order_index: number
  unlocks_access_level: number
  completed_at: string | null
}
 
interface SprintTaskCount {
  todo: number
  in_progress: number
  done: number
}
 
interface Sprint {
  id: number
  project_id: number
  name: string
  goal: string | null
  start_date: string
  end_date: string
  status: 'planning' | 'active' | 'completed'
  velocity: number | null
  retro_good: string | null
  retro_improve: string | null
  retro_actions: string | null
  days_remaining: number
  total_points: number
  completed_points: number
  task_counts?: SprintTaskCount
}
 
interface BoardTask {
  id: number
  project_id: number
  sprint_id: number | null
  assigned_to: number | null
  parent_task_id: number | null
  role_tag: string | null
  title: string
  description: string | null
  energy: string | null
  priority: string | null
  story_points: number | null
  status: string
  position: number
  due_date: string | null
  completed_at: string | null
  assignee: { id: number; name: string; avatar_url: string | null } | null
  sprint: { id: number; name: string; status: string } | null
  subtasks: BoardTask[]
}
 
const props = defineProps<{
  project: Project
  isMember: boolean
  isOwner: boolean
  hasApplied: boolean
  hasRated: boolean
  pendingApplications: Application[]
  userApplication: Application | null
  inviteLinks: InviteLink[]
  milestones: Milestone[]
  ndaRequired: boolean
  userNdaSigned: boolean
  userNdaSignedAt: string | null
  userAgreementSigned: boolean
  userAgreementSignedAt: string | null
  userAccessLevel: number
  agreementText: string | null
  groupChatId: number | null
  activeSprint: Sprint | null
  sprints: Sprint[]
  taskCounts: { todo: number; in_progress: number; done: number; backlog: number }
  decisionCount: number
  fileCount: number
  pulseStatus: string | null
  pulseHistory: PulseEntry[]
}>()
 
const activeTab = ref('Overview')
const showStatusMenu = ref(false)
const showTrustLayer = ref(false)
const confirmDelete = ref(false)
const applyingRole = ref<number | null>(null)
const applyingUndecided = ref(false)
const applyMessage = ref('')
const isSubmittingApplication = ref(false)
const messagingOwner = ref(false)
const showAddRole = ref(false)
const newRoleForm = ref({ role_name: '', slots: 1, description: '' })
const newMilestoneForm = ref({ title: '', description: '', unlocks_access_level: '1' })
const teamAgreementTextForm = ref(props.agreementText ?? '')
const showInviteForm = ref(false)
const isGeneratingInvite = ref(false)
const generatedInviteUrl = ref('')
const copiedInviteKey = ref('')
const inviteLinksList = ref<InviteLink[]>([...props.inviteLinks])
const inviteForm = ref({
  role_id: '',
  expires_in: '7d',
  max_uses: '',
})
 
// Group chat state
const chatMessages = ref<any[]>([])
const chatInput = ref('')
const chatLoading = ref(false)
const typingUser = ref(null)
const chatParticipants = ref<any[]>([])
const chatMessagesContainer = ref(null)
let typingTimeout: any = null
let lastTypingSent = 0
 
// ── Decision Log ──────────────────────────────────────────────────────────────
const decisions = ref<any[]>([])
const decisionLoading = ref(false)
const showDecisionForm = ref(false)
const newDecision = ref({ decision: '', reason: '' })
 
// ── Alive Signals ─────────────────────────────────────────────────────────────
const memberActivity = ref<any[]>([])
const justSignaled = ref(false)
const aliveDisabled = ref(false)
 
// ── GitHub ────────────────────────────────────────────────────────────────────
const githubCommits = ref<any[]>([])
const githubPRs = ref<any[]>([])
const githubContributors = ref<any[]>([])
const githubLoading = ref(false)
const githubError = ref<string | null>(null)
const linkRepoUrl = ref('')
const isLinkingRepo = ref(false)
let githubDataLoaded = false
 
// ── Files ─────────────────────────────────────────────────────────────────────
const projectFiles = ref<any[]>([])
const filesLoading = ref(false)
const uploading = ref(false)
const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
 
// ── Board state ──────────────────────────────────────────────────────────────
const boardView = ref<'sprint' | 'backlog' | 'velocity'>(props.activeSprint ? 'sprint' : 'backlog')
const allSprints = ref<Sprint[]>([...(props.sprints ?? [])])
const currentActiveSprint = ref<Sprint | null>(props.activeSprint ?? null)
 
const sprintTasks = reactive<{ todo: BoardTask[]; in_progress: BoardTask[]; done: BoardTask[] }>({
  todo: [],
  in_progress: [],
  done: [],
})
const backlogTasks = ref<BoardTask[]>([])
const backlogLoading = ref(false)
const sprintBoardLoading = ref(false)
const selectedTask = ref<BoardTask | null>(null)
const isGenerating = ref(false)
 
const filterRole = ref('')
const filterEnergy = ref('')
const filterAssignee = ref('')
 
// Create sprint modal
const showCreateSprint = ref(false)
const isSavingSprint = ref(false)
const newSprintForm = reactive({
  name: `Sprint ${(props.sprints?.length ?? 0) + 1}`,
  goal: '',
  start_date: new Date().toISOString().split('T')[0],
  end_date: new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0],
})
 
// Start sprint
const showStartSprint = ref(false)
const sprintToStart = ref<Sprint | null>(null)
 
// Complete sprint modal
const showCompleteSprint = ref(false)
const retroForm = reactive({ retro_good: '', retro_improve: '', retro_actions: '' })
 
// Add task modal
const showAddTask = ref(false)
const isSavingTask = ref(false)
const addTaskDefaultStatus = ref('todo')
const addTaskForm = reactive({
  title: '',
  description: '',
  story_points: '',
  priority: 'medium',
  status: 'todo',
  sprint_id: '',
})
 
const sprintColumns = [
  { status: 'todo', label: 'Todo', countClass: 'text-dc-primary' },
  { status: 'in_progress', label: 'In Progress', countClass: 'text-dc-warning' },
  { status: 'done', label: 'Done', countClass: 'text-dc-success' },
]
 
const priorityClasses: Record<string, string> = {
  high: 'bg-dc-danger/15 text-dc-danger',
  medium: 'bg-dc-warning/15 text-dc-warning',
  low: 'bg-dc-success/15 text-dc-success',
}
 
const energyBadges: Record<string, { label: string; class: string }> = {
  quick_win: { label: 'Quick Win', class: 'bg-dc-success/15 text-dc-success' },
  deep_work: { label: 'Deep Work', class: 'bg-dc-primary/15 text-dc-primary' },
  blocked: { label: 'Blocked', class: 'bg-dc-danger/15 text-dc-danger' },
}
 
const storyPointOptions = [
  { value: '', label: '— No estimate' },
  { value: '1', label: '1 pt' },
  { value: '2', label: '2 pts' },
  { value: '3', label: '3 pts' },
  { value: '5', label: '5 pts' },
  { value: '8', label: '8 pts' },
  { value: '13', label: '13 pts' },
]
 
const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
]
 
const statusOptions = [
  { value: 'todo', label: 'Todo' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'done', label: 'Done' },
]
 
const addTaskSprintOptions = computed(() => [
  { value: '', label: '— Product Backlog' },
  ...allSprints.value
    .filter(s => s.status !== 'completed')
    .map(s => ({ value: String(s.id), label: `${s.name} (${s.status})` })),
])
 
// ── Board computed ────────────────────────────────────────────────────────────
 
const activeSprint = computed(() => currentActiveSprint.value)
 
const completedSprints = computed(() =>
  allSprints.value.filter(s => s.status === 'completed')
)
 
const maxVelocity = computed(() =>
  Math.max(1, ...completedSprints.value.map(s => s.velocity ?? 0))
)
 
const averageVelocity = computed(() => {
  const done = completedSprints.value
  if (done.length === 0) return 0
  return Math.round(done.reduce((sum, s) => sum + (s.velocity ?? 0), 0) / done.length)
})
 
const sprintProgress = computed(() => {
  if (!currentActiveSprint.value) return 0
  const total = currentActiveSprint.value.total_points
  if (total === 0) return 0
  return Math.round((currentActiveSprint.value.completed_points / total) * 100)
})
 
const backlogTotalPoints = computed(() =>
  backlogTasks.value.reduce((sum, t) => sum + (t.story_points ?? 0), 0)
)
 
const totalTaskCount = computed(() => {
  const counts = props.taskCounts
  return (counts?.backlog ?? 0) + (counts?.todo ?? 0) + (counts?.in_progress ?? 0) + (counts?.done ?? 0)
})
 
function filteredByStatus(status: string): BoardTask[] {
  return (sprintTasks[status as keyof typeof sprintTasks] ?? []).filter(passesFilter)
}
 
function passesFilter(task: BoardTask): boolean {
  if (filterRole.value && task.role_tag !== filterRole.value) return false
  if (filterEnergy.value && task.energy !== filterEnergy.value) return false
  if (filterAssignee.value && String(task.assigned_to) !== filterAssignee.value) return false
  return true
}
 
const filteredSprintTodo = computed(() => filteredByStatus('todo'))
const filteredSprintInProgress = computed(() => filteredByStatus('in_progress'))
const filteredSprintDone = computed(() => filteredByStatus('done'))
 
function clearFilters() {
  filterRole.value = ''
  filterEnergy.value = ''
  filterAssignee.value = ''
}
 
// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = ['Overview', 'Members', 'Settings', 'Board', 'Chat', 'Voice', 'Files', 'GitHub', 'Decision log']
const displayedTabs = computed(() => props.ndaRequired ? ['Overview'] : tabs)
 
const completedMilestonesCount = computed(() => {
  return props.milestones.filter((milestone) => Boolean(milestone.completed_at)).length
})
 
watch(
  () => props.ndaRequired,
  (required) => {
    if (required) activeTab.value = 'Overview'
  },
  { immediate: true }
)
 
const inviteExpiryOptions = [
  { value: '1h', label: '1 hour' },
  { value: '6h', label: '6 hours' },
  { value: '24h', label: '24 hours' },
  { value: '7d', label: '7 days' },
  { value: '30d', label: '30 days' },
  { value: 'never', label: 'Never' },
]
 
const inviteRoleOptions = computed(() => [
  { value: '', label: 'Any role (General invite)' },
  ...props.project.roles
    .filter((role) => role.is_open)
    .map((role) => {
      const slotsLeft = Math.max(0, role.slots - (role.filled || 0))
      return {
        value: String(role.id),
        label: `${role.role_name} (${slotsLeft} slot${slotsLeft === 1 ? '' : 's'} left)`,
      }
    }),
])
 
const validTransitions = computed(() => {
  const status = props.project.status
  const transitions: Record<string, Array<{ status: string; label: string }>> = {
    open: [
      { status: 'active', label: 'Start project' },
      { status: 'archived', label: 'Archive' },
    ],
    active: [
      { status: 'completed', label: 'Mark completed' },
      { status: 'at_risk', label: 'Mark at risk' },
      { status: 'archived', label: 'Archive' },
    ],
    at_risk: [
      { status: 'active', label: 'Recovered' },
      { status: 'archived', label: 'Archive' },
    ],
    completed: [
      { status: 'archived', label: 'Archive' },
    ],
  }
  return transitions[status] || []
})
 
function statusBadgeText(status: string): string {
  const labels: Record<string, string> = {
    open: 'Open',
    active: 'Active',
    completed: 'Completed',
    at_risk: 'At Risk',
    archived: 'Archived',
  }
  return labels[status] || status
}
 
function formatDate(date: string): string {
  const d = new Date(date)
  const now = new Date()
  const diffMs = now.getTime() - d.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
 
  if (diffDays === 0) return 'today'
  if (diffDays === 1) return 'yesterday'
  if (diffDays < 7) return `${diffDays}d ago`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`
  return `${Math.floor(diffDays / 30)}m ago`
}
 
function formatFullDate(date: string): string {
  return new Date(date).toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}
 
function formatInviteExpiry(date: string | null): string {
  if (!date) return 'Never'
 
  const expires = new Date(date)
  const now = new Date()
  const diffMs = expires.getTime() - now.getTime()
  const absMs = Math.abs(diffMs)
  const diffHours = Math.round(absMs / (1000 * 60 * 60))
  const diffDays = Math.round(absMs / (1000 * 60 * 60 * 24))
 
  if (diffMs < 0) {
    if (diffHours < 24) return `${Math.max(1, diffHours)}h ago`
    return `${Math.max(1, diffDays)}d ago`
  }
 
  if (diffHours < 24) return `in ${Math.max(1, diffHours)}h`
  return `in ${Math.max(1, diffDays)}d`
}
 
function formatInviteUses(link: InviteLink): string {
  if (link.max_uses) return `${link.uses} / ${link.max_uses} used`
  return `${link.uses} used (unlimited)`
}
 
function truncateToken(token: string): string {
  return `${token.slice(0, 8)}...`
}
 
function inviteStatus(link: InviteLink): string {
  if (link.expires_at && new Date(link.expires_at).getTime() < Date.now()) return 'Expired'
  if (link.max_uses && link.uses >= link.max_uses) return 'Maxed'
  return 'Active'
}
 
function whatsAppShareUrl(url: string): string {
  return `https://wa.me/?text=${encodeURIComponent(`Join my DevConnect project: ${url}`)}`
}
 
function linkedInShareUrl(url: string): string {
  return `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`
}
 
function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || ''
}
 
// ── GitHub functions ──────────────────────────────────────────────────────────
 
const repoDisplayName = computed(() => {
  if (!props.project.repo_url) return ''
  try {
    const path = new URL(props.project.repo_url).pathname.replace(/^\//, '').replace(/\.git$/, '')
    return path
  } catch {
    return props.project.repo_url
  }
})
 
async function loadGitHubData() {
  if (!props.project.repo_url || githubDataLoaded) return
  githubLoading.value = true
  githubError.value = null
 
  try {
    const [commitsRes, prsRes, contribRes] = await Promise.all([
      fetch(urls.projects.github.commits(props.project.id), { headers: { Accept: 'application/json' } }),
      fetch(urls.projects.github.pulls(props.project.id), { headers: { Accept: 'application/json' } }),
      fetch(urls.projects.github.contributors(props.project.id), { headers: { Accept: 'application/json' } }),
    ])
 
    const commits = await commitsRes.json()
    const prs = await prsRes.json()
    const contributors = await contribRes.json()
 
    if (commits.error) githubError.value = commits.error
    else githubCommits.value = commits
 
    if (!prs.error) githubPRs.value = prs
    if (!contributors.error) githubContributors.value = contributors
 
    githubDataLoaded = true
  } catch {
    githubError.value = 'Failed to load GitHub data'
  }
  githubLoading.value = false
}
 
async function linkRepo() {
  if (!linkRepoUrl.value.trim() || isLinkingRepo.value) return
  isLinkingRepo.value = true
  try {
    await fetch(urls.projects.github.link(props.project.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        Accept: 'application/json',
      },
      body: JSON.stringify({ repo_url: linkRepoUrl.value }),
    })
    router.reload()
  } catch (err) {
    console.error(err)
  }
  isLinkingRepo.value = false
}
 
async function unlinkRepo() {
  if (!confirm('Unlink this GitHub repository?')) return
  await fetch(urls.projects.github.unlink(props.project.id), {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json',
    },
  })
  githubDataLoaded = false
  router.reload()
}
 
async function copyInviteUrl(url: string, key: string) {
  await navigator.clipboard.writeText(url)
  copiedInviteKey.value = key
  window.setTimeout(() => {
    if (copiedInviteKey.value === key) copiedInviteKey.value = ''
  }, 2000)
}
 
async function generateInviteLink() {
  isGeneratingInvite.value = true
 
  try {
    const response = await fetch(urls.projects.inviteLinks.store(props.project.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        role_id: inviteForm.value.role_id ? Number(inviteForm.value.role_id) : null,
        expires_in: inviteForm.value.expires_in,
        max_uses: inviteForm.value.max_uses ? Number(inviteForm.value.max_uses) : null,
      }),
    })
 
    if (!response.ok) {
      const error = await response.json().catch(() => null)
      alert(error?.message || 'Could not generate invite link.')
      return
    }
 
    const data = await response.json()
    generatedInviteUrl.value = data.link
    inviteLinksList.value = [data.invite, ...inviteLinksList.value]
    inviteForm.value = { role_id: '', expires_in: '7d', max_uses: '' }
    showInviteForm.value = false
  } finally {
    isGeneratingInvite.value = false
  }
}
 
function updateStatus(newStatus: string) {
  router.patch(
    urls.projects.updateStatus(props.project.id),
    { status: newStatus },
    { preserveScroll: true }
  )
  showStatusMenu.value = false
}
 
function deleteProject() {
  router.delete(urls.projects.destroy(props.project.id))
}
 
async function messageProjectOwner() {
  if (messagingOwner.value) return
  messagingOwner.value = true
  try {
    const res = await fetch('/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ recipient_id: props.project.owner.id }),
    })
    if (res.redirected) {
      window.location.href = res.url
      return
    }
    const data = await res.json()
    if (data?.id) router.visit(`/messages/${data.id}`)
  } catch (e) {
    console.error('messageProjectOwner failed', e)
  } finally {
    messagingOwner.value = false
  }
}
 
function submitApplication(roleId: number | null) {
  isSubmittingApplication.value = true
  router.post(
    urls.projects.apply(props.project.id),
    { role_id: roleId, message: applyMessage.value || null },
    {
      onFinish: () => {
        isSubmittingApplication.value = false
        applyingRole.value = null
        applyingUndecided.value = false
        applyMessage.value = ''
      },
    }
  )
}
 
function acceptApplication(app: Application) {
  if (confirm(`Accept ${app.user.name}'s application?`)) {
    router.patch(
      urls.projects.applications.accept(props.project.id, app.id),
      {},
      { preserveScroll: true }
    )
  }
}
 
function declineApplication(app: Application) {
  if (confirm(`Decline ${app.user.name}'s application?`)) {
    router.patch(
      urls.projects.applications.decline(props.project.id, app.id),
      {},
      { preserveScroll: true }
    )
  }
}
 
function withdrawApplication() {
  if (confirm('Withdraw your application?')) {
    router.delete(
      urls.projects.applications.withdraw(props.project.id, props.userApplication?.id ?? ''),
      { preserveScroll: true }
    )
  }
}
 
function removeMember(member: ProjectMember) {
  if (confirm(`Remove ${member.user.name} from the project?`)) {
    router.delete(
      urls.projects.members.remove(props.project.id, member.id),
      { preserveScroll: true }
    )
  }
}
 
function leaveProject() {
  if (confirm('Are you sure you want to leave this project?')) {
    router.post(urls.projects.leave(props.project.id))
  }
}
 
function addRole() {
  if (!newRoleForm.value.role_name.trim()) return
  router.post(
    urls.projects.roles.store(props.project.id),
    newRoleForm.value,
    {
      onSuccess: () => {
        newRoleForm.value = { role_name: '', slots: 1, description: '' }
        showAddRole.value = false
      },
    }
  )
}
 
function saveTeamAgreementText() {
  if (!teamAgreementTextForm.value.trim()) return
 
  router.post(
    urls.projects.agreementText(props.project.id),
    { agreement_text: teamAgreementTextForm.value },
    { preserveScroll: true }
  )
}
 
function addMilestone() {
  if (!newMilestoneForm.value.title.trim()) return
 
  router.post(
    urls.projects.milestones.store(props.project.id),
    {
      title: newMilestoneForm.value.title,
      description: newMilestoneForm.value.description || null,
      unlocks_access_level: Number(newMilestoneForm.value.unlocks_access_level),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        newMilestoneForm.value = { title: '', description: '', unlocks_access_level: '1' }
      },
    }
  )
}
 
function completeMilestone(milestone: Milestone) {
  if (confirm(`Mark "${milestone.title}" complete?`)) {
    router.patch(
      urls.projects.milestones.complete(props.project.id, milestone.id),
      {},
      { preserveScroll: true }
    )
  }
}
 
function deleteMilestone(milestone: Milestone) {
  if (milestone.completed_at) return
 
  if (confirm(`Delete the "${milestone.title}" milestone?`)) {
    router.delete(
      urls.projects.milestones.destroy(props.project.id, milestone.id),
      { preserveScroll: true }
    )
  }
}
 
function editRole(role: ProjectRole) {
  alert('Edit role - coming soon')
}
 
function deleteRole(role: ProjectRole) {
  if (confirm(`Delete the "${role.role_name}" role?`)) {
    router.delete(urls.projects.roles.destroy(props.project.id, role.id))
  }
}
 
function revokeInviteLink(link: InviteLink) {
  if (confirm('Revoke this invite link?')) {
    router.delete(
      urls.projects.inviteLinks.destroy(props.project.id, link.id),
      {
        preserveScroll: true,
        onSuccess: () => {
          inviteLinksList.value = inviteLinksList.value.filter((item) => item.id !== link.id)
        },
      }
    )
  }
}
 
// Chat functions
function getInitials(name: string): string {
  const parts = name.split(' ')
  return (parts[0]?.[0] || '') + (parts[1]?.[0] || '')
}
 
function formatChatTime(dateTime: string): string {
  const date = new Date(dateTime)
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)
 
  if (messageDate.getTime() === today.getTime()) {
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
  }
 
  if (messageDate.getTime() === yesterday.getTime()) {
    return 'Yesterday'
  }
 
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}
 
function scrollChatToBottom() {
  if (chatMessagesContainer.value) {
    nextTick(() => {
      chatMessagesContainer.value.scrollTop = chatMessagesContainer.value.scrollHeight
    })
  }
}
 
async function loadChatMessages() {
  if (chatMessages.value.length > 0 || !props.groupChatId) return
  chatLoading.value = true
 
  try {
    const response = await fetch(urls.projects.chat(props.project.id), {
      headers: { 'Accept': 'application/json' }
    })
    const data = await response.json()
    chatMessages.value = data.messages.data
    chatParticipants.value = data.participants
    scrollChatToBottom()
    subscribeToGroupChat()
  } catch (err) {
    console.error('Failed to load chat', err)
  }
 
  chatLoading.value = false
}
 
function subscribeToGroupChat() {
  if (!props.groupChatId || !window.Echo) return
 
  window.Echo.private(`conversation.${props.groupChatId}`)
    .listen('.message.sent', (e: any) => {
      if (e.sender.id !== $page.props.auth.user.id) {
        chatMessages.value.push(e)
        nextTick(() => scrollChatToBottom())
      }
    })
    .listen('.message.updated', (e: any) => {
      const idx = chatMessages.value.findIndex(m => m.id === e.id)
      if (idx !== -1) chatMessages.value[idx] = { ...chatMessages.value[idx], body: e.body, edited_at: e.edited_at }
    })
    .listen('.message.deleted', (e: any) => {
      const idx = chatMessages.value.findIndex(m => m.id === e.id)
      if (idx !== -1) chatMessages.value[idx].deleted_at = new Date().toISOString()
    })
    .listen('.user.typing', (e: any) => {
      if (e.user.id !== $page.props.auth.user.id) {
        typingUser.value = e.user
        clearTimeout(typingTimeout)
        typingTimeout = setTimeout(() => {
          typingUser.value = null
        }, 3000)
      }
    })
}

function handleChatKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendChatMessage()
  }
}

function handleChatTyping() {
  const now = Date.now()
  if (now - lastTypingSent > 3000 && props.groupChatId) {
    lastTypingSent = now
    fetch(urls.messages.typing(props.groupChatId), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
    }).catch(() => {})
  }
}

async function sendChatMessage() {
  if (!chatInput.value.trim() || !props.groupChatId) return

  const body = chatInput.value
  chatInput.value = ''

  const tempMsg = {
    id: 'temp-' + Date.now(),
    body,
    sender: { id: $page.props.auth.user.id, name: $page.props.auth.user.name },
    created_at: new Date().toISOString(),
    edited_at: null,
    deleted_at: null,
  }

  chatMessages.value.push(tempMsg)
  nextTick(() => scrollChatToBottom())

  try {
    const response = await fetch(urls.messages.send(props.groupChatId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ body }),
    })

    if (!response.ok) throw new Error('Failed to send message')

    const data = await response.json()
    const idx = chatMessages.value.findIndex(m => m.id === tempMsg.id)
    if (idx !== -1) {
      chatMessages.value[idx] = data.message
      nextTick(() => scrollChatToBottom())
    }
  } catch (err) {
    console.error('Error sending message:', err)
    chatMessages.value = chatMessages.value.filter(m => m.id !== tempMsg.id)
  }
}

watch(
  () => activeTab.value,
  (newTab) => {
    if (newTab === 'Chat' && props.groupChatId) {
      loadChatMessages()
    }
    if (newTab === 'Board') {
      loadBoardData()
    }
    if (newTab === 'Decision log') {
      loadDecisions()
    }
    if (newTab === 'Files') {
      loadFiles()
    }
    if (newTab === 'Members' && (props.isMember || props.isOwner)) {
      loadMemberActivity()
    }
    if (newTab === 'GitHub') {
      loadGitHubData()
    }
  }
)

// ── Board functions ────────────────────────────────────────────────────────────

async function loadBoardData() {
  await Promise.all([loadSprintTasks(), loadBacklog()])
}

async function loadSprintTasks() {
  if (!currentActiveSprint.value) return
  sprintBoardLoading.value = true
  try {
    const res = await fetch(
      urls.projects.tasks.index(props.project.id, { sprint_id: String(currentActiveSprint.value.id) }),
      { headers: { 'Accept': 'application/json' } }
    )
    const data = await res.json()
    sprintTasks.todo = data.todo ?? []
    sprintTasks.in_progress = data.in_progress ?? []
    sprintTasks.done = data.done ?? []
  } catch (e) {
    console.error('Failed to load sprint tasks', e)
  }
  sprintBoardLoading.value = false
}

async function loadBacklog() {
  backlogLoading.value = true
  try {
    const res = await fetch(
      urls.projects.tasks.index(props.project.id, { sprint_id: 'backlog' }),
      { headers: { 'Accept': 'application/json' } }
    )
    const data = await res.json()
    backlogTasks.value = data.backlog ?? []
  } catch (e) {
    console.error('Failed to load backlog', e)
  }
  backlogLoading.value = false
}

function openTaskDetail(task: BoardTask) {
  selectedTask.value = task
}

function openAddTask(status = 'todo') {
  addTaskDefaultStatus.value = status
  addTaskForm.title = ''
  addTaskForm.description = ''
  addTaskForm.story_points = ''
  addTaskForm.priority = 'medium'
  addTaskForm.status = status
  addTaskForm.sprint_id = currentActiveSprint.value ? String(currentActiveSprint.value.id) : ''
  showAddTask.value = true
}

async function saveAddTask() {
  if (!addTaskForm.title.trim()) return
  isSavingTask.value = true
  try {
    const res = await fetch(urls.projects.tasks.store(props.project.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        title: addTaskForm.title,
        description: addTaskForm.description || null,
        story_points: addTaskForm.story_points ? Number(addTaskForm.story_points) : null,
        priority: addTaskForm.priority,
        status: addTaskForm.status,
        sprint_id: addTaskForm.sprint_id ? Number(addTaskForm.sprint_id) : null,
      }),
    })
    if (res.ok) {
      const data = await res.json()
      const task: BoardTask = data.task
      if (task.sprint_id && currentActiveSprint.value && task.sprint_id === currentActiveSprint.value.id) {
        const col = task.status as keyof typeof sprintTasks
        if (col in sprintTasks) sprintTasks[col].push(task)
      } else {
        backlogTasks.value.push(task)
      }
      showAddTask.value = false
    }
  } finally {
    isSavingTask.value = false
  }
}

async function generateAiTasks() {
  if (isGenerating.value) return
  isGenerating.value = true
  try {
    const res = await fetch(urls.projects.tasks.generate(props.project.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({}),
    })
    const data = await res.json()
    if (data.success) {
      backlogTasks.value = [...backlogTasks.value, ...data.tasks]
      boardView.value = 'backlog'
    } else {
      alert(data.error ?? 'Failed to generate tasks')
    }
  } catch (e) {
    alert('Failed to generate tasks. Please try again.')
  }
  isGenerating.value = false
}

async function createSprint() {
  isSavingSprint.value = true
  try {
    const res = await fetch(urls.projects.sprints.store(props.project.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        name: newSprintForm.name,
        goal: newSprintForm.goal || null,
        start_date: newSprintForm.start_date,
        end_date: newSprintForm.end_date,
      }),
    })
    if (res.ok) {
      const data = await res.json()
      allSprints.value.unshift(data.sprint)
      showCreateSprint.value = false
      newSprintForm.name = `Sprint ${allSprints.value.length + 1}`
      newSprintForm.goal = ''
    } else {
      const err = await res.json()
      alert(err.message ?? 'Failed to create sprint')
    }
  } finally {
    isSavingSprint.value = false
  }
}

async function startSprint() {
  if (!sprintToStart.value) return
  isSavingSprint.value = true
  try {
    const res = await fetch(urls.projects.sprints.start(props.project.id, sprintToStart.value.id), {
      method: 'PATCH',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
    })
    if (res.ok) {
      const data = await res.json()
      currentActiveSprint.value = data.sprint
      const idx = allSprints.value.findIndex(s => s.id === data.sprint.id)
      if (idx !== -1) allSprints.value[idx] = data.sprint
      showStartSprint.value = false
      sprintToStart.value = null
      boardView.value = 'sprint'
      await loadSprintTasks()
    } else {
      const err = await res.json()
      alert(err.message ?? 'Failed to start sprint')
    }
  } finally {
    isSavingSprint.value = false
  }
}

async function completeSprint() {
  if (!currentActiveSprint.value) return
  isSavingSprint.value = true
  try {
    const res = await fetch(urls.projects.sprints.complete(props.project.id, currentActiveSprint.value.id), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        retro_good: retroForm.retro_good || null,
        retro_improve: retroForm.retro_improve || null,
        retro_actions: retroForm.retro_actions || null,
      }),
    })
    if (res.ok) {
      const data = await res.json()
      const idx = allSprints.value.findIndex(s => s.id === data.sprint.id)
      if (idx !== -1) allSprints.value[idx] = data.sprint
      currentActiveSprint.value = null
      sprintTasks.todo = []
      sprintTasks.in_progress = []
      sprintTasks.done = []
      showCompleteSprint.value = false
      retroForm.retro_good = ''
      retroForm.retro_improve = ''
      retroForm.retro_actions = ''
      await loadBacklog()
      boardView.value = 'velocity'
    } else {
      const err = await res.json()
      alert(err.message ?? 'Failed to complete sprint')
    }
  } finally {
    isSavingSprint.value = false
  }
}

async function assignTaskToActiveSprint(task: BoardTask) {
  if (!currentActiveSprint.value) return
  const res = await fetch(urls.projects.tasks.assignSprint(props.project.id, task.id), {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify({ sprint_id: currentActiveSprint.value.id }),
  })
  if (res.ok) {
    backlogTasks.value = backlogTasks.value.filter(t => t.id !== task.id)
    const updated = { ...task, sprint_id: currentActiveSprint.value.id, status: 'todo' }
    sprintTasks.todo.push(updated as BoardTask)
  }
}

async function onDragEnd(evt: any, toStatus: string) {
  const col = toStatus as keyof typeof sprintTasks
  const tasks = sprintTasks[col]

  tasks.forEach((task, idx) => {
    if (task.status !== toStatus || task.position !== idx) {
      fetch(urls.projects.tasks.move(props.project.id, task.id), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
          status: toStatus,
          position: idx,
          sprint_id: currentActiveSprint.value?.id ?? null,
        }),
      }).then(async (res) => {
        if (res.ok) {
          const data = await res.json()
          tasks[idx] = data.task
        }
      })
    }
  })
}

async function onBacklogReorder() {
  backlogTasks.value.forEach((task, idx) => {
    fetch(urls.projects.tasks.move(props.project.id, task.id), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({ status: task.status, position: idx, sprint_id: null }),
    })
  })
}

function onTaskUpdated(updated: BoardTask) {
  if (selectedTask.value?.id === updated.id) {
    selectedTask.value = updated
  }

  // Update in sprint columns
  for (const col of ['todo', 'in_progress', 'done'] as const) {
    const idx = sprintTasks[col].findIndex(t => t.id === updated.id)
    if (idx !== -1) {
      if (updated.sprint_id === null) {
        // Moved to backlog
        sprintTasks[col].splice(idx, 1)
        if (!backlogTasks.value.find(t => t.id === updated.id)) {
          backlogTasks.value.unshift(updated)
        }
      } else if (updated.status !== col) {
        // Status changed
        sprintTasks[col].splice(idx, 1)
        const newCol = updated.status as keyof typeof sprintTasks
        if (newCol in sprintTasks) sprintTasks[newCol].push(updated)
      } else {
        sprintTasks[col][idx] = updated
      }
      return
    }
  }

  // Update in backlog
  const backlogIdx = backlogTasks.value.findIndex(t => t.id === updated.id)
  if (backlogIdx !== -1) {
    if (updated.sprint_id && currentActiveSprint.value && updated.sprint_id === currentActiveSprint.value.id) {
      backlogTasks.value.splice(backlogIdx, 1)
      const col = updated.status as keyof typeof sprintTasks
      if (col in sprintTasks) sprintTasks[col].push(updated)
    } else {
      backlogTasks.value[backlogIdx] = updated
    }
  }
}

async function quickCompleteTask(task: BoardTask) {
  const res = await fetch(urls.projects.tasks.move(props.project.id, task.id), {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      status: 'done',
      position: task.position,
      sprint_id: task.sprint_id,
    }),
  })

  if (res.ok) {
    const data = await res.json()
    onTaskUpdated(data.task)
  }
}

function onTaskDeleted(taskId: number) {
  selectedTask.value = null
  for (const col of ['todo', 'in_progress', 'done'] as const) {
    const idx = sprintTasks[col].findIndex(t => t.id === taskId)
    if (idx !== -1) { sprintTasks[col].splice(idx, 1); return }
  }
  backlogTasks.value = backlogTasks.value.filter(t => t.id !== taskId)
}

// ── Tab helpers ───────────────────────────────────────────────────────────────

function tabDisplayLabel(tab: string): string {
  if (tab === 'Decision log' && props.decisionCount > 0) return `Decision log (${props.decisionCount})`
  if (tab === 'Files' && props.fileCount > 0) return `Files (${props.fileCount})`
  return tab
}

// ── Decision Log functions ────────────────────────────────────────────────────

async function loadDecisions() {
  if (decisions.value.length > 0) return
  decisionLoading.value = true
  try {
    const res = await fetch(urls.projects.decisions.index(props.project.id), {
      headers: { 'Accept': 'application/json' },
    })
    decisions.value = await res.json()
  } catch (err) {
    console.error('Failed to load decisions', err)
  }
  decisionLoading.value = false
}

async function saveDecision() {
  if (!newDecision.value.decision.trim()) return
  try {
    const res = await fetch(urls.projects.decisions.store(props.project.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify(newDecision.value),
    })
    if (!res.ok) {
      const err = await res.json().catch(() => null)
      alert(err?.message || 'Failed to save decision')
      return
    }
    const data = await res.json()
    decisions.value.unshift(data)
    newDecision.value = { decision: '', reason: '' }
    showDecisionForm.value = false
  } catch (err) {
    console.error('Failed to save decision', err)
  }
}

async function deleteDecision(id: number) {
  if (!confirm('Delete this decision?')) return
  try {
    await fetch(urls.projects.decisions.destroy(props.project.id, id), {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    })
    decisions.value = decisions.value.filter((d) => d.id !== id)
  } catch (err) {
    console.error('Failed to delete decision', err)
  }
}

// ── Alive Signal functions ────────────────────────────────────────────────────

async function loadMemberActivity() {
  try {
    const res = await fetch(urls.projects.alive.index(props.project.id), {
      headers: { 'Accept': 'application/json' },
    })
    memberActivity.value = await res.json()
  } catch (err) {
    console.error('Failed to load member activity', err)
  }
}

function getActivityForUser(userId: number): string {
  return memberActivity.value.find((m) => m.user_id === userId)?.status || 'never'
}

function getActivityLabel(userId: number): string {
  const member = memberActivity.value.find((m) => m.user_id === userId)
  if (!member?.last_signal) return 'Never checked in'
  if (member.status === 'active') return 'Active today'
  if (member.status === 'away') return 'Away (1–3 days)'
  return 'Inactive (3+ days)'
}

async function sendAliveSignal() {
  if (aliveDisabled.value) return
  try {
    const res = await fetch(urls.projects.alive.store(props.project.id), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    })
    const data = await res.json()
    if (data.signaled) {
      justSignaled.value = true
      aliveDisabled.value = true
      setTimeout(() => { justSignaled.value = false }, 5000)
      setTimeout(() => { aliveDisabled.value = false }, 3600000)
    } else {
      alert(data.message || 'Already checked in this hour')
      aliveDisabled.value = true
      setTimeout(() => { aliveDisabled.value = false }, 3600000)
    }
  } catch (err) {
    console.error('Failed to send alive signal', err)
  }
}

// ── File functions ────────────────────────────────────────────────────────────

async function loadFiles() {
  if (projectFiles.value.length > 0) return
  filesLoading.value = true
  try {
    const res = await fetch(urls.projects.files.index(props.project.id), {
      headers: { 'Accept': 'application/json' },
    })
    projectFiles.value = await res.json()
  } catch (err) {
    console.error('Failed to load files', err)
  }
  filesLoading.value = false
}

async function handleFileDrop(e: DragEvent) {
  isDragging.value = false
  const files = e.dataTransfer?.files
  if (files?.length) await uploadFiles(files)
}

async function handleFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  const files = input.files
  if (files?.length) await uploadFiles(files)
  input.value = ''
}

async function uploadFiles(fileList: FileList) {
  uploading.value = true
  for (const file of Array.from(fileList)) {
    if (file.size > 10 * 1024 * 1024) {
      alert(`${file.name} is too large (max 10 MB)`)
      continue
    }
    const formData = new FormData()
    formData.append('file', file)
    try {
      const res = await fetch(urls.projects.files.store(props.project.id), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        body: formData,
      })
      if (!res.ok) {
        const err = await res.json().catch(() => null)
        alert(err?.message || `Failed to upload ${file.name}`)
        continue
      }
      const data = await res.json()
      projectFiles.value.unshift(data)
    } catch (err) {
      console.error('Upload failed:', err)
      alert(`Failed to upload ${file.name}`)
    }
  }
  uploading.value = false
}

async function deleteFile(fileId: number) {
  if (!confirm('Delete this file?')) return
  try {
    await fetch(urls.projects.files.destroy(props.project.id, fileId), {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    })
    projectFiles.value = projectFiles.value.filter((f) => f.id !== fileId)
  } catch (err) {
    console.error('Failed to delete file', err)
  }
}

// Load board data when Board tab is active on mount
onMounted(() => {
  if (activeTab.value === 'Board') loadBoardData()
})
</script>
