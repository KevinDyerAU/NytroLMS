/**
 * NytroLMS Database Types
 * Mirrors the Supabase (PostgreSQL) schema for the LMS application.
 */

// ─── Users ───────────────────────────────────────────────────────────────────

export type UserRole = 'Root' | 'Admin' | 'Moderator' | 'Leader' | 'Trainer' | 'Student' | 'Mini Admin';

export interface DbUser {
  id: number;
  first_name: string;
  last_name: string;
  username: string | null;
  email: string;
  study_type: string | null;
  email_verified_at: string | null;
  is_active: number; // 0 or 1
  is_archived: number; // 0 or 1
  userable_type: string | null;
  userable_id: number | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface DbUserDetail {
  id: number;
  user_id: number;
  signup_links_id: number | null;
  signup_through_link: number | null;
  avatar: string | null;
  phone: string | null;
  address: string | null;
  language: string | null;
  preferred_language: string | null;
  preferred_name: string | null;
  position: string | null;
  role: string | null;
  registered_by: number | null;
  purchase_order: string | null;
  country_id: number | null;
  timezone: string | null;
  last_logged_in: string | null;
  first_login: string | null;
  first_enrollment: string | null;
  onboard_at: string | null;
  status: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface UserWithRole extends DbUser {
  role_name: UserRole;
  detail?: DbUserDetail;
}

// ─── Companies ───────────────────────────────────────────────────────────────

export interface DbCompany {
  id: number;
  name: string;
  email: string;
  address: string | null;
  number: string;
  poc_user_id: number | null;
  bm_user_id: number | null;
  created_by: string;
  modified_by: string;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

// ─── Courses ─────────────────────────────────────────────────────────────────

export interface DbCourse {
  id: number;
  is_main_course: number | null;
  slug: string;
  title: string;
  course_type: string;
  category: string | null;
  course_length_days: number;
  course_expiry_days: number | null;
  next_course_after_days: number;
  next_course: number | null;
  auto_register_next_course: number;
  visibility: string;
  status: string;
  version: number;
  version_log: string | null;
  is_archived: number;
  restricted_roles: string | null;
  revisions: number | null;
  published_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Lessons ─────────────────────────────────────────────────────────────────

export interface DbLesson {
  id: number;
  order: number | null;
  slug: string;
  title: string;
  release_key: string;
  release_value: string | null;
  has_work_placement: number | null;
  has_topic: number;
  course_id: number;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Topics ──────────────────────────────────────────────────────────────────

export interface DbTopic {
  id: number;
  order: number | null;
  slug: string;
  title: string;
  estimated_time: number | null;
  has_quiz: number;
  lesson_id: number;
  course_id: number | null;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Quizzes ─────────────────────────────────────────────────────────────────

export interface DbQuiz {
  id: number;
  order: number | null;
  is_lln: number;
  slug: string;
  title: string;
  passing_percentage: number;
  estimated_time: number | null;
  allowed_attempts: number | null;
  topic_id: number;
  lesson_id: number | null;
  course_id: number | null;
  has_checklist: number | null;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Student Course Enrolments ───────────────────────────────────────────────

export interface DbStudentCourseEnrolment {
  id: number;
  user_id: number;
  course_id: number;
  is_main_course: number;
  is_locked: number;
  is_semester_2: number;
  student_course_stats_id: number | null;
  admin_reports_id: number | null;
  course_progress_id: number | null;
  last_updated: string | null;
  allowed_to_next_course: number;
  course_start_at: string | null;
  course_ends_at: string | null;
  status: string;
  has_lln_completed: number | null;
  course_completed_at: string | null;
  course_expiry: string | null;
  version_log: string | null;
  version: number;
  deferred: number | null;
  deferred_details: string | null;
  cert_issued: number;
  cert_issued_on: string | null;
  cert_issued_by: number | null;
  cert_details: string | null;
  is_chargeable: number;
  registration_date: string | null;
  registered_by: number | null;
  registered_on_create: number;
  show_on_widget: number;
  show_registration_date: number;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Admin Reports ───────────────────────────────────────────────────────────

export interface DbAdminReport {
  id: number;
  student_id: number;
  course_id: number;
  trainer_id: number | null;
  leader_id: number | null;
  company_id: number | null;
  student_details: string;
  student_status: string;
  student_last_active: string | null;
  student_course_start_date: string | null;
  student_course_end_date: string | null;
  allowed_to_next_course: string;
  is_main_course: number;
  course_status: string | null;
  course_completed_at: string | null;
  course_expiry: string | null;
  course_details: string | null;
  student_course_progress: string | null;
  trainer_details: string;
  leader_details: string;
  leader_last_active: string | null;
  company_details: string | null;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Course Progress ─────────────────────────────────────────────────────────

export interface DbCourseProgress {
  id: number;
  user_id: number;
  course_id: number;
  percentage: string;
  details: string;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

// ─── Evaluations (Assessments) ───────────────────────────────────────────────

export interface DbEvaluation {
  id: number;
  results: string;
  evaluable_type: string;
  evaluable_id: number;
  status: string | null;
  email_sent_on: string | null;
  evaluator_id: number;
  student_id: number;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Quiz Attempts ───────────────────────────────────────────────────────────

export interface DbQuizAttempt {
  id: number;
  user_id: number;
  course_id: number;
  lesson_id: number;
  topic_id: number;
  quiz_id: number;
  questions: string;
  submitted_answers: string;
  attempt: number;
  system_result: string | null;
  status: string;
  assisted: number;
  accessor_id: number | null;
  accessed_at: string | null;
  is_valid_accessor: number | null;
  user_ip: string | null;
  submitted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

// ─── Activity Log ────────────────────────────────────────────────────────────

export interface DbActivityLog {
  id: number;
  log_name: string | null;
  description: string;
  subject_type: string | null;
  event: string | null;
  subject_id: number | null;
  causer_type: string | null;
  causer_id: number | null;
  properties: string | null;
  batch_uuid: string | null;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Settings ────────────────────────────────────────────────────────────────

export interface DbSetting {
  id: number;
  key: string;
  value: string;
  user_id: number | null;
}

// ─── Enrolments (Onboarding) ─────────────────────────────────────────────────

export interface DbEnrolment {
  id: number;
  user_id: number;
  enrolment_key: string;
  enrolment_value: string;
  is_active: number;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Roles ───────────────────────────────────────────────────────────────────

export interface DbRole {
  id: number;
  name: string;
  guard_name: string;
  created_at: string | null;
  updated_at: string | null;
}

// ─── Signup Links ────────────────────────────────────────────────────────────

export interface DbSignupLink {
  id: number;
  company_id: number;
  leader_id: number;
  course_id: number;
  creator_id: number;
  key: string;
  is_active: number;
  is_chargeable: number;
  created_at: string | null;
  updated_at: string | null;
}
