# CLAUDE.md — Daily Win Board

## Project Overview

A daily task and habit management web app called **Daily Win Board**. Built for a takaful consultant who needs a structured daily execution system that solves the core problem with Kanban: no urgency, no dopamine, no forcing function.

The system is NOT Kanban. It is a **daily commitment + priority tier + win tracking** system.

---

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Blade + Tailwind CSS (no separate JS framework unless specified)
- **Database**: MySQL (via Hostinger)
- **Auth**: Google OAuth (Laravel Socialite)
- **Push Notifications**: Browser Push (web-push library)
- **Hosting**: Hostinger shared/VPS

---

## UI Design Language — Gumroad Style

This is the most important section. The entire app must feel like **Gumroad** — clean, bold, high-contrast, almost brutalist-minimal. Not soft SaaS, not pastel productivity. Gumroad-style means:

### Typography
- Primary font: **`DM Sans`** or **`Syne`** (load from Google Fonts) — bold, geometric, modern
- Monospace accents: **`JetBrains Mono`** for numbers, streaks, win counts
- Large, confident type. Section headers at 20–24px bold. Body 14–15px.
- NO rounded-off corporate sans like Inter or Roboto

### Color Palette
```css
:root {
  --color-bg: #FFFFFF;
  --color-surface: #F4F4F0;
  --color-border: #000000;
  --color-text: #000000;
  --color-text-muted: #6B6B6B;
  --color-accent: #FF90E8;       /* Gumroad pink */
  --color-accent-alt: #FFC900;   /* Amber highlight */
  --color-success: #23A094;      /* Teal for done state */
  --color-danger: #E24B4A;       /* Red for must-do urgency */
  --color-must: #FF4F00;         /* Bold orange-red */
  --color-should: #FFC900;       /* Amber */
  --color-good: #23A094;         /* Teal */
  --color-park: #B0B0A8;         /* Muted gray */
}
```

### Borders & Shapes
- **Thick 2px black borders** on cards, buttons, inputs — Gumroad signature
- Slightly rounded corners: `border-radius: 6px` max. No soft pillowy radius.
- Box shadows: `3px 3px 0 #000` (hard offset shadow, not blurred) — Gumroad's iconic "lifted" card effect
- Buttons: solid black border, white background, hard shadow. On hover: background flips to black, text flips to white.

### Buttons
```css
.btn {
  background: #fff;
  border: 2px solid #000;
  box-shadow: 3px 3px 0 #000;
  border-radius: 6px;
  padding: 10px 20px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.1s;
}
.btn:hover {
  background: #000;
  color: #fff;
  transform: translate(2px, 2px);
  box-shadow: 1px 1px 0 #000;
}
```

### Form Inputs
- Border: `2px solid #000`
- No drop shadow at rest, `box-shadow: 2px 2px 0 #000` on focus
- Background: white

---

## Core Features to Build

### 1. Daily Commitment Lock (`/dashboard`)
- A prominent input at the top of the page — full width, big font
- Label: **"What is your ONE non-negotiable task today?"**
- User types and clicks "Lock it in" — it saves to DB and the input is replaced with the committed text + a lock icon
- Can be unlocked (changed) once per day maximum
- Stored in `daily_commits` table: `user_id`, `text`, `date`, `locked_at`

### 2. Four-Tier Task Board (same page, below commit)
Display as a 2×2 grid on desktop, stacked on mobile.

| Section | Color accent | Description |
|---|---|---|
| Must Do Today | `--color-must` (orange-red) | Max 3 tasks. Hard limit enforced in UI |
| Should Do Today | `--color-should` (amber) | Up to 5 tasks |
| Good To Do | `--color-good` (teal) | Nice to have, no limit |
| Parking Lot | `--color-park` (gray) | Ideas, not today |

Each section:
- Has a colored left border accent (4px solid)
- Title bar with section name + task count badge
- Task items: checkbox (custom styled) + task text + delete icon on hover
- "Add task" input at the bottom of each section (inline, not modal)
- Pressing Enter adds the task

Task table: `tasks` — `id`, `user_id`, `text`, `section` (enum: must/should/good/park), `done` (bool), `date`, `order`, `created_at`

### 3. Task Completion + Dopamine Hit
- Clicking checkbox marks task done
- Done tasks: text gets strikethrough, row fades to 50% opacity, moves to bottom of list
- **Celebration toast** appears top-right: bold black border card, big emoji, short message. Auto-dismisses after 1.5s
- Rotate through messages: `["Done! Keep going 🔥", "Yes! That counts!", "One more win!", "You showed up.", "Progress! ⭐"]`
- "Must Do Today" has a hard cap of 3 tasks. If user tries to add a 4th, show inline error: "Maximum 3 must-do tasks. Prioritise."

### 4. Win Counter + Streak Tracker
Show in a stats bar below the task board (2 metric cards side by side):

- **Wins Today** — count of done tasks for today
- **Day Streak** — consecutive days where user completed at least their one non-negotiable OR marked at least 1 "must" task done

Streak logic:
- Check `daily_commits` and `tasks` for previous day
- If user was active yesterday (had any done task), streak increments
- If not, streak resets to 1
- Store streak in `user_stats` table or as a user meta field

### 5. Parking Lot → Tomorrow Promotion
- Button on each parking lot task: "Move to Tomorrow"
- Creates a copy of the task in the "should" section dated for tomorrow
- Original parking lot entry is soft-deleted or archived

### 6. Reset Day (manual)
- Small link at bottom: "Reset today's tasks"
- Confirmation dialog (Gumroad-style: black border modal, no backdrop blur)
- Clears done states, removes tasks, resets today's commit

---

## Database Schema

```sql
-- Users (managed by Socialite)
users: id, name, email, google_id, avatar, created_at

-- Daily commitments
daily_commits: id, user_id, text, date (date), locked_at (timestamp), unlocked_count (int default 0), created_at

-- Tasks
tasks: id, user_id, text, section (enum: must/should/good/park), done (bool default false), date (date), sort_order (int), done_at (timestamp nullable), created_at, deleted_at

-- User stats
user_stats: id, user_id, streak (int default 0), total_wins (int default 0), last_active_date (date), updated_at
```

---

## Routes

```
GET  /dashboard              → Today's board (auth required)
POST /commit                 → Save daily commitment
POST /commit/unlock          → Unlock commitment for editing
POST /tasks                  → Add task
PATCH /tasks/{id}/toggle     → Mark done/undone
PATCH /tasks/{id}/move       → Move task to another section (restrictions apply)
PATCH /tasks/{id}/promote    → Move parking lot → tomorrow's should
DELETE /tasks/{id}           → Delete task
POST /day/reset              → Reset today
GET  /auth/google            → Redirect to Google
GET  /auth/google/callback   → Handle callback
```

---

## Page Layout (dashboard)

```
┌─────────────────────────────────────────────────────┐
│  DAILY WIN BOARD           [streak 🔥 5d] [wins ✅ 3] │
├─────────────────────────────────────────────────────┤
│  [One non-negotiable commitment input — full width]  │
├───────────────────┬─────────────────────────────────┤
│  MUST DO TODAY    │  SHOULD DO TODAY                 │
│  (red-orange)     │  (amber)                         │
│  max 3 tasks      │  up to 5                         │
├───────────────────┼─────────────────────────────────┤
│  GOOD TO DO       │  PARKING LOT                     │
│  (teal)           │  (gray, move to tomorrow btn)    │
├───────────────────┴─────────────────────────────────┤
│  [Wins today: 3]  [Streak: 5 days]  [Reset day link]│
└─────────────────────────────────────────────────────┘
```

---

## Gumroad UI Reference Components

When building components, reference these patterns:

**Card:**
```html
<div style="background:#fff; border:2px solid #000; border-radius:6px; box-shadow:4px 4px 0 #000; padding:1.25rem;">
```

**Section header:**
```html
<div style="border-left: 4px solid var(--color-must); padding-left: 0.75rem; font-weight:800; font-size:13px; text-transform:uppercase; letter-spacing:0.05em;">
  MUST DO TODAY <span style="background:#000;color:#fff;font-size:11px;padding:2px 7px;border-radius:3px;margin-left:8px;">0/3</span>
</div>
```

**Checkbox (custom):**
```html
<!-- Unchecked -->
<div style="width:20px;height:20px;border:2px solid #000;border-radius:3px;cursor:pointer;"></div>
<!-- Checked -->
<div style="width:20px;height:20px;border:2px solid #000;background:#000;border-radius:3px;display:flex;align-items:center;justify-content:center;">
  <svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" fill="none"/></svg>
</div>
```

**Toast notification:**
```html
<div style="position:fixed;top:1rem;right:1rem;background:#fff;border:2px solid #000;box-shadow:4px 4px 0 #000;border-radius:6px;padding:1rem 1.25rem;font-weight:700;font-size:15px;z-index:9999;">
  🔥 Done! Keep going
</div>
```

**Modal (reset confirmation):**
```html
<div style="background:#fff;border:2px solid #000;box-shadow:6px 6px 0 #000;border-radius:8px;padding:2rem;max-width:380px;margin:auto;">
  <h3 style="font-weight:800;margin-bottom:0.5rem;">Reset today?</h3>
  <p style="color:#6B6B6B;font-size:14px;margin-bottom:1.5rem;">This clears all tasks and your commitment for today. Cannot be undone.</p>
  <div style="display:flex;gap:0.75rem;">
    <button class="btn">Cancel</button>
    <button class="btn btn-danger">Yes, reset</button>
  </div>
</div>
```

---

## Tailwind Config Notes

Extend Tailwind to add custom colors matching the palette above:

```js
// tailwind.config.js
theme: {
  extend: {
    colors: {
      'gum-accent': '#FF90E8',
      'gum-amber': '#FFC900',
      'gum-teal': '#23A094',
      'gum-must': '#FF4F00',
      'gum-park': '#B0B0A8',
    },
    fontFamily: {
      display: ['Syne', 'sans-serif'],
      body: ['DM Sans', 'sans-serif'],
      mono: ['JetBrains Mono', 'monospace'],
    },
    boxShadow: {
      'hard': '4px 4px 0 #000',
      'hard-sm': '2px 2px 0 #000',
      'hard-hover': '1px 1px 0 #000',
    }
  }
}
```

---

## Behaviour Notes for Claude Code

- All task mutations should be AJAX (fetch API or Axios) — no full page reloads
- Optimistic UI: mark done immediately in DOM, then sync to server
- If server returns error, revert the optimistic update and show inline error
- Date handling: always use user's local date (not UTC) for "today"
- Mobile: stack 2×2 grid to single column. Full-width inputs.
- The "Must Do Today" 3-task hard cap should be enforced both client-side (disable add button) AND server-side (validate in TaskController)
- On first load with no tasks, show empty state per section with a ghost/dashed placeholder card with prompt text

### Move Rules
- `must` tasks cannot be moved — delete or complete only
- Nothing can be moved TO `must` via the move action
- Allowed moves: `should ↔ good`, `should → park`, `good → park`, `park → should/good`
- Moving to `should` respects the 5-task cap

### Carry-Forward Rules
- Undone `must` and `should` tasks from previous days automatically appear at the top of their sections the next day
- `good` and `park` tasks do NOT carry forward (lower priority / intentionally deferred)
- Done tasks from previous days do NOT appear
- Urgency badges: 1 day overdue → `⚠️ LATE` (amber), 2+ days → `🚨 URGENT` (red)
- Must cap (max 3) counts ALL undone must tasks regardless of date, including carry-forward

---

## Phase 2 — In Scope

### Win History (`/history`)
- List all done tasks for the authenticated user, ordered by done_at desc
- Group by date
- Show done_at time, section badge, task text
- No pagination needed initially — load all

### Admin Dashboard (`/admin`)
- Gate: `is_admin = true` on users table (bool, default false)
- Admin is also a regular user — they retain full access to `/dashboard`
- Admin-only views: user list, total wins per user, streak leaderboard, daily active users, tasks created today
- Middleware: `AdminMiddleware` checks `auth()->user()->is_admin`

### Weekly Review (`/review`)
- Wins per day for the last 7 days (bar or number grid)
- Streak display
- Completion rate by section (done / total per section this week)

## Phase 3 — Out of Scope (Goal Cascade)

- Goal Cascade (10yr → 5yr → yearly → quarterly → daily)
- Daily tasks linkable to quarterly goals
- Recurring tasks
- Team/shared boards
- Mobile app / PWA

---

## Build Order Recommendation

1. Auth (Google OAuth) + basic layout shell
2. Dashboard page structure + Tailwind config + fonts
3. Daily commit feature (input → lock flow)
4. Task CRUD (add, toggle done, delete) for all 4 sections
5. Win counter + streak logic
6. Celebration toast
7. Parking lot → promote to tomorrow
8. Reset day
9. Polish: animations, empty states, mobile responsiveness
