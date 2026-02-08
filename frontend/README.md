# NytroLMS — Modern React Frontend

A complete UI rewrite of the KeyLMS Nytro application, built with **React 19**, **TypeScript**, **Tailwind CSS 4**, and **shadcn/ui** to match the NytroAI design language. This frontend replaces the legacy Laravel Blade/Bootstrap UI with a modern single-page application that communicates with the existing Laravel API backend (and will later migrate to Supabase Edge Functions).

---

## Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Design System](#design-system)
4. [Pages and Features](#pages-and-features)
5. [Authentication — Supabase Auth](#authentication--supabase-auth)
6. [Auth Migration Status](#auth-migration-status)
7. [Environment Variables](#environment-variables)
8. [Getting Started](#getting-started)
9. [Project Structure](#project-structure)
10. [Next Steps](#next-steps)

---

## Overview

This frontend was created as part of the NytroLMS modernisation initiative. The goals are:

- **Match the NytroAI look and feel** — Same design tokens, typography (Outfit + Inter), colour palette, sidebar navigation, and interaction patterns.
- **Decouple the UI from Laravel** — The React frontend communicates via REST API, enabling a future migration from Laravel to Supabase Edge Functions without touching the UI.
- **Retain the Nytro brand identity** — The Nytro logo, wizard mascot, and brand colours (blue, teal, gold) are preserved throughout.
- **Improve performance** — Client-side rendering eliminates the 2.9-second database connection overhead that plagued the server-rendered Blade templates.

---

## Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Framework** | React 19 | Component-based UI |
| **Language** | TypeScript 5.6 | Type safety |
| **Styling** | Tailwind CSS 4 | Utility-first CSS |
| **Components** | shadcn/ui (Radix primitives) | Accessible, composable UI components |
| **Routing** | React Router DOM v7 | Client-side routing |
| **Auth** | @supabase/supabase-js | Supabase Authentication |
| **Charts** | Recharts | Dashboard data visualisation |
| **Animations** | Framer Motion | Micro-interactions and transitions |
| **Icons** | Lucide React | Consistent icon set |
| **Build** | Vite 7 | Fast HMR and production builds |

---

## Design System

The design system mirrors NytroAI and is defined in `src/index.css`:

### Typography
- **Display / Headings:** Outfit (Google Fonts) — weights 400–700
- **Body / UI:** Inter (Google Fonts) — weights 400–600

### Colour Palette (OKLCH)

| Token | Light Mode | Usage |
|-------|-----------|-------|
| `--primary` | Deep Blue | Buttons, active states, links |
| `--secondary` | Light grey | Secondary surfaces |
| `--accent` | Teal | Highlights, badges, progress |
| `--destructive` | Red | Errors, delete actions |
| `--muted` | Soft grey | Disabled states, borders |

Brand accent colours used in components:
- **Nytro Blue:** `#1e40af` — Primary actions
- **Nytro Teal:** `#0d9488` — Success, progress, accents
- **Nytro Gold:** `#d97706` — Warnings, highlights, wizard

### Layout
- Persistent collapsible sidebar navigation (desktop)
- Bottom navigation bar (mobile)
- Card-based content areas with subtle shadows
- Consistent 0.65rem border radius

---

## Pages and Features

| Page | Route | Description |
|------|-------|-------------|
| **Landing** | `/` | Public-facing page with login dialog, feature highlights, and Nytro branding |
| **Dashboard** | `/dashboard` | KPI widgets (students, courses, completions, assessments), activity feed, quick actions |
| **Students** | `/students` | Student directory with search, filter by status, role badges |
| **Courses** | `/courses` | Course catalogue with grid/list view, progress indicators, category filters |
| **Assessments** | `/assessments` | Assessment management with status tracking (Draft, Published, Graded) |
| **Enrolments** | `/enrolments` | Enrolment records with student-course mapping, date tracking |
| **Companies** | `/companies` | Company/organisation management with student counts |
| **Reports** | `/reports` | Report templates (completion, assessment, enrolment, compliance) |
| **User Management** | `/user-management` | User CRUD with role assignment (Root, Admin, Mini Admin, Leader, Trainer, Student) |
| **Settings** | `/settings` | Tabbed settings: General, Notifications, Security, Integrations |
| **Reset Password** | `/reset-password` | Supabase password reset flow |

> **Note:** All pages currently use **mock data** for demonstration. The next phase involves wiring each page to the Laravel API or directly to Supabase tables.

---

## Authentication — Supabase Auth

The frontend uses **Supabase Auth** (`@supabase/supabase-js`) for authentication, replacing the legacy Laravel session-based auth.

### How It Works

1. **Login:** Users sign in with email + password via `supabase.auth.signInWithPassword()`
2. **Session:** Supabase manages JWT tokens automatically (stored in localStorage)
3. **Profile Lookup:** After auth, the app queries `public.users` to fetch the LMS profile (name, role, company, avatar)
4. **Role-Based Access:** User roles (Root, Admin, Mini Admin, Leader, Trainer, Student) are stored in both `auth.users.raw_user_meta_data` and `public.model_has_roles`
5. **Protected Routes:** The `<ProtectedRoute>` component redirects unauthenticated users to the landing page
6. **Sign Up:** New user registration via `supabase.auth.signUp()` with email confirmation
7. **Password Reset:** Forgot password flow via `supabase.auth.resetPasswordForEmail()`
8. **Logout:** `supabase.auth.signOut()` clears the session

### Auth Context (`src/contexts/AuthContext.tsx`)

The `AuthProvider` wraps the entire app and provides:
- `user` — Current Supabase auth user
- `lmsProfile` — LMS-specific profile data (name, role, company)
- `isAuthenticated` — Boolean auth state
- `isLoading` — Loading state during session check
- `login(email, password)` — Sign in
- `signup(email, password, firstName, lastName)` — Register
- `logout()` — Sign out
- `resetPassword(email)` — Send reset email

### Graceful Degradation

If Supabase environment variables are not configured, the app displays a configuration warning in the login dialog instead of crashing. This allows the UI to be previewed without a live Supabase connection.

---

## Auth Migration Status

### Completed ✅

All **211 users** from `public.users` have been migrated to `auth.users` via direct SQL insertion using the Supabase MCP tools.

| Metric | Value |
|--------|-------|
| **Total auth.users created** | 211 |
| **Total auth.identities created** | 211 |
| **Failures** | 0 |
| **Email confirmed** | All users (auto-confirmed as existing users) |

### Role Distribution

| Role | Count |
|------|-------|
| Student | 132 |
| Leader | 39 |
| Admin | 15 |
| Trainer | 14 |
| Mini Admin | 8 |
| Root | 3 |

### Temporary Password

> **All migrated users have been assigned the temporary password: `NytroLMS2026!`**

Users should reset their password on first login. You can trigger password reset emails via:

1. **Supabase Dashboard** — Authentication > Users > Send password reset
2. **Bulk script** — `node scripts/send-reset-emails.mjs --verbose` (requires `SUPABASE_SERVICE_ROLE_KEY`)

### Metadata Stored in auth.users

Each `auth.users` entry contains:

**`raw_user_meta_data`** (accessible via `supabase.auth.getUser()`):
```json
{
  "lms_user_id": 250,
  "first_name": "Kevin",
  "last_name": "Dyer",
  "full_name": "Kevin Dyer",
  "role": "Admin",
  "migrated_from": "laravel_lms"
}
```

**`raw_app_meta_data`** (server-side only, used for RLS policies):
```json
{
  "provider": "email",
  "providers": ["email"],
  "role": "admin",
  "lms_user_id": 250
}
```

---

## Environment Variables

The following environment variables must be set for the frontend to connect to Supabase:

| Variable | Description | Example |
|----------|-------------|---------|
| `VITE_SUPABASE_URL` | Supabase project URL | `https://rshmacirxysfwwyrszes.supabase.co` |
| `VITE_SUPABASE_ANON_KEY` | Supabase anonymous/public key | `eyJhbGciOiJIUzI1NiIs...` |

These are **client-side** variables (prefixed with `VITE_`) and are safe to expose in the browser. They only grant access according to your Row Level Security (RLS) policies.

For the migration scripts in `scripts/`, you also need:

| Variable | Description | Where to Find |
|----------|-------------|---------------|
| `SUPABASE_SERVICE_ROLE_KEY` | Admin key for auth operations | Supabase Dashboard > Settings > API |

> **Warning:** The service role key has full admin access. Never expose it in client-side code or commit it to the repository.

---

## Getting Started

### Prerequisites
- Node.js 22+
- pnpm 10+

### Installation

```bash
cd frontend
pnpm install
```

### Development

```bash
# Create .env file with Supabase credentials
cat > .env << EOF
VITE_SUPABASE_URL=https://rshmacirxysfwwyrszes.supabase.co
VITE_SUPABASE_ANON_KEY=your_anon_key_here
EOF

# Start dev server
pnpm dev
```

The app will be available at `http://localhost:3000`.

### Build for Production

```bash
pnpm build
```

Output is in `dist/` — a static bundle that can be deployed to any CDN or static hosting.

---

## Project Structure

```
frontend/
├── public/                    # Static assets
├── src/
│   ├── components/
│   │   ├── ui/                # shadcn/ui primitives (Button, Card, Dialog, etc.)
│   │   ├── DashboardLayout.tsx  # Main app layout with sidebar
│   │   ├── Sidebar.tsx          # Navigation sidebar (NytroAI-style)
│   │   ├── LoginDialog.tsx      # Auth dialog with login/signup/reset
│   │   ├── KPIWidget.tsx        # Dashboard metric cards
│   │   ├── ProtectedRoute.tsx   # Auth guard for routes
│   │   ├── ScrollToTop.tsx      # Scroll-to-top button
│   │   └── ErrorBoundary.tsx    # Error boundary wrapper
│   ├── contexts/
│   │   ├── AuthContext.tsx       # Supabase auth state management
│   │   └── ThemeContext.tsx      # Dark/light theme management
│   ├── hooks/                   # Custom React hooks
│   ├── lib/
│   │   ├── supabase.ts          # Supabase client initialisation
│   │   └── utils.ts             # Utility functions (cn, etc.)
│   ├── pages/
│   │   ├── Landing.tsx           # Public landing page
│   │   ├── Dashboard.tsx         # Main dashboard
│   │   ├── Students.tsx          # Student management
│   │   ├── Courses.tsx           # Course catalogue
│   │   ├── Assessments.tsx       # Assessment management
│   │   ├── Enrolments.tsx        # Enrolment records
│   │   ├── Companies.tsx         # Company management
│   │   ├── Reports.tsx           # Reporting
│   │   ├── UserManagement.tsx    # User administration
│   │   ├── Settings.tsx          # App settings
│   │   └── ResetPassword.tsx     # Password reset flow
│   ├── App.tsx                   # Root component with routes
│   ├── main.tsx                  # React entry point
│   └── index.css                 # Global styles and design tokens
├── index.html                    # HTML template
├── package.json
├── tsconfig.json
├── vite.config.ts
└── components.json               # shadcn/ui configuration
```

---

## Next Steps

### Phase 1 — Immediate (Configuration)

- [ ] **Add Supabase secrets** to the deployment environment (`VITE_SUPABASE_URL`, `VITE_SUPABASE_ANON_KEY`)
- [ ] **Send password reset emails** to all migrated users so they can set their own passwords (replace temp password `NytroLMS2026!`)
- [ ] **Test login flow** end-to-end with a real Supabase connection

### Phase 2 — Data Integration (Connect to Real Data)

- [ ] **Wire Dashboard** to Supabase queries (student counts, course completions, assessment stats)
- [ ] **Wire Students page** to `public.users` table with role filtering
- [ ] **Wire Courses page** to `public.courses` table
- [ ] **Wire Assessments page** to `public.assessments` / `public.quizzes` tables
- [ ] **Wire Enrolments page** to `public.course_student` table
- [ ] **Wire Companies page** to `public.companies` table
- [ ] **Wire Reports page** to aggregate queries
- [ ] **Wire User Management** to `public.users` + `public.model_has_roles` with CRUD operations
- [ ] **Wire Settings page** to `public.settings` / user preferences

### Phase 3 — Backend Migration (Laravel → Edge Functions)

- [ ] **Create Supabase Edge Functions** to replace Laravel API controllers
- [ ] **Implement Row Level Security (RLS)** policies based on user roles
- [ ] **Migrate file uploads** to Supabase Storage
- [ ] **Migrate email notifications** to Supabase Edge Functions + Resend/SendGrid
- [ ] **Decommission Laravel** once all endpoints are migrated

### Phase 4 — Enhancement

- [ ] **Nytro AI Wizard** — Port the NytroAI assistant/wizard into the sidebar for AI-powered features (pre-marking, validation)
- [ ] **Real-time updates** — Use Supabase Realtime for live dashboard updates
- [ ] **Dark mode** — Theme switching is scaffolded in `ThemeContext.tsx`
- [ ] **Mobile optimisation** — Responsive layouts are in place; test and refine on devices
- [ ] **Audit logging** — Implement early as per project requirements
- [ ] **Azure deployment** — Script automated CI/CD via GitHub Actions

---

## Related Documentation

- [`KeyLMSDocs/UI_REWRITE_PLAN.md`](../KeyLMSDocs/UI_REWRITE_PLAN.md) — Comprehensive UI rewrite plan
- [`scripts/README.md`](../scripts/README.md) — Migration and utility scripts documentation
- [`../README.md`](../README.md) — Original KeyLMSNytro technical documentation
