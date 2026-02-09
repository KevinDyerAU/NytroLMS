/**
 * NytroLMS Supabase Data Access Layer
 * Direct Supabase queries replacing Laravel API calls for optimal performance.
 */

import { supabase, isSupabaseConfigured } from './supabase';
import { useEdgeFunctions, callEdgeFunction } from './edge-client';
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

  // Route through edge function if enabled
  if (useEdgeFunctions) {
    const queryParams: Record<string, string> = {};
    if (params?.search) queryParams.search = params.search;
    if (params?.role) queryParams.role = params.role;
    if (params?.status) queryParams.status = params.status;
    if (params?.limit) queryParams.limit = String(params.limit);
    if (params?.offset) queryParams.offset = String(params.offset);
    const result = await callEdgeFunction<{ data: UserWithDetails[]; total: number }>('students', { params: queryParams });
    return result;
  }

  try {
    // Get users (no join — user_details fetched separately to avoid schema cache issues)
    let query = supabase
      .from('users')
      .select('*', { count: 'exact' })
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

    const userIds = (users ?? []).map(u => u.id);

    // Fetch roles and user_details in parallel
    const [{ data: roleData }, { data: roles }, { data: details }] = await Promise.all([
      supabase.from('model_has_roles').select('model_id, role_id').in('model_id', userIds.length > 0 ? userIds : [0]),
      supabase.from('roles').select('id, name'),
      supabase.from('user_details').select('*').in('user_id', userIds.length > 0 ? userIds : [0]),
    ]);

    const roleMap = new Map<number, string>();
    (roles ?? []).forEach(r => roleMap.set(r.id, r.name));

    const userRoleMap = new Map<number, string>();
    (roleData ?? []).forEach(mr => {
      userRoleMap.set(mr.model_id, roleMap.get(mr.role_id) ?? 'Student');
    });

    const detailsMap = new Map<number, DbUserDetail>();
    (details ?? []).forEach((d: any) => detailsMap.set(d.user_id, d));

    const enriched: UserWithDetails[] = (users ?? []).map(u => ({
      ...u,
      role_name: (userRoleMap.get(u.id) ?? 'Student') as UserRole,
      user_details: detailsMap.get(u.id) ?? null,
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

  // Route through edge function if enabled
  if (useEdgeFunctions) {
    const result = await callEdgeFunction<UserWithDetails>('students', { path: String(id) });
    return result;
  }

  try {
    // Fetch user and details separately to avoid schema cache relationship issues
    const [{ data: user, error }, { data: detail }, { data: roleData }] = await Promise.all([
      supabase.from('users').select('*').eq('id', id).single(),
      supabase.from('user_details').select('*').eq('user_id', id).maybeSingle(),
      supabase.from('model_has_roles').select('role_id').eq('model_id', id).limit(1),
    ]);
    if (error) throw error;
    if (!user) return null;

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
      user_details: detail ?? null,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch user');
  }
}

// ─── Student Detail Data ─────────────────────────────────────────────────────

export interface StudentEnrolment extends DbStudentCourseEnrolment {
  course_title: string;
  course_slug: string;
  progress_percentage: number | null;
}

export async function fetchStudentEnrolments(studentId: number): Promise<StudentEnrolment[]> {
  assertConfigured();
  try {
    const { data: enrolments, error } = await supabase
      .from('student_course_enrolments')
      .select('*')
      .eq('user_id', studentId)
      .order('created_at', { ascending: false });
    if (error) throw error;
    if (!enrolments || enrolments.length === 0) return [];

    const courseIds = Array.from(new Set(enrolments.map(e => e.course_id)));
    const [coursesResult, progressResult] = await Promise.all([
      supabase.from('courses').select('id, title, slug').in('id', courseIds),
      supabase.from('course_progress').select('course_id, percentage').eq('user_id', studentId).in('course_id', courseIds),
    ]);

    const courseMap = new Map<number, { title: string; slug: string }>();
    (coursesResult.data ?? []).forEach(c => courseMap.set(c.id, { title: c.title, slug: c.slug }));

    const progressMap = new Map<number, number>();
    (progressResult.data ?? []).forEach(p => {
      const pct = parseFloat(p.percentage);
      if (!isNaN(pct)) progressMap.set(p.course_id, pct);
    });

    return enrolments.map(e => ({
      ...e,
      course_title: courseMap.get(e.course_id)?.title ?? 'Unknown Course',
      course_slug: courseMap.get(e.course_id)?.slug ?? '',
      progress_percentage: progressMap.get(e.course_id) ?? null,
    }));
  } catch (e) {
    handleError(e, 'Failed to fetch student enrolments');
  }
}

export interface StudentCompany {
  id: number;
  name: string;
  email: string;
}

export interface StudentRelatedUser {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  is_active: number;
}

export interface StudentFullDetail extends UserWithDetails {
  companies: StudentCompany[];
  leaders: StudentRelatedUser[];
  trainers: StudentRelatedUser[];
  enrolments: StudentEnrolment[];
  registered_by_name: string | null;
}

export async function fetchStudentFullDetail(studentId: number): Promise<StudentFullDetail | null> {
  assertConfigured();
  try {
    // Parallel fetch: user + enrolments + all attachables (companies, leaders, trainers)
    const [userResult, enrolmentsResult, attachablesResult] = await Promise.all([
      fetchUserById(studentId),
      fetchStudentEnrolments(studentId),
      supabase
        .from('user_has_attachables')
        .select('user_id, attachable_type, attachable_id')
        .eq('user_id', studentId),
    ]);

    if (!userResult) return null;

    // Parse companies, leaders and trainers from attachables
    const companyIds: number[] = [];
    const leaderIds: number[] = [];
    const trainerIds: number[] = [];
    (attachablesResult.data ?? []).forEach(a => {
      if (a.attachable_type === 'App\\Models\\Company') companyIds.push(a.attachable_id);
      if (a.attachable_type === 'App\\Models\\Leader') leaderIds.push(a.attachable_id);
      if (a.attachable_type === 'App\\Models\\Trainer') trainerIds.push(a.attachable_id);
    });

    // Get company details
    let companies: StudentCompany[] = [];
    if (companyIds.length > 0) {
      const { data: companyData } = await supabase
        .from('companies')
        .select('id, name, email')
        .in('id', companyIds);
      companies = companyData ?? [];
    }

    let leaders: StudentRelatedUser[] = [];
    let trainers: StudentRelatedUser[] = [];

    const relatedUserIds = Array.from(new Set([...leaderIds, ...trainerIds]));
    if (relatedUserIds.length > 0) {
      const { data: relatedUsers } = await supabase
        .from('users')
        .select('id, first_name, last_name, email, is_active')
        .in('id', relatedUserIds);

      const userMap = new Map<number, StudentRelatedUser>();
      (relatedUsers ?? []).forEach(u => userMap.set(u.id, u));

      leaders = leaderIds.map(id => userMap.get(id)).filter(Boolean) as StudentRelatedUser[];
      trainers = trainerIds.map(id => userMap.get(id)).filter(Boolean) as StudentRelatedUser[];
    }

    // Get registered_by name
    let registered_by_name: string | null = null;
    const regById = userResult.user_details?.registered_by;
    if (regById) {
      const { data: regUser } = await supabase
        .from('users')
        .select('first_name, last_name')
        .eq('id', regById)
        .maybeSingle();
      if (regUser) registered_by_name = `${regUser.first_name} ${regUser.last_name}`;
    }

    return {
      ...userResult,
      companies,
      leaders,
      trainers,
      enrolments: enrolmentsResult,
      registered_by_name,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch student full detail');
  }
}

export async function createStudent(data: {
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  address?: string;
  language?: string;
  preferred_language?: string;
  preferred_name?: string;
  purchase_order?: string;
  study_type?: string;
  company_id?: number;
  leader_id?: number;
  trainer_id?: number;
  course_id?: number;
  schedule?: string;
  employment_service?: string;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    // Create user
    const { data: newUser, error: userError } = await supabase
      .from('users')
      .insert({
        first_name: data.first_name,
        last_name: data.last_name,
        username: data.first_name + data.last_name,
        email: data.email,
        password: '', // Password handled via Supabase Auth
        study_type: data.study_type || null,
        is_active: 1,
        is_archived: 0,
      })
      .select('id')
      .single();
    if (userError) throw userError;

    const studentId = newUser.id;

    // Create user_details
    await supabase.from('user_details').insert({
      user_id: studentId,
      phone: data.phone || '',
      address: data.address || '',
      language: data.language || 'en',
      preferred_language: data.preferred_language || null,
      preferred_name: data.preferred_name || null,
      purchase_order: data.purchase_order || null,
      timezone: 'Australia/Melbourne',
      status: 'ACTIVE',
    });

    // Assign Student role
    const { data: studentRole } = await supabase
      .from('roles')
      .select('id')
      .eq('name', 'Student')
      .single();
    if (studentRole) {
      await supabase.from('model_has_roles').insert({
        role_id: studentRole.id,
        model_type: 'App\\Models\\User',
        model_id: studentId,
      });
    }

    // Assign company via user_has_attachables
    if (data.company_id) {
      await supabase.from('user_has_attachables').insert({
        user_id: studentId,
        attachable_type: 'App\\Models\\Company',
        attachable_id: data.company_id,
      });
    }

    // Assign leader via user_has_attachables
    if (data.leader_id) {
      await supabase.from('user_has_attachables').insert({
        user_id: studentId,
        attachable_type: 'App\\Models\\Leader',
        attachable_id: data.leader_id,
      });
    }

    // Assign trainer via user_has_attachables
    if (data.trainer_id) {
      await supabase.from('user_has_attachables').insert({
        user_id: studentId,
        attachable_type: 'App\\Models\\Trainer',
        attachable_id: data.trainer_id,
      });
    }

    // Create basic enrolment record
    if (data.schedule || data.employment_service) {
      await supabase.from('enrolments').insert({
        user_id: studentId,
        enrolment_key: 'basic',
        enrolment_value: JSON.stringify({
          schedule: data.schedule || '',
          employment_service: data.employment_service || '',
        }),
        is_active: 1,
      });
    }

    // Assign course
    if (data.course_id) {
      const { data: course } = await supabase
        .from('courses')
        .select('id, course_length_days, version')
        .eq('id', data.course_id)
        .single();
      if (course) {
        const now = new Date();
        const endDate = new Date(now);
        endDate.setDate(endDate.getDate() + (course.course_length_days || 365));
        await supabase.from('student_course_enrolments').insert({
          user_id: studentId,
          course_id: course.id,
          status: 'ENROLLED',
          course_start_at: now.toISOString().split('T')[0] + ' 00:00:00',
          course_ends_at: endDate.toISOString().split('T')[0] + ' 00:00:00',
          version: course.version || 1,
          allowed_to_next_course: 1,
          is_chargeable: 0,
          registered_by: null,
          registered_on_create: 1,
        });
      }
    }

    return { id: studentId };
  } catch (e) {
    handleError(e, 'Failed to create student');
  }
}

export async function updateStudent(studentId: number, data: {
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
  address?: string;
  language?: string;
  preferred_language?: string;
  preferred_name?: string;
  purchase_order?: string;
  study_type?: string;
}): Promise<void> {
  assertConfigured();
  try {
    // Update users table
    const userFields: Record<string, unknown> = {};
    if (data.first_name !== undefined) userFields.first_name = data.first_name;
    if (data.last_name !== undefined) userFields.last_name = data.last_name;
    if (data.email !== undefined) userFields.email = data.email;
    if (data.study_type !== undefined) userFields.study_type = data.study_type;

    if (Object.keys(userFields).length > 0) {
      const { error } = await supabase.from('users').update(userFields).eq('id', studentId);
      if (error) throw error;
    }

    // Update user_details table
    const detailFields: Record<string, unknown> = {};
    if (data.phone !== undefined) detailFields.phone = data.phone;
    if (data.address !== undefined) detailFields.address = data.address;
    if (data.language !== undefined) detailFields.language = data.language;
    if (data.preferred_language !== undefined) detailFields.preferred_language = data.preferred_language;
    if (data.preferred_name !== undefined) detailFields.preferred_name = data.preferred_name;
    if (data.purchase_order !== undefined) detailFields.purchase_order = data.purchase_order;

    if (Object.keys(detailFields).length > 0) {
      const { error } = await supabase.from('user_details').update(detailFields).eq('user_id', studentId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update student');
  }
}

export async function activateStudent(studentId: number): Promise<void> {
  assertConfigured();
  try {
    await Promise.all([
      supabase.from('users').update({ is_active: 1 }).eq('id', studentId),
      supabase.from('user_details').update({ status: 'ACTIVE' }).eq('user_id', studentId),
    ]);
  } catch (e) {
    handleError(e, 'Failed to activate student');
  }
}

export async function deactivateStudent(studentId: number): Promise<void> {
  assertConfigured();
  try {
    await Promise.all([
      supabase.from('users').update({ is_active: 0 }).eq('id', studentId),
      supabase.from('user_details').update({ status: 'INACTIVE' }).eq('user_id', studentId),
    ]);
  } catch (e) {
    handleError(e, 'Failed to deactivate student');
  }
}

export async function fetchStudentActivities(studentId: number, limit = 50): Promise<DbActivityLog[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('activity_log')
      .select('*')
      .or(`subject_id.eq.${studentId},causer_id.eq.${studentId}`)
      .order('created_at', { ascending: false })
      .limit(limit);
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch student activities');
  }
}

export async function fetchAllCompanies(): Promise<{ id: number; name: string }[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('companies')
      .select('id, name')
      .is('deleted_at', null)
      .order('name');
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch companies list');
  }
}

export async function fetchAllLeaders(): Promise<StudentRelatedUser[]> {
  assertConfigured();
  try {
    const { data: leaderRoleAssignments } = await supabase
      .from('model_has_roles')
      .select('model_id')
      .eq('model_type', 'App\\Models\\User')
      .in('role_id', [3]); // Leader role_id = 3 typically

    // Fallback: get role id by name
    const { data: leaderRole } = await supabase
      .from('roles')
      .select('id')
      .eq('name', 'Leader')
      .single();

    if (!leaderRole) return [];

    const { data: assignments } = await supabase
      .from('model_has_roles')
      .select('model_id')
      .eq('role_id', leaderRole.id);

    const ids = (assignments ?? []).map(a => a.model_id);
    if (ids.length === 0) return [];

    const { data, error } = await supabase
      .from('users')
      .select('id, first_name, last_name, email, is_active')
      .in('id', ids)
      .eq('is_active', 1)
      .order('first_name');
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch leaders');
  }
}

export async function fetchAllTrainers(): Promise<StudentRelatedUser[]> {
  assertConfigured();
  try {
    const { data: trainerRole } = await supabase
      .from('roles')
      .select('id')
      .eq('name', 'Trainer')
      .single();

    if (!trainerRole) return [];

    const { data: assignments } = await supabase
      .from('model_has_roles')
      .select('model_id')
      .eq('role_id', trainerRole.id);

    const ids = (assignments ?? []).map(a => a.model_id);
    if (ids.length === 0) return [];

    const { data, error } = await supabase
      .from('users')
      .select('id, first_name, last_name, email, is_active')
      .in('id', ids)
      .eq('is_active', 1)
      .order('first_name');
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch trainers');
  }
}

// ─── Student Documents & Notes ───────────────────────────────────────────────

export interface StudentDocument {
  id: number;
  user_id: number;
  file_name: string;
  file_size: number;
  file_path: string;
  file_uuid: string;
  created_at: string | null;
  updated_at: string | null;
}

export interface StudentNote {
  id: number;
  user_id: number;
  note_body: string;
  is_pinned: number;
  pin_log: string | null;
  subject_type: string;
  subject_id: number;
  data: string | null;
  created_at: string | null;
  updated_at: string | null;
  author_name?: string;
}

const DOCUMENTS_BUCKET = 'student-documents';

export async function fetchStudentDocuments(studentId: number): Promise<StudentDocument[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('student_documents')
      .select('*')
      .eq('user_id', studentId)
      .order('created_at', { ascending: false });
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch student documents');
  }
}

export async function uploadStudentDocument(
  studentId: number,
  file: File,
): Promise<StudentDocument> {
  assertConfigured();
  try {
    const fileUuid = crypto.randomUUID();
    const storagePath = `${studentId}/${fileUuid}-${file.name}`;

    // 1. Upload to Supabase Storage
    const { error: uploadError } = await supabase.storage
      .from(DOCUMENTS_BUCKET)
      .upload(storagePath, file, {
        cacheControl: '3600',
        upsert: false,
      });
    if (uploadError) throw uploadError;

    // 2. Insert record into student_documents table
    const { data: doc, error: dbError } = await supabase
      .from('student_documents')
      .insert({
        user_id: studentId,
        file_name: file.name,
        file_size: file.size,
        file_path: storagePath,
        file_uuid: fileUuid,
      })
      .select('*')
      .single();

    if (dbError) {
      // Rollback: remove uploaded file if DB insert fails
      await supabase.storage.from(DOCUMENTS_BUCKET).remove([storagePath]);
      throw dbError;
    }

    return doc;
  } catch (e) {
    handleError(e, 'Failed to upload document');
  }
}

export async function getDocumentDownloadUrl(filePath: string): Promise<string> {
  assertConfigured();
  try {
    const { data, error } = await supabase.storage
      .from(DOCUMENTS_BUCKET)
      .createSignedUrl(filePath, 300); // 5-minute signed URL
    if (error) throw error;
    return data.signedUrl;
  } catch (e) {
    handleError(e, 'Failed to get download URL');
  }
}

export async function deleteStudentDocument(documentId: number, filePath: string): Promise<void> {
  assertConfigured();
  try {
    // 1. Remove from Supabase Storage
    const { error: storageError } = await supabase.storage
      .from(DOCUMENTS_BUCKET)
      .remove([filePath]);
    if (storageError) throw storageError;

    // 2. Remove DB record
    const { error: dbError } = await supabase
      .from('student_documents')
      .delete()
      .eq('id', documentId);
    if (dbError) throw dbError;
  } catch (e) {
    handleError(e, 'Failed to delete document');
  }
}

export async function fetchStudentNotes(studentId: number): Promise<StudentNote[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('notes')
      .select('*')
      .eq('subject_type', 'App\\Models\\User')
      .eq('subject_id', studentId)
      .order('is_pinned', { ascending: false })
      .order('created_at', { ascending: false });
    if (error) throw error;

    // Get author names
    const authorIds = Array.from(new Set((data ?? []).map(n => n.user_id)));
    let authorMap = new Map<number, string>();
    if (authorIds.length > 0) {
      const { data: authors } = await supabase
        .from('users')
        .select('id, first_name, last_name')
        .in('id', authorIds);
      (authors ?? []).forEach(a => authorMap.set(a.id, `${a.first_name} ${a.last_name}`));
    }

    return (data ?? []).map(n => ({
      ...n,
      author_name: authorMap.get(n.user_id) ?? 'Unknown',
    }));
  } catch (e) {
    handleError(e, 'Failed to fetch student notes');
  }
}

export async function createStudentNote(studentId: number, noteBody: string, authorId: number): Promise<StudentNote> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('notes')
      .insert({
        user_id: authorId,
        note_body: noteBody,
        is_pinned: 0,
        subject_type: 'App\\Models\\User',
        subject_id: studentId,
      })
      .select('*')
      .single();
    if (error) throw error;
    return data;
  } catch (e) {
    handleError(e, 'Failed to create note');
  }
}

export async function updateStudentNote(noteId: number, noteBody: string): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase
      .from('notes')
      .update({ note_body: noteBody })
      .eq('id', noteId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to update note');
  }
}

export async function deleteStudentNote(noteId: number): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('notes').delete().eq('id', noteId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to delete note');
  }
}

export async function toggleNotePin(noteId: number, isPinned: boolean): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase
      .from('notes')
      .update({ is_pinned: isPinned ? 1 : 0 })
      .eq('id', noteId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to update note');
  }
}

export async function fetchAvailableCourses(): Promise<{ id: number; title: string; category: string | null; status: string }[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('courses')
      .select('id, title, category, status')
      .eq('is_archived', 0)
      .in('status', ['PUBLISHED', 'DRAFT'])
      .order('category')
      .order('title');
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch available courses');
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

export interface CourseFullDetail extends DbCourse {
  lessons: (DbLesson & { topics_count: number })[];
  enrolments_count: number;
  enrolled_students: { id: number; first_name: string; last_name: string; status: string }[];
}

export async function fetchCourseFullDetail(courseId: number): Promise<CourseFullDetail | null> {
  assertConfigured();
  try {
    const [courseResult, lessonsResult, enrolmentsResult] = await Promise.all([
      supabase.from('courses').select('*').eq('id', courseId).single(),
      supabase.from('lessons').select('*').eq('course_id', courseId).order('order', { ascending: true }),
      supabase.from('student_course_enrolments').select('user_id, status').eq('course_id', courseId),
    ]);

    if (courseResult.error || !courseResult.data) return null;

    // Get topic counts per lesson
    const lessonIds = (lessonsResult.data ?? []).map(l => l.id);
    let topicCountMap = new Map<number, number>();
    if (lessonIds.length > 0) {
      const { data: topics } = await supabase
        .from('topics')
        .select('lesson_id')
        .in('lesson_id', lessonIds);
      (topics ?? []).forEach(t => {
        topicCountMap.set(t.lesson_id, (topicCountMap.get(t.lesson_id) ?? 0) + 1);
      });
    }

    // Get enrolled student names
    const studentIds = Array.from(new Set((enrolmentsResult.data ?? []).map(e => e.user_id)));
    let enrolledStudents: CourseFullDetail['enrolled_students'] = [];
    if (studentIds.length > 0) {
      const { data: students } = await supabase
        .from('users')
        .select('id, first_name, last_name')
        .in('id', studentIds.slice(0, 50)); // Limit to 50 for performance
      const statusMap = new Map<number, string>();
      (enrolmentsResult.data ?? []).forEach(e => statusMap.set(e.user_id, e.status));
      enrolledStudents = (students ?? []).map(s => ({
        ...s,
        status: statusMap.get(s.id) ?? 'ENROLLED',
      }));
    }

    return {
      ...courseResult.data,
      lessons: (lessonsResult.data ?? []).map(l => ({
        ...l,
        topics_count: topicCountMap.get(l.id) ?? 0,
      })),
      enrolments_count: enrolmentsResult.data?.length ?? 0,
      enrolled_students: enrolledStudents,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch course detail');
  }
}

export async function createCourse(data: {
  title: string;
  course_type: string;
  course_length_days: number;
  visibility: string;
  status: string;
  category?: string;
  course_expiry_days?: number;
  is_main_course?: number;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    const slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const { data: newCourse, error } = await supabase
      .from('courses')
      .insert({
        title: data.title,
        slug,
        course_type: data.course_type,
        course_length_days: data.course_length_days,
        course_expiry_days: data.course_expiry_days || null,
        visibility: data.visibility,
        status: data.status,
        category: data.category || null,
        is_main_course: data.is_main_course ?? 1,
        is_archived: 0,
        version: 1,
        next_course_after_days: 0,
        auto_register_next_course: 0,
      })
      .select('id')
      .single();
    if (error) throw error;
    return { id: newCourse.id };
  } catch (e) {
    handleError(e, 'Failed to create course');
  }
}

export async function updateCourse(courseId: number, data: {
  title?: string;
  course_type?: string;
  course_length_days?: number;
  course_expiry_days?: number | null;
  visibility?: string;
  status?: string;
  category?: string | null;
  is_main_course?: number;
  is_archived?: number;
}): Promise<void> {
  assertConfigured();
  try {
    const fields: Record<string, unknown> = {};
    if (data.title !== undefined) {
      fields.title = data.title;
      fields.slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    if (data.course_type !== undefined) fields.course_type = data.course_type;
    if (data.course_length_days !== undefined) fields.course_length_days = data.course_length_days;
    if (data.course_expiry_days !== undefined) fields.course_expiry_days = data.course_expiry_days;
    if (data.visibility !== undefined) fields.visibility = data.visibility;
    if (data.status !== undefined) fields.status = data.status;
    if (data.category !== undefined) fields.category = data.category;
    if (data.is_main_course !== undefined) fields.is_main_course = data.is_main_course;
    if (data.is_archived !== undefined) fields.is_archived = data.is_archived;

    if (Object.keys(fields).length > 0) {
      const { error } = await supabase.from('courses').update(fields).eq('id', courseId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update course');
  }
}

// ─── Lessons ─────────────────────────────────────────────────────────────────

export async function createLesson(data: {
  title: string;
  course_id: number;
  release_key: string;
  release_value?: string | null;
  has_work_placement?: number;
  lb_content?: string | null;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    if (useEdgeFunctions) {
      const result = await callEdgeFunction<{ id: number }>('courses', {
        method: 'POST',
        path: `${data.course_id}/lessons`,
        body: data,
      });
      return result;
    }

    const slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    // Get current lesson count for ordering
    const { count } = await supabase
      .from('lessons')
      .select('id', { count: 'exact', head: true })
      .eq('course_id', data.course_id);
    const order = count ?? 0;
    const now = new Date().toISOString();

    const { data: newLesson, error } = await supabase
      .from('lessons')
      .insert({
        title: data.title,
        slug,
        course_id: data.course_id,
        order,
        release_key: data.release_key || 'IMMEDIATELY',
        release_value: data.release_value || null,
        has_work_placement: data.has_work_placement ?? 0,
        has_topic: false,
        lb_content: data.lb_content || null,
        created_at: now,
        updated_at: now,
      })
      .select('id')
      .single();
    if (error) throw error;
    return { id: newLesson.id };
  } catch (e) {
    handleError(e, 'Failed to create lesson');
  }
}

export async function updateLesson(lessonId: number, data: {
  title?: string;
  release_key?: string;
  release_value?: string | null;
  has_work_placement?: number;
  lb_content?: string | null;
  course_id?: number;
}): Promise<void> {
  assertConfigured();
  try {
    if (useEdgeFunctions) {
      await callEdgeFunction('courses', {
        method: 'PUT',
        path: `lessons/${lessonId}`,
        body: data,
      });
      return;
    }

    const fields: Record<string, unknown> = {};
    if (data.title !== undefined) {
      fields.title = data.title;
      fields.slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    if (data.release_key !== undefined) fields.release_key = data.release_key;
    if (data.release_value !== undefined) fields.release_value = data.release_value;
    if (data.has_work_placement !== undefined) fields.has_work_placement = data.has_work_placement;
    if (data.lb_content !== undefined) fields.lb_content = data.lb_content;
    if (data.course_id !== undefined) fields.course_id = data.course_id;
    fields.updated_at = new Date().toISOString();

    if (Object.keys(fields).length > 1) {
      const { error } = await supabase.from('lessons').update(fields).eq('id', lessonId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update lesson');
  }
}

export async function deleteLesson(lessonId: number): Promise<void> {
  assertConfigured();
  try {
    if (useEdgeFunctions) {
      await callEdgeFunction('courses', {
        method: 'DELETE',
        path: `lessons/${lessonId}`,
      });
      return;
    }

    // Check for associated topics
    const { count } = await supabase
      .from('topics')
      .select('id', { count: 'exact', head: true })
      .eq('lesson_id', lessonId);
    if (count && count > 0) {
      throw new Error('Delete associated topics first.');
    }

    const { error } = await supabase.from('lessons').delete().eq('id', lessonId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to delete lesson');
  }
}

// ─── Topics ──────────────────────────────────────────────────────────────────

export async function createTopic(data: {
  title: string;
  course_id: number;
  lesson_id: number;
  estimated_time: number;
  lb_content?: string | null;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    const slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const { count } = await supabase
      .from('topics')
      .select('id', { count: 'exact', head: true })
      .eq('lesson_id', data.lesson_id);
    const order = count ?? 0;
    const now = new Date().toISOString();

    const { data: newTopic, error } = await supabase
      .from('topics')
      .insert({
        title: data.title,
        slug,
        course_id: data.course_id,
        lesson_id: data.lesson_id,
        order,
        estimated_time: data.estimated_time,
        has_quiz: false,
        lb_content: data.lb_content || null,
        created_at: now,
        updated_at: now,
      })
      .select('id')
      .single();
    if (error) throw error;

    // Mark parent lesson as having topics
    await supabase.from('lessons').update({ has_topic: true, updated_at: now }).eq('id', data.lesson_id);

    return { id: newTopic.id };
  } catch (e) {
    handleError(e, 'Failed to create topic');
  }
}

export async function updateTopic(topicId: number, data: {
  title?: string;
  estimated_time?: number;
  lb_content?: string | null;
  course_id?: number;
  lesson_id?: number;
}): Promise<void> {
  assertConfigured();
  try {
    const fields: Record<string, unknown> = {};
    if (data.title !== undefined) {
      fields.title = data.title;
      fields.slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    if (data.estimated_time !== undefined) fields.estimated_time = data.estimated_time;
    if (data.lb_content !== undefined) fields.lb_content = data.lb_content;
    if (data.course_id !== undefined) fields.course_id = data.course_id;
    if (data.lesson_id !== undefined) fields.lesson_id = data.lesson_id;
    fields.updated_at = new Date().toISOString();

    if (Object.keys(fields).length > 1) {
      const { error } = await supabase.from('topics').update(fields).eq('id', topicId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update topic');
  }
}

export async function deleteTopic(topicId: number): Promise<void> {
  assertConfigured();
  try {
    // Check for associated quizzes
    const { count } = await supabase
      .from('quizzes')
      .select('id', { count: 'exact', head: true })
      .eq('topic_id', topicId);
    if (count && count > 0) {
      throw new Error('Delete associated quizzes first.');
    }

    // Get lesson_id before deleting for has_topic check
    const { data: topic } = await supabase.from('topics').select('lesson_id').eq('id', topicId).single();
    const { error } = await supabase.from('topics').delete().eq('id', topicId);
    if (error) throw error;

    // Check if lesson still has topics
    if (topic) {
      const { count: remaining } = await supabase
        .from('topics')
        .select('id', { count: 'exact', head: true })
        .eq('lesson_id', topic.lesson_id);
      if (remaining === 0) {
        await supabase.from('lessons').update({ has_topic: false, updated_at: new Date().toISOString() }).eq('id', topic.lesson_id);
      }
    }
  } catch (e) {
    handleError(e, 'Failed to delete topic');
  }
}

// ─── Quizzes ─────────────────────────────────────────────────────────────────

export async function createQuiz(data: {
  title: string;
  course_id: number;
  lesson_id: number;
  topic_id: number;
  estimated_time: number;
  passing_percentage: number;
  allowed_attempts: number;
  has_checklist?: number;
  lb_content?: string | null;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    const slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const { count } = await supabase
      .from('quizzes')
      .select('id', { count: 'exact', head: true })
      .eq('topic_id', data.topic_id);
    const order = count ?? 0;
    const now = new Date().toISOString();

    const { data: newQuiz, error } = await supabase
      .from('quizzes')
      .insert({
        title: data.title,
        slug,
        course_id: data.course_id,
        lesson_id: data.lesson_id,
        topic_id: data.topic_id,
        order,
        estimated_time: data.estimated_time,
        passing_percentage: data.passing_percentage,
        allowed_attempts: data.allowed_attempts ?? 999,
        has_checklist: data.has_checklist ?? 0,
        lb_content: data.lb_content || null,
        created_at: now,
        updated_at: now,
      })
      .select('id')
      .single();
    if (error) throw error;

    // Mark parent topic as having quizzes
    await supabase.from('topics').update({ has_quiz: true, updated_at: now }).eq('id', data.topic_id);

    return { id: newQuiz.id };
  } catch (e) {
    handleError(e, 'Failed to create quiz');
  }
}

export async function updateQuiz(quizId: number, data: {
  title?: string;
  estimated_time?: number;
  passing_percentage?: number;
  allowed_attempts?: number;
  has_checklist?: number;
  lb_content?: string | null;
  course_id?: number;
  lesson_id?: number;
  topic_id?: number;
}): Promise<void> {
  assertConfigured();
  try {
    const fields: Record<string, unknown> = {};
    if (data.title !== undefined) {
      fields.title = data.title;
      fields.slug = data.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    if (data.estimated_time !== undefined) fields.estimated_time = data.estimated_time;
    if (data.passing_percentage !== undefined) fields.passing_percentage = data.passing_percentage;
    if (data.allowed_attempts !== undefined) fields.allowed_attempts = data.allowed_attempts;
    if (data.has_checklist !== undefined) fields.has_checklist = data.has_checklist;
    if (data.lb_content !== undefined) fields.lb_content = data.lb_content;
    if (data.course_id !== undefined) fields.course_id = data.course_id;
    if (data.lesson_id !== undefined) fields.lesson_id = data.lesson_id;
    if (data.topic_id !== undefined) fields.topic_id = data.topic_id;
    fields.updated_at = new Date().toISOString();

    if (Object.keys(fields).length > 1) {
      const { error } = await supabase.from('quizzes').update(fields).eq('id', quizId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update quiz');
  }
}

export async function deleteQuiz(quizId: number): Promise<void> {
  assertConfigured();
  try {
    // Get topic_id before deleting for has_quiz check
    const { data: quiz } = await supabase.from('quizzes').select('topic_id').eq('id', quizId).single();

    // Delete associated questions first (soft delete)
    await supabase.from('questions').delete().eq('quiz_id', quizId);

    // Delete quiz attempts
    await supabase.from('quiz_attempts').delete().eq('quiz_id', quizId);

    const { error } = await supabase.from('quizzes').delete().eq('id', quizId);
    if (error) throw error;

    // Check if topic still has quizzes
    if (quiz) {
      const { count: remaining } = await supabase
        .from('quizzes')
        .select('id', { count: 'exact', head: true })
        .eq('topic_id', quiz.topic_id);
      if (remaining === 0) {
        await supabase.from('topics').update({ has_quiz: false, updated_at: new Date().toISOString() }).eq('id', quiz.topic_id);
      }
    }
  } catch (e) {
    handleError(e, 'Failed to delete quiz');
  }
}

// ─── Questions ───────────────────────────────────────────────────────────────

export interface QuestionData {
  id?: number;
  slug?: string;
  order: number;
  title: string;
  content: string;
  answer_type: string;
  required?: number;
  options?: Record<string, unknown> | null;
  correct_answer?: string | null;
  table_structure?: Record<string, unknown> | null;
}

/** Bulk save questions for a quiz (create/update). Matches Laravel QuestionController::update() */
export async function saveQuestions(quizId: number, questions: QuestionData[]): Promise<void> {
  assertConfigured();
  try {
    const now = new Date().toISOString();

    for (const q of questions) {
      const slug = q.slug || q.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      const record = {
        order: q.order,
        required: q.required ?? 0,
        title: q.title,
        content: q.content,
        answer_type: q.answer_type,
        options: q.options ? (typeof q.options === 'string' ? q.options : JSON.stringify(q.options)) : null,
        correct_answer: q.correct_answer || null,
        table_structure: q.table_structure ? (typeof q.table_structure === 'string' ? q.table_structure : JSON.stringify(q.table_structure)) : null,
        updated_at: now,
      };

      if (q.id) {
        // Update existing question
        const { error } = await supabase.from('questions').update(record).eq('id', q.id).eq('quiz_id', quizId);
        if (error) throw error;
      } else {
        // Create new question
        const { error } = await supabase.from('questions').insert({
          ...record,
          slug,
          quiz_id: quizId,
          created_at: now,
        });
        if (error) throw error;
      }
    }
  } catch (e) {
    handleError(e, 'Failed to save questions');
  }
}

export async function deleteQuestion(questionId: number): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('questions').delete().eq('id', questionId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to delete question');
  }
}

/** Fetch all questions for a quiz ordered by `order` */
export async function fetchQuizQuestions(quizId: number): Promise<QuestionData[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('questions')
      .select('*')
      .eq('quiz_id', quizId)
      .order('order', { ascending: true });
    if (error) throw error;

    return (data ?? []).map((q) => ({
      id: q.id,
      slug: q.slug,
      order: q.order ?? 0,
      title: q.title,
      content: q.content,
      answer_type: q.answer_type,
      required: q.required,
      options: typeof q.options === 'string' ? JSON.parse(q.options) : q.options,
      correct_answer: q.correct_answer,
      table_structure: typeof q.table_structure === 'string' ? JSON.parse(q.table_structure) : q.table_structure,
    }));
  } catch (e) {
    handleError(e, 'Failed to fetch quiz questions');
  }
}

// ─── Content Reordering ──────────────────────────────────────────────────────

/** Reorder lessons within a course. Matches Laravel CourseController::reorder() */
export async function reorderLessons(courseId: number, orderedIds: number[]): Promise<void> {
  assertConfigured();
  try {
    for (let pos = 0; pos < orderedIds.length; pos++) {
      const { error } = await supabase
        .from('lessons')
        .update({ order: pos })
        .eq('id', orderedIds[pos])
        .eq('course_id', courseId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to reorder lessons');
  }
}

/** Reorder topics within a lesson. Matches Laravel LessonController::reorder() */
export async function reorderTopics(lessonId: number, orderedIds: number[]): Promise<void> {
  assertConfigured();
  try {
    for (let pos = 0; pos < orderedIds.length; pos++) {
      const { error } = await supabase
        .from('topics')
        .update({ order: pos })
        .eq('id', orderedIds[pos])
        .eq('lesson_id', lessonId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to reorder topics');
  }
}

/** Reorder quizzes within a topic. Matches Laravel TopicController::reorder() */
export async function reorderQuizzes(topicId: number, orderedIds: number[]): Promise<void> {
  assertConfigured();
  try {
    for (let pos = 0; pos < orderedIds.length; pos++) {
      const { error } = await supabase
        .from('quizzes')
        .update({ order: pos })
        .eq('id', orderedIds[pos])
        .eq('topic_id', topicId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to reorder quizzes');
  }
}

/** Reorder questions within a quiz. Matches Laravel QuizController::reorder() */
export async function reorderQuestions(quizId: number, orderedIds: number[]): Promise<void> {
  assertConfigured();
  try {
    for (let pos = 0; pos < orderedIds.length; pos++) {
      const { error } = await supabase
        .from('questions')
        .update({ order: pos })
        .eq('id', orderedIds[pos])
        .eq('quiz_id', quizId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to reorder questions');
  }
}

// ─── Featured Image Upload ───────────────────────────────────────────────────

const IMAGEABLE_TYPES: Record<string, string> = {
  course: 'App\\Models\\Course',
  lesson: 'App\\Models\\Lesson',
  topic: 'App\\Models\\Topic',
  quiz: 'App\\Models\\Quiz',
};

/** Upload a featured image for a course/lesson/topic/quiz. Matches Laravel featuredImage() */
export async function uploadFeaturedImage(
  entityType: 'course' | 'lesson' | 'topic' | 'quiz',
  entityId: number,
  file: File,
): Promise<{ url: string }> {
  assertConfigured();
  try {
    const imageableType = IMAGEABLE_TYPES[entityType];
    const ext = file.name.split('.').pop()?.toLowerCase() || 'jpg';
    const filePath = `featured/${entityType}/${entityId}/${Date.now()}.${ext}`;

    // Upload to Supabase Storage
    const { error: uploadError } = await supabase.storage
      .from('media')
      .upload(filePath, file, { upsert: true });
    if (uploadError) throw uploadError;

    const { data: publicUrlData } = supabase.storage.from('media').getPublicUrl(filePath);

    // Delete existing featured image record
    await supabase
      .from('images')
      .delete()
      .eq('imageable_type', imageableType)
      .eq('imageable_id', entityId)
      .eq('type', 'FEATURED');

    // Create new image record
    const now = new Date().toISOString();
    const { error: dbError } = await supabase.from('images').insert({
      type: 'FEATURED',
      file_path: filePath,
      imageable_type: imageableType,
      imageable_id: entityId,
      created_at: now,
      updated_at: now,
    });
    if (dbError) throw dbError;

    return { url: publicUrlData.publicUrl };
  } catch (e) {
    handleError(e, 'Failed to upload featured image');
  }
}

/** Delete a featured image. Matches Laravel deleteImage() */
export async function deleteFeaturedImage(
  entityType: 'course' | 'lesson' | 'topic' | 'quiz',
  entityId: number,
): Promise<void> {
  assertConfigured();
  try {
    const imageableType = IMAGEABLE_TYPES[entityType];

    // Get existing image record
    const { data: image } = await supabase
      .from('images')
      .select('id, file_path')
      .eq('imageable_type', imageableType)
      .eq('imageable_id', entityId)
      .eq('type', 'FEATURED')
      .maybeSingle();

    if (image) {
      // Delete from storage
      await supabase.storage.from('media').remove([image.file_path]);
      // Delete record
      await supabase.from('images').delete().eq('id', image.id);
    }
  } catch (e) {
    handleError(e, 'Failed to delete featured image');
  }
}

/** Get the featured image URL for an entity */
export async function getFeaturedImageUrl(
  entityType: 'course' | 'lesson' | 'topic' | 'quiz',
  entityId: number,
): Promise<string | null> {
  assertConfigured();
  try {
    const imageableType = IMAGEABLE_TYPES[entityType];
    const { data: image } = await supabase
      .from('images')
      .select('file_path')
      .eq('imageable_type', imageableType)
      .eq('imageable_id', entityId)
      .eq('type', 'FEATURED')
      .maybeSingle();

    if (!image) return null;
    const { data: publicUrlData } = supabase.storage.from('media').getPublicUrl(image.file_path);
    return publicUrlData.publicUrl;
  } catch (e) {
    return null;
  }
}

// ─── Lesson Detail Fetch (for drill-down) ────────────────────────────────────

export interface LessonFullDetail {
  id: number;
  title: string;
  slug: string;
  course_id: number;
  course_title: string;
  order: number;
  release_key: string;
  release_value: string | null;
  has_work_placement: number;
  has_topic: number;
  lb_content: string | null;
  created_at: string | null;
  topics: {
    id: number;
    title: string;
    slug: string;
    order: number;
    estimated_time: number;
    has_quiz: number;
    quizzes_count: number;
  }[];
}

export async function fetchLessonFullDetail(lessonId: number): Promise<LessonFullDetail | null> {
  assertConfigured();
  try {
    const [lessonResult, topicsResult] = await Promise.all([
      supabase.from('lessons').select('*, courses!inner(title)').eq('id', lessonId).single(),
      supabase.from('topics').select('*').eq('lesson_id', lessonId).order('order', { ascending: true }),
    ]);
    if (lessonResult.error || !lessonResult.data) return null;
    const lesson = lessonResult.data;
    const topics = topicsResult.data ?? [];

    // Count quizzes per topic
    const topicIds = topics.map((t: { id: number }) => t.id);
    let quizCounts: Record<number, number> = {};
    if (topicIds.length > 0) {
      const { data: quizzes } = await supabase
        .from('quizzes')
        .select('id, topic_id')
        .in('topic_id', topicIds);
      if (quizzes) {
        for (const q of quizzes) {
          quizCounts[q.topic_id] = (quizCounts[q.topic_id] || 0) + 1;
        }
      }
    }

    return {
      id: lesson.id,
      title: lesson.title,
      slug: lesson.slug,
      course_id: lesson.course_id,
      course_title: (lesson as any).courses?.title ?? '',
      order: lesson.order,
      release_key: lesson.release_key,
      release_value: lesson.release_value,
      has_work_placement: lesson.has_work_placement,
      has_topic: lesson.has_topic ? 1 : 0,
      lb_content: lesson.lb_content,
      created_at: lesson.created_at,
      topics: topics.map((t: any) => ({
        id: t.id,
        title: t.title,
        slug: t.slug,
        order: t.order ?? 0,
        estimated_time: t.estimated_time ?? 0,
        has_quiz: t.has_quiz ? 1 : 0,
        quizzes_count: quizCounts[t.id] || 0,
      })),
    };
  } catch (e) {
    handleError(e, 'Failed to fetch lesson detail');
  }
}

// ─── Topic Detail Fetch (for drill-down) ─────────────────────────────────────

export interface TopicFullDetail {
  id: number;
  title: string;
  slug: string;
  course_id: number;
  lesson_id: number;
  lesson_title: string;
  order: number;
  estimated_time: number;
  has_quiz: number;
  lb_content: string | null;
  created_at: string | null;
  quizzes: {
    id: number;
    title: string;
    slug: string;
    order: number;
    estimated_time: number;
    passing_percentage: number;
    allowed_attempts: number;
    has_checklist: number;
    questions_count: number;
  }[];
}

export async function fetchTopicFullDetail(topicId: number): Promise<TopicFullDetail | null> {
  assertConfigured();
  try {
    const [topicResult, quizzesResult] = await Promise.all([
      supabase.from('topics').select('*, lessons!inner(title)').eq('id', topicId).single(),
      supabase.from('quizzes').select('*').eq('topic_id', topicId).order('order', { ascending: true }),
    ]);
    if (topicResult.error || !topicResult.data) return null;
    const topic = topicResult.data;
    const quizzes = quizzesResult.data ?? [];

    // Count questions per quiz
    const quizIds = quizzes.map((q: { id: number }) => q.id);
    let questionCounts: Record<number, number> = {};
    if (quizIds.length > 0) {
      const { data: questions } = await supabase
        .from('questions')
        .select('id, quiz_id')
        .in('quiz_id', quizIds);
      if (questions) {
        for (const q of questions) {
          questionCounts[q.quiz_id] = (questionCounts[q.quiz_id] || 0) + 1;
        }
      }
    }

    return {
      id: topic.id,
      title: topic.title,
      slug: topic.slug,
      course_id: topic.course_id,
      lesson_id: topic.lesson_id,
      lesson_title: (topic as any).lessons?.title ?? '',
      order: topic.order ?? 0,
      estimated_time: topic.estimated_time ?? 0,
      has_quiz: topic.has_quiz ? 1 : 0,
      lb_content: topic.lb_content,
      created_at: topic.created_at,
      quizzes: quizzes.map((q: any) => ({
        id: q.id,
        title: q.title,
        slug: q.slug,
        order: q.order ?? 0,
        estimated_time: q.estimated_time ?? 0,
        passing_percentage: q.passing_percentage ?? 0,
        allowed_attempts: q.allowed_attempts ?? 999,
        has_checklist: q.has_checklist ?? 0,
        questions_count: questionCounts[q.id] || 0,
      })),
    };
  } catch (e) {
    handleError(e, 'Failed to fetch topic detail');
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

export async function createEnrolment(data: {
  user_id: number;
  course_id: number;
  course_start_at?: string;
  course_ends_at?: string;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    const now = new Date().toISOString();
    const { data: newEnrolment, error } = await supabase
      .from('student_course_enrolments')
      .insert({
        user_id: data.user_id,
        course_id: data.course_id,
        status: 'ACTIVE',
        is_main_course: 1,
        is_locked: 0,
        is_semester_2: 0,
        allowed_to_next_course: 0,
        course_start_at: data.course_start_at || now,
        course_ends_at: data.course_ends_at || null,
        version: 1,
        deferred: 0,
        cert_issued: 0,
        is_chargeable: 1,
        registered_on_create: 1,
        show_on_widget: 1,
        show_registration_date: 1,
        registration_date: now,
        created_at: now,
        updated_at: now,
      })
      .select('id')
      .single();
    if (error) throw error;
    return { id: newEnrolment.id };
  } catch (e) {
    handleError(e, 'Failed to create enrolment');
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

export interface CompanyFullDetail extends DbCompany {
  poc_user_name: string | null;
  bm_user_name: string | null;
  leaders: { id: number; first_name: string; last_name: string; email: string; is_active: number }[];
  students: { id: number; first_name: string; last_name: string; email: string; is_active: number }[];
  signup_links: { id: number; key: string; course_title: string; is_active: number; created_at: string | null }[];
}

export async function fetchCompanyFullDetail(companyId: number): Promise<CompanyFullDetail | null> {
  assertConfigured();
  try {
    const { data: company, error } = await supabase
      .from('companies')
      .select('*')
      .eq('id', companyId)
      .single();
    if (error || !company) return null;

    // Get all attachables for this company (users linked to this company)
    const { data: attachables } = await supabase
      .from('user_has_attachables')
      .select('user_id')
      .eq('attachable_type', 'App\\Models\\Company')
      .eq('attachable_id', companyId);

    const linkedUserIds = (attachables ?? []).map(a => a.user_id);

    // Get user details and roles for linked users
    let leaders: CompanyFullDetail['leaders'] = [];
    let students: CompanyFullDetail['students'] = [];

    if (linkedUserIds.length > 0) {
      const [usersResult, rolesResult] = await Promise.all([
        supabase.from('users').select('id, first_name, last_name, email, is_active').in('id', linkedUserIds),
        supabase.from('model_has_roles').select('model_id, role_id').in('model_id', linkedUserIds),
      ]);

      const { data: allRoles } = await supabase.from('roles').select('id, name');
      const roleNameMap = new Map<number, string>();
      (allRoles ?? []).forEach(r => roleNameMap.set(r.id, r.name));

      const userRoleMap = new Map<number, string>();
      (rolesResult.data ?? []).forEach(mr => userRoleMap.set(mr.model_id, roleNameMap.get(mr.role_id) ?? 'Student'));

      (usersResult.data ?? []).forEach(u => {
        const role = userRoleMap.get(u.id) ?? 'Student';
        if (role === 'Leader') leaders.push(u);
        else students.push(u);
      });
    }

    // Get signup links
    const { data: links } = await supabase
      .from('signup_links')
      .select('id, key, course_id, is_active, created_at')
      .eq('company_id', companyId);

    let signupLinks: CompanyFullDetail['signup_links'] = [];
    if (links && links.length > 0) {
      const courseIds = Array.from(new Set(links.map(l => l.course_id)));
      const { data: courses } = await supabase.from('courses').select('id, title').in('id', courseIds);
      const courseMap = new Map<number, string>();
      (courses ?? []).forEach(c => courseMap.set(c.id, c.title));
      signupLinks = links.map(l => ({
        id: l.id,
        key: l.key,
        course_title: courseMap.get(l.course_id) ?? 'Unknown',
        is_active: l.is_active,
        created_at: l.created_at,
      }));
    }

    // Get POC and BM user names
    let poc_user_name: string | null = null;
    let bm_user_name: string | null = null;
    const specialIds = [company.poc_user_id, company.bm_user_id].filter(Boolean) as number[];
    if (specialIds.length > 0) {
      const { data: specialUsers } = await supabase
        .from('users')
        .select('id, first_name, last_name')
        .in('id', specialIds);
      (specialUsers ?? []).forEach(u => {
        if (u.id === company.poc_user_id) poc_user_name = `${u.first_name} ${u.last_name}`;
        if (u.id === company.bm_user_id) bm_user_name = `${u.first_name} ${u.last_name}`;
      });
    }

    return { ...company, poc_user_name, bm_user_name, leaders, students, signup_links: signupLinks };
  } catch (e) {
    handleError(e, 'Failed to fetch company detail');
  }
}

// ─── Signup Link Management ──────────────────────────────────────────────────

/** Create a signup link for a company. Matches Laravel CompanyController::signupLink() */
export async function createSignupLink(data: {
  company_id: number;
  leader_id: number;
  course_id: number;
  creator_id: number;
  is_chargeable?: boolean;
}): Promise<{ id: number; key: string }> {
  assertConfigured();
  try {
    // Check for existing link with same company + course
    const { data: existing } = await supabase
      .from('signup_links')
      .select('id')
      .eq('company_id', data.company_id)
      .eq('course_id', data.course_id)
      .maybeSingle();
    if (existing) {
      throw new Error('A signup link for this course already exists for this company.');
    }

    const key = crypto.randomUUID();
    const now = new Date().toISOString();
    const { data: link, error } = await supabase
      .from('signup_links')
      .insert({
        company_id: data.company_id,
        leader_id: data.leader_id,
        course_id: data.course_id,
        creator_id: data.creator_id,
        key,
        is_active: 1,
        is_chargeable: data.is_chargeable ? 1 : 0,
        created_at: now,
        updated_at: now,
      })
      .select('id, key')
      .single();
    if (error) throw error;
    return { id: link.id, key: link.key };
  } catch (e) {
    handleError(e, 'Failed to create signup link');
  }
}

/** Delete a signup link. Matches Laravel CompanyController::deleteLink() */
export async function deleteSignupLink(linkId: number): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('signup_links').delete().eq('id', linkId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to delete signup link');
  }
}

/** Toggle a signup link active/inactive */
export async function toggleSignupLinkActive(linkId: number, isActive: boolean): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase
      .from('signup_links')
      .update({ is_active: isActive ? 1 : 0, updated_at: new Date().toISOString() })
      .eq('id', linkId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to update signup link');
  }
}

/** Fetch a signup link by its UUID key (public, no auth required) */
export async function fetchSignupLinkByKey(key: string): Promise<{
  id: number;
  key: string;
  company_id: number;
  company_name: string;
  leader_id: number;
  course_id: number;
  course_title: string;
  is_active: number;
  is_chargeable: number;
} | null> {
  assertConfigured();
  try {
    const { data: link, error } = await supabase
      .from('signup_links')
      .select('*')
      .eq('key', key)
      .maybeSingle();
    if (error || !link) return null;
    if (!link.is_active) return null;

    // Fetch company and course names
    const [companyResult, courseResult] = await Promise.all([
      supabase.from('companies').select('name').eq('id', link.company_id).single(),
      supabase.from('courses').select('title').eq('id', link.course_id).single(),
    ]);

    return {
      id: link.id,
      key: link.key,
      company_id: link.company_id,
      company_name: companyResult.data?.name ?? 'Unknown',
      leader_id: link.leader_id,
      course_id: link.course_id,
      course_title: courseResult.data?.title ?? 'Unknown',
      is_active: link.is_active,
      is_chargeable: link.is_chargeable,
    };
  } catch (e) {
    return null;
  }
}

/** Register a student via signup link. Matches Laravel SignupController::store() */
export async function registerViaSignupLink(data: {
  signup_link_key: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  timezone: string;
  password: string;
}): Promise<{ success: boolean; message: string }> {
  assertConfigured();
  try {
    // 1. Validate signup link
    const { data: link } = await supabase
      .from('signup_links')
      .select('*')
      .eq('key', data.signup_link_key)
      .single();
    if (!link || !link.is_active) {
      throw new Error('Invalid or inactive signup link.');
    }

    // 2. Create Supabase Auth user
    const { data: authData, error: authError } = await supabase.auth.signUp({
      email: data.email,
      password: data.password,
      options: {
        data: {
          first_name: data.first_name,
          last_name: data.last_name,
          role: 'Student',
        },
      },
    });
    if (authError) throw authError;
    if (!authData.user) throw new Error('Failed to create account.');

    const userId = parseInt(authData.user.id, 10) || 0;
    // For Supabase Auth, the user record in public.users should be created by a trigger.
    // We need to wait a moment then set up the profile data.

    // 3. Check if user record exists in public.users (created by trigger)
    // The users table is linked to auth.users — try to find the record
    const { data: existingUser } = await supabase
      .from('users')
      .select('id')
      .eq('email', data.email)
      .maybeSingle();

    const studentId = existingUser?.id;
    if (!studentId) {
      return { success: true, message: 'Account created. Please log in to complete setup.' };
    }

    // 4. Create user_details record
    const now = new Date().toISOString();
    await supabase.from('user_details').upsert({
      user_id: studentId,
      phone: data.phone,
      timezone: data.timezone,
      language: 'en',
      signup_links_id: link.id,
      signup_through_link: 1,
      purchase_order: 'N/A',
      status: 'ENROLLED',
      created_at: now,
      updated_at: now,
    }, { onConflict: 'user_id' });

    // 5. Assign Student role
    const { data: studentRole } = await supabase
      .from('roles')
      .select('id')
      .eq('name', 'Student')
      .single();
    if (studentRole) {
      await supabase.from('model_has_roles').upsert({
        role_id: studentRole.id,
        model_type: 'App\\Models\\User',
        model_id: studentId,
      }, { onConflict: 'role_id,model_id,model_type' });
    }

    // 6. Link student to company and leader via user_has_attachables
    await supabase.from('user_has_attachables').insert([
      { user_id: studentId, attachable_type: 'App\\Models\\Company', attachable_id: link.company_id },
      { user_id: studentId, attachable_type: 'App\\Models\\Leader', attachable_id: link.leader_id },
    ]);

    // 7. Create basic enrolment record
    await supabase.from('enrolments').insert({
      user_id: studentId,
      enrolment_key: 'basic',
      enrolment_value: JSON.stringify({ schedule: 'Not Applicable', employment_service: 'Other' }),
      is_active: 1,
      created_at: now,
      updated_at: now,
    });

    // 8. Enrol student in the course (matching assign_course_on_create)
    const { data: course } = await supabase
      .from('courses')
      .select('id, title, course_length_days, version, auto_register_next_course, next_course, next_course_after_days')
      .eq('id', link.course_id)
      .single();

    if (course) {
      const courseStart = new Date().toISOString().split('T')[0] + ' 00:00:00';
      const endDate = new Date();
      endDate.setDate(endDate.getDate() + (course.course_length_days || 365));
      const courseEnd = endDate.toISOString().split('T')[0] + ' 00:00:00';
      const isSemester2 = (course.title || '').toLowerCase().includes('semester 2');
      const isMainCourse = !isSemester2;

      await supabase.from('student_course_enrolments').upsert({
        user_id: studentId,
        course_id: course.id,
        allowed_to_next_course: 1,
        course_start_at: courseStart,
        course_ends_at: courseEnd,
        status: 'ENROLLED',
        version: course.version,
        is_chargeable: link.is_chargeable,
        registered_by: studentId,
        registered_on_create: 1,
        is_semester_2: isSemester2 ? 1 : 0,
        is_main_course: isMainCourse ? 1 : 0,
        show_registration_date: 0,
        created_at: now,
        updated_at: now,
      }, { onConflict: 'user_id,course_id' });

      // 9. Auto-enrol next course if configured
      if (course.auto_register_next_course && course.next_course) {
        const { data: nextCourse } = await supabase
          .from('courses')
          .select('id, course_length_days, version')
          .eq('id', course.next_course)
          .single();
        if (nextCourse) {
          const nextStart = new Date(endDate);
          nextStart.setDate(nextStart.getDate() + (course.next_course_after_days || 0));
          const nextEnd = new Date(nextStart);
          nextEnd.setDate(nextEnd.getDate() + (nextCourse.course_length_days || 365));

          await supabase.from('student_course_enrolments').upsert({
            user_id: studentId,
            course_id: nextCourse.id,
            allowed_to_next_course: 0,
            course_start_at: nextStart.toISOString().split('T')[0] + ' 00:00:00',
            course_ends_at: nextEnd.toISOString().split('T')[0] + ' 00:00:00',
            status: 'ENROLLED',
            version: nextCourse.version,
            is_chargeable: link.is_chargeable,
            registered_by: studentId,
            registered_on_create: 1,
            is_semester_2: 1,
            is_main_course: 0,
            show_registration_date: 0,
            created_at: now,
            updated_at: now,
          }, { onConflict: 'user_id,course_id' });
        }
      }
    }

    return { success: true, message: 'Registration successful! You can now log in.' };
  } catch (e) {
    if (e instanceof Error) {
      if (e.message.includes('already registered') || e.message.includes('already been registered')) {
        return { success: false, message: 'This email is already registered. Please log in instead.' };
      }
      return { success: false, message: e.message };
    }
    return { success: false, message: 'Registration failed. Please try again.' };
  }
}

/** Fetch available timezones for signup form */
export async function fetchTimezones(): Promise<{ id: number; name: string; region: string }[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('timezones')
      .select('id, name, region')
      .order('region', { ascending: true });
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    return [];
  }
}

export async function fetchCompanyById(companyId: number): Promise<DbCompany | null> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('companies')
      .select('*')
      .eq('id', companyId)
      .single();
    if (error) throw error;
    return data;
  } catch (e) {
    handleError(e, 'Failed to fetch company');
  }
}

export async function createCompany(data: {
  name: string;
  email: string;
  address?: string;
  number?: string;
  poc_user_id?: number | null;
  bm_user_id?: number | null;
}): Promise<{ id: number }> {
  assertConfigured();
  try {
    const { data: newCompany, error } = await supabase
      .from('companies')
      .insert({
        name: data.name,
        email: data.email,
        address: data.address || null,
        number: data.number || '',
        poc_user_id: data.poc_user_id || null,
        bm_user_id: data.bm_user_id || null,
        created_by: '',
        modified_by: '[]',
      })
      .select('id')
      .single();
    if (error) throw error;
    return { id: newCompany.id };
  } catch (e) {
    handleError(e, 'Failed to create company');
  }
}

export async function updateCompany(companyId: number, data: {
  name?: string;
  email?: string;
  address?: string | null;
  number?: string;
  poc_user_id?: number | null;
  bm_user_id?: number | null;
}): Promise<void> {
  assertConfigured();
  try {
    const fields: Record<string, unknown> = {};
    if (data.name !== undefined) fields.name = data.name;
    if (data.email !== undefined) fields.email = data.email;
    if (data.address !== undefined) fields.address = data.address;
    if (data.number !== undefined) fields.number = data.number;
    if (data.poc_user_id !== undefined) fields.poc_user_id = data.poc_user_id;
    if (data.bm_user_id !== undefined) fields.bm_user_id = data.bm_user_id;

    if (Object.keys(fields).length > 0) {
      const { error } = await supabase.from('companies').update(fields).eq('id', companyId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update company');
  }
}

export async function fetchQuizAttemptDetail(attemptId: number): Promise<DbQuizAttempt & {
  student_name: string;
  course_title: string;
  lesson_title: string;
  topic_title: string;
  quiz_title: string;
} | null> {
  assertConfigured();
  try {
    const { data: attempt, error } = await supabase
      .from('quiz_attempts')
      .select('*')
      .eq('id', attemptId)
      .single();
    if (error || !attempt) return null;

    const [studentResult, courseResult, lessonResult, topicResult, quizResult] = await Promise.all([
      supabase.from('users').select('first_name, last_name').eq('id', attempt.user_id).single(),
      supabase.from('courses').select('title').eq('id', attempt.course_id).single(),
      supabase.from('lessons').select('title').eq('id', attempt.lesson_id).single(),
      supabase.from('topics').select('title').eq('id', attempt.topic_id).single(),
      supabase.from('quizzes').select('title, passing_percentage').eq('id', attempt.quiz_id).single(),
    ]);

    return {
      ...attempt,
      student_name: studentResult.data ? `${studentResult.data.first_name} ${studentResult.data.last_name}` : 'Unknown',
      course_title: courseResult.data?.title ?? 'Unknown',
      lesson_title: lessonResult.data?.title ?? 'Unknown',
      topic_title: topicResult.data?.title ?? 'Unknown',
      quiz_title: quizResult.data?.title ?? 'Unknown',
    };
  } catch (e) {
    handleError(e, 'Failed to fetch assessment detail');
  }
}

// ─── Full Quiz Attempt Review (questions, answers, evaluation, feedback) ──────

export interface QuizQuestion {
  id: number;
  order: string | number;
  title: string;
  content: string;
  answer_type: string; // 'SINGLE' | 'MCQ' | 'TABLE' etc.
  options: Record<string, unknown> | unknown[] | null;
  correct_answer: string | null;
  table_structure: unknown | null;
}

export interface EvaluationResult {
  status: string; // 'satisfactory' | 'not satisfactory' | 'correct' | 'incorrect'
  comment: string;
}

export interface QuizAttemptFullReview {
  // Attempt metadata
  id: number;
  user_id: number;
  quiz_id: number;
  course_id: number;
  lesson_id: number;
  topic_id: number;
  attempt: number;
  status: string;
  system_result: string | null;
  assisted: number;
  submitted_at: string | null;
  accessed_at: string | null;
  accessor_id: number | null;
  created_at: string | null;
  // Resolved names
  student_name: string;
  course_title: string;
  lesson_title: string;
  topic_title: string;
  quiz_title: string;
  passing_percentage: number | null;
  // Questions & answers
  questions: QuizQuestion[];
  submitted_answers: Record<string, unknown>;
  // Evaluation (per-question marking results)
  evaluation: {
    id: number;
    results: Record<string, EvaluationResult> | null;
    status: string | null;
    evaluator_id: number | null;
    evaluator_name: string | null;
    updated_at: string | null;
  } | null;
  // Feedback messages
  feedbacks: {
    id: number;
    body: Record<string, unknown> | null;
    owner_id: number | null;
    owner_name: string | null;
    updated_at: string | null;
  }[];
}

export async function fetchQuizAttemptFullReview(attemptId: number): Promise<QuizAttemptFullReview | null> {
  assertConfigured();
  try {
    const { data: attempt, error } = await supabase
      .from('quiz_attempts')
      .select('*')
      .eq('id', attemptId)
      .single();
    if (error || !attempt) return null;

    // Transition SUBMITTED → REVIEWING on open (matching Laravel AssessmentsController::show())
    if (attempt.status === 'SUBMITTED') {
      await supabase.from('quiz_attempts')
        .update({ status: 'REVIEWING', updated_at: new Date().toISOString() })
        .eq('id', attemptId);
      attempt.status = 'REVIEWING';
    }

    // Parse JSON fields
    let questions: QuizQuestion[] = [];
    try {
      questions = typeof attempt.questions === 'string' ? JSON.parse(attempt.questions) : (attempt.questions ?? []);
    } catch { questions = []; }

    let submittedAnswers: Record<string, unknown> = {};
    try {
      submittedAnswers = typeof attempt.submitted_answers === 'string'
        ? JSON.parse(attempt.submitted_answers)
        : (attempt.submitted_answers ?? {});
    } catch { submittedAnswers = {}; }

    // Fetch related data in parallel
    const [studentResult, courseResult, lessonResult, topicResult, quizResult, evalResult, feedbackResult] = await Promise.all([
      supabase.from('users').select('first_name, last_name').eq('id', attempt.user_id).single(),
      supabase.from('courses').select('title').eq('id', attempt.course_id).single(),
      supabase.from('lessons').select('title').eq('id', attempt.lesson_id).single(),
      supabase.from('topics').select('title').eq('id', attempt.topic_id).single(),
      supabase.from('quizzes').select('title, passing_percentage').eq('id', attempt.quiz_id).single(),
      supabase.from('evaluations')
        .select('id, results, status, evaluator_id, updated_at')
        .eq('evaluable_type', 'App\\Models\\QuizAttempt')
        .eq('evaluable_id', attemptId)
        .order('id', { ascending: false })
        .limit(1)
        .maybeSingle(),
      supabase.from('feedbacks')
        .select('id, body, owner_id, updated_at')
        .eq('attachable_type', 'App\\Models\\Quiz')
        .eq('attachable_id', attempt.quiz_id)
        .eq('user_id', attempt.user_id)
        .order('id', { ascending: false }),
    ]);

    // Parse evaluation results
    let evaluation: QuizAttemptFullReview['evaluation'] = null;
    if (evalResult.data) {
      let evalResults: Record<string, EvaluationResult> | null = null;
      try {
        evalResults = typeof evalResult.data.results === 'string'
          ? JSON.parse(evalResult.data.results)
          : evalResult.data.results;
      } catch { evalResults = null; }

      let evaluatorName: string | null = null;
      if (evalResult.data.evaluator_id && evalResult.data.evaluator_id > 0) {
        const { data: ev } = await supabase.from('users').select('first_name, last_name').eq('id', evalResult.data.evaluator_id).single();
        evaluatorName = ev ? `${ev.first_name} ${ev.last_name}` : null;
      } else if (evalResult.data.evaluator_id === 0) {
        evaluatorName = 'System';
      }

      evaluation = {
        id: evalResult.data.id,
        results: evalResults,
        status: evalResult.data.status,
        evaluator_id: evalResult.data.evaluator_id,
        evaluator_name: evaluatorName,
        updated_at: evalResult.data.updated_at,
      };
    }

    // Parse feedback bodies
    const feedbacks: QuizAttemptFullReview['feedbacks'] = [];
    for (const fb of feedbackResult.data ?? []) {
      let body: Record<string, unknown> | null = null;
      try {
        body = typeof fb.body === 'string' ? JSON.parse(fb.body) : fb.body;
      } catch { body = null; }

      let ownerName: string | null = null;
      if (fb.owner_id && fb.owner_id > 0) {
        const { data: ow } = await supabase.from('users').select('first_name, last_name').eq('id', fb.owner_id).single();
        ownerName = ow ? `${ow.first_name} ${ow.last_name}` : null;
      } else if (fb.owner_id === 0) {
        ownerName = 'System';
      }

      feedbacks.push({
        id: fb.id,
        body,
        owner_id: fb.owner_id,
        owner_name: ownerName,
        updated_at: fb.updated_at,
      });
    }

    return {
      id: attempt.id,
      user_id: attempt.user_id,
      quiz_id: attempt.quiz_id,
      course_id: attempt.course_id,
      lesson_id: attempt.lesson_id,
      topic_id: attempt.topic_id,
      attempt: attempt.attempt,
      status: attempt.status,
      system_result: attempt.system_result,
      assisted: attempt.assisted,
      submitted_at: attempt.submitted_at,
      accessed_at: attempt.accessed_at,
      accessor_id: attempt.accessor_id,
      created_at: attempt.created_at,
      student_name: studentResult.data ? `${studentResult.data.first_name} ${studentResult.data.last_name}` : 'Unknown',
      course_title: courseResult.data?.title ?? 'Unknown',
      lesson_title: lessonResult.data?.title ?? 'Unknown',
      topic_title: topicResult.data?.title ?? 'Unknown',
      quiz_title: quizResult.data?.title ?? 'Unknown',
      passing_percentage: quizResult.data?.passing_percentage ?? null,
      questions,
      submitted_answers: submittedAnswers,
      evaluation,
      feedbacks,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch assessment review');
  }
}

// ─── Assessment Marking Actions ──────────────────────────────────────────────

export async function evaluateQuestion(
  attemptId: number,
  questionId: string,
  status: string,
  comment: string,
  studentId: number,
  evaluatorId: number,
): Promise<void> {
  assertConfigured();
  try {
    // Get existing evaluation for this attempt
    const { data: existing } = await supabase
      .from('evaluations')
      .select('id, results')
      .eq('evaluable_type', 'App\\Models\\QuizAttempt')
      .eq('evaluable_id', attemptId)
      .order('id', { ascending: false })
      .limit(1)
      .maybeSingle();

    const newResult = { [questionId]: { status, comment } };

    if (existing && !existing.results?.status) {
      // Update existing incomplete evaluation
      let existingResults: Record<string, unknown> = {};
      try {
        existingResults = typeof existing.results === 'string' ? JSON.parse(existing.results) : (existing.results ?? {});
      } catch { existingResults = {}; }

      const merged = { ...existingResults, ...newResult };
      const { error } = await supabase
        .from('evaluations')
        .update({ results: merged })
        .eq('id', existing.id);
      if (error) throw error;
    } else {
      // Create new evaluation
      const { error } = await supabase
        .from('evaluations')
        .insert({
          evaluable_type: 'App\\Models\\QuizAttempt',
          evaluable_id: attemptId,
          results: newResult,
          student_id: studentId,
          evaluator_id: evaluatorId,
        });
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to evaluate question');
  }
}

// LLN/PTR config constants
const LLN_QUIZ_ID = 11111;
const LLN_LESSON_ID = 11111;
const PTR_LESSON_ID = 11112;

export async function submitAssessmentFeedback(
  attemptId: number,
  quizId: number,
  studentId: number,
  evaluatorId: number,
  feedbackMessage: string,
  overallStatus: 'SATISFACTORY' | 'FAIL',
  assisted?: boolean,
): Promise<{ autoReturned: boolean }> {
  assertConfigured();
  try {
    const now = new Date().toISOString();

    // Fetch attempt details for competency/notification logic
    const { data: attempt } = await supabase
      .from('quiz_attempts')
      .select('id, user_id, quiz_id, course_id, lesson_id, topic_id')
      .eq('id', attemptId)
      .single();
    if (!attempt) throw new Error('Attempt not found');

    // Get latest evaluation
    const { data: evaluation } = await supabase
      .from('evaluations')
      .select('id, results, status')
      .eq('evaluable_type', 'App\\Models\\QuizAttempt')
      .eq('evaluable_id', attemptId)
      .order('id', { ascending: false })
      .limit(1)
      .maybeSingle();

    // Check if evaluation is already complete (has status) — if so, create a new one with carried-forward results
    const evalStatus = overallStatus === 'SATISFACTORY' ? 'SATISFACTORY' : 'UNSATISFACTORY';
    if (evaluation && evaluation.status) {
      // Already marked — create new evaluation with carried-forward results
      await supabase.from('evaluations').insert({
        evaluable_type: 'App\\Models\\QuizAttempt',
        evaluable_id: attemptId,
        results: typeof evaluation.results === 'string' ? evaluation.results : JSON.stringify(evaluation.results ?? {}),
        student_id: studentId,
        evaluator_id: evaluatorId,
        status: evalStatus,
        created_at: now,
        updated_at: now,
      });
    } else if (evaluation) {
      // Not yet marked — update existing
      await supabase.from('evaluations').update({
        status: evalStatus,
        evaluator_id: evaluatorId,
        updated_at: now,
      }).eq('id', evaluation.id);
    }

    // Create feedback
    await supabase.from('feedbacks').insert({
      attachable_type: 'App\\Models\\Quiz',
      attachable_id: quizId,
      body: JSON.stringify({ message: feedbackMessage, evaluation_id: evaluation?.id, attempt_id: attemptId }),
      user_id: studentId,
      owner_id: evaluatorId,
      created_at: now,
      updated_at: now,
    });

    // Update quiz attempt status
    await supabase.from('quiz_attempts').update({
      status: overallStatus,
      assisted: assisted ? 1 : 0,
      accessor_id: evaluatorId,
      accessed_at: now,
      is_valid_accessor: 1,
      updated_at: now,
    }).eq('id', attemptId);

    // Determine assessment type for notification/competency logic
    const isLlnQuiz = attempt.quiz_id === LLN_QUIZ_ID;
    const isPreCourse = await checkIsPreCourseAssessment(attempt.lesson_id, attempt.course_id);

    // ─── Notification + competency logic (matching Laravel feedbackPost) ───
    if (isLlnQuiz) {
      // LLN quiz → NewLLNDMarked notification
      await createAssessmentNotification(studentId, evaluatorId, attemptId, 'App\\Notifications\\NewLLNDMarked');

      // If SATISFACTORY + assisted, create a note
      if (overallStatus === 'SATISFACTORY' && assisted) {
        const noteBody = '<p>The trainer has marked the LLND activity as satisfactory, with <strong>assistance required</strong>.</p>' +
          '<p>An email notification has been sent to the student advising them to <strong>reach out for help</strong> with the course when needed.</p>';
        await supabase.from('notes').insert({
          user_id: 0,
          subject_type: 'App\\Models\\User',
          subject_id: studentId,
          note_body: noteBody,
          is_pinned: 0,
          created_at: now,
          updated_at: now,
        });
      }
    } else if (isPreCourse) {
      // Pre-course assessment → PreCourseAssessmentMarked notification
      await createAssessmentNotification(studentId, evaluatorId, attemptId, 'App\\Notifications\\PreCourseAssessmentMarked');
    } else {
      // Regular assessment
      if (overallStatus !== 'SATISFACTORY') {
        // Auto-return to student on FAIL
        await supabase.from('quiz_attempts').update({ status: 'RETURNED', updated_at: now }).eq('id', attemptId);
        await createAssessmentNotification(studentId, evaluatorId, attemptId, 'App\\Notifications\\AssessmentReturned');
        return { autoReturned: true };
      }

      // SATISFACTORY → AssessmentMarked notification + competency
      await createAssessmentNotification(studentId, evaluatorId, attemptId, 'App\\Notifications\\AssessmentMarked');
      await checkAndAddCompetency(studentId, attempt.lesson_id, attempt.course_id);
    }

    return { autoReturned: false };
  } catch (e) {
    handleError(e, 'Failed to submit assessment feedback');
  }
}

export async function returnAssessment(attemptId: number, evaluatorId?: number): Promise<void> {
  assertConfigured();
  try {
    const now = new Date().toISOString();

    // Get attempt to find student
    const { data: attempt } = await supabase
      .from('quiz_attempts')
      .select('id, user_id')
      .eq('id', attemptId)
      .single();

    const { error } = await supabase
      .from('quiz_attempts')
      .update({ status: 'RETURNED', updated_at: now })
      .eq('id', attemptId);
    if (error) throw error;

    // Create AssessmentReturned notification
    if (attempt) {
      await createAssessmentNotification(
        attempt.user_id,
        evaluatorId ?? 0,
        attemptId,
        'App\\Notifications\\AssessmentReturned',
      );
    }
  } catch (e) {
    handleError(e, 'Failed to return assessment');
  }
}

/**
 * Email assessment results to a student.
 * Matches Laravel AssessmentsController::emailPost().
 */
export async function emailAssessment(attemptId: number, evaluatorId: number): Promise<void> {
  assertConfigured();
  try {
    const { data: attempt } = await supabase
      .from('quiz_attempts')
      .select('id, user_id')
      .eq('id', attemptId)
      .single();
    if (!attempt) throw new Error('Attempt not found');

    await createAssessmentNotification(
      attempt.user_id,
      evaluatorId,
      attemptId,
      'App\\Notifications\\AssessmentEmailed',
    );
  } catch (e) {
    handleError(e, 'Failed to email assessment');
  }
}

/**
 * Create a notification record in the notifications table.
 * Matches Laravel's database notification channel.
 */
async function createAssessmentNotification(
  studentId: number,
  evaluatorId: number,
  attemptId: number,
  notificationType: string,
): Promise<void> {
  const now = new Date().toISOString();
  // Generate UUID-like ID for notification
  const id = crypto.randomUUID();
  await supabase.from('notifications').insert({
    id,
    type: notificationType,
    notifiable_type: 'App\\Models\\User',
    notifiable_id: studentId,
    data: JSON.stringify({ student: studentId, evaluator: evaluatorId, assessment: attemptId }),
    created_at: now,
    updated_at: now,
  });
}

/**
 * Check if a lesson is a pre-course assessment (order = 0, not Semester 2).
 * Matches Laravel's isPreCourseAssessment check.
 */
async function checkIsPreCourseAssessment(lessonId: number, courseId: number): Promise<boolean> {
  const { data: lesson } = await supabase
    .from('lessons')
    .select('id, "order", course_id')
    .eq('id', lessonId)
    .eq('course_id', courseId)
    .eq('order', 0)
    .maybeSingle();

  if (!lesson) return false;

  // Check course title doesn't contain "Semester 2"
  const { data: course } = await supabase
    .from('courses')
    .select('title')
    .eq('id', courseId)
    .single();

  if (!course) return false;
  return !course.title.toLowerCase().includes('semester 2');
}

/**
 * Check if all quizzes in a lesson are satisfactory, checklists complete,
 * and work placement done — then add/update competency.
 * Matches Laravel StudentCourseService::addCompetency() + competencyCheck().
 */
async function checkAndAddCompetency(
  userId: number,
  lessonId: number,
  courseId: number,
): Promise<void> {
  // Skip LLN and PTR lessons
  if (lessonId === LLN_LESSON_ID || lessonId === PTR_LESSON_ID) return;

  // Check lesson completion: all quizzes in the lesson must be SATISFACTORY
  const { data: quizzes } = await supabase
    .from('quizzes')
    .select('id, has_checklist')
    .eq('lesson_id', lessonId);

  if (!quizzes || quizzes.length === 0) return;

  // For each quiz, check if the latest attempt is SATISFACTORY
  for (const quiz of quizzes) {
    const { data: latestAttempt } = await supabase
      .from('quiz_attempts')
      .select('id, status')
      .eq('quiz_id', quiz.id)
      .eq('user_id', userId)
      .is('deleted_at', null)
      .order('id', { ascending: false })
      .limit(1)
      .maybeSingle();

    if (!latestAttempt || latestAttempt.status !== 'SATISFACTORY') return;
  }

  // Check checklists complete (quizzes with has_checklist=1 must have a matching CHECKLIST event)
  const checklistQuizzes = quizzes.filter(q => q.has_checklist === 1);
  if (checklistQuizzes.length > 0) {
    for (const cq of checklistQuizzes) {
      const { data: checklist } = await supabase
        .from('student_lms_attachables')
        .select('id, properties')
        .eq('event', 'CHECKLIST')
        .eq('attachable_type', 'App\\Models\\Quiz')
        .eq('attachable_id', cq.id)
        .eq('student_id', userId)
        .order('id', { ascending: false })
        .limit(1)
        .maybeSingle();

      if (!checklist) return;
      const props = typeof checklist.properties === 'string'
        ? JSON.parse(checklist.properties) : checklist.properties;
      if (props?.status === 'NOT SATISFACTORY') return;
    }
  }

  // Check work placement complete
  const { data: lesson } = await supabase
    .from('lessons')
    .select('id, has_work_placement')
    .eq('id', lessonId)
    .single();

  if (lesson?.has_work_placement) {
    const { data: wp } = await supabase
      .from('student_lms_attachables')
      .select('id')
      .eq('event', 'WORK_PLACEMENT')
      .eq('attachable_type', 'App\\Models\\Lesson')
      .eq('attachable_id', lessonId)
      .eq('student_id', userId)
      .limit(1)
      .maybeSingle();

    if (!wp) return;
  }

  // All checks passed — upsert competency
  const now = new Date().toISOString();

  // Get course start date from enrolment
  const { data: enrolment } = await supabase
    .from('student_course_enrolments')
    .select('course_start_at')
    .eq('user_id', userId)
    .eq('course_id', courseId)
    .limit(1)
    .maybeSingle();

  // Get lesson start/end dates from quiz attempts
  const { data: firstAttempt } = await supabase
    .from('quiz_attempts')
    .select('submitted_at, created_at')
    .eq('user_id', userId)
    .eq('lesson_id', lessonId)
    .order('created_at', { ascending: true })
    .limit(1)
    .maybeSingle();

  const { data: lastAttempt } = await supabase
    .from('quiz_attempts')
    .select('accessed_at, submitted_at')
    .eq('user_id', userId)
    .eq('lesson_id', lessonId)
    .order('accessed_at', { ascending: false })
    .limit(1)
    .maybeSingle();

  const lessonStart = firstAttempt?.submitted_at ?? firstAttempt?.created_at ?? now;
  const lessonEnd = lastAttempt?.accessed_at ?? lastAttempt?.submitted_at ?? now;

  await supabase.from('competencies').upsert({
    user_id: userId,
    lesson_id: lessonId,
    course_id: courseId,
    is_competent: 1,
    course_start: enrolment?.course_start_at ?? now,
    lesson_start: lessonStart.substring(0, 10),
    lesson_end: lessonEnd.substring(0, 10),
    param: JSON.stringify({
      last_attempt_end_date: lastAttempt?.accessed_at ?? null,
      checklist_end_date: null,
    }),
    updated_at: now,
  }, { onConflict: 'user_id,lesson_id,course_id' });
}

// ─── Generic Report Builder ───────────────────────────────────────────────────

export interface ReportFilters {
  startDate?: string;
  endDate?: string;
  status?: string;
  courseId?: number;
  companyId?: number;
  role?: string;
}

export interface GeneratedReport {
  reportType: string;
  generatedAt: string;
  generatedBy: string;
  recordCount: number;
  filters: ReportFilters;
  data: Record<string, unknown>[];
}

export async function buildReport(reportType: string, filters?: ReportFilters): Promise<GeneratedReport> {
  assertConfigured();
  try {
    const { data, error } = await supabase.functions.invoke('reports/build', {
      body: { reportType, filters: filters || {} },
    });
    if (error) throw error;
    return data as GeneratedReport;
  } catch (e) {
    handleError(e, 'Failed to generate report');
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

// ─── Profile ─────────────────────────────────────────────────────────────────

export interface ProfileData {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  username: string | null;
  is_active: number;
  created_at: string | null;
  role_name: string;
  detail: {
    avatar: string | null;
    phone: string | null;
    address: string | null;
    preferred_name: string | null;
    language: string | null;
    preferred_language: string | null;
    timezone: string | null;
    position: string | null;
    last_logged_in: string | null;
  } | null;
}

export async function fetchProfile(userId: number): Promise<ProfileData | null> {
  assertConfigured();
  try {
    const { data: user, error } = await supabase
      .from('users')
      .select('id, first_name, last_name, email, username, is_active, created_at')
      .eq('id', userId)
      .single();
    if (error || !user) return null;

    const [detailResult, roleResult] = await Promise.all([
      supabase.from('user_details').select('avatar, phone, address, preferred_name, language, preferred_language, timezone, position, last_logged_in').eq('user_id', userId).maybeSingle(),
      supabase.from('model_has_roles').select('role_id').eq('model_id', userId).maybeSingle(),
    ]);

    let role_name = 'Student';
    if (roleResult.data?.role_id) {
      const { data: role } = await supabase.from('roles').select('name').eq('id', roleResult.data.role_id).single();
      if (role) role_name = role.name;
    }

    return { ...user, role_name, detail: detailResult.data ?? null };
  } catch (e) {
    handleError(e, 'Failed to fetch profile');
  }
}

export async function updateProfile(userId: number, updates: {
  first_name?: string;
  last_name?: string;
  username?: string;
  phone?: string;
  address?: string;
  preferred_name?: string;
  language?: string;
  preferred_language?: string;
  timezone?: string;
  position?: string;
}): Promise<void> {
  assertConfigured();
  try {
    const { first_name, last_name, username, ...detailUpdates } = updates;

    // Update users table
    const userUpdates: Record<string, unknown> = {};
    if (first_name !== undefined) userUpdates.first_name = first_name;
    if (last_name !== undefined) userUpdates.last_name = last_name;
    if (username !== undefined) userUpdates.username = username;

    if (Object.keys(userUpdates).length > 0) {
      const { error } = await supabase.from('users').update(userUpdates).eq('id', userId);
      if (error) throw error;
    }

    // Update user_details table
    if (Object.keys(detailUpdates).length > 0) {
      const { error } = await supabase.from('user_details').update(detailUpdates).eq('user_id', userId);
      if (error) throw error;
    }
  } catch (e) {
    handleError(e, 'Failed to update profile');
  }
}

export async function updateProfileAvatar(userId: number, file: File): Promise<string> {
  assertConfigured();
  try {
    // Check if avatars bucket exists, if not we store in student-documents
    const bucketName = 'student-documents';
    const storagePath = `avatars/${userId}/${crypto.randomUUID()}-${file.name}`;

    const { error: uploadError } = await supabase.storage
      .from(bucketName)
      .upload(storagePath, file, { cacheControl: '3600', upsert: false });
    if (uploadError) throw uploadError;

    // Get public URL
    const { data } = supabase.storage.from(bucketName).getPublicUrl(storagePath);

    // Update user_details avatar field
    const { error: dbError } = await supabase
      .from('user_details')
      .update({ avatar: storagePath })
      .eq('user_id', userId);
    if (dbError) throw dbError;

    return data.publicUrl;
  } catch (e) {
    handleError(e, 'Failed to update avatar');
  }
}

export async function changePassword(newPassword: string): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.auth.updateUser({ password: newPassword });
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to change password');
  }
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

// ─── Student-Facing LMS ────────────────────────────────────────────────────

export interface StudentEnrolledCourse {
  enrolment_id: number;
  course_id: number;
  course_title: string;
  course_code: string | null;
  status: string;
  course_start_at: string | null;
  course_ends_at: string | null;
  course_completed_at: string | null;
  lesson_count: number;
  topic_count: number;
  quiz_count: number;
  quizzes_passed: number;
  progress_percentage: number;
}

export async function fetchMyEnrolledCourses(userId: number): Promise<StudentEnrolledCourse[]> {
  assertConfigured();
  try {
    const { data: enrolments, error } = await supabase
      .from('student_course_enrolments')
      .select('id, course_id, status, course_start_at, course_ends_at, course_completed_at, course_progress_id')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });
    if (error) throw error;
    if (!enrolments || enrolments.length === 0) return [];

    const courseIds = Array.from(new Set(enrolments.map(e => e.course_id)));

    const { data: courses } = await supabase
      .from('courses')
      .select('id, title, code')
      .in('id', courseIds);

    const courseMap = new Map((courses ?? []).map(c => [c.id, c]));

    // Get quiz counts per course
    const { data: quizCounts } = await supabase
      .from('quizzes')
      .select('course_id')
      .in('course_id', courseIds);

    const quizCountMap = new Map<number, number>();
    (quizCounts ?? []).forEach(q => {
      quizCountMap.set(q.course_id, (quizCountMap.get(q.course_id) ?? 0) + 1);
    });

    // Get passed quiz counts per course for this user
    const { data: passedAttempts } = await supabase
      .from('quiz_attempts')
      .select('course_id, quiz_id')
      .eq('user_id', userId)
      .eq('status', 'SATISFACTORY')
      .is('deleted_at', null)
      .in('course_id', courseIds);

    const passedMap = new Map<number, Set<number>>();
    (passedAttempts ?? []).forEach(a => {
      if (!passedMap.has(a.course_id)) passedMap.set(a.course_id, new Set());
      passedMap.get(a.course_id)!.add(a.quiz_id);
    });

    // Get lesson/topic counts
    const { data: lessonCounts } = await supabase
      .from('lessons')
      .select('course_id')
      .in('course_id', courseIds);

    const lessonCountMap = new Map<number, number>();
    (lessonCounts ?? []).forEach(l => {
      lessonCountMap.set(l.course_id, (lessonCountMap.get(l.course_id) ?? 0) + 1);
    });

    const { data: topicCounts } = await supabase
      .from('topics')
      .select('course_id')
      .in('course_id', courseIds);

    const topicCountMap = new Map<number, number>();
    (topicCounts ?? []).forEach(t => {
      topicCountMap.set(t.course_id, (topicCountMap.get(t.course_id) ?? 0) + 1);
    });

    return enrolments.map(e => {
      const course = courseMap.get(e.course_id);
      const totalQuizzes = quizCountMap.get(e.course_id) ?? 0;
      const passed = passedMap.get(e.course_id)?.size ?? 0;
      const pct = totalQuizzes > 0 ? Math.round((passed / totalQuizzes) * 100) : 0;

      return {
        enrolment_id: e.id,
        course_id: e.course_id,
        course_title: course?.title ?? 'Unknown Course',
        course_code: course?.code ?? null,
        status: e.status ?? 'ACTIVE',
        course_start_at: e.course_start_at,
        course_ends_at: e.course_ends_at,
        course_completed_at: e.course_completed_at,
        lesson_count: lessonCountMap.get(e.course_id) ?? 0,
        topic_count: topicCountMap.get(e.course_id) ?? 0,
        quiz_count: totalQuizzes,
        quizzes_passed: passed,
        progress_percentage: pct,
      };
    });
  } catch (e) {
    handleError(e, 'Failed to fetch enrolled courses');
  }
}

export interface StudentLessonView {
  id: number;
  order: number;
  title: string;
  course_id: number;
  topics: {
    id: number;
    order: number;
    title: string;
    estimated_time: number | null;
    has_quiz: number;
    quizzes: {
      id: number;
      title: string;
      status: string | null; // Latest attempt status for this user
      attempts: number;
    }[];
  }[];
}

export async function fetchCourseLessonsForStudent(courseId: number, userId: number): Promise<StudentLessonView[]> {
  assertConfigured();
  try {
    const { data: lessons, error } = await supabase
      .from('lessons')
      .select('id, "order", title, course_id')
      .eq('course_id', courseId)
      .order('order');
    if (error) throw error;
    if (!lessons || lessons.length === 0) return [];

    const lessonIds = lessons.map(l => l.id);

    const { data: topics } = await supabase
      .from('topics')
      .select('id, "order", title, estimated_time, has_quiz, lesson_id')
      .in('lesson_id', lessonIds)
      .order('order');

    const { data: quizzes } = await supabase
      .from('quizzes')
      .select('id, title, topic_id, lesson_id')
      .eq('course_id', courseId)
      .order('order');

    // Get user's quiz attempts for this course
    const { data: attempts } = await supabase
      .from('quiz_attempts')
      .select('quiz_id, status, attempt')
      .eq('user_id', userId)
      .eq('course_id', courseId)
      .is('deleted_at', null)
      .order('attempt', { ascending: false });

    // Build quiz status map: quiz_id -> { status, attempts }
    const quizStatusMap = new Map<number, { status: string; attempts: number }>();
    (attempts ?? []).forEach(a => {
      if (!quizStatusMap.has(a.quiz_id)) {
        quizStatusMap.set(a.quiz_id, { status: a.status, attempts: a.attempt });
      }
    });

    // Build topic map
    const topicMap = new Map<number, typeof topics>();
    (topics ?? []).forEach(t => {
      if (!topicMap.has(t.lesson_id)) topicMap.set(t.lesson_id, []);
      topicMap.get(t.lesson_id)!.push(t);
    });

    // Build quiz map by topic
    const quizByTopic = new Map<number, typeof quizzes>();
    (quizzes ?? []).forEach(q => {
      const key = q.topic_id ?? q.lesson_id;
      if (!quizByTopic.has(key)) quizByTopic.set(key, []);
      quizByTopic.get(key)!.push(q);
    });

    return lessons.map(l => ({
      id: l.id,
      order: l.order,
      title: l.title,
      course_id: l.course_id,
      topics: (topicMap.get(l.id) ?? []).map(t => ({
        id: t.id,
        order: t.order,
        title: t.title,
        estimated_time: t.estimated_time,
        has_quiz: t.has_quiz,
        quizzes: (quizByTopic.get(t.id) ?? []).map(q => {
          const qs = quizStatusMap.get(q.id);
          return {
            id: q.id,
            title: q.title,
            status: qs?.status ?? null,
            attempts: qs?.attempts ?? 0,
          };
        }),
      })),
    }));
  } catch (e) {
    handleError(e, 'Failed to fetch course lessons');
  }
}

export interface QuizForAttempt {
  id: number;
  title: string;
  passing_percentage: number;
  allowed_attempts: number;
  questions: any[];
  user_attempts: number;
}

export async function fetchQuizForAttempt(quizId: number, userId: number): Promise<QuizForAttempt> {
  assertConfigured();
  try {
    const { data: quiz, error } = await supabase
      .from('quizzes')
      .select('id, title, passing_percentage, allowed_attempts')
      .eq('id', quizId)
      .single();
    if (error) throw error;

    const { data: questions } = await supabase
      .from('questions')
      .select('id, "order", title, content, answer_type, options, correct_answer, table_structure')
      .eq('quiz_id', quizId)
      .order('order');

    // Count existing attempts
    const { count } = await supabase
      .from('quiz_attempts')
      .select('id', { count: 'exact', head: true })
      .eq('quiz_id', quizId)
      .eq('user_id', userId)
      .is('deleted_at', null);

    return {
      id: quiz.id,
      title: quiz.title,
      passing_percentage: quiz.passing_percentage ?? 0,
      allowed_attempts: quiz.allowed_attempts ?? 3,
      questions: (questions ?? []).map(q => ({
        ...q,
        options: typeof q.options === 'string' ? JSON.parse(q.options) : q.options,
        correct_answer: typeof q.correct_answer === 'string' ? JSON.parse(q.correct_answer) : q.correct_answer,
        table_structure: typeof q.table_structure === 'string' ? JSON.parse(q.table_structure) : q.table_structure,
      })),
      user_attempts: count ?? 0,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch quiz');
  }
}

export async function submitQuizAttempt(params: {
  userId: number;
  courseId: number;
  lessonId: number;
  topicId: number;
  quizId: number;
  questions: any[];
  submittedAnswers: Record<string, any>;
  attemptNumber: number;
}): Promise<{ id: number; status: string }> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('quiz_attempts')
      .insert({
        user_id: params.userId,
        course_id: params.courseId,
        lesson_id: params.lessonId,
        topic_id: params.topicId,
        quiz_id: params.quizId,
        questions: JSON.stringify(params.questions),
        submitted_answers: JSON.stringify(params.submittedAnswers),
        attempt: params.attemptNumber,
        status: 'SUBMITTED',
        submitted_at: new Date().toISOString(),
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      })
      .select('id, status')
      .single();
    if (error) throw error;
    return data;
  } catch (e) {
    handleError(e, 'Failed to submit quiz attempt');
  }
}

// ─── Enhanced Quiz Attempt (Question-by-Question) ─────────────────────────────
// Replaces Laravel Frontend/LMS/QuizController (1189 lines)
// Core flow: show quiz → save answer per question → auto-complete → system evaluate → view result

export interface QuizAttemptState {
  attemptId: number | null;
  quizId: number;
  courseId: number;
  lessonId: number;
  topicId: number;
  questions: any[];
  submittedAnswers: Record<string, any>;
  currentQuestionIndex: number;
  totalQuestions: number;
  isComplete: boolean;
  status: string;
  systemResult: string;
  attemptNumber: number;
  canAttempt: boolean;
  reason: string | null;
  lastAttemptStatus: string | null;
  lastAttemptResult: string | null;
}

export interface QuizResultData {
  attempt: {
    id: number;
    status: string;
    system_result: string;
    attempt: number;
    submitted_at: string | null;
    submitted_answers: Record<string, any>;
    questions: any[];
  };
  evaluation: {
    results: Record<string, { status: string; comment: string }>;
    status: string;
  } | null;
  feedback: {
    obtained: number;
    passing: number;
    message: string;
  } | null;
  quiz: {
    id: number;
    title: string;
    passing_percentage: number;
  };
  correctAnswers: Record<string, any>;
}

/**
 * Check if a quiz can be attempted and load existing attempt state.
 * Replaces Laravel QuizController::show() access control logic.
 */
export async function fetchQuizAttemptState(
  quizId: number,
  userId: number,
  courseId: number,
): Promise<QuizAttemptState> {
  assertConfigured();
  try {
    // Parallel: quiz metadata + questions + last attempt + last failed attempt
    const [quizRes, questionsRes, lastAttemptRes, inProgressRes] = await Promise.all([
      supabase.from('quizzes').select('id, title, passing_percentage, allowed_attempts, topic_id, lesson_id, course_id').eq('id', quizId).single(),
      supabase.from('questions').select('id, "order", title, content, answer_type, options, correct_answer, required, table_structure').eq('quiz_id', quizId).order('order'),
      supabase.from('quiz_attempts').select('id, status, system_result, attempt, submitted_answers, submitted_at')
        .eq('quiz_id', quizId).eq('user_id', userId).order('id', { ascending: false }).limit(1),
      supabase.from('quiz_attempts').select('id, status, system_result, attempt, submitted_answers, questions')
        .eq('quiz_id', quizId).eq('user_id', userId).eq('system_result', 'INPROGRESS').order('id', { ascending: false }).limit(1),
    ]);

    if (quizRes.error || !quizRes.data) throw quizRes.error ?? new Error('Quiz not found');

    const quiz = quizRes.data;
    const questions = (questionsRes.data ?? []).map(q => ({
      ...q,
      options: typeof q.options === 'string' ? JSON.parse(q.options) : q.options,
      correct_answer: typeof q.correct_answer === 'string' ? JSON.parse(q.correct_answer) : q.correct_answer,
      table_structure: typeof q.table_structure === 'string' ? JSON.parse(q.table_structure) : q.table_structure,
    }));

    const lastAttempt = (lastAttemptRes.data ?? [])[0] ?? null;
    const inProgress = (inProgressRes.data ?? [])[0] ?? null;

    // Access control (matching Laravel logic)
    let canAttempt = true;
    let reason: string | null = null;

    if (lastAttempt) {
      const { status, system_result, attempt: attemptNum } = lastAttempt;
      // Already submitted and waiting for result
      if (['COMPLETED', 'EVALUATED', 'MARKED'].includes(system_result) &&
          !['FAIL', 'RETURNED', 'ATTEMPTING'].includes(status)) {
        canAttempt = false;
        reason = 'Already submitted. Waiting for result.';
      }
      // Max attempts exceeded
      if (attemptNum >= (quiz.allowed_attempts ?? 999)) {
        canAttempt = false;
        reason = 'Maximum attempts reached.';
      }
      // Can re-attempt if RETURNED or FAIL
      if (['RETURNED', 'FAIL'].includes(status)) {
        canAttempt = true;
        reason = null;
      }
    }

    // If there's an in-progress attempt, resume it
    if (inProgress) {
      const existingAnswers = parseJsonSafe(inProgress.submitted_answers) ?? {};
      return {
        attemptId: inProgress.id,
        quizId: quiz.id,
        courseId: courseId || quiz.course_id,
        lessonId: quiz.lesson_id,
        topicId: quiz.topic_id,
        questions,
        submittedAnswers: existingAnswers,
        currentQuestionIndex: Object.keys(existingAnswers).length,
        totalQuestions: questions.length,
        isComplete: false,
        status: 'ATTEMPTING',
        systemResult: 'INPROGRESS',
        attemptNumber: inProgress.attempt,
        canAttempt: true,
        reason: null,
        lastAttemptStatus: lastAttempt?.status ?? null,
        lastAttemptResult: lastAttempt?.system_result ?? null,
      };
    }

    // Determine next attempt number
    let nextAttemptNumber = 1;
    if (lastAttempt) {
      if (['RETURNED', 'FAIL'].includes(lastAttempt.status)) {
        nextAttemptNumber = lastAttempt.attempt + 1;
      } else {
        nextAttemptNumber = lastAttempt.attempt;
      }
    }

    return {
      attemptId: null,
      quizId: quiz.id,
      courseId: courseId || quiz.course_id,
      lessonId: quiz.lesson_id,
      topicId: quiz.topic_id,
      questions,
      submittedAnswers: {},
      currentQuestionIndex: 0,
      totalQuestions: questions.length,
      isComplete: false,
      status: 'NOT_STARTED',
      systemResult: '',
      attemptNumber: nextAttemptNumber,
      canAttempt,
      reason,
      lastAttemptStatus: lastAttempt?.status ?? null,
      lastAttemptResult: lastAttempt?.system_result ?? null,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch quiz attempt state');
  }
}

/**
 * Save a single quiz answer (question-by-question).
 * Creates attempt on first answer, updates on subsequent.
 * Matches Laravel QuizController::attempt() + createQuizAttempt() + updateQuizAttempt().
 */
export async function saveQuizAnswer(params: {
  userId: number;
  quizId: number;
  courseId: number;
  lessonId: number;
  topicId: number;
  questionId: number;
  answer: any;
  attemptId: number | null;
  attemptNumber: number;
  questions: any[];
}): Promise<{
  attemptId: number;
  submittedAnswers: Record<string, any>;
  isComplete: boolean;
  status: string;
  systemResult: string;
  evaluationResult: Record<string, { status: string; comment: string }> | null;
  feedbackResult: { obtained: number; passing: number; message: string } | null;
}> {
  assertConfigured();
  try {
    const now = new Date().toISOString();

    if (params.attemptId) {
      // UPDATE existing attempt
      const { data: existing } = await supabase
        .from('quiz_attempts')
        .select('id, submitted_answers, questions, attempt')
        .eq('id', params.attemptId)
        .single();

      if (!existing) throw new Error('Attempt not found');

      const prevAnswers = parseJsonSafe(existing.submitted_answers) ?? {};
      const updatedAnswers = { ...prevAnswers, [String(params.questionId)]: params.answer };

      const questionsSnap = parseJsonSafe(existing.questions) ?? params.questions;
      const isComplete = Object.keys(updatedAnswers).length >= questionsSnap.length;

      if (isComplete) {
        // Fetch quiz passing_percentage for correct evaluation
        const { data: quizMeta } = await supabase.from('quizzes')
          .select('passing_percentage').eq('id', params.quizId).single();
        const passingPct = quizMeta?.passing_percentage ?? 0;

        // Complete the attempt + system evaluate
        const evalResult = runSystemEvaluation(questionsSnap, updatedAnswers, params.quizId);

        // Apply correct passing percentage to determine pass/fail
        if (evalResult.evaluation && passingPct > 0 && evalResult.feedback) {
          const obtained = evalResult.feedback.obtained;
          evalResult.status = obtained >= passingPct ? 'SATISFACTORY' : 'FAIL';
          evalResult.systemResult = 'EVALUATED';
          evalResult.feedback.passing = passingPct;
          evalResult.feedback.message = `You obtained ${obtained}%. Passing marks: ${passingPct}%.`;
        }

        await supabase.from('quiz_attempts').update({
          submitted_answers: JSON.stringify(updatedAnswers),
          system_result: evalResult.systemResult,
          status: evalResult.status,
          submitted_at: now,
          accessor_id: evalResult.evaluation ? 0 : undefined,
          accessed_at: evalResult.evaluation ? now : undefined,
          updated_at: now,
        }).eq('id', params.attemptId);

        // Create evaluation + feedback records if auto-graded
        if (evalResult.evaluation) {
          await createEvaluationAndFeedback(params.attemptId, params.quizId, params.userId, evalResult);
        }

        return {
          attemptId: params.attemptId,
          submittedAnswers: updatedAnswers,
          isComplete: true,
          status: evalResult.status,
          systemResult: evalResult.systemResult,
          evaluationResult: evalResult.evaluation,
          feedbackResult: evalResult.feedback,
        };
      } else {
        // Still in progress
        await supabase.from('quiz_attempts').update({
          submitted_answers: JSON.stringify(updatedAnswers),
          status: 'ATTEMPTING',
          updated_at: now,
        }).eq('id', params.attemptId);

        return {
          attemptId: params.attemptId,
          submittedAnswers: updatedAnswers,
          isComplete: false,
          status: 'ATTEMPTING',
          systemResult: 'INPROGRESS',
          evaluationResult: null,
          feedbackResult: null,
        };
      }
    } else {
      // CREATE new attempt
      const submittedAnswers = { [String(params.questionId)]: params.answer };
      const isComplete = params.questions.length <= 1;

      let insertData: any = {
        user_id: params.userId,
        quiz_id: params.quizId,
        course_id: params.courseId,
        lesson_id: params.lessonId,
        topic_id: params.topicId,
        questions: JSON.stringify(params.questions),
        submitted_answers: JSON.stringify(submittedAnswers),
        attempt: params.attemptNumber,
        system_result: 'INPROGRESS',
        status: 'ATTEMPTING',
        user_ip: '0.0.0.0',
        created_at: now,
        updated_at: now,
      };

      if (isComplete) {
        // Fetch quiz passing_percentage for correct evaluation
        const { data: quizMeta } = await supabase.from('quizzes')
          .select('passing_percentage').eq('id', params.quizId).single();
        const passingPct = quizMeta?.passing_percentage ?? 0;

        const evalResult = runSystemEvaluation(params.questions, submittedAnswers, params.quizId);

        // Apply correct passing percentage
        if (evalResult.evaluation && passingPct > 0 && evalResult.feedback) {
          const obtained = evalResult.feedback.obtained;
          evalResult.status = obtained >= passingPct ? 'SATISFACTORY' : 'FAIL';
          evalResult.systemResult = 'EVALUATED';
          evalResult.feedback.passing = passingPct;
          evalResult.feedback.message = `You obtained ${obtained}%. Passing marks: ${passingPct}%.`;
        }

        insertData.system_result = evalResult.systemResult;
        insertData.status = evalResult.status;
        insertData.submitted_at = now;
        if (evalResult.evaluation) {
          insertData.accessor_id = 0;
          insertData.accessed_at = now;
        }

        const { data: newAttempt, error } = await supabase.from('quiz_attempts')
          .insert(insertData).select('id').single();
        if (error) throw error;

        if (evalResult.evaluation) {
          await createEvaluationAndFeedback(newAttempt.id, params.quizId, params.userId, evalResult);
        }

        return {
          attemptId: newAttempt.id,
          submittedAnswers,
          isComplete: true,
          status: evalResult.status,
          systemResult: evalResult.systemResult,
          evaluationResult: evalResult.evaluation,
          feedbackResult: evalResult.feedback,
        };
      }

      const { data: newAttempt, error } = await supabase.from('quiz_attempts')
        .insert(insertData).select('id').single();
      if (error) throw error;

      return {
        attemptId: newAttempt.id,
        submittedAnswers,
        isComplete: false,
        status: 'ATTEMPTING',
        systemResult: 'INPROGRESS',
        evaluationResult: null,
        feedbackResult: null,
      };
    }
  } catch (e) {
    handleError(e, 'Failed to save quiz answer');
  }
}

/**
 * System evaluation — auto-grade quizzes with passing_percentage > 0.
 * Matches Laravel QuizController::systemEvaluation().
 */
function runSystemEvaluation(
  questions: any[],
  submittedAnswers: Record<string, any>,
  quizId: number,
): {
  systemResult: string;
  status: string;
  evaluation: Record<string, { status: string; comment: string }> | null;
  feedback: { obtained: number; passing: number; message: string } | null;
  passingPercentage: number;
} {
  // We need to check the quiz's passing_percentage
  // Since we already have questions with correct_answer, check if any have correct answers
  const questionsWithCorrectAnswers = questions.filter(q => q.correct_answer != null && q.correct_answer !== '');

  if (questionsWithCorrectAnswers.length === 0) {
    // No auto-grading — just mark as COMPLETED/SUBMITTED for trainer review
    return { systemResult: 'COMPLETED', status: 'SUBMITTED', evaluation: null, feedback: null, passingPercentage: 0 };
  }

  // Auto-grade
  const results: Record<string, { status: string; comment: string }> = {};
  const correctIds: number[] = [];
  const totalQuestions = questions.length;

  for (const question of questions) {
    const qId = String(question.id);
    const correctAnswer = question.correct_answer;
    const submittedAnswer = submittedAnswers[qId];

    if (correctAnswer == null || correctAnswer === '') continue;

    let isCorrect = false;
    if (question.answer_type === 'MCQ') {
      // MCQ: compare arrays
      const ca = Array.isArray(correctAnswer) ? correctAnswer : JSON.parse(correctAnswer);
      const sa = Array.isArray(submittedAnswer) ? submittedAnswer : [];
      isCorrect = JSON.stringify(ca.sort()) === JSON.stringify(sa.sort());
    } else {
      // SINGLE / other: compare as integers or strings
      isCorrect = String(correctAnswer) === String(submittedAnswer);
    }

    if (isCorrect) {
      correctIds.push(question.id);
      results[qId] = { status: 'correct', comment: 'Marked by System' };
    } else {
      results[qId] = { status: 'incorrect', comment: 'Marked by System' };
    }
  }

  const obtainedPercentage = totalQuestions > 0 ? Math.round((correctIds.length / totalQuestions) * 100) : 0;

  // We need the passing_percentage to determine pass/fail
  // Since it's not passed in, we check: if we have results, we assume it's auto-graded
  // The quiz's passing_percentage will be checked by the caller — but for now use the results
  // We'll determine pass/fail based on whether correctIds.length > 0
  // Actually, we need to pass passingPercentage from the quiz. Let's just mark as EVALUATED
  // and let the feedback show the score.

  return {
    systemResult: 'EVALUATED',
    status: obtainedPercentage >= 50 ? 'SATISFACTORY' : 'FAIL', // Default 50%, overridden below
    evaluation: results,
    feedback: {
      obtained: obtainedPercentage,
      passing: 0, // Will be set by caller
      message: `You obtained ${obtainedPercentage}%.`,
    },
    passingPercentage: 0,
  };
}

/**
 * Enhanced system evaluation that knows the quiz's passing percentage.
 */
export async function runQuizSystemEvaluation(
  attemptId: number,
  quizId: number,
  userId: number,
): Promise<{
  status: string;
  systemResult: string;
  evaluation: Record<string, { status: string; comment: string }> | null;
  feedback: { obtained: number; passing: number; message: string } | null;
}> {
  assertConfigured();
  try {
    const [attemptRes, quizRes] = await Promise.all([
      supabase.from('quiz_attempts').select('id, questions, submitted_answers').eq('id', attemptId).single(),
      supabase.from('quizzes').select('id, passing_percentage').eq('id', quizId).single(),
    ]);

    if (!attemptRes.data || !quizRes.data) throw new Error('Not found');

    const questions = parseJsonSafe(attemptRes.data.questions) ?? [];
    const answers = parseJsonSafe(attemptRes.data.submitted_answers) ?? {};
    const passingPct = quizRes.data.passing_percentage ?? 0;

    if (passingPct <= 0) {
      // No auto-grading
      return { status: 'SUBMITTED', systemResult: 'COMPLETED', evaluation: null, feedback: null };
    }

    const evalResult = runSystemEvaluation(questions, answers, quizId);

    // Apply correct passing percentage
    const obtained = evalResult.feedback?.obtained ?? 0;
    const finalStatus = obtained >= passingPct ? 'SATISFACTORY' : 'FAIL';

    const now = new Date().toISOString();
    await supabase.from('quiz_attempts').update({
      system_result: 'EVALUATED',
      status: finalStatus,
      accessor_id: 0,
      accessed_at: now,
      updated_at: now,
    }).eq('id', attemptId);

    const feedback = {
      obtained,
      passing: passingPct,
      message: `You obtained ${obtained}%. Passing marks: ${passingPct}%.`,
    };

    if (evalResult.evaluation) {
      await createEvaluationAndFeedback(attemptId, quizId, userId, {
        ...evalResult,
        status: finalStatus,
        feedback,
      });
    }

    return {
      status: finalStatus,
      systemResult: 'EVALUATED',
      evaluation: evalResult.evaluation,
      feedback,
    };
  } catch (e) {
    handleError(e, 'Failed to run system evaluation');
  }
}

/**
 * Create evaluation + feedback records in DB.
 * Matches Laravel's Evaluation + Feedback model creation.
 */
async function createEvaluationAndFeedback(
  attemptId: number,
  quizId: number,
  userId: number,
  evalResult: {
    evaluation: Record<string, { status: string; comment: string }> | null;
    status: string;
    feedback: { obtained: number; passing: number; message: string } | null;
  },
): Promise<void> {
  const now = new Date().toISOString();

  // Create evaluation record (polymorphic on QuizAttempt)
  if (evalResult.evaluation) {
    await supabase.from('evaluations').insert({
      results: JSON.stringify(evalResult.evaluation),
      evaluable_type: 'App\\Models\\QuizAttempt',
      evaluable_id: attemptId,
      status: evalResult.status === 'FAIL' ? 'UNSATISFACTORY' : 'SATISFACTORY',
      evaluator_id: 0,
      student_id: userId,
      created_at: now,
      updated_at: now,
    });
  }

  // Create feedback record (polymorphic on Quiz)
  if (evalResult.feedback) {
    await supabase.from('feedbacks').insert({
      body: JSON.stringify(evalResult.feedback),
      attachable_type: 'App\\Models\\Quiz',
      attachable_id: quizId,
      user_id: userId,
      owner_id: 0,
      created_at: now,
      updated_at: now,
    });
  }
}

/**
 * Fetch quiz result for viewing (student-facing).
 * Matches Laravel QuizController::viewResult().
 */
export async function fetchQuizResult(
  quizId: number,
  attemptId: number,
  userId: number,
): Promise<QuizResultData> {
  assertConfigured();
  try {
    const [attemptRes, quizRes, evalRes, feedbackRes] = await Promise.all([
      supabase.from('quiz_attempts').select('id, status, system_result, attempt, submitted_at, submitted_answers, questions')
        .eq('id', attemptId).single(),
      supabase.from('quizzes').select('id, title, passing_percentage').eq('id', quizId).single(),
      supabase.from('evaluations').select('id, results, status')
        .eq('evaluable_type', 'App\\Models\\QuizAttempt').eq('evaluable_id', attemptId)
        .order('id', { ascending: false }).limit(1),
      supabase.from('feedbacks').select('id, body')
        .eq('attachable_type', 'App\\Models\\Quiz').eq('attachable_id', quizId)
        .eq('user_id', userId)
        .order('id', { ascending: false }).limit(1),
    ]);

    if (!attemptRes.data || !quizRes.data) throw new Error('Not found');

    const attempt = attemptRes.data;
    const questions = parseJsonSafe(attempt.questions) ?? [];
    const submittedAnswers = parseJsonSafe(attempt.submitted_answers) ?? {};

    // Build correct answers map from questions snapshot
    const correctAnswers: Record<string, any> = {};
    for (const q of questions) {
      if (q.correct_answer != null) {
        correctAnswers[String(q.id)] = typeof q.correct_answer === 'string'
          ? JSON.parse(q.correct_answer) : q.correct_answer;
      }
    }

    const evalData = (evalRes.data ?? [])[0];
    const evalResults = evalData ? (parseJsonSafe(evalData.results) ?? {}) : null;

    const feedbackData = (feedbackRes.data ?? [])[0];
    const feedbackBody = feedbackData ? (parseJsonSafe(feedbackData.body) ?? null) : null;

    return {
      attempt: {
        ...attempt,
        submitted_answers: submittedAnswers,
        questions: questions.map((q: any) => ({
          ...q,
          options: typeof q.options === 'string' ? JSON.parse(q.options) : q.options,
          table_structure: typeof q.table_structure === 'string' ? JSON.parse(q.table_structure) : q.table_structure,
        })),
      },
      evaluation: evalData ? { results: evalResults, status: evalData.status } : null,
      feedback: feedbackBody,
      quiz: quizRes.data,
      correctAnswers,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch quiz result');
  }
}

/**
 * Fetch the last completed/evaluated attempt for a quiz (for result viewing from course list).
 */
export async function fetchLastQuizAttempt(
  quizId: number,
  userId: number,
): Promise<{ id: number; status: string; system_result: string; attempt: number } | null> {
  assertConfigured();
  try {
    const { data } = await supabase
      .from('quiz_attempts')
      .select('id, status, system_result, attempt')
      .eq('quiz_id', quizId)
      .eq('user_id', userId)
      .neq('system_result', 'INPROGRESS')
      .order('id', { ascending: false })
      .limit(1);
    return (data ?? [])[0] ?? null;
  } catch {
    return null;
  }
}

// ─── Student Training Plan ────────────────────────────────────────────────────
// Replaces Laravel StudentTrainingPlanService (2631 lines → ~250 lines)
// Queries source-of-truth tables directly instead of cached JSON

export interface TrainingPlanAttempt {
  id: number;
  quiz_id: number;
  status: string;
  system_result: string | null;
  attempt: number;
  submitted_at: string | null;
  accessed_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface TrainingPlanQuiz {
  id: number;
  title: string;
  topic_id: number;
  has_checklist: number;
  status: string;           // ATTEMPTING | SUBMITTED | SATISFACTORY | NOT SATISFACTORY
  attempts: TrainingPlanAttempt[];
  checklist?: { status: string; count: number };
}

export interface TrainingPlanTopic {
  id: number;
  title: string;
  order: number;
  has_quiz: number;
  lesson_id: number;
  status: string;           // ATTEMPTING | SUBMITTED | COMPLETED
  quizzes: TrainingPlanQuiz[];
}

export interface TrainingPlanLesson {
  id: number;
  title: string;
  order: number;
  has_topic: number;
  has_work_placement: number;
  release_key: string | null;
  release_value: string | null;
  course_id: number;
  status: string;           // ATTEMPTING | SUBMITTED | COMPLETED
  is_marked_complete: boolean;
  marked_at: string | null;
  is_unlocked: boolean;
  competency: {
    is_competent: boolean;
    competent_on: string | null;
    notes: any;
  } | null;
  work_placement_complete: boolean;
  topics: TrainingPlanTopic[];
}

export interface TrainingPlanCourse {
  course_id: number;
  course_title: string;
  course_code: string | null;
  is_main_course: boolean;
  category: string | null;
  status: string;           // ATTEMPTING | SUBMITTED | COMPLETED
  percentage: number;
  expected_percentage: number;
  enrolment: {
    id: number;
    status: string;
    course_start_at: string | null;
    course_ends_at: string | null;
    course_completed_at: string | null;
  } | null;
  lessons: TrainingPlanLesson[];
}

export type StudentTrainingPlan = TrainingPlanCourse[];

function parseJsonSafe(text: string | null): any {
  if (!text) return null;
  try { return JSON.parse(text); } catch { return null; }
}

function deriveQuizStatus(attempts: TrainingPlanAttempt[]): string {
  if (attempts.length === 0) return 'ATTEMPTING';
  const latest = attempts[0]; // sorted desc by created_at
  if (latest.status === 'SATISFACTORY') return 'SATISFACTORY';
  if (['FAIL', 'RETURNED', 'NOT SATISFACTORY'].includes(latest.status)) return 'NOT SATISFACTORY';
  if (['SUBMITTED', 'EVALUATED', 'MARKED', 'REVIEWING'].includes(latest.status)) return 'SUBMITTED';
  return 'ATTEMPTING';
}

function deriveTopicStatus(quizzes: TrainingPlanQuiz[], markedAt: string | null): string {
  if (markedAt) return 'COMPLETED';
  if (quizzes.length === 0) return 'ATTEMPTING';
  const allPassed = quizzes.every(q => q.status === 'SATISFACTORY');
  if (allPassed) return 'COMPLETED';
  const anySubmitted = quizzes.some(q => q.status === 'SUBMITTED' || q.status === 'SATISFACTORY');
  return anySubmitted ? 'SUBMITTED' : 'ATTEMPTING';
}

function deriveLessonStatus(topics: TrainingPlanTopic[], markedAt: string | null): string {
  if (markedAt) return 'COMPLETED';
  if (topics.length === 0) return 'ATTEMPTING';
  const allCompleted = topics.every(t => t.status === 'COMPLETED');
  if (allCompleted) return 'COMPLETED';
  const anySubmitted = topics.some(t => t.status === 'SUBMITTED' || t.status === 'COMPLETED');
  return anySubmitted ? 'SUBMITTED' : 'ATTEMPTING';
}

function deriveCourseStatus(lessons: TrainingPlanLesson[]): string {
  if (lessons.length === 0) return 'ATTEMPTING';
  const allCompleted = lessons.every(l => l.status === 'COMPLETED');
  if (allCompleted) return 'COMPLETED';
  const anySubmitted = lessons.some(l => l.status === 'SUBMITTED' || l.status === 'COMPLETED');
  return anySubmitted ? 'SUBMITTED' : 'ATTEMPTING';
}

function computeExpectedPercentage(startAt: string | null, endsAt: string | null): number {
  if (!startAt || !endsAt) return 0;
  const start = new Date(startAt).getTime();
  const end = new Date(endsAt).getTime();
  const now = Date.now();
  if (now < start) return 0;
  if (now >= end) return 100;
  const total = end - start;
  if (total <= 0) return 0;
  return Math.min(100, Math.round(((now - start) / total) * 100));
}

function computePercentage(lessons: TrainingPlanLesson[]): number {
  let total = 0;
  let passed = 0;
  for (const lesson of lessons) {
    total++; // lesson itself counts
    if (lesson.status === 'COMPLETED') passed++;
    for (const topic of lesson.topics) {
      total++;
      if (topic.status === 'COMPLETED') passed++;
      for (const quiz of topic.quizzes) {
        total++;
        if (quiz.status === 'SATISFACTORY') passed++;
      }
    }
  }
  if (total === 0) return 0;
  return Math.min(100, Math.round((passed / total) * 100 * 100) / 100);
}

/**
 * Fetches the full training plan for a student — the core migration of Laravel's
 * StudentTrainingPlanService::getTrainingPlan().
 * Queries source-of-truth tables directly instead of the cached course_progress.details JSON.
 */
export async function fetchStudentTrainingPlan(userId: number): Promise<StudentTrainingPlan> {
  assertConfigured();
  try {
    // 1. Fetch enrolments (skip DELIST)
    const { data: enrolments, error: enrolErr } = await supabase
      .from('student_course_enrolments')
      .select('id, course_id, status, course_start_at, course_ends_at, course_completed_at')
      .eq('user_id', userId)
      .neq('status', 'DELIST');
    if (enrolErr) throw enrolErr;
    if (!enrolments || enrolments.length === 0) return [];

    const courseIds = Array.from(new Set(enrolments.map(e => e.course_id)));

    // 2. Parallel fetch: courses, lessons, quizzes, attempts, competencies, unlocks, progress, checklists, work placements
    const [
      coursesRes, lessonsRes, quizzesRes, attemptsRes,
      competenciesRes, unlocksRes, progressRes, checklistsRes, workPlacementsRes,
    ] = await Promise.all([
      supabase.from('courses').select('id, title, slug, is_main_course, category').in('id', courseIds),
      supabase.from('lessons').select('id, course_id, title, "order", has_topic, has_work_placement, release_key, release_value').in('course_id', courseIds).order('order'),
      supabase.from('quizzes').select('id, title, topic_id, lesson_id, course_id, has_checklist').in('course_id', courseIds).order('order'),
      supabase.from('quiz_attempts').select('id, quiz_id, status, system_result, attempt, submitted_at, accessed_at, created_at, updated_at')
        .eq('user_id', userId).in('course_id', courseIds).order('created_at', { ascending: false }),
      supabase.from('competencies').select('id, lesson_id, is_competent, competent_on, course_start, lesson_start, lesson_end, notes')
        .eq('user_id', userId).in('course_id', courseIds),
      supabase.from('lesson_unlocks').select('lesson_id, course_id, unlocked_at')
        .eq('user_id', userId).in('course_id', courseIds),
      supabase.from('course_progress').select('course_id, percentage, details')
        .eq('user_id', userId).in('course_id', courseIds),
      supabase.from('student_lms_attachables').select('id, attachable_id, attachable_type, event, properties, student_id')
        .eq('student_id', userId).eq('event', 'CHECKLIST'),
      supabase.from('student_lms_attachables').select('id, attachable_id, attachable_type, event, student_id')
        .eq('student_id', userId).eq('event', 'WORK_PLACEMENT'),
    ]);

    const courses = coursesRes.data ?? [];
    const allLessons = lessonsRes.data ?? [];
    const quizzes = quizzesRes.data ?? [];
    const attempts = attemptsRes.data ?? [];
    const competencies = competenciesRes.data ?? [];
    const unlocks = unlocksRes.data ?? [];
    const progressRows = progressRes.data ?? [];
    const checklists = checklistsRes.data ?? [];
    const workPlacements = workPlacementsRes.data ?? [];

    // Now fetch topics using actual lesson IDs
    const lessonIds = allLessons.map(l => l.id);
    const { data: allTopics } = lessonIds.length > 0
      ? await supabase.from('topics').select('id, lesson_id, title, "order", has_quiz').in('lesson_id', lessonIds).order('order')
      : { data: [] };
    const topics = allTopics ?? [];

    // Build lookup maps
    const courseMap = new Map(courses.map(c => [c.id, c]));
    const enrolmentMap = new Map(enrolments.map(e => [e.course_id, e]));
    const lessonsByCourse = new Map<number, typeof allLessons>();
    allLessons.forEach(l => {
      if (!lessonsByCourse.has(l.course_id)) lessonsByCourse.set(l.course_id, []);
      lessonsByCourse.get(l.course_id)!.push(l);
    });
    const topicsByLesson = new Map<number, typeof topics>();
    topics.forEach(t => {
      if (!topicsByLesson.has(t.lesson_id)) topicsByLesson.set(t.lesson_id, []);
      topicsByLesson.get(t.lesson_id)!.push(t);
    });
    const quizzesByTopic = new Map<number, typeof quizzes>();
    quizzes.forEach(q => {
      const key = q.topic_id ?? 0;
      if (!quizzesByTopic.has(key)) quizzesByTopic.set(key, []);
      quizzesByTopic.get(key)!.push(q);
    });
    const attemptsByQuiz = new Map<number, TrainingPlanAttempt[]>();
    attempts.forEach(a => {
      if (!attemptsByQuiz.has(a.quiz_id)) attemptsByQuiz.set(a.quiz_id, []);
      const list = attemptsByQuiz.get(a.quiz_id)!;
      if (list.length < 3) list.push(a); // keep latest 3
    });
    const competencyByLesson = new Map(competencies.map(c => [c.lesson_id, c]));
    const unlockSet = new Set(unlocks.map(u => `${u.lesson_id}_${u.course_id}`));

    // Checklist map: quiz_id → checklist items
    const checklistByQuiz = new Map<number, typeof checklists>();
    checklists.forEach(cl => {
      if (cl.attachable_type?.includes('Quiz')) {
        if (!checklistByQuiz.has(cl.attachable_id)) checklistByQuiz.set(cl.attachable_id, []);
        checklistByQuiz.get(cl.attachable_id)!.push(cl);
      }
    });

    // Work placement map: lesson_id → boolean
    const wpByLesson = new Set<number>();
    workPlacements.forEach(wp => {
      if (wp.attachable_type?.includes('Lesson')) wpByLesson.add(wp.attachable_id);
    });

    // Parse progress.details for marked_at info (this is where Laravel stored mark-complete timestamps)
    const progressDetailsMap = new Map<number, any>();
    progressRows.forEach(p => {
      const details = parseJsonSafe(p.details);
      if (details) progressDetailsMap.set(p.course_id, details);
    });

    // 3. Build hierarchical training plan
    const plan: StudentTrainingPlan = [];

    for (const courseId of courseIds) {
      const course = courseMap.get(courseId);
      const enrolment = enrolmentMap.get(courseId);
      if (!course || !enrolment) continue;

      const courseLessons = lessonsByCourse.get(courseId) ?? [];
      const progressDetails = progressDetailsMap.get(courseId);

      const planLessons: TrainingPlanLesson[] = [];

      for (const lesson of courseLessons) {
        const lessonTopics = topicsByLesson.get(lesson.id) ?? [];
        const comp = competencyByLesson.get(lesson.id);
        const isUnlocked = unlockSet.has(`${lesson.id}_${courseId}`);

        // Get marked_at from progress details JSON
        const lessonDetails = progressDetails?.lessons?.list?.[String(lesson.id)];
        const markedAt = lessonDetails?.marked_at ?? null;

        const planTopics: TrainingPlanTopic[] = [];

        for (const topic of lessonTopics) {
          const topicQuizzes = quizzesByTopic.get(topic.id) ?? [];
          const topicDetails = lessonDetails?.topics?.list?.[String(topic.id)];
          const topicMarkedAt = topicDetails?.marked_at ?? null;

          const planQuizzes: TrainingPlanQuiz[] = [];

          for (const quiz of topicQuizzes) {
            const qAttempts = attemptsByQuiz.get(quiz.id) ?? [];
            const qStatus = deriveQuizStatus(qAttempts);

            // Checklist info
            const cls = checklistByQuiz.get(quiz.id) ?? [];
            const checklistInfo = quiz.has_checklist ? {
              status: cls.length === 0 ? 'NOT ATTEMPTED' : (() => {
                const latestProps = parseJsonSafe((cls[0] as any)?.properties);
                return latestProps?.status ?? 'NOT ATTEMPTED';
              })(),
              count: cls.length,
            } : undefined;

            planQuizzes.push({
              id: quiz.id,
              title: quiz.title,
              topic_id: quiz.topic_id,
              has_checklist: quiz.has_checklist ?? 0,
              status: qStatus,
              attempts: qAttempts,
              checklist: checklistInfo,
            });
          }

          const topicStatus = deriveTopicStatus(planQuizzes, topicMarkedAt);

          planTopics.push({
            id: topic.id,
            title: topic.title,
            order: topic.order,
            has_quiz: topic.has_quiz,
            lesson_id: topic.lesson_id,
            status: topicStatus,
            quizzes: planQuizzes,
          });
        }

        const lessonStatus = deriveLessonStatus(planTopics, markedAt);

        planLessons.push({
          id: lesson.id,
          title: lesson.title,
          order: lesson.order,
          has_topic: lesson.has_topic,
          has_work_placement: lesson.has_work_placement ?? 0,
          release_key: lesson.release_key,
          release_value: lesson.release_value ?? null,
          course_id: lesson.course_id,
          status: lessonStatus,
          is_marked_complete: !!markedAt,
          marked_at: markedAt,
          is_unlocked: isUnlocked,
          competency: comp ? {
            is_competent: comp.is_competent === 1,
            competent_on: comp.competent_on,
            notes: parseJsonSafe(comp.notes),
          } : null,
          work_placement_complete: wpByLesson.has(lesson.id),
          topics: planTopics,
        });
      }

      const courseStatus = deriveCourseStatus(planLessons);
      const percentage = computePercentage(planLessons);
      const expectedPct = computeExpectedPercentage(enrolment.course_start_at, enrolment.course_ends_at);

      plan.push({
        course_id: courseId,
        course_title: course.title,
        course_code: course.slug,
        is_main_course: course.is_main_course === 1,
        category: course.category,
        status: courseStatus,
        percentage,
        expected_percentage: expectedPct,
        enrolment: {
          id: enrolment.id,
          status: enrolment.status,
          course_start_at: enrolment.course_start_at,
          course_ends_at: enrolment.course_ends_at,
          course_completed_at: enrolment.course_completed_at,
        },
        lessons: planLessons,
      });
    }

    return plan;
  } catch (e) {
    handleError(e, 'Failed to fetch student training plan');
  }
}

// ─── Training Plan Admin Actions ──────────────────────────────────────────────

/** Mark a lesson as complete for a student (admin action) */
export async function markLessonComplete(userId: number, lessonId: number, courseId: number): Promise<void> {
  assertConfigured();
  try {
    const now = new Date().toISOString();
    // Update course_progress.details JSON to set lesson.marked_at
    const { data: progress } = await supabase
      .from('course_progress')
      .select('id, details')
      .eq('user_id', userId)
      .eq('course_id', courseId)
      .single();

    if (progress) {
      const details = parseJsonSafe(progress.details) ?? {};
      if (details.lessons?.list?.[String(lessonId)]) {
        details.lessons.list[String(lessonId)].marked_at = now;
        details.lessons.list[String(lessonId)].completed = true;
        details.lessons.list[String(lessonId)].completed_at = now;
      }
      await supabase.from('course_progress')
        .update({ details: JSON.stringify(details), updated_at: now })
        .eq('id', progress.id);
    }
  } catch (e) {
    handleError(e, 'Failed to mark lesson complete');
  }
}

/** Mark a topic as complete for a student (admin action) */
export async function markTopicComplete(userId: number, topicId: number, lessonId: number, courseId: number): Promise<void> {
  assertConfigured();
  try {
    const now = new Date().toISOString();
    const { data: progress } = await supabase
      .from('course_progress')
      .select('id, details')
      .eq('user_id', userId)
      .eq('course_id', courseId)
      .single();

    if (progress) {
      const details = parseJsonSafe(progress.details) ?? {};
      const topicPath = details.lessons?.list?.[String(lessonId)]?.topics?.list?.[String(topicId)];
      if (topicPath) {
        topicPath.marked_at = now;
        topicPath.completed = true;
        topicPath.completed_at = now;
      }
      await supabase.from('course_progress')
        .update({ details: JSON.stringify(details), updated_at: now })
        .eq('id', progress.id);
    }
  } catch (e) {
    handleError(e, 'Failed to mark topic complete');
  }
}

/** Mark a lesson as competent for a student (admin action) */
export async function markLessonCompetent(
  userId: number,
  lessonId: number,
  courseId: number,
  params: { competent_on: string; course_start?: string; lesson_start?: string; lesson_end?: string; notes?: string }
): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('competencies').upsert({
      user_id: userId,
      lesson_id: lessonId,
      course_id: courseId,
      is_competent: 1,
      competent_on: params.competent_on,
      course_start: params.course_start ?? null,
      lesson_start: params.lesson_start ?? null,
      lesson_end: params.lesson_end ?? null,
      notes: params.notes ? JSON.stringify({ added_by: { user_id: userId }, text: params.notes }) : null,
      updated_at: new Date().toISOString(),
    }, { onConflict: 'user_id,lesson_id,course_id' });
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to mark lesson competent');
  }
}

/** Unlock a lesson for a student (admin action) */
export async function unlockLesson(lessonId: number, userId: number, courseId: number, unlockedBy: number): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('lesson_unlocks').upsert({
      lesson_id: lessonId,
      user_id: userId,
      course_id: courseId,
      unlocked_by: unlockedBy,
      unlocked_at: new Date().toISOString(),
    }, { onConflict: 'lesson_id,user_id,course_id' });
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to unlock lesson');
  }
}

/** Lock a lesson for a student (admin action — removes unlock) */
export async function lockLesson(lessonId: number, userId: number, courseId: number): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('lesson_unlocks')
      .delete()
      .eq('lesson_id', lessonId)
      .eq('user_id', userId)
      .eq('course_id', courseId);
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to lock lesson');
  }
}

/** Mark work placement complete for a lesson */
export async function markWorkPlacementComplete(studentId: number, lessonId: number, causerId: number): Promise<void> {
  assertConfigured();
  try {
    const { error } = await supabase.from('student_lms_attachables').insert({
      student_id: studentId,
      event: 'WORK_PLACEMENT',
      attachable_type: 'App\\Models\\Lesson',
      attachable_id: lessonId,
      description: 'Work placement marked complete',
      causer_type: 'App\\Models\\User',
      causer_id: causerId,
    });
    if (error) throw error;
  } catch (e) {
    handleError(e, 'Failed to mark work placement complete');
  }
}

// ─── Student Onboarding ───────────────────────────────────────────────────────
// Replaces Laravel EnrolmentController (1484 lines)
// 6-step wizard: Personal → Education → Employer → Requirements → PTR Quiz → Agreement

// Config constants matching Laravel's config/ptr.php and config/lln.php
const PTR_QUIZ_ID = 11112;
const PTR_EXCLUDED_CATEGORIES = ['non_accredited', 'accelerator'];
const PTR_IMPLEMENTATION_DATE = '2025-09-01';

export interface OnboardingStep {
  number: number;
  title: string;
  slug: string;
  completed: boolean;
  disabled: boolean;
}

export interface OnboardingState {
  enrolmentId: number | null;
  enrolmentKey: string;
  isActive: boolean;
  isReEnrolment: boolean;
  currentStep: number;
  steps: OnboardingStep[];
  stepData: Record<string, any>;
  ptrRequired: boolean;
  ptrCompleted: boolean;
  ptrExcluded: boolean;
}

export interface OnboardingStepData {
  [key: string]: any;
}

/**
 * Fetch the current onboarding state for a user.
 * Reads the enrolments table + checks PTR requirement.
 */
export async function fetchOnboardingState(userId: number): Promise<OnboardingState> {
  assertConfigured();
  try {
    // 1. Find active enrolment (onboard or onboard{N})
    const { data: enrolments, error: enrolErr } = await supabase
      .from('enrolments')
      .select('id, user_id, enrolment_key, enrolment_value, is_active')
      .eq('user_id', userId)
      .or('enrolment_key.eq.onboard,enrolment_key.like.onboard%')
      .order('id', { ascending: false });
    if (enrolErr) throw enrolErr;

    // Find active enrolment
    const activeEnrolment = (enrolments ?? []).find(e =>
      e.is_active === 1 && (e.enrolment_key === 'onboard' || /^onboard\d+$/.test(e.enrolment_key))
    );

    // Check if re-enrolment (any enrolment has step-6)
    const isReEnrolment = (enrolments ?? []).some(e => {
      const val = parseJsonSafe(e.enrolment_value);
      return val && val['step-6'];
    });

    // Parse step data
    const stepData = activeEnrolment ? (parseJsonSafe(activeEnrolment.enrolment_value) ?? {}) : {};
    const completedSteps = Object.keys(stepData)
      .filter(k => k.startsWith('step-'))
      .map(k => parseInt(k.replace('step-', '')));
    const maxStep = completedSteps.length > 0 ? Math.max(...completedSteps) : 0;
    const currentStep = maxStep >= 6 ? 0 : maxStep + 1; // 0 means fully complete

    // 2. Check PTR requirement
    const ptrCheck = await checkPtrRequirement(userId);

    // 3. Build steps
    const stepDefs = [
      { number: 1, title: 'Personal Info' },
      { number: 2, title: 'Education Details' },
      { number: 3, title: 'Employer Details' },
      { number: 4, title: 'Requirements' },
      { number: 5, title: 'Pre-Training Review' },
      { number: 6, title: 'Agreement' },
    ];
    const steps: OnboardingStep[] = stepDefs.map(s => ({
      ...s,
      slug: `step-${s.number}`,
      completed: completedSteps.includes(s.number),
      disabled: s.number === 5 && (isReEnrolment || ptrCheck.excluded),
    }));

    return {
      enrolmentId: activeEnrolment?.id ?? null,
      enrolmentKey: activeEnrolment?.enrolment_key ?? 'onboard',
      isActive: !!activeEnrolment?.is_active,
      isReEnrolment,
      currentStep: currentStep || 1,
      steps,
      stepData,
      ptrRequired: ptrCheck.required,
      ptrCompleted: ptrCheck.completed,
      ptrExcluded: ptrCheck.excluded,
    };
  } catch (e) {
    handleError(e, 'Failed to fetch onboarding state');
  }
}

/**
 * Check whether PTR is required for a user, excluded, or already completed.
 */
export async function checkPtrRequirement(userId: number): Promise<{
  required: boolean;
  completed: boolean;
  excluded: boolean;
  courseId: number | null;
}> {
  assertConfigured();
  try {
    // Find main course enrolment
    const { data: mainEnrolment } = await supabase
      .from('student_course_enrolments')
      .select('id, course_id, is_main_course, status, created_at')
      .eq('user_id', userId)
      .eq('is_main_course', 1)
      .neq('status', 'DELIST')
      .limit(1)
      .single();

    if (!mainEnrolment) {
      return { required: false, completed: true, excluded: true, courseId: null };
    }

    // Check course category for exclusion
    const { data: course } = await supabase
      .from('courses')
      .select('id, category, title')
      .eq('id', mainEnrolment.course_id)
      .single();

    if (!course) {
      return { required: false, completed: true, excluded: true, courseId: null };
    }

    // Check if Semester 2
    if (course.title?.toLowerCase().includes('semester 2')) {
      return { required: false, completed: true, excluded: true, courseId: course.id };
    }

    // Check category exclusion
    if (PTR_EXCLUDED_CATEGORIES.includes(course.category?.toLowerCase() ?? '')) {
      return { required: false, completed: true, excluded: true, courseId: course.id };
    }

    // Check grandfathering
    const enrolDate = new Date(mainEnrolment.created_at);
    const implDate = new Date(PTR_IMPLEMENTATION_DATE);
    if (enrolDate < implDate) {
      return { required: false, completed: true, excluded: true, courseId: course.id };
    }

    // PTR is required — check if already completed
    const { count } = await supabase
      .from('quiz_attempts')
      .select('id', { count: 'exact', head: true })
      .eq('user_id', userId)
      .eq('quiz_id', PTR_QUIZ_ID)
      .eq('course_id', mainEnrolment.course_id)
      .neq('system_result', 'INPROGRESS')
      .neq('status', 'ATTEMPTING');

    return {
      required: true,
      completed: (count ?? 0) > 0,
      excluded: false,
      courseId: course.id,
    };
  } catch {
    return { required: false, completed: true, excluded: true, courseId: null };
  }
}

/**
 * Save data for a single onboarding step.
 * Creates or updates the enrolment record in the enrolments table.
 */
export async function saveOnboardingStep(
  userId: number,
  step: number,
  data: OnboardingStepData,
): Promise<{ enrolmentId: number; nextStep: number }> {
  assertConfigured();
  try {
    const stepKey = `step-${step}`;

    // Find existing active enrolment
    const { data: existing } = await supabase
      .from('enrolments')
      .select('id, enrolment_key, enrolment_value, is_active')
      .eq('user_id', userId)
      .or('enrolment_key.eq.onboard,enrolment_key.like.onboard%')
      .eq('is_active', 1)
      .order('id', { ascending: false })
      .limit(1)
      .single();

    let enrolmentValue: Record<string, any> = {};
    let enrolmentKey = 'onboard';
    let enrolmentId: number;

    if (existing) {
      enrolmentValue = parseJsonSafe(existing.enrolment_value) ?? {};
      enrolmentKey = existing.enrolment_key;
      enrolmentValue[stepKey] = data;

      const { error } = await supabase.from('enrolments').update({
        enrolment_value: JSON.stringify(enrolmentValue),
        updated_at: new Date().toISOString(),
      }).eq('id', existing.id);
      if (error) throw error;
      enrolmentId = existing.id;
    } else {
      // Check if this is a re-enrolment
      const { data: allEnrolments } = await supabase
        .from('enrolments')
        .select('id, enrolment_key, enrolment_value')
        .eq('user_id', userId)
        .or('enrolment_key.eq.onboard,enrolment_key.like.onboard%');

      const hasCompleted = (allEnrolments ?? []).some(e => {
        const val = parseJsonSafe(e.enrolment_value);
        return val && val['step-6'];
      });

      if (hasCompleted) {
        // Determine next enrolment key
        const keys = (allEnrolments ?? []).map(e => e.enrolment_key);
        let maxNum = 0;
        for (const k of keys) {
          if (k === 'onboard') maxNum = Math.max(maxNum, 1);
          const m = k.match(/^onboard(\d+)$/);
          if (m) maxNum = Math.max(maxNum, parseInt(m[1]));
        }
        enrolmentKey = maxNum === 0 ? 'onboard2' : `onboard${maxNum + 1}`;
      }

      enrolmentValue[stepKey] = data;
      const { data: newEnrolment, error } = await supabase.from('enrolments').insert({
        user_id: userId,
        enrolment_key: enrolmentKey,
        enrolment_value: JSON.stringify(enrolmentValue),
        is_active: 1,
      }).select('id').single();
      if (error) throw error;
      enrolmentId = newEnrolment.id;
    }

    // Determine next step
    let nextStep = step + 1;
    if (nextStep === 5) {
      const ptrCheck = await checkPtrRequirement(userId);
      if (ptrCheck.excluded || ptrCheck.completed) {
        // Auto-save step 5 as skipped and advance to 6
        enrolmentValue['step-5'] = { ptr_excluded: true, completed_at: Date.now() };
        await supabase.from('enrolments').update({
          enrolment_value: JSON.stringify(enrolmentValue),
          updated_at: new Date().toISOString(),
        }).eq('id', enrolmentId);
        nextStep = 6;
      }
    }
    if (nextStep > 6) nextStep = 0; // complete

    return { enrolmentId, nextStep };
  } catch (e) {
    handleError(e, `Failed to save onboarding step ${step}`);
  }
}

/**
 * Complete the onboarding process (step 6 finalization).
 * Sets user_details.status = 'ONBOARDED', creates activity log + note.
 */
export async function completeOnboarding(
  userId: number,
  agreementData: { agreement: string; signed_on: string },
  performedBy: number,
): Promise<void> {
  assertConfigured();
  try {
    const now = new Date().toISOString();

    // 1. Save step 6 data
    await saveOnboardingStep(userId, 6, agreementData);

    // 2. Update user_details status to ONBOARDED
    const { error: detailErr } = await supabase
      .from('user_details')
      .update({ status: 'ONBOARDED', onboard_at: now, updated_at: now })
      .eq('user_id', userId);
    if (detailErr) throw detailErr;

    // 3. Log activity
    await supabase.from('activity_log').insert({
      log_name: 'default',
      description: 'ENROLMENT',
      subject_type: 'App\\Models\\User',
      subject_id: userId,
      causer_type: 'App\\Models\\User',
      causer_id: performedBy,
      properties: JSON.stringify({
        user_id: userId,
        status: 'ONBOARDED',
        onboard_at: now,
        by: performedBy,
      }),
    });

    // 4. Create note
    await supabase.from('notes').insert({
      user_id: 0,
      subject_type: 'App\\Models\\User',
      subject_id: userId,
      note_body: `<p>Student completed onboarding on ${new Date().toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })}</p>`,
      data: JSON.stringify({
        type: 'ONBOARD AGREEMENT SIGNED',
        message: 'Student completed onboarding',
        date: now,
      }),
    });
  } catch (e) {
    handleError(e, 'Failed to complete onboarding');
  }
}

/** Fetch countries list for step 1 */
export async function fetchCountries(): Promise<{ id: number; name: string }[]> {
  assertConfigured();
  try {
    const { data, error } = await supabase
      .from('countries')
      .select('id, name')
      .order('name');
    if (error) throw error;
    return data ?? [];
  } catch (e) {
    handleError(e, 'Failed to fetch countries');
  }
}

/**
 * Fetch the PTR quiz with questions for step 5.
 * Returns the quiz data + any in-progress attempt.
 */
export async function fetchPtrQuiz(userId: number, courseId: number): Promise<{
  quiz: { id: number; title: string; questions: any[] } | null;
  existingAttempt: any | null;
  alreadyCompleted: boolean;
}> {
  assertConfigured();
  try {
    // Fetch quiz + questions
    const { data: quiz } = await supabase
      .from('quizzes')
      .select('id, title')
      .eq('id', PTR_QUIZ_ID)
      .single();

    if (!quiz) return { quiz: null, existingAttempt: null, alreadyCompleted: false };

    const { data: questions } = await supabase
      .from('questions')
      .select('id, title, content, answer_type, options, correct_answer, required, "order", table_structure')
      .eq('quiz_id', PTR_QUIZ_ID)
      .is('deleted_at', null)
      .order('order');

    // Check for existing attempt
    const { data: attempt } = await supabase
      .from('quiz_attempts')
      .select('id, quiz_id, status, system_result, submitted_answers, attempt, course_id')
      .eq('user_id', userId)
      .eq('quiz_id', PTR_QUIZ_ID)
      .eq('course_id', courseId)
      .order('id', { ascending: false })
      .limit(1)
      .single();

    const alreadyCompleted = !!attempt && 
      attempt.system_result !== 'INPROGRESS' && 
      attempt.status !== 'ATTEMPTING';

    return {
      quiz: { ...quiz, questions: questions ?? [] },
      existingAttempt: attempt?.system_result === 'INPROGRESS' ? attempt : null,
      alreadyCompleted,
    };
  } catch {
    return { quiz: null, existingAttempt: null, alreadyCompleted: false };
  }
}

/**
 * Save a PTR quiz answer (one question at a time, matching Laravel's approach).
 */
export async function savePtrQuizAnswer(
  userId: number,
  courseId: number,
  questionId: number,
  answer: any,
): Promise<{ attemptId: number; isComplete: boolean; nextQuestionIndex: number }> {
  assertConfigured();
  try {
    // Find or create attempt
    const { data: existing } = await supabase
      .from('quiz_attempts')
      .select('id, submitted_answers, questions, attempt')
      .eq('user_id', userId)
      .eq('quiz_id', PTR_QUIZ_ID)
      .eq('course_id', courseId)
      .eq('system_result', 'INPROGRESS')
      .order('id', { ascending: false })
      .limit(1)
      .single();

    let attemptId: number;
    let submittedAnswers: Record<string, any>;
    let questionsSnapshot: any[];

    if (existing) {
      // Update existing attempt
      submittedAnswers = parseJsonSafe(existing.submitted_answers) ?? {};
      questionsSnapshot = parseJsonSafe(existing.questions) ?? [];
      submittedAnswers[String(questionId)] = answer;

      await supabase.from('quiz_attempts').update({
        submitted_answers: JSON.stringify(submittedAnswers),
        updated_at: new Date().toISOString(),
      }).eq('id', existing.id);
      attemptId = existing.id;
    } else {
      // Fetch questions snapshot
      const { data: questions } = await supabase
        .from('questions')
        .select('id, title, content, answer_type, options, correct_answer, required, "order", table_structure')
        .eq('quiz_id', PTR_QUIZ_ID)
        .is('deleted_at', null)
        .order('order');

      questionsSnapshot = questions ?? [];
      submittedAnswers = { [String(questionId)]: answer };

      // Get quiz metadata for lesson/topic IDs
      const { data: quizMeta } = await supabase
        .from('quizzes')
        .select('lesson_id, topic_id')
        .eq('id', PTR_QUIZ_ID)
        .single();

      const { data: newAttempt, error } = await supabase.from('quiz_attempts').insert({
        user_id: userId,
        quiz_id: PTR_QUIZ_ID,
        course_id: courseId,
        lesson_id: quizMeta?.lesson_id ?? 0,
        topic_id: quizMeta?.topic_id ?? 0,
        questions: JSON.stringify(questionsSnapshot),
        submitted_answers: JSON.stringify(submittedAnswers),
        attempt: 1,
        system_result: 'INPROGRESS',
        status: 'ATTEMPTING',
      }).select('id').single();
      if (error) throw error;
      attemptId = newAttempt.id;
    }

    // Check completion
    const answeredCount = Object.keys(submittedAnswers).length;
    const totalQuestions = questionsSnapshot.length;
    const isComplete = answeredCount >= totalQuestions;

    if (isComplete) {
      // Mark as completed
      await supabase.from('quiz_attempts').update({
        system_result: 'COMPLETED',
        status: 'SUBMITTED',
        submitted_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      }).eq('id', attemptId);
    }

    return {
      attemptId,
      isComplete,
      nextQuestionIndex: answeredCount,
    };
  } catch (e) {
    handleError(e, 'Failed to save PTR quiz answer');
  }
}

/**
 * Check if a student has completed LLN for a specific course.
 */
export async function checkLlnCompletion(userId: number, courseId: number): Promise<boolean> {
  assertConfigured();
  try {
    const { data } = await supabase
      .from('student_course_enrolments')
      .select('has_lln_completed')
      .eq('user_id', userId)
      .eq('course_id', courseId)
      .single();
    return data?.has_lln_completed === 1;
  } catch {
    return false;
  }
}
