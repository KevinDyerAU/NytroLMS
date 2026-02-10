# NytroLMS — Laravel to Supabase Edge Functions Migration Plan

**Author:** Manus AI  
**Date:** 9 February 2026  
**Status:** Draft  
**Repository:** KevinDyerAU/KeyLMSNytro

---

## 1. Executive Summary

This document provides a comprehensive audit of all business logic currently residing in the NytroLMS Laravel application and maps each component to its Supabase Edge Function equivalent. The Laravel codebase contains approximately **16,000 lines of controller code**, **10,600 lines of service code**, and **3,100 lines of model code** across 50+ controllers, 12 services, 30+ models, 19 notifications, 7 jobs, 8 listeners, and 7 policies.

The migration strategy follows a **phased, feature-by-feature approach** that allows the React frontend to progressively switch from Laravel API calls to Edge Function calls without disrupting existing functionality. Each Edge Function will be a self-contained TypeScript module deployed to the same Supabase region as the database, eliminating the 2.9-second connection latency that currently plagues the Laravel architecture.

---

## 2. Current Architecture Inventory

### 2.1 Controller Inventory (16,173 lines total)

The following table lists every controller in the Laravel application, its line count, and the business domain it serves. Controllers are grouped by functional area.

| Controller | Lines | Domain | Priority |
|---|---|---|---|
| **AccountManager/StudentController** | 4,163 | Student CRUD, course assignment, progress, certificates, evidence upload, competency marking | P0 — Critical |
| **EnrolmentController** | 1,483 | Enrolment lifecycle, status transitions, bulk operations | P0 — Critical |
| **Frontend/LMS/QuizController** | 1,188 | Student quiz attempts, answer submission, result viewing | P0 — Critical |
| **AssessmentsController** | 400 | Trainer assessment marking, evaluation, feedback, return/email | P0 — Critical |
| **AccountManager/CompanyController** | 346 | Company CRUD, signup link management | P1 — High |
| **AccountManager/LeaderController** | 442 | Leader CRUD, student assignment, company association | P1 — High |
| **AccountManager/NoteController** | 434 | Notes CRUD (polymorphic on users) | P1 — High |
| **AccountManager/TrainerController** | 324 | Trainer CRUD, student/course assignment | P1 — High |
| **LMS/CourseController** | 365 | Admin course CRUD, lesson/topic/quiz management | P1 — High |
| **LMS/LessonController** | 303 | Admin lesson CRUD, ordering, content management | P1 — High |
| **LMS/TopicController** | 339 | Admin topic CRUD, ordering, quiz association | P1 — High |
| **LMS/QuizController** | 342 | Admin quiz CRUD, question management | P1 — High |
| **LMS/QuestionController** | — | Question CRUD within quizzes | P1 — High |
| **Frontend/LMS/CourseController** | 226 | Student course view, progress display, mark complete | P0 — Critical |
| **Frontend/LMS/LessonController** | 263 | Student lesson view, activity tracking, mark complete | P0 — Critical |
| **Frontend/LMS/TopicController** | 411 | Student topic view, activity tracking, mark complete | P0 — Critical |
| **UserManagement/UserController** | 288 | Admin user CRUD (non-student roles) | P1 — High |
| **UserManagement/RoleController** | 261 | Role CRUD, permission assignment, cloning | P2 — Medium |
| **UserManagement/PermissionController** | — | Permission CRUD | P2 — Medium |
| **Reports/AdminReportController** | — | Admin reports, daily registration reports | P1 — High |
| **Reports/CompetencyReportController** | — | Competency completion reports | P1 — High |
| **Reports/EnrolmentReportController** | — | Enrolment reports with DataTables | P2 — Medium |
| **Reports/CommencedUnitsReportController** | — | Commenced units reports | P2 — Medium |
| **Reports/DailyEnrolmentReportController** | — | Daily enrolment reports | P2 — Medium |
| **Reports/WorkPlacementsReport** | — | Work placement reports | P2 — Medium |
| **CompetencyController** | — | Competency records, lesson end dates | P1 — High |
| **BulkActionsController** | — | Bulk note creation | P2 — Medium |
| **ProgressComparisonController** | — | Progress comparison between students | P2 — Medium |
| **AdminToolController** | 523 | Sync student profiles, test service consistency | P3 — Low |
| **AccountManager/SignupController** | 260 | Public student self-registration via signup links | P1 — High |
| **AccountManager/DocumentController** | 204 | Student document uploads and management | P1 — High |
| **AccountManager/WorkPlacementController** | 186 | Work placement records CRUD | P2 — Medium |
| **AccountManager/LessonUnlockController** | — | Manual lesson unlock for students | P2 — Medium |
| **User/ProfileController** | — | User profile view/edit, password change | P1 — High |
| **User/AvatarController** | — | Avatar upload/management | P3 — Low |
| **Settings/SettingsController** | 208 | Application settings management | P2 — Medium |
| **Settings/CountryController** | — | Country reference data | P3 — Low |
| **Auth/LoginController** | 195 | Authentication, login, logout | P0 — Critical (Done via Supabase Auth) |
| **Auth/RegisterController** | — | Registration | P0 — Critical (Done via Supabase Auth) |
| **Auth/ForgotPasswordController** | — | Password reset request | P0 — Critical (Done via Supabase Auth) |
| **Auth/ResetPasswordController** | — | Password reset execution | P0 — Critical (Done via Supabase Auth) |
| **PlaygroundController** | 997 | Development/testing playground | P3 — Not migrated |
| **Select2Controller** | — | Dynamic dropdown data source | P2 — Medium |

### 2.2 Service Layer Inventory (10,625 lines total)

Services contain the most complex business logic and are the highest priority for accurate migration.

| Service | Lines | Responsibility |
|---|---|---|
| **CourseProgressService** | 4,700 | Course progress calculation, lesson/topic/quiz status tracking, LLND status, checklist verification, percentage computation, admin report updates. This is the **most complex service** in the system. |
| **StudentTrainingPlanService** | 2,630 | Training plan generation, progress details aggregation, lesson start/end dates, quiz attempt processing, checklist completion verification |
| **UserDataExportService** | 818 | GDPR-style user data export, SQL generation for all user-related tables |
| **StudentCourseService** | 582 | Course assignment, enrolment creation, competency management, admin report creation, next-course enrolment logic |
| **DailyRegistrationReportService** | 517 | Daily registration CSV report generation, SharePoint upload |
| **AdminReportService** | 497 | Admin report creation/update, progress synchronisation, course stats |
| **PtrCompletionService** | 414 | Pre-Training Review (PTR) completion checking, quiz status verification |
| **StudentActivityService** | 196 | Student activity logging (login, course/lesson/topic/quiz start/end events) |
| **CronJobManagerService** | 107 | Scheduled job management and reporting |
| **LlnCompletionService** | 104 | Language, Literacy, Numeracy, and Digital (LLND) completion checking |
| **StudentSyncService** | 38 | Student profile synchronisation |
| **InitialPasswordGenerationService** | 22 | Initial password generation for new accounts |

### 2.3 Model Inventory (3,101 lines total)

| Model | Lines | Key Relationships | Scopes |
|---|---|---|---|
| **User** | 462 | hasOne(UserDetail), morphTo(userable), belongsToMany(Company), hasMany(Enrolment), roles (Spatie) | onlyStudents, onlyActive, active, inactive, notRoot, notRole, isRelatedCompany |
| **Lesson** | 365 | belongsTo(Course), hasMany(Topic), hasMany(Quiz), morphMany(Image) | ordered, published, accessible |
| **Quiz** | 273 | belongsTo(Topic, Lesson, Course), hasMany(Question), hasMany(QuizAttempt) | published, accessible |
| **QuizAttempt** | 167 | belongsTo(Quiz, User, Lesson, Topic, Course) | latestPassed, latestAttempt, onlyPending, relatedTrainer, relatedLeader |
| **Topic** | 166 | belongsTo(Lesson, Course), hasMany(Quiz), morphMany(Image) | ordered |
| **Course** | 160 | hasMany(Lesson), hasMany(Enrolment), morphMany(Image) | published, accessible, notRestricted |
| **StudentCourseEnrolment** | 139 | belongsTo(User, Course), hasOne(CourseProgress), hasOne(EnrolmentStats) | active, completed |
| **AdminReport** | 133 | belongsTo(User, Course, StudentCourseEnrolment, CourseProgress) | excludeLlnAndPtrCourses (global) |
| **CourseProgress** | 123 | belongsTo(Course, User) | — |
| **Question** | 92 | belongsTo(Quiz), morphMany(Image) | — |
| **WorkPlacement** | 89 | belongsTo(User, Course, Company, Leader, Creator) | — |
| **Company** | 86 | belongsToMany(User), hasMany(SignupLink) | — |
| **StudentActivity** | 76 | belongsTo(User, Course, Lesson, Topic, Quiz) | — |
| **LessonUnlock** | 76 | belongsTo(User, Lesson, Course) | — |

### 2.4 Middleware Inventory

These middleware enforce business rules that must be replicated as Edge Function guards or RLS policies.

| Middleware | Purpose | Edge Function Equivalent |
|---|---|---|
| **AccountManagerMiddleware** | Restricts access to account manager routes (Admin, Leader, Root, Mini Admin, Moderator roles) | RLS policy + Edge Function role check |
| **AdminMiddleware** | Restricts to Admin/Root roles only | RLS policy |
| **OwnerMiddleware** | Restricts to Root role only | RLS policy |
| **PrivilegedMiddleware** | Restricts to privileged roles (Admin, Root, Moderator) | RLS policy |
| **TeachableMiddleware** | Requires `mark assessments` permission | RLS policy + Edge Function check |
| **LlnAccessMiddleware** | Redirects students to LLND quiz if not completed | Edge Function guard on course access |
| **PtrAccessMiddleware** | Redirects students to PTR quiz if not completed for enrolled courses | Edge Function guard on course access |
| **OnBoardMiddleware** | Redirects new students to onboarding if profile incomplete | Frontend route guard |
| **ResetPasswordFirst** | Forces password change on first login | Frontend route guard (already handled) |
| **StudentVerifyEmail** | Requires email verification for students | Supabase Auth email verification |
| **LocaleMiddleware** | Sets application locale from user preferences | Frontend i18n |

### 2.5 Authorization Policies

| Policy | Rules | Edge Function Equivalent |
|---|---|---|
| **UserPolicy** | viewAny requires `manage users`; view/update/delete checks role hierarchy (cannot modify equal or superior roles) | RLS policy with role hierarchy function |
| **RolePolicy** | viewAny requires `manage roles`; view/update/delete checks role hierarchy; Root role is immutable | RLS policy |
| **CompanyPolicy** | Leaders can only view/edit their own companies | RLS policy with company-user join |
| **EnrolmentPolicy** | Leaders can only view enrolments for their company's students | RLS policy with company-user join |
| **LeaderPolicy** | Standard CRUD authorization | RLS policy |
| **PermissionPolicy** | Standard CRUD authorization | RLS policy |
| **ProfilePolicy** | Users can only edit their own profile | RLS policy |

### 2.6 Notification Inventory (19 notifications)

| Notification | Channels | Trigger | Edge Function Equivalent |
|---|---|---|---|
| **NewAccountNotification** | Mail | User created by admin | Edge Function + Supabase email (or SendGrid/Resend) |
| **NewLeaderNotification** | Mail | Leader account created | Edge Function + email |
| **ResendPasswordEmailNotification** | Mail | Admin resends password | Edge Function + email |
| **ResetPasswordNotification** | Mail | Password reset requested | Supabase Auth built-in |
| **StudentAssignedCourse** | Mail | Student assigned to course | Edge Function + email |
| **StudentRegistrationReceiptNotification** | Mail | Student self-registers | Edge Function + email |
| **AssessmentMarked** | Mail, Database | Trainer marks assessment satisfactory | Edge Function + email + DB insert |
| **AssessmentReturned** | Mail | Trainer returns assessment | Edge Function + email |
| **AssessmentEmailed** | Mail | Assessment emailed to student | Edge Function + email |
| **NewLLNDMarked** | Mail | LLND assessment marked | Edge Function + email |
| **PreCourseAssessmentMarked** | Mail | Pre-course assessment marked | Edge Function + email |
| **AnacondaAccountNotification** | Mail | Anaconda integration account | Edge Function + email |
| **AnacondaCourseNotification** | Mail | Anaconda course notification | Edge Function + email |
| **UsernameRequest** | Mail | Username recovery | Edge Function + email |
| **CronJobReportNotification** | Mail, Slack | Cron job status report | Supabase Cron + Edge Function |
| **DailyRegistrationReportSuccess** | Mail | Daily report generated | Supabase Cron + Edge Function |
| **DailyRegistrationReportFailure** | Mail | Daily report failed | Supabase Cron + Edge Function |
| **SlackAlertNotification** | Slack | System alerts | Edge Function + Slack webhook |
| **TestEmailNotification** | Mail | Test email | Edge Function + email |

### 2.7 Background Jobs

| Job | Purpose | Edge Function Equivalent |
|---|---|---|
| **AdminReportProcess** | Generate/update admin reports | Supabase pg_cron + database function |
| **CleanStudentData** | Clean student data (GDPR) | Edge Function (manual trigger) |
| **CourseProgressProcess** | Recalculate course progress | Database function (triggered by quiz_attempts insert/update) |
| **QuizAttemptData** | Process quiz attempt data | Database trigger |
| **StudentActivityData** | Process student activity logs | Database trigger |
| **UpdateAdminReport** | Update admin report after assessment | Database trigger on quiz_attempts |
| **UpdateCourseProgress** | Update course progress after activity | Database trigger |

### 2.8 Event/Listener System

| Event | Listeners | Edge Function Equivalent |
|---|---|---|
| **Authenticated** | LogSuccessfulLogin (updates last_logged_in) | Database trigger on auth.users sign-in |
| **QuizAttemptStatusChanged** | UpdateLlnStatusListener (updates LLND status) | Database trigger on quiz_attempts status change |
| **Registered** | AccountCreated, StudentRegisteredToLeader | Edge Function post-registration hook |

---

## 3. Edge Function Architecture

### 3.1 Design Principles

The Edge Functions architecture follows these principles, aligned with the project's architectural preferences:

1. **No multi-tenancy** — Single-tenant design, all data in one Supabase project.
2. **Auditing from day one** — All mutations logged via `activity_log` table and database triggers.
3. **Role-based access via RLS** — Supabase Row Level Security policies enforce authorization at the database level, replacing Laravel middleware and policies.
4. **n8n for API orchestration** — External API calls (email, Slack, SharePoint) are centralised through n8n workflows, not embedded in Edge Functions.
5. **GitHub-automated deployments** — Edge Functions deployed via GitHub Actions CI/CD pipeline.

### 3.2 Proposed Edge Function Structure

```
supabase/
├── functions/
│   ├── _shared/                    # Shared utilities
│   │   ├── auth.ts                 # Auth helpers (verify JWT, get user role)
│   │   ├── cors.ts                 # CORS headers
│   │   ├── db.ts                   # Supabase client factory
│   │   ├── errors.ts               # Standardised error responses
│   │   ├── types.ts                # Shared TypeScript types
│   │   └── audit.ts                # Audit logging helper
│   │
│   ├── students/                   # Student management
│   │   └── index.ts                # CRUD, course assignment, progress
│   │
│   ├── courses/                    # Course management
│   │   └── index.ts                # CRUD, lesson/topic/quiz management
│   │
│   ├── enrolments/                 # Enrolment lifecycle
│   │   └── index.ts                # Create, update status, bulk operations
│   │
│   ├── assessments/                # Assessment marking
│   │   └── index.ts                # Mark, return, email, feedback
│   │
│   ├── quiz-attempts/              # Quiz attempt processing
│   │   └── index.ts                # Submit, evaluate, progress update
│   │
│   ├── companies/                  # Company management
│   │   └── index.ts                # CRUD, signup links
│   │
│   ├── leaders/                    # Leader management
│   │   └── index.ts                # CRUD, student assignment
│   │
│   ├── trainers/                   # Trainer management
│   │   └── index.ts                # CRUD, student/course assignment
│   │
│   ├── users/                      # User management (admin)
│   │   └── index.ts                # CRUD, role assignment, password reset
│   │
│   ├── roles/                      # Role & permission management
│   │   └── index.ts                # CRUD, permission assignment
│   │
│   ├── progress/                   # Course progress
│   │   └── index.ts                # Calculate, re-evaluate, training plan
│   │
│   ├── reports/                    # Reporting
│   │   └── index.ts                # Admin reports, competency, enrolment
│   │
│   ├── documents/                  # Document management
│   │   └── index.ts                # Upload, list, delete (Supabase Storage)
│   │
│   ├── notes/                      # Notes management
│   │   └── index.ts                # CRUD (polymorphic)
│   │
│   ├── signup/                     # Public student registration
│   │   └── index.ts                # Self-registration via signup links
│   │
│   ├── profile/                    # User profile
│   │   └── index.ts                # View, edit, password change
│   │
│   ├── work-placements/            # Work placement records
│   │   └── index.ts                # CRUD
│   │
│   ├── competencies/               # Competency records
│   │   └── index.ts                # List, detail, lesson end dates
│   │
│   ├── notifications/              # Notification dispatch
│   │   └── index.ts                # Trigger n8n workflows for email/Slack
│   │
│   └── admin-tools/                # Admin utilities
│       └── index.ts                # Sync profiles, data export
│
├── migrations/                     # Database migrations
│   ├── rls_policies.sql            # Row Level Security policies
│   ├── db_functions.sql            # Database functions (progress calc, etc.)
│   └── db_triggers.sql             # Database triggers (audit, progress)
│
└── seed/                           # Seed data
    └── permissions.sql             # Spatie permissions → Supabase equivalent
```

### 3.3 RLS Policy Strategy

Row Level Security replaces Laravel middleware and policies. The following database function provides role-based access:

```sql
-- Helper function: get current user's role
CREATE OR REPLACE FUNCTION public.get_user_role()
RETURNS TEXT AS $$
  SELECT COALESCE(
    (auth.jwt() -> 'app_metadata' ->> 'role'),
    'anonymous'
  );
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Helper function: get current user's LMS user ID
CREATE OR REPLACE FUNCTION public.get_lms_user_id()
RETURNS BIGINT AS $$
  SELECT COALESCE(
    (auth.jwt() -> 'user_metadata' ->> 'lms_user_id')::BIGINT,
    0
  );
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- Helper function: check role hierarchy
CREATE OR REPLACE FUNCTION public.is_superior_role(actor_role TEXT, target_role TEXT)
RETURNS BOOLEAN AS $$
  SELECT CASE
    WHEN actor_role = 'root' THEN TRUE
    WHEN actor_role = 'admin' THEN target_role NOT IN ('root', 'admin')
    WHEN actor_role = 'moderator' THEN target_role NOT IN ('root', 'admin', 'moderator')
    WHEN actor_role = 'mini admin' THEN target_role IN ('leader', 'trainer', 'student')
    WHEN actor_role = 'leader' THEN target_role = 'student'
    WHEN actor_role = 'trainer' THEN target_role = 'student'
    ELSE FALSE
  END;
$$ LANGUAGE sql IMMUTABLE;
```

**Example RLS policies:**

```sql
-- Students: Leaders can only see students in their companies
CREATE POLICY "leaders_view_own_students" ON public.users
  FOR SELECT USING (
    get_user_role() = 'leader'
    AND EXISTS (
      SELECT 1 FROM public.signup_links sl
      WHERE sl.company_id IN (
        SELECT company_id FROM public.signup_links
        WHERE company_id IN (
          SELECT id FROM public.companies c
          JOIN public.company_user cu ON cu.company_id = c.id
          WHERE cu.user_id = get_lms_user_id()
        )
      )
      AND sl.key = (SELECT signup_links_id FROM public.user_details WHERE user_id = users.id)
    )
  );

-- Quiz attempts: Trainers can only see attempts for their assigned students
CREATE POLICY "trainers_view_assigned_attempts" ON public.quiz_attempts
  FOR SELECT USING (
    get_user_role() = 'trainer'
    AND EXISTS (
      SELECT 1 FROM public.user_has_attachables uha
      WHERE uha.attachable_type = 'App\\Models\\Trainer'
      AND uha.user_id = quiz_attempts.user_id
      AND uha.attachable_id IN (
        SELECT id FROM public.trainers WHERE user_id = get_lms_user_id()
      )
    )
  );
```

### 3.4 Database Functions (Replacing Services)

The most complex services should be implemented as PostgreSQL functions for performance, since they run in the same process as the database:

```sql
-- Replace CourseProgressService.updateProgress()
CREATE OR REPLACE FUNCTION public.calculate_course_progress(
  p_user_id BIGINT,
  p_course_id BIGINT
) RETURNS JSONB AS $$
DECLARE
  v_progress JSONB;
  v_total_items INT;
  v_completed_items INT;
  v_percentage FLOAT;
BEGIN
  -- Count total lessons, topics, quizzes
  SELECT COUNT(*) INTO v_total_items
  FROM public.lessons l
  JOIN public.topics t ON t.lesson_id = l.id
  JOIN public.quizzes q ON q.topic_id = t.id
  WHERE l.course_id = p_course_id AND l.status = 'PUBLISHED';

  -- Count completed items (satisfactory quiz attempts)
  SELECT COUNT(DISTINCT qa.quiz_id) INTO v_completed_items
  FROM public.quiz_attempts qa
  JOIN public.quizzes q ON q.id = qa.quiz_id
  JOIN public.topics t ON t.id = q.topic_id
  JOIN public.lessons l ON l.id = t.lesson_id
  WHERE qa.user_id = p_user_id
    AND l.course_id = p_course_id
    AND qa.status = 'SATISFACTORY';

  v_percentage := CASE WHEN v_total_items > 0
    THEN (v_completed_items::FLOAT / v_total_items::FLOAT) * 100
    ELSE 0 END;

  -- Update or insert progress record
  INSERT INTO public.course_progress (user_id, course_id, percentage, updated_at)
  VALUES (p_user_id, p_course_id, v_percentage, NOW())
  ON CONFLICT (user_id, course_id)
  DO UPDATE SET percentage = v_percentage, updated_at = NOW();

  RETURN jsonb_build_object(
    'user_id', p_user_id,
    'course_id', p_course_id,
    'total_items', v_total_items,
    'completed_items', v_completed_items,
    'percentage', v_percentage
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
```

### 3.5 Database Triggers (Replacing Events/Listeners/Jobs)

```sql
-- Trigger: Log successful login (replaces LogSuccessfulLogin listener)
CREATE OR REPLACE FUNCTION public.on_auth_sign_in()
RETURNS TRIGGER AS $$
BEGIN
  UPDATE public.user_details
  SET last_logged_in = NOW()
  WHERE user_id = (
    SELECT (NEW.raw_user_meta_data ->> 'lms_user_id')::BIGINT
  );
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Trigger: Update progress after quiz attempt (replaces QuizAttemptStatusChanged event)
CREATE OR REPLACE FUNCTION public.on_quiz_attempt_change()
RETURNS TRIGGER AS $$
BEGIN
  IF NEW.status IN ('SATISFACTORY', 'NOT_SATISFACTORY', 'RETURNED') THEN
    PERFORM public.calculate_course_progress(NEW.user_id, NEW.course_id);

    -- Update admin report
    INSERT INTO public.admin_reports (student_id, course_id, updated_at)
    VALUES (NEW.user_id, NEW.course_id, NOW())
    ON CONFLICT (student_id, course_id) DO UPDATE SET updated_at = NOW();
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE TRIGGER quiz_attempt_status_changed
  AFTER INSERT OR UPDATE OF status ON public.quiz_attempts
  FOR EACH ROW EXECUTE FUNCTION public.on_quiz_attempt_change();

-- Trigger: Audit log (replaces Spatie Activity Log)
CREATE OR REPLACE FUNCTION public.audit_log()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.activity_log (
    log_name, description, subject_type, subject_id,
    causer_type, causer_id, properties, created_at
  ) VALUES (
    TG_TABLE_NAME,
    TG_OP,
    TG_TABLE_NAME,
    COALESCE(NEW.id, OLD.id),
    'user',
    COALESCE(
      (current_setting('request.jwt.claims', true)::jsonb -> 'user_metadata' ->> 'lms_user_id')::BIGINT,
      0
    ),
    jsonb_build_object('old', to_jsonb(OLD), 'new', to_jsonb(NEW)),
    NOW()
  );
  RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
```

### 3.6 Notification Strategy via n8n

Following the preference for centralising API calls in n8n, all notifications (email, Slack) will be dispatched through n8n workflows:

```
Edge Function → HTTP POST to n8n webhook → n8n workflow → SendGrid/Slack/etc.
```

**n8n Workflow Structure:**

| Workflow | Trigger | Actions |
|---|---|---|
| `lms-email-notification` | Webhook (POST) | Route by `notification_type`, render email template, send via SendGrid |
| `lms-slack-alert` | Webhook (POST) | Format message, post to Slack channel |
| `lms-daily-report` | Cron (daily) | Query Supabase, generate CSV, upload to SharePoint, email report |
| `lms-assessment-notification` | Webhook (POST) | Send assessment marked/returned/emailed notifications |

---

## 4. Migration Phases

### Phase 0: Foundation (Week 1-2)

**Objective:** Set up the Edge Functions infrastructure and shared utilities.

| Task | Description | Effort |
|---|---|---|
| Set up Supabase CLI and Edge Functions project | Configure `supabase/functions/` directory structure | 2h |
| Create `_shared/` utilities | Auth helpers, CORS, error handling, audit logging, types | 4h |
| Implement RLS helper functions | `get_user_role()`, `get_lms_user_id()`, `is_superior_role()` | 2h |
| Set up audit trigger | Generic audit trigger for all tables | 2h |
| Configure GitHub Actions | CI/CD pipeline for Edge Function deployment | 4h |
| Set up n8n notification workflows | Email and Slack webhook workflows | 4h |
| **Total** | | **18h** |

### Phase 1: Authentication & User Profile (Week 2-3)

**Objective:** Complete the auth flow and user profile management. Auth is already partially done via Supabase Auth migration.

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Profile view/edit | ProfileController | `profile/index.ts` | 4h |
| Password change | ProfileController.password/passwordReset | `profile/index.ts` | 2h |
| First-login password force | ResetPasswordFirst middleware | Frontend route guard (done) | 0h |
| Email verification | StudentVerifyEmail middleware | Supabase Auth config | 1h |
| Avatar upload | AvatarController | `profile/index.ts` + Supabase Storage | 3h |
| RLS policies for users/user_details | UserPolicy | `migrations/rls_policies.sql` | 3h |
| **Total** | | | **13h** |

### Phase 2: Student Management (Week 3-5)

**Objective:** Migrate the largest controller (4,163 lines) — the core of the LMS.

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Student list with filtering | StudentController.index | `students/index.ts` (GET) | 4h |
| Student create | StudentController.store | `students/index.ts` (POST) | 6h |
| Student view/show | StudentController.show | `students/index.ts` (GET /:id) | 4h |
| Student edit/update | StudentController.update | `students/index.ts` (PUT) | 4h |
| Student activate/deactivate | StudentController.activate/deactivate | `students/index.ts` (PATCH) | 3h |
| Course assignment | StudentController.assign_course | `students/index.ts` (POST /:id/courses) | 8h |
| Next course enrolment | StudentController.enrolNextCourse | `students/index.ts` (POST /:id/next-course) | 6h |
| Evidence/checklist upload | StudentController.uploadEvidenceChecklist | `documents/index.ts` + Supabase Storage | 4h |
| Quiz checklist upload | StudentController.uploadQuizChecklist | `documents/index.ts` + Supabase Storage | 4h |
| Mark lesson/topic complete | StudentController.markLessonComplete/markTopicComplete | `progress/index.ts` | 4h |
| Competency marking | StudentController.competentLessonComplete | `competencies/index.ts` | 3h |
| Work placement marking | StudentController.markWorkPlacementComplete | `work-placements/index.ts` | 3h |
| Get training plan | StudentController.getTrainingPlan | `progress/index.ts` (GET /training-plan) | 6h |
| Get assessments | StudentController.getAssessments | `assessments/index.ts` (GET) | 3h |
| Get activities | StudentController.getStudentActivities | `students/index.ts` (GET /:id/activities) | 3h |
| Issue certificate | StudentController.issueCertificate | `students/index.ts` (POST /certificate) | 4h |
| Re-evaluate progress | StudentController.reEvaluateProgress | `progress/index.ts` (POST /re-evaluate) | 4h |
| Reset progress | StudentController.resetProgress | `progress/index.ts` (POST /reset) | 3h |
| Resend password | StudentController.resendPassword | `students/index.ts` + n8n | 2h |
| Delete courses | StudentController.delete_courses | `students/index.ts` (DELETE /:id/courses) | 2h |
| Edit/update enrolment | StudentController.editEnrolment/updateEnrolment | `enrolments/index.ts` | 3h |
| Skip LLND | StudentController.skipLLND | `students/index.ts` (POST /:id/skip-llnd) | 2h |
| Clean student data | StudentController.cleanStudent | `admin-tools/index.ts` | 3h |
| RLS policies for students | CompanyPolicy, LeaderPolicy | `migrations/rls_policies.sql` | 4h |
| **Total** | | | **83h** |

### Phase 3: Course & Content Management (Week 5-7)

**Objective:** Migrate admin course/lesson/topic/quiz CRUD and student-facing course views.

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Course CRUD | LMS/CourseController | `courses/index.ts` | 6h |
| Lesson CRUD + ordering | LMS/LessonController | `courses/index.ts` (nested) | 6h |
| Topic CRUD + ordering | LMS/TopicController | `courses/index.ts` (nested) | 6h |
| Quiz CRUD | LMS/QuizController | `courses/index.ts` (nested) | 6h |
| Question CRUD | LMS/QuestionController | `courses/index.ts` (nested) | 4h |
| Student course view | Frontend/LMS/CourseController | `progress/index.ts` | 4h |
| Student lesson view | Frontend/LMS/LessonController | `progress/index.ts` | 4h |
| Student topic view | Frontend/LMS/TopicController | `progress/index.ts` | 4h |
| Student quiz attempt | Frontend/LMS/QuizController | `quiz-attempts/index.ts` | 8h |
| Quiz result view | Frontend/LMS/QuizController.viewResult | `quiz-attempts/index.ts` | 3h |
| Lesson unlock | LessonUnlockController | `courses/index.ts` | 2h |
| RLS policies for courses | — | `migrations/rls_policies.sql` | 3h |
| **Total** | | | **56h** |

### Phase 4: Assessments & Enrolments (Week 7-8)

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Assessment list/view | AssessmentsController.index/show | `assessments/index.ts` | 4h |
| Assessment marking | AssessmentsController.markPost | `assessments/index.ts` (POST /mark) | 6h |
| Assessment return | AssessmentsController.returnPost | `assessments/index.ts` (POST /return) | 2h |
| Assessment email | AssessmentsController.emailPost | `notifications/index.ts` + n8n | 2h |
| Enrolment list | EnrolmentController.index | `enrolments/index.ts` | 3h |
| Enrolment create | EnrolmentController.store | `enrolments/index.ts` (POST) | 4h |
| Enrolment update | EnrolmentController.update | `enrolments/index.ts` (PUT) | 3h |
| Enrolment status change | EnrolmentController.changeStatus | `enrolments/index.ts` (PATCH) | 3h |
| Bulk enrolment | EnrolmentController.bulkEnrol | `enrolments/index.ts` (POST /bulk) | 4h |
| RLS policies | EnrolmentPolicy | `migrations/rls_policies.sql` | 3h |
| **Total** | | | **34h** |

### Phase 5: Companies, Leaders, Trainers (Week 8-9)

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Company CRUD | CompanyController | `companies/index.ts` | 4h |
| Signup link management | CompanyController + SignupController | `signup/index.ts` | 6h |
| Leader CRUD | LeaderController | `leaders/index.ts` | 6h |
| Trainer CRUD | TrainerController | `trainers/index.ts` | 6h |
| Notes CRUD | NoteController | `notes/index.ts` | 4h |
| Document management | DocumentController | `documents/index.ts` + Storage | 4h |
| Work placement CRUD | WorkPlacementController | `work-placements/index.ts` | 4h |
| **Total** | | | **34h** |

### Phase 6: User Management & Roles (Week 9-10)

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| User CRUD (admin) | UserController | `users/index.ts` | 6h |
| Role CRUD | RoleController | `roles/index.ts` | 4h |
| Role cloning | RoleController.clone | `roles/index.ts` (POST /clone) | 2h |
| Permission CRUD | PermissionController | `roles/index.ts` | 3h |
| Settings management | SettingsController | `admin-tools/index.ts` | 3h |
| **Total** | | | **18h** |

### Phase 7: Reports & Background Jobs (Week 10-12)

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Admin reports | AdminReportController | `reports/index.ts` | 6h |
| Competency reports | CompetencyReportController | `reports/index.ts` | 4h |
| Enrolment reports | EnrolmentReportController | `reports/index.ts` | 4h |
| Commenced units reports | CommencedUnitsReportController | `reports/index.ts` | 3h |
| Daily enrolment reports | DailyEnrolmentReportController | `reports/index.ts` | 3h |
| Work placement reports | WorkPlacementsReport | `reports/index.ts` | 3h |
| Daily registration report | DailyRegistrationReportService | Supabase pg_cron + n8n | 6h |
| Progress comparison | ProgressComparisonController | `progress/index.ts` | 3h |
| Admin tools (sync, export) | AdminToolController | `admin-tools/index.ts` | 4h |
| Select2 data sources | Select2Controller | `students/index.ts` (GET /search) | 2h |
| **Total** | | | **38h** |

### Phase 8: Course Progress Service (Week 12-14)

**This is the most complex migration task.** The CourseProgressService (4,700 lines) and StudentTrainingPlanService (2,630 lines) contain deeply nested logic for calculating progress percentages, LLND status, checklist completion, and training plan generation.

| Task | Laravel Source | Edge Function | Effort |
|---|---|---|---|
| Core progress calculation | CourseProgressService.updateProgress | PostgreSQL function `calculate_course_progress()` | 12h |
| Lesson progress | CourseProgressService.updateLessonProgress | PostgreSQL function | 6h |
| Topic progress | CourseProgressService.updateTopicProgress | PostgreSQL function | 6h |
| Quiz status tracking | CourseProgressService.getQuizStatus | PostgreSQL function | 4h |
| LLND completion check | LlnCompletionService + CourseProgressService | PostgreSQL function | 6h |
| PTR completion check | PtrCompletionService | PostgreSQL function | 4h |
| Checklist verification | CourseProgressService.verifyChecklistCompletion | PostgreSQL function | 6h |
| Training plan generation | StudentTrainingPlanService.getTrainingPlan | `progress/index.ts` + PostgreSQL function | 10h |
| Student course stats | CourseProgressService.updateStudentCourseStats | PostgreSQL function | 4h |
| Progress session update | CourseProgressService.updateProgressSession | PostgreSQL function | 3h |
| Database triggers | — | `migrations/db_triggers.sql` | 4h |
| **Total** | | | **65h** |

---

## 5. Migration Summary

### 5.1 Effort Estimate

| Phase | Description | Estimated Hours |
|---|---|---|
| Phase 0 | Foundation | 18h |
| Phase 1 | Auth & Profile | 13h |
| Phase 2 | Student Management | 83h |
| Phase 3 | Course & Content | 56h |
| Phase 4 | Assessments & Enrolments | 34h |
| Phase 5 | Companies, Leaders, Trainers | 34h |
| Phase 6 | User Management & Roles | 18h |
| Phase 7 | Reports & Background Jobs | 38h |
| Phase 8 | Course Progress Service | 65h |
| **Total** | | **359h** |

At a sustainable pace of 30-35 productive hours per week, this represents approximately **10-12 weeks** of focused development work.

### 5.2 Risk Assessment

| Risk | Impact | Mitigation |
|---|---|---|
| CourseProgressService complexity (4,700 lines) | High — incorrect progress calculation breaks the core LMS experience | Implement comprehensive test suite; run parallel with Laravel for validation period |
| LLND/PTR middleware logic | Medium — students may bypass required assessments | Implement as both RLS policies and frontend guards; add database constraints |
| Notification delivery reliability | Medium — missed emails affect student experience | Use n8n with retry logic and dead-letter queues; monitor delivery rates |
| Data consistency during migration | High — dual-write period may cause inconsistencies | Use feature flags to switch between Laravel and Edge Functions per endpoint |
| Edge Function cold starts | Low — may add 200-500ms to first request | Use Supabase Pro plan for warm instances; implement connection pooling |

### 5.3 Testing Strategy

Each Edge Function must have:

1. **Unit tests** — Test business logic in isolation using Deno test runner.
2. **Integration tests** — Test against a Supabase test project with seeded data.
3. **Parallel validation** — During migration, run both Laravel and Edge Function endpoints and compare responses.
4. **RLS policy tests** — Verify that each role can only access permitted data.

### 5.4 Rollback Strategy

The migration uses a **feature flag approach**:

```typescript
// Frontend API client
const API_BASE = import.meta.env.VITE_USE_EDGE_FUNCTIONS === 'true'
  ? `${SUPABASE_URL}/functions/v1`
  : LARAVEL_API_URL;
```

Each feature can be independently toggled between Laravel and Edge Functions, allowing instant rollback if issues are discovered.

---

## 6. Gaps Identified in Current Frontend

The current React frontend (NytroLMS) has pages for Dashboard, Students, Courses, Assessments, Enrolments, Companies, Reports, User Management, and Settings. The following functionality from the Laravel application is **not yet represented** in the frontend and must be added:

| Missing Feature | Laravel Source | Priority |
|---|---|---|
| Student detail view (full profile, enrolments, progress, activities, documents, notes, training plan) | StudentController.show (1,650+ lines of view logic) | P0 |
| Course content authoring (lesson/topic/quiz/question CRUD) | LMS/* controllers | P1 |
| Student-facing LMS (course view, lesson view, topic view, quiz taking) | Frontend/LMS/* controllers | P0 |
| Assessment marking interface (evaluation form, feedback, checklist) | AssessmentsController.show/markPost | P0 |
| Signup link management and public registration | SignupController, CompanyController | P1 |
| Leader management | LeaderController | P1 |
| Trainer management | TrainerController | P1 |
| Note management (per student) | NoteController | P1 |
| Document management (per student) | DocumentController | P1 |
| Work placement management | WorkPlacementController | P2 |
| Competency records | CompetencyController | P1 |
| Progress comparison | ProgressComparisonController | P2 |
| Admin tools (sync, data export) | AdminToolController | P3 |
| Bulk actions (notes) | BulkActionsController | P2 |
| Certificate issuance | StudentController.issueCertificate | P1 |
| Training plan view | StudentTrainingPlanService | P0 |

---

## 7. Recommended Next Steps

1. **Immediate:** Merge the current frontend PR and set up the Supabase Edge Functions project structure (Phase 0).
2. **Week 1-2:** Implement shared utilities, RLS policies, and audit triggers.
3. **Week 2-3:** Migrate auth/profile endpoints (Phase 1) — lowest risk, builds confidence.
4. **Week 3-5:** Tackle student management (Phase 2) — highest value, enables frontend data integration.
5. **Parallel:** Build missing frontend pages as each Edge Function becomes available.
6. **Week 12-14:** Migrate the CourseProgressService last — most complex, requires thorough testing.

---

*This document should be reviewed and updated as the migration progresses. Each completed phase should be marked with its actual completion date and any deviations from the plan.*
