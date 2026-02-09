/**
 * NytroLMS Supabase Data Access Layer
 * Direct Supabase queries replacing Laravel API calls for optimal performance.
 */

import { supabase, isSupabaseConfigured } from './supabase';
import type {
  DbUser, DbCompany, DbCourse, DbLesson, DbTopic, DbQuiz,
  DbStudentCourseEnrolment, DbAdminReport, DbCourseProgress,
  DbEvaluation, DbQuizAttempt, DbActivityLog, DbSetting,
  DbEnrolment, DbUserDetail, DbSignupLink, UserRole,
} from './types';

// ─── Error Handling ──────────────────────────────────────────────────────────

export class ApiError extends Error {
  constructor(message: string, public code?: string) {
    super(message);
    this.name = 'ApiError';
  }
}

function assertConfigured() {
  if (!isSupabaseConfigured) {
    throw new ApiError('Supabase is not configured. Please add VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY.', 'NOT_CONFIGURED');
  }
}

function handleError(error: unknown, context: string): never {
  const msg = error instanceof Error ? error.message : String(error);
  console.error(`[API] ${context}:`, msg);
  throw new ApiError(`${context}: ${msg}`);
}

// ─── Dashboard ───────────────────────────────────────────────────────────────

export interface DashboardStats {
  totalStudents: number;
  activeStudents: number;
  totalCourses: number;
  publishedCourses: number;
  totalCompanies: number;
  totalEnrolments: number;
  activeEnrolments: number;
  completedEnrolments: number;
  pendingAssessments: number;
}

export async function fetchDashboardStats(): Promise<DashboardStats> {
  assertConfigured();
  try {
    const [
      { count: totalStudents },
      { count: activeStudents },
      { count: totalCourses },
      { count: publishedCourses },
      { count: totalCompanies },
      { count: totalEnrolments },
      { count: activeEnrolments },
      { count: completedEnrolments },
      { count: pendingAssessments },
    ] = await Promise.all([
      supabase.from('users').select('*', { count: 'exact', head: true })
        .eq('is_archived', 0)
        .not('id', 'in', '(1,2,43)'), // Exclude system/root users from student count
      supabase.from('users').select('*', { count: 'exact', head: true })
        .eq('is_active', 1).eq('is_archived', 0),
      supabase.from('courses').select('*', { count: 'exact', head: true }),
      supabase.from('courses').select('*', { count: 'exact', head: true })
        .eq('status', 'PUBLISHED'),
      supabase.from('companies').select('*', { count: 'exact', head: true })
        .is('deleted_at', null),
      supabase.from('student_course_enrolments').select('*', { count: 'exact', head: true }),
      supabase.from('student_course_enrolments').select('*', { count: 'exact', head: true })
        .eq('status', 'ACTIVE'),
      supabase.from('student_course_enrolments').select('*', { count: 'exact', head: true })
        .eq('status', 'COMPLETED'),
      supabase.from('quiz_attempts').select('*', { count: 'exact', head: true })
        .eq('status', 'SUBMITTED'),
    ]);

    return {
      totalStudents: totalStudents ?? 0,
      activeStudents: activeStudents ?? 0,
      totalCourses: totalCourses ?? 0,
      publishedCourses: publishedCourses ?? 0,
      totalCompanies: totalCompanies ?? 0,
      totalEnrolments: totalEnrolments ?? 0,
      activeEnrolments: activeEnrolments ?? 0,
      completedEnrolments: completedEnrolments ?? 0,
      pendingAssessments: pendingAssessments ?? 0,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch dashboard stats');
  }
}

export async function fetchRecentActivity(limit = 20): Promise<DbActivityLog[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('activity_log')
      .select('*')
      .order('created_at', { ascending: false })
      .limit(limit);
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch recent activity');
  }
}

// ─── Students / Users ────────────────────────────────────────────────────────

export interface UserWithDetails extends DbUser {
  role_name: UserRole;
  user_details: DbUserDetail | null;
}

export async function fetchStudents(params?: {
  search?: string;
  role?: string;
  status?: 'active' | 'inactive' | 'archived';
  limit?: number;
  offset?: number;
}): Promise<{ data: UserWithDetails[]; total: number }> {
  assertConfigured();
  try {
    // First get users
    let query = supabase
      .from('users')
      .select('*, user_details(*)', { count: 'exact' })
      .eq('is_archived', params?.status === 'archived' ? 1 : 0);

    if (params?.status === 'active') query = query.eq('is_active', 1);
    if (params?.status === 'inactive') query = query.eq('is_active', 0);

    if (params?.search) {
      query = query.or(
        `first_name.ilike.%${params.search}%,last_name.ilike.%${params.search}%,email.ilike.%${params.search}%`
      );
    }

    query = query
      .order('created_at', { ascending: false })
      .range(params?.offset ?? 0, (params?.offset ?? 0) + (params?.limit ?? 25) - 1);

    const { data: users, error, count } = await query;
    if (error) throw error;

    // Get roles for these users
    const userIds = (users ?? []).map(u => u.id);
    const { data: roleData } = await supabase
      .from('model_has_roles')
      .select('model_id, role_id')
      .in('model_id', userIds);

    const { data: roles } = await supabase
      .from('roles')
      .select('id, name');

    const roleMap = new Map<number, string>();
    (roles ?? []).forEach(r => roleMap.set(r.id, r.name));

    const userRoleMap = new Map<number, string>();
    (roleData ?? []).forEach(mr => {
      userRoleMap.set(mr.model_id, roleMap.get(mr.role_id) ?? 'Student');
    });

    const enriched: UserWithDetails[] = (users ?? []).map(u => ({
      ...u,
      role_name: (userRoleMap.get(u.id) ?? 'Student') as UserRole,
      user_details: Array.isArray(u.user_details) ? u.user_details[0] ?? null : u.user_details,
    }));

    // Filter by role if specified
    let filtered = enriched;
    if (params?.role && params.role !== 'all') {
      filtered = enriched.filter(u => u.role_name === params.role);
    }

    return { data: filtered, total: count ?? 0 };
  } catch (e) {
    handleError(e, 'Failed to fetch students');
  }
}

export async function fetchUserById(id: number): Promise<UserWithDetails | null> {
  assertConfigured();
  try {
    const { data: user, error } = await supabase
      .from('users')
      .select('*, user_details(*)')
      .eq('id', id)
      .single();
    if (error) throw error;
    if (!user) return null;

    const { data: roleData } = await supabase
      .from('model_has_roles')
      .select('role_id')
      .eq('model_id', id)
      .limit(1);

    let roleName: UserRole = 'Student';
    if (roleData && roleData.length > 0) {
      const { data: role } = await supabase
        .from('roles')
        .select('name')
        .eq('id', roleData[0].role_id)
        .single();
      if (role) roleName = role.name as UserRole;
    }

    return {
      ...user,
      role_name: roleName,
      user_details: Array.isArray(user.user_details) ? user.user_details[0] ?? null : user.user_details,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch user');
  }
}

// ─── Courses ─────────────────────────────────────────────────────────────────

export interface CourseWithDetails extends DbCourse {
  lessons_count: number;
  enrolments_count: number;
}

export async function fetchCourses(params?: {
  search?: string;
  status?: string;
  limit?: number;
  offset?: number;
}): Promise<{ data: CourseWithDetails[]; total: number }> {
  assertConfigured();
  try {
    let query = supabase
      .from('courses')
      .select('*', { count: 'exact' });

    if (params?.search) {
      query = query.ilike('title', `%${params.search}%`);
    }
    if (params?.status && params.status !== 'all') {
      query = query.eq('status', params.status);
    }

    query = query
      .order('created_at', { ascending: false })
      .range(params?.offset ?? 0, (params?.offset ?? 0) + (params?.limit ?? 25) - 1);

    const { data: courses, error, count } = await query;
    if (error) throw error;

    const courseIds = (courses ?? []).map(c => c.id);

    // Get lesson counts
    const { data: lessonCounts } = await supabase
      .from('lessons')
      .select('course_id')
      .in('course_id', courseIds);

    const lessonCountMap = new Map<number, number>();
    (lessonCounts ?? []).forEach(l => {
      lessonCountMap.set(l.course_id, (lessonCountMap.get(l.course_id) ?? 0) + 1);
    });

    // Get enrolment counts
    const { data: enrolmentCounts } = await supabase
      .from('student_course_enrolments')
      .select('course_id')
      .in('course_id', courseIds);

    const enrolmentCountMap = new Map<number, number>();
    (enrolmentCounts ?? []).forEach(e => {
      enrolmentCountMap.set(e.course_id, (enrolmentCountMap.get(e.course_id) ?? 0) + 1);
    });

    const enriched: CourseWithDetails[] = (courses ?? []).map(c => ({
      ...c,
      lessons_count: lessonCountMap.get(c.id) ?? 0,
      enrolments_count: enrolmentCountMap.get(c.id) ?? 0,
    }));

    return { data: enriched, total: count ?? 0 };
  } catch (e) {
    handleError(e, 'Failed to fetch courses');
  }
}

export async function fetchCourseById(id: number): Promise<DbCourse | null> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('courses')
      .select('*')
      .eq('id', id)
      .single();
    if (error) throw error;
    return data;
  } catch (e) {
    handleError(e, 'Failed to fetch course');
  }
}

export async function fetchLessonsByCourse(courseId: number): Promise<DbLesson[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('lessons')
      .select('*')
      .eq('course_id', courseId)
      .order('order', { ascending: true });
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch lessons');
  }
}

// ─── Assessments (Evaluations + Quiz Attempts) ──────────────────────────────

export interface AssessmentSummary {
  id: number;
  type: 'evaluation' | 'quiz_attempt';
  student_id: number;
  student_name?: string;
  course_title?: string;
  status: string;
  created_at: string | null;
  updated_at: string | null;
}

export async function fetchAssessments(params?: {
  search?: string;
  status?: string;
  limit?: number;
  offset?: number;
}): Promise<{ data: AssessmentSummary[]; total: number }> {
  assertConfigured();
  try {
    // Fetch quiz attempts as the primary assessment type
    let query = supabase
      .from('quiz_attempts')
      .select('*', { count: 'exact' });

    if (params?.status && params.status !== 'all') {
      query = query.eq('status', params.status);
    }

    query = query
      .order('created_at', { ascending: false })
      .range(params?.offset ?? 0, (params?.offset ?? 0) + (params?.limit ?? 25) - 1);

    const { data: attempts, error, count } = await query;
    if (error) throw error;

    // Get student names
    const studentIds = Array.from(new Set((attempts ?? []).map(a => a.user_id)));
    const { data: students } = await supabase
      .from('users')
      .select('id, first_name, last_name')
      .in('id', studentIds);

    const studentMap = new Map<number, string>();
    (students ?? []).forEach(s => studentMap.set(s.id, `${s.first_name} ${s.last_name}`));

    // Get course titles
    const courseIds = Array.from(new Set((attempts ?? []).map(a => a.course_id)));
    const { data: courses } = await supabase
      .from('courses')
      .select('id, title')
      .in('id', courseIds);

    const courseMap = new Map<number, string>();
    (courses ?? []).forEach(c => courseMap.set(c.id, c.title));

    const summaries: AssessmentSummary[] = (attempts ?? []).map(a => ({
      id: a.id,
      type: 'quiz_attempt' as const,
      student_id: a.user_id,
      student_name: studentMap.get(a.user_id) ?? 'Unknown',
      course_title: courseMap.get(a.course_id) ?? 'Unknown',
      status: a.status,
      created_at: a.created_at,
      updated_at: a.updated_at,
    }));

    return { data: summaries, total: count ?? 0 };
  } catch (e) {
    handleError(e, 'Failed to fetch assessments');
  }
}

// ─── Enrolments ──────────────────────────────────────────────────────────────

export interface EnrolmentWithDetails extends DbStudentCourseEnrolment {
  student_name: string;
  student_email: string;
  course_title: string;
}

export async function fetchEnrolments(params?: {
  search?: string;
  status?: string;
  limit?: number;
  offset?: number;
}): Promise<{ data: EnrolmentWithDetails[]; total: number }> {
  assertConfigured();
  try {
    let query = supabase
      .from('student_course_enrolments')
      .select('*', { count: 'exact' });

    if (params?.status && params.status !== 'all') {
      query = query.eq('status', params.status);
    }

    query = query
      .order('created_at', { ascending: false })
      .range(params?.offset ?? 0, (params?.offset ?? 0) + (params?.limit ?? 25) - 1);

    const { data: enrolments, error, count } = await query;
    if (error) throw error;

    // Get student names
    const studentIds = Array.from(new Set((enrolments ?? []).map(e => e.user_id)));
    const { data: students } = await supabase
      .from('users')
      .select('id, first_name, last_name, email')
      .in('id', studentIds);

    const studentMap = new Map<number, { name: string; email: string }>();
    (students ?? []).forEach(s =>
      studentMap.set(s.id, { name: `${s.first_name} ${s.last_name}`, email: s.email })
    );

    // Get course titles
    const courseIds = Array.from(new Set((enrolments ?? []).map(e => e.course_id)));
    const { data: courses } = await supabase
      .from('courses')
      .select('id, title')
      .in('id', courseIds);

    const courseMap = new Map<number, string>();
    (courses ?? []).forEach(c => courseMap.set(c.id, c.title));

    const enriched: EnrolmentWithDetails[] = (enrolments ?? []).map(e => ({
      ...e,
      student_name: studentMap.get(e.user_id)?.name ?? 'Unknown',
      student_email: studentMap.get(e.user_id)?.email ?? '',
      course_title: courseMap.get(e.course_id) ?? 'Unknown',
    }));

    // Client-side search filter
    let filtered = enriched;
    if (params?.search) {
      const s = params.search.toLowerCase();
      filtered = enriched.filter(e =>
        e.student_name.toLowerCase().includes(s) ||
        e.student_email.toLowerCase().includes(s) ||
        e.course_title.toLowerCase().includes(s)
      );
    }

    return { data: filtered, total: count ?? 0 };
  } catch (e) {
    handleError(e, 'Failed to fetch enrolments');
  }
}

// ─── Companies ───────────────────────────────────────────────────────────────

export interface CompanyWithCounts extends DbCompany {
  student_count: number;
  leader_name: string | null;
}

export async function fetchCompanies(params?: {
  search?: string;
  limit?: number;
  offset?: number;
}): Promise<{ data: CompanyWithCounts[]; total: number }> {
  assertConfigured();
  try {
    let query = supabase
      .from('companies')
      .select('*', { count: 'exact' })
      .is('deleted_at', null);

    if (params?.search) {
      query = query.or(`name.ilike.%${params.search}%,email.ilike.%${params.search}%`);
    }

    query = query
      .order('name', { ascending: true })
      .range(params?.offset ?? 0, (params?.offset ?? 0) + (params?.limit ?? 25) - 1);

    const { data: companies, error, count } = await query;
    if (error) throw error;

    const companyIds = (companies ?? []).map(c => c.id);

    // Get signup links to count students per company
    const { data: signupLinks } = await supabase
      .from('signup_links')
      .select('id, company_id, leader_id')
      .in('company_id', companyIds);

    // Get user_details linked via signup_links
    const slIds = (signupLinks ?? []).map(sl => sl.id);
    const { data: userDetails } = await supabase
      .from('user_details')
      .select('signup_links_id')
      .in('signup_links_id', slIds);

    // Map company -> student count
    const slCompanyMap = new Map<number, number>();
    (signupLinks ?? []).forEach(sl => slCompanyMap.set(sl.id, sl.company_id));

    const companyStudentCount = new Map<number, number>();
    (userDetails ?? []).forEach(ud => {
      const compId = slCompanyMap.get(ud.signup_links_id);
      if (compId) companyStudentCount.set(compId, (companyStudentCount.get(compId) ?? 0) + 1);
    });

    // Get POC user names
    const pocIds = (companies ?? []).map(c => c.poc_user_id).filter(Boolean) as number[];
    const { data: pocUsers } = await supabase
      .from('users')
      .select('id, first_name, last_name')
      .in('id', pocIds);

    const pocMap = new Map<number, string>();
    (pocUsers ?? []).forEach(u => pocMap.set(u.id, `${u.first_name} ${u.last_name}`));

    const enriched: CompanyWithCounts[] = (companies ?? []).map(c => ({
      ...c,
      student_count: companyStudentCount.get(c.id) ?? 0,
      leader_name: c.poc_user_id ? pocMap.get(c.poc_user_id) ?? null : null,
    }));

    return { data: enriched, total: count ?? 0 };
  } catch (e) {
    handleError(e, 'Failed to fetch companies');
  }
}

// ─── Reports ─────────────────────────────────────────────────────────────────

export async function fetchAdminReports(params?: {
  search?: string;
  status?: string;
  limit?: number;
  offset?: number;
}): Promise<{ data: DbAdminReport[]; total: number }> {
  assertConfigured();
  try {
    let query = supabase
      .from('admin_reports')
      .select('*', { count: 'exact' });

    if (params?.status && params.status !== 'all') {
      query = query.eq('student_status', params.status);
    }

    query = query
      .order('updated_at', { ascending: false })
      .range(params?.offset ?? 0, (params?.offset ?? 0) + (params?.limit ?? 25) - 1);

    const { data, error, count } = await query;
    if (error) throw error;

    return { data: data ?? [], total: count ?? 0 };
  } catch (e) {
    handleError(e, 'Failed to fetch admin reports');
  }
}

// ─── User Management ─────────────────────────────────────────────────────────

export async function fetchRoles(): Promise<{ id: number; name: string }[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('roles')
      .select('id, name')
      .order('id', { ascending: true });
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch roles');
  }
}

export async function fetchUserRoleDistribution(): Promise<{ role: string; count: number }[]> {
  assertConfigured();
  try {
    const { data: roleAssignments } = await supabase
      .from('model_has_roles')
      .select('role_id');

    const { data: roles } = await supabase
      .from('roles')
      .select('id, name');

    const roleMap = new Map<number, string>();
    (roles ?? []).forEach(r => roleMap.set(r.id, r.name));

    const counts = new Map<string, number>();
    (roleAssignments ?? []).forEach(ra => {
      const name = roleMap.get(ra.role_id) ?? 'Unknown';
      counts.set(name, (counts.get(name) ?? 0) + 1);
    });

    return Array.from(counts.entries()).map(([role, count]) => ({ role, count }));
  } catch (e) {
    handleError(e, 'Failed to fetch role distribution');
  }
}

// ─── Settings ────────────────────────────────────────────────────────────────

export async function fetchSettings(): Promise<Record<string, string>> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('settings')
      .select('key, value');
    if (error) throw error;

    const settings: Record<string, string> = {};
    (data ?? []).forEach(s => { settings[s.key] = s.value; });
    return settings;
  } catch (e) {
    handleError(e, 'Failed to fetch settings');
  }
}

export async function updateSetting(key: string, value: string): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase
      .from('settings')
      .upsert({ key, value }, { onConflict: 'key' });
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to update setting');
  }
}

// ─── Course Progress Stats ──────────────────────────────────────────────────

export interface CourseProgressSummary {
  course_id: number;
  course_title: string;
  total_enrolled: number;
  completed: number;
  in_progress: number;
  not_started: number;
  avg_progress: number;
}

export async function fetchCourseProgressSummary(): Promise<CourseProgressSummary[]> {
  assertConfigured();
  try {
    const { data: courses } = await supabase
      .from('courses')
      .select('id, title')
      .eq('is_archived', 0);

    const { data: enrolments } = await supabase
      .from('student_course_enrolments')
      .select('course_id, status');

    const { data: progress } = await supabase
      .from('course_progress')
      .select('course_id, percentage');

    const courseMap = new Map<number, string>();
    (courses ?? []).forEach(c => courseMap.set(c.id, c.title));

    const enrolmentStats = new Map<number, { total: number; completed: number; active: number }>();
    (enrolments ?? []).forEach(e => {
      const stats = enrolmentStats.get(e.course_id) ?? { total: 0, completed: 0, active: 0 };
      stats.total++;
      if (e.status === 'COMPLETED') stats.completed++;
      if (e.status === 'ACTIVE') stats.active++;
      enrolmentStats.set(e.course_id, stats);
    });

    const progressStats = new Map<number, number[]>();
    (progress ?? []).forEach(p => {
      const pct = parseFloat(p.percentage) || 0;
      const arr = progressStats.get(p.course_id) ?? [];
      arr.push(pct);
      progressStats.set(p.course_id, arr);
    });

    return (courses ?? []).map(c => {
      const stats = enrolmentStats.get(c.id) ?? { total: 0, completed: 0, active: 0 };
      const pcts = progressStats.get(c.id) ?? [];
      const avg = pcts.length > 0 ? pcts.reduce((a, b) => a + b, 0) / pcts.length : 0;

      return {
        course_id: c.id,
        course_title: c.title,
        total_enrolled: stats.total,
        completed: stats.completed,
        in_progress: stats.active,
        not_started: stats.total - stats.completed - stats.active,
        avg_progress: Math.round(avg),
      };
    }).filter(c => c.total_enrolled > 0);
  } catch (e) {
    handleError(e, 'Failed to fetch course progress summary');
  }
}
