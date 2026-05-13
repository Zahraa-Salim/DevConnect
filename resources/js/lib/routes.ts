type Id = string | number

function queryString(params: Record<string, string | number | null | undefined>): string {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      query.set(key, String(value))
    }
  })

  const serialized = query.toString()
  return serialized ? `?${serialized}` : ''
}

export const urls = {
  contribute: {
    index: (params?: Record<string, string | undefined>) => `/contribute${queryString(params ?? {})}`,
    rank: () => '/contribute/rank',
    logs: {
      store: () => '/contribution-logs',
      update: (log: Id) => `/contribution-logs/${log}`,
      convert: (log: Id) => `/contribution-logs/${log}/convert`,
    },
  },
  messages: {
    index: () => '/messages',
    store: () => '/messages',
    show: (conversation: Id) => `/messages/${conversation}`,
    send: (conversation: Id) => `/messages/${conversation}/messages`,
    update: (message: Id) => `/messages/msg/${message}`,
    destroy: (message: Id) => `/messages/msg/${message}`,
    typing: (conversation: Id) => `/messages/${conversation}/typing`,
    usersSearch: (q: string) => `/messages/users/search${queryString({ q })}`,
  },
  ideas: {
    index: () => '/ideas',
    store: () => '/ideas',
    generate: () => '/ideas/generate',
    show: (idea: Id) => `/ideas/${idea}`,
    vote: (idea: Id) => `/ideas/${idea}/vote`,
    comments: {
      store: (idea: Id) => `/ideas/${idea}/comments`,
      destroy: (idea: Id, comment: Id) => `/ideas/${idea}/comments/${comment}`,
    },
  },
  projects: {
    index: () => '/projects',
    create: (ideaId?: Id | null) => `/projects/create${queryString({ idea_id: ideaId })}`,
    store: () => '/projects',
    show: (project: Id) => `/projects/${project}`,
    edit: (project: Id) => `/projects/${project}/edit`,
    update: (project: Id) => `/projects/${project}`,
    destroy: (project: Id) => `/projects/${project}`,
    updateStatus: (project: Id) => `/projects/${project}/status`,
    readiness: (project: Id) => `/projects/${project}/readiness`,
    apply: (project: Id) => `/projects/${project}/apply`,
    leave: (project: Id) => `/projects/${project}/leave`,
    rate: (project: Id) => `/projects/${project}/rate`,
    ratings: {
      store: (project: Id) => `/projects/${project}/ratings`,
    },
    suggestions: {
      index: (project: Id) => `/projects/${project}/suggestions`,
      generate: (project: Id) => `/projects/${project}/suggestions`,
    },
    nda: (project: Id) => `/projects/${project}/nda`,
    ndaSign: (project: Id) => `/projects/${project}/nda/sign`,
    agreement: (project: Id) => `/projects/${project}/agreement`,
    agreementSign: (project: Id) => `/projects/${project}/agreement/sign`,
    agreementText: (project: Id) => `/projects/${project}/agreement/text`,
    inviteLinks: {
      store: (project: Id) => `/projects/${project}/invite-links`,
      index: (project: Id) => `/projects/${project}/invite-links`,
      destroy: (project: Id, inviteLink: Id) => `/projects/${project}/invite-links/${inviteLink}`,
    },
    applications: {
      accept: (project: Id, application: Id) => `/projects/${project}/applications/${application}/accept`,
      decline: (project: Id, application: Id) => `/projects/${project}/applications/${application}/decline`,
      withdraw: (project: Id, application: Id) => `/projects/${project}/applications/${application}`,
    },
    members: {
      remove: (project: Id, member: Id) => `/projects/${project}/members/${member}`,
    },
    roles: {
      store: (project: Id) => `/projects/${project}/roles`,
      destroy: (project: Id, role: Id) => `/projects/${project}/roles/${role}`,
    },
    milestones: {
      store: (project: Id) => `/projects/${project}/milestones`,
      complete: (project: Id, milestone: Id) => `/projects/${project}/milestones/${milestone}/complete`,
      destroy: (project: Id, milestone: Id) => `/projects/${project}/milestones/${milestone}`,
    },
    sprints: {
      index: (project: Id) => `/projects/${project}/sprints`,
      store: (project: Id) => `/projects/${project}/sprints`,
      update: (project: Id, sprint: Id) => `/projects/${project}/sprints/${sprint}`,
      destroy: (project: Id, sprint: Id) => `/projects/${project}/sprints/${sprint}`,
      start: (project: Id, sprint: Id) => `/projects/${project}/sprints/${sprint}/start`,
      complete: (project: Id, sprint: Id) => `/projects/${project}/sprints/${sprint}/complete`,
    },
    tasks: {
      index: (project: Id, params?: Record<string, string | number | null | undefined>) =>
        `/projects/${project}/tasks${queryString(params ?? {})}`,
      store: (project: Id) => `/projects/${project}/tasks`,
      generate: (project: Id) => `/projects/${project}/tasks/generate`,
      show: (project: Id, task: Id) => `/projects/${project}/tasks/${task}`,
      update: (project: Id, task: Id) => `/projects/${project}/tasks/${task}`,
      destroy: (project: Id, task: Id) => `/projects/${project}/tasks/${task}`,
      move: (project: Id, task: Id) => `/projects/${project}/tasks/${task}/move`,
      assignSprint: (project: Id, task: Id) => `/projects/${project}/tasks/${task}/assign-sprint`,
      removeSprint: (project: Id, task: Id) => `/projects/${project}/tasks/${task}/remove-sprint`,
    },
    chat: (project: Id) => `/projects/${project}/chat`,
    decisions: {
      index: (project: Id) => `/projects/${project}/decisions`,
      store: (project: Id) => `/projects/${project}/decisions`,
      destroy: (project: Id, decision: Id) => `/projects/${project}/decisions/${decision}`,
    },
    alive: {
      store: (project: Id) => `/projects/${project}/alive`,
      index: (project: Id) => `/projects/${project}/alive`,
    },
    files: {
      index: (project: Id) => `/projects/${project}/files`,
      store: (project: Id) => `/projects/${project}/files`,
      download: (project: Id, file: Id) => `/projects/${project}/files/${file}/download`,
      destroy: (project: Id, file: Id) => `/projects/${project}/files/${file}`,
    },
    github: {
      link: (project: Id) => `/projects/${project}/github/link`,
      unlink: (project: Id) => `/projects/${project}/github/unlink`,
      commits: (project: Id) => `/projects/${project}/github/commits`,
      pulls: (project: Id) => `/projects/${project}/github/pulls`,
      contributors: (project: Id) => `/projects/${project}/github/contributors`,
    },
  },
}
