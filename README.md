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

- **One non-negotiable commitment** — you lock in your most important task before anything else
- **Three priority tiers** — Must / Should / Good To Do. Hard cap of 3 on "Must" so you can't lie to yourself
- **Real dopamine on done** — win counter, streak tracker, and celebration toast every time you tick something off

---

## Features

- 🔒 **Daily Commitment Lock** — one non-negotiable task, locked in each morning
- 📋 **Four-tier task board** — Must Do, Should Do, Good To Do, Parking Lot
- ✅ **Win counter + day streak** — tracks consistency, not just completion
- 🎉 **Celebration toast** — small dopamine hit on every completed task
- ⇄ **Move between sections** — with enforced rules (Must is locked, nothing promotes to Must)
- 🚗 **Parking Lot → Tomorrow** — promote ideas to tomorrow's board in one click
- ⚠️ **Carry-forward with urgency badges** — undone Must/Should tasks reappear next day marked Late or Urgent
- 🔄 **Reset Day** — clean slate, no guilt
- 🔐 **Google OAuth** — one-click login, no passwords

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Frontend | Blade + Tailwind CSS |
| Database | MySQL 8.0 |
| Auth | Google OAuth via Laravel Socialite |
| Push Notifications | Browser Push (web-push) |
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
git clone https://github.com/your-username/daily-win-board.git
cd daily-win-board
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

VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
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

---

## VAPID Keys (Push Notifications)

Generate your VAPID keys:

```bash
php artisan webpush:vapid
```

Copy the output into your `.env` as `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY`.

---

## Database Schema

```
users               — Google OAuth users
daily_commits       — One non-negotiable per day per user
tasks               — All tasks across all sections and dates
user_stats          — Streak and total win counts
```

See `CLAUDE.md` for full schema details.

---

## Deployment (Hostinger)

1. Upload files via Git or SFTP
2. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
3. Run `php artisan config:cache` and `php artisan route:cache`
4. Point document root to `/public`
5. Set up a cron job for Laravel scheduler:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Roadmap

### Phase 1 (Complete)
- [x] Project setup and CLAUDE.md spec
- [x] Google OAuth + user management
- [x] Daily commitment lock
- [x] Four-tier task board (Must / Should / Good / Parking Lot)
- [x] Win counter + streak tracker
- [x] Celebration toast
- [x] Parking lot → promote to tomorrow
- [x] Move tasks between sections (with restrictions)
- [x] Carry-forward undone Must/Should with Late/Urgent badges
- [x] Reset day
- [x] Deployed to Hostinger (`life.drtakaful.com`)
- [ ] Mobile responsive polish

### Phase 2 (Group A — Extensions)
- [ ] Win history — completed tasks log with dates and times
- [ ] Admin dashboard — user management, usage stats (admin retains own user dashboard too)
- [ ] Weekly review — wins per day, streak graph, completion rate by section

### Phase 3 (Group B — Goal Cascade)
- [ ] Goal Cascade — 10-year → 5-year → yearly → quarterly → daily
- [ ] Daily tasks linkable to quarterly goals
- [ ] Recurring tasks
- [ ] PWA / installable on mobile

---

## Project Structure

```
daily-win-board/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── TaskController.php
│   │   └── CommitController.php
│   └── Models/
│       ├── User.php
│       ├── Task.php
│       ├── DailyCommit.php
│       └── UserStat.php
├── resources/
│   └── views/
│       ├── layouts/app.blade.php
│       └── dashboard/index.blade.php
├── routes/web.php
├── database/migrations/
├── CLAUDE.md
└── README.md
```

---

## Contributing

This is a personal project but PRs are welcome. Open an issue first to discuss what you'd like to change.

---

## License

MIT — do what you want with it.

---

Built by [Hana Suhaimi](https://drtakaful.com) · [@nufas](https://instagram.com/nufas)
