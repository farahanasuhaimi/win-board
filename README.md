# Daily Win Board

A daily execution system built for people who know what they need to do — but struggle to actually show up and do it.

Not Kanban. Not another todo list. A **commitment-first, dopamine-driven daily board** that forces prioritisation, rewards action, and tracks consistency over time.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.x-38BDF8?style=flat&logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-black?style=flat)

---

## Why This Exists

Kanban boards give you visibility but zero urgency. Cards just sit there. Nothing forces you to start, and "Done" never feels satisfying enough.

Daily Win Board fixes this with three mechanisms:

- **One non-negotiable commitment** — you lock in your most important task before anything else, optionally linked to a Must task so ticking it marks your commitment done
- **Three priority tiers** — Must / Should / Good To Do. Hard cap of 3 on "Must" so you can't lie to yourself
- **Real dopamine on done** — win counter, streak tracker, and celebration toast every time you tick something off

---

## Features

- 🔒 **Daily Commitment Lock** — one non-negotiable, locked in each morning; pick from your Must tasks or type your own intention
- 📋 **Four-tier task board** — Must Do, Should Do, Good To Do, Parking Lot
- ✅ **Win counter + day streak** — tracks consistency, not just completion
- 🎉 **Celebration toast** — small dopamine hit on every completed task
- ⇄ **Move between sections** — with enforced rules (can move Must → Parking Lot via confirmation)
- ⚠️ **Carry-forward with urgency scale** — undone Must/Should/Park tasks reappear next day; urgency shown as emoji scale ⚠️→🟠→🔴→🚨→🔥→☠️→💀 (1–7+ days overdue)
- 🗑️ **Must task delete guard** — deleting a Must task prompts: park it first, or delete anyway
- 📅 **Weekly Review** — Mon–Sun bar chart (wins by completion date), section completion rates
- 📜 **Win History** — weekly summary cards for completed past weeks
- 👤 **Admin dashboard** — user management, stats, admin role management
- 🔄 **Reset Day** — clean slate, no guilt
- 🔐 **Google OAuth** — one-click login, no passwords
- ⏱️ **Task time estimates** — optional on all sections, required on Must; dropdown (10m/30m/1h/2h/6h); running total shown in Must header; overload warning when Must exceeds 12 hours
- 📱 **PWA** — installable on mobile (manifest + service worker); install prompt on login page
- 📱 **Mobile responsive** — two-row navbar, task action buttons always visible on touch
- 📅 **Google Calendar strip** — today's schedule from all your Google Calendars shown on the dashboard; loads async so the board is never blocked

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Frontend | Blade + Tailwind CSS v4 |
| Database | MySQL 8.0 |
| Auth | Google OAuth via Laravel Socialite |
| Hosting | Hostinger |

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8.0+
- A Google Cloud project with OAuth 2.0 credentials

### Installation

**1. Clone the repository**

```bash
git clone https://github.com/farahanasuhaimi/win-board.git
cd win-board
```

**2. Install dependencies**

```bash
composer install
npm install
```

**3. Set up environment**

```bash
cp .env.example .env
php artisan key:generate
```

**4. Configure `.env`**

```env
APP_NAME="Daily Win Board"
APP_URL=http://localhost:8000

DB_DATABASE=daily_win_board
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

**5. Run migrations**

```bash
php artisan migrate
```

**6. Build assets**

```bash
npm run dev
# or for production:
npm run build
```

**7. Serve**

```bash
php artisan serve
```

Visit `http://localhost:8000` and log in with Google.

---

## Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or use existing)
3. Enable **Google+ API** and **Google Identity**
4. Go to Credentials → Create OAuth 2.0 Client ID
5. Set authorised redirect URI to `http://localhost:8000/auth/google/callback`
6. Copy Client ID and Secret into `.env`

For production, add your live domain as an additional authorised redirect URI.

---

## Database Schema

```
users               — Google OAuth users (name, email, google_id, avatar, is_admin, google_access_token, google_refresh_token, google_token_expires_at)
daily_commits       — One non-negotiable per day per user (text, task_id FK nullable, locked_at)
tasks               — All tasks across all sections and dates (soft deletes, done_at, estimated_minutes nullable)
user_stats          — Streak and total win counts
```

---

## Google Calendar Setup

One-time setup required per user:

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → your project
2. Enable the **Google Calendar API** (APIs & Services → Library → search "Google Calendar API")
3. Go to **Google Auth Platform → Audience** → add your Gmail as a test user (or publish the app)
4. Your existing OAuth credentials will work — no new client ID needed
5. Run `php artisan migrate` (adds `google_access_token`, `google_refresh_token`, `google_token_expires_at` to users)
6. **Re-login with Google** — the OAuth flow now requests `calendar.readonly` scope to save the refresh token

Once logged in, the dashboard fetches today's events from **all your Google Calendars** asynchronously — the board loads first, the schedule strip fills in after.

---

## Deployment (Hostinger)

1. Push via Git, then `git pull origin main` on server
2. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
3. Run `php artisan migrate`
4. Run `php artisan config:cache && php artisan route:cache`
5. Point document root to `/public`
6. Set yourself as admin:

```sql
UPDATE users SET is_admin = 1 WHERE email = 'your@email.com';
```

---

## Roadmap

### Phase 1 ✅
- [x] Google OAuth + user management
- [x] Daily commitment lock
- [x] Four-tier task board (Must / Should / Good / Parking Lot)
- [x] Win counter + streak tracker
- [x] Celebration toast
- [x] Move tasks between sections (with rules)
- [x] Carry-forward undone Must/Should with Late/Urgent badges
- [x] Reset day
- [x] Deployed to Hostinger (`life.drtakaful.com`)

### Phase 2 ✅
- [x] Win History — weekly summary cards (past completed weeks)
- [x] Weekly Review — Mon–Sun bar chart, wins by done_at, section completion rates
- [x] Admin dashboard — user management, usage stats

### Phase 2 Polish ✅
- [x] Mobile responsive navbar (two-row layout)
- [x] Task action buttons visible on mobile (no hover required)
- [x] Parking Lot tasks carry forward day-to-day
- [x] Commitment links to a Must task — ticking it marks commitment ✅ done
- [x] Must task delete guard — confirm with park option
- [x] Review chart fixed (upward bars, wins by completion date)
- [x] History redesigned as weekly summary cards

### Phase 2.5 ✅
- [x] PWA — manifest + service worker, installable on mobile
- [x] Urgency scale — emoji-only (⚠️→💀) replacing text badges, 7-level daily escalation
- [x] Task time estimates — 10m/30m/1h/2h/6h dropdown; required on Must, optional elsewhere
- [x] Must overload warning — banner fires when total Must estimate exceeds 12 hours
- [x] Done task persistence — carry-forward tasks completed today stay visible until tomorrow
- [x] Google Calendar strip — today's events from all calendars; async load, meeting vs Must free-time indicator

### Phase 3 — Goal Cascade (planned)
- [ ] Goal Cascade — 10-year → 5-year → yearly → quarterly → daily
- [ ] Daily tasks linkable to quarterly goals
- [ ] Recurring tasks

---

## Project Structure

```
win-board/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── TaskController.php
│   │   ├── CommitController.php
│   │   ├── HistoryController.php
│   │   ├── ReviewController.php
│   │   └── AdminController.php
│   └── Models/
│       ├── User.php
│       ├── Task.php
│       ├── DailyCommit.php
│       └── UserStat.php
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── dashboard/index.blade.php
│   ├── history/index.blade.php
│   ├── review/index.blade.php
│   └── admin/index.blade.php
├── routes/web.php
├── database/migrations/
├── CLAUDE.md
└── README.md
```

---

## License

MIT — do what you want with it.

---

Built by [Hana Suhaimi](https://drtakaful.com)
