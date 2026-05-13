# DevConnect LB

A collaboration platform for Lebanese computer science students and junior developers to discover project ideas, form teams, and build real things together.

Built with **Laravel 12 · Vue 3 · Inertia.js · Tailwind v4 · Laravel Reverb · MySQL 8**

---

## Requirements

| Tool | Version |
|------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| MySQL | 8.0+ |
| Redis | 6+ (for queues, cache, and Reverb scaling) |

---

## Quick Start

All commands run from the `backend/` directory.

### 1. Clone and enter the project

```bash
git clone https://github.com/your-org/devconnect-lb.git
cd devconnect-lb/backend
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install Node dependencies

```bash
npm install
```

### 4. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Then open `.env` and fill in the values below.

---

## Environment Variables

### Database (MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devconnect_lb
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database first:

```bash
mysql -u root -p -e "CREATE DATABASE devconnect_lb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Session & Cache (database driver is fine for local dev)

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

To use Redis instead (recommended for production):

```env
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### GitHub OAuth

Register an OAuth app at [github.com/settings/developers](https://github.com/settings/developers).  
Set the callback URL to `http://localhost:8000/auth/github/callback`.

```env
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
GITHUB_REDIRECT_URI=http://localhost:8000/auth/github/callback

# Optional: personal access token for GitHub API calls (issue fetching, repo stats)
GITHUB_TOKEN=ghp_yourtoken
```

### Anthropic (AI features)

```env
ANTHROPIC_API_KEY=sk-ant-your-real-key
ANTHROPIC_MODEL=claude-sonnet-4-20250514
```

> Without this key the AI idea generator, project matching, and task generation will return a 503 error instead of crashing. All other features work without it.

### Laravel Reverb (real-time WebSocket)

```env
BROADCAST_CONNECTION=reverb
BROADCAST_ENABLED=true

REVERB_APP_ID=devconnect
REVERB_APP_KEY=devconnect-key
REVERB_APP_SECRET=devconnect-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend must match these values
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> For local development without real-time features, leave `BROADCAST_CONNECTION=log` and `BROADCAST_ENABLED=false` (the `.env.example` defaults). Chat will still work on page load — just no live push.

---

## Database Migrations

Run all 44 migrations:

```bash
php artisan migrate
```

To reset and re-run from scratch:

```bash
php artisan migrate:fresh
```

To reset and seed with demo data:

```bash
php artisan migrate:fresh --seed
```

### Seeders

| Command | What it seeds |
|---------|--------------|
| `php artisan db:seed` | Full set: users, projects, ideas, mentors, notifications |
| `php artisan db:seed --class=DemoUserSeeder` | Sara Khalil demo account + all related data |
| `php artisan db:seed --class=UserSeeder` | Base users only (admin, Rami, Lara, Tarek, etc.) |

**Demo login credentials** (after running `DemoUserSeeder`):

| Email | Password | Role |
|-------|----------|------|
| `sara.khalil@designco.io` | `demo1234` | Designer / mentor |
| `admin@devconnect.lb` | `password` | Admin |
| `rami@devconnect.lb` | `password` | Developer |
| `lara@devconnect.lb` | `password` | Developer |

---

## Running the Development Environment

### Option A — All services in one command

```bash
npm run dev
```

This runs Laravel server + Vite (HMR) + queue worker + log viewer concurrently via the `dev` script in `package.json`.

### Option B — Services individually (separate terminals)

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (Vue HMR)
npm run dev

# Terminal 3 — Queue worker (needed for notifications, AI jobs)
php artisan queue:listen

# Terminal 4 — WebSocket server (needed for real-time chat)
php artisan reverb:start

# Terminal 5 — Log viewer (optional)
php artisan pail
```

The app will be at **http://127.0.0.1:8000**  
Vite dev server runs at **http://localhost:5173**  
Reverb WebSocket runs at **ws://localhost:8080**

---

## Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Useful Artisan Commands

```bash
# Clear all caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# Open interactive REPL
php artisan tinker

# Verify Anthropic API key is configured
php artisan tinker --execute="App\Services\AnthropicClientFactory::make(); echo 'API key OK';"

# Generate a model + migration + resource controller
php artisan make:model ModelName -mrc

# List all registered routes
php artisan route:list

# Run tests
php artisan test
php artisan test --filter=TestClassName
```

---

## Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Route handlers → Inertia::render(...)
│   │   ├── Middleware/      # EnsureOnboardingComplete, CheckAdmin, HandleInertiaRequests
│   │   └── Requests/        # Form request validation
│   ├── Models/              # 38 Eloquent models
│   ├── Services/            # AI services (AiIdeaGeneratorService, etc.)
│   └── Events/              # Broadcast events (NewMessage, TypingIndicator)
├── config/
│   ├── anthropic.php        # AI model config
│   └── reverb.php           # WebSocket config
├── database/
│   ├── migrations/          # 44 migration files
│   └── seeders/             # DatabaseSeeder, DemoUserSeeder, etc.
├── resources/
│   ├── css/app.css          # Tailwind v4 with @theme block
│   └── js/
│       ├── Pages/           # Vue 3 page components (Inertia)
│       ├── Components/      # Shared UI components (Button, Card, etc.)
│       ├── Layouts/         # AuthenticatedLayout, AdminLayout
│       └── lib/routes.ts    # Typed URL helpers
├── routes/
│   ├── web.php              # All routes (auth, projects, chat, admin)
│   └── channels.php         # Reverb broadcast channel auth
└── CLAUDE.md                # AI assistant guidance for this repo
```

---

## Architecture Notes

**Inertia.js full-stack** — controllers return `Inertia::render('PageName', [...props])`. There is no separate REST API. All data flows through Inertia props on page load.

**Real-time** — Laravel Reverb (WebSocket) + Laravel Echo (frontend). Group chat and DMs broadcast on private channels defined in `routes/channels.php`.

**Auth flow** — two middleware layers protect authenticated routes: `auth` (Sanctum session) and `EnsureOnboardingComplete` (redirects to quiz/profile until onboarding is done). Admin routes additionally require `CheckAdmin`.

**AI services** — all AI calls go through `AnthropicClientFactory::make()`. If `ANTHROPIC_API_KEY` is missing the factory throws a `RuntimeException` — the service catches this and returns a JSON error. Check the key with `php artisan tinker` if you see 503 on AI routes.

---

## Common Issues

**Port 8000 already in use**
```bash
php artisan serve --port=8001
```

**Vite can't connect / assets not loading**  
Make sure `npm run dev` is running in a separate terminal.

**Queue jobs not processing**  
Run `php artisan queue:listen` — required for notifications and background AI calls.

**Real-time chat not working**  
Run `php artisan reverb:start` and confirm `BROADCAST_ENABLED=true` and `BROADCAST_CONNECTION=reverb` in `.env`.

**AI features return 503**  
Set a valid `ANTHROPIC_API_KEY` in `.env` and run `php artisan config:clear`.

**Migration fails on column already exists**  
```bash
php artisan migrate:fresh
```
This drops and recreates all tables — only use on a local dev database.