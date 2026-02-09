/**
 * Shared TypeScript types for Edge Functions.
 * Mirrors the database schema used by the LMS application.
 */

export type UserRole =
  | 'Root'
  | 'Admin'
  | 'Moderator'
  | 'Mini Admin'
  | 'Leader'
  | 'Trainer'
  | 'Student';

export interface DbUser {
  id: number;
  first_name: string;
  last_name: string;
  username: string | null;
  email: string;
  study_type: string | null;
  email_verified_at: string | null;
  is_active: number;
  is_archived: number;
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
  is_archived: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface DbStudentCourseEnrolment {
  id: number;
  user_id: number;
  course_id: number;
  is_main_course: number;
  status: string;
  course_start_at: string | null;
  course_ends_at: string | null;
  course_completed_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface DbQuizAttempt {
  id: number;
  user_id: number;
  course_id: number;
  lesson_id: number;
  topic_id: number;
  quiz_id: number;
  attempt: number;
  status: string;
  submitted_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface DbCompany {
  id: number;
  name: string;
  email: string;
  address: string | null;
  number: string;
  poc_user_id: number | null;
  bm_user_id: number | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
}

export interface PaginationParams {
  limit?: number;
  offset?: number;
  search?: string;
}
