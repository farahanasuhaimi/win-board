# CLAUDE.md — Daily Win Board

## Project Overview

A daily task and habit management web app called **Daily Win Board**. Built for a takaful consultant who needs a structured daily execution system that solves the core problem with Kanban: no urgency, no dopamine, no forcing function.

The system is NOT Kanban. It is a **daily commitment + priority tier + win tracking** system.

**Live**: `https://life.drtakaful.com`
**GitHub**: `https://github.com/farahanasuhaimi/win-board`

---

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Blade + Tailwind CSS v4 (no separate JS framework)
- **Database**: MySQL (via Hostinger)
- **Auth**: Google OAuth (Laravel Socialite)
- **Hosting**: Hostinger shared/VPS

---

## UI Design Language — Gumroad Style

This is the most important section. The entire app must feel like **Gumroad** — clean, bold, high-contrast, almost brutalist-minimal. Not soft SaaS, not pastel productivity.

### Typography
- Primary font: **`Syne`** (display/headers) + **`DM Sans`** (body) — load from Google Fonts
- Monospace accents: **`JetBrains Mono`** for numbers, streaks, win counts
- Large, confident type. Section headers at 13px bold uppercase. Body 14–15px.

### Color Palette
```css
:root {
  --color-bg: #F4F4F0;
  --color-surface: #FFFFFF;
  --color-border: #000000;
  --color-text: #000000;
  --color-text-muted: #6B6B6B;
  --color-must: #FF4F00;
  --color-should: #FFC900;
  --color-good: #23A094;
  --color-park: #B0B0A8;
  --color-danger: #E24B4A;
}
```

### Borders & Shapes
- **Thick 2px black borders** on cards, buttons, inputs
- Slightly rounded: `border-radius: 6px` max
- Box shadows: `4px 4px 0 #000` (hard offset, not blurred)
- Buttons: hover flips background to black, text to white

---

## Database Schema

```sql
users:         id, name, email, google_id, avatar, is_admin (bool), created_at
daily_commits: id, user_id, text, task_id (FK nullable → tasks), date, locked_at, unlocked_count, created_at
tasks:         id, user_id, text, section (enum: must/should/good/park), done (bool), date, sort_order, done_at (nullable), created_at, deleted_at
user_stats:    id, user_id, streak, total_wins, last_active_date, updated_at
```

---

## Routes

```
GET    /dashboard              → Today's board
POST   /commit                 → Save/lock daily commitment (accepts task_id nullable)
POST   /commit/unlock          → Unlock commitment for editing
POST   /tasks                  → Add task
PATCH  /tasks/{id}/toggle      → Mark done/undone (updates done_at)
PATCH  /tasks/{id}/move        → Move task to another section
DELETE /tasks/{id}             → Delete task
POST   /day/reset              → Reset today
GET    /history                → Weekly summary cards (past completed weeks)
GET    /review                 → Weekly review (Mon-Sun, bar chart + section stats)
GET    /admin                  → Admin dashboard (is_admin required)
GET    /auth/google            → Redirect to Google
GET    /auth/google/callback   → Handle callback
POST   /logout                 → Logout
```

---

## Core Features

### 1. Daily Commitment Lock (`/dashboard`)
- Prominent card at top of dashboard
- If Must tasks exist: shows a picker (clickable buttons) — select one to auto-fill
- Free-text fallback if no Must tasks
- "Lock it in" saves to DB; input replaced with locked text + 🔒 icon
- If linked to a Must task (`task_id`): ticking that task flips the banner to ✅ Done in real-time
- Can be unlocked (edited) once per day max

### 2. Four-Tier Task Board
2×2 grid on desktop, stacked on mobile.

| Section | Color | Cap |
|---|---|---|
| Must Do Today | `#FF4F00` | 3 (hard, includes carry-forward) |
| Should Do Today | `#FFC900` | 5 |
| Good To Do | `#23A094` | None |
| Parking Lot | `#B0B0A8` | None |

- Colored left border accent (4px solid) per section
- Task items: checkbox + text + action buttons (always visible on mobile, hover-only on desktop `md:`)
- "Add task" inline input at bottom
- Must tasks: × button always dimmed (`opacity-30`); clicking shows confirmation modal

### 3. Task Completion
- Checkbox toggle → done state (strikethrough, 50% opacity, moves to bottom)
- Celebration toast: rotates through messages, auto-dismisses after 1.5s

### 4. Win Counter + Streak
- Wins Today: done tasks for today
- Day Streak: consecutive days with at least one win
- Stored in `user_stats`

### 5. Reset Day
- Confirmation modal (Gumroad-style)
- Clears all tasks and commitment for today

---

## Move Rules

```
must   → park only (via confirmation modal — not direct)
should ↔ good, should → park
good   ↔ should, good  → park
park   → should, park  → good
```

- Nothing moves TO `must`
- Moving to `should` respects the 5-task cap
- All moves are AJAX, no page reload

---

## Carry-Forward Rules

- Undone `must`, `should`, and `park` tasks carry forward to the next day automatically
- `good` tasks do NOT carry forward
- Done tasks do NOT carry forward
- `must` and `should` carry-forward tasks get urgency badges:
  - 1 day overdue → `⚠️ LATE` (amber)
  - 2+ days → `🚨 URGENT` (red)
- `park` carry-forward tasks get no urgency badges (timeless by design)
- Must cap (max 3) counts ALL undone must tasks regardless of date

---

## Must Task Delete Guard

- × button on Must tasks: always visible but dimmed (`opacity-30`)
- Clicking shows a modal with three options:
  1. **🅿️ Move to Parking Lot** — moves task to park section
  2. **Delete anyway** — hard delete
  3. **Cancel**

---

## Win History (`/history`)

- Shows past **completed weeks** only (current week excluded until Sunday ends)
- Each week = one summary card: week range label, wins badge, overall completion bar, section breakdown
- Sections with 0 tasks hidden
- Most recent week first

---

## Weekly Review (`/review`)

- Week boundary: Monday–Sunday (current calendar week)
- **Wins per day chart**: upward bar chart, wins counted by `done_at` (when completed, not when assigned)
- Today highlighted in orange; future days dimmed
- Section completion rates based on tasks assigned this week

---

## Admin (`/admin`)

- Gated by `is_admin = true` on users
- Admin retains full access to their own dashboard
- Shows: user list, total wins, streaks, can grant/revoke admin

---

## Behaviour Notes

- All task mutations: AJAX (fetch API) — no full page reloads
- Optimistic UI where appropriate
- Date handling: always use server's local date via `now()->toDateString()`
- Mobile: stacked 2×2 grid, two-row navbar (brand + logout top, nav links below)
- Task action buttons: `opacity-100 md:opacity-0 md:group-hover:opacity-100` — visible on mobile, hover-only on desktop
- Wins counted by `done_at` throughout (not task.date) for all analytics

---

## Phase 3 — Goal Cascade (Planned)

- Goal Cascade: 10-year → 5-year → yearly → quarterly → daily
- Daily tasks linkable to quarterly goals
- Recurring tasks
- PWA / installable on mobile
