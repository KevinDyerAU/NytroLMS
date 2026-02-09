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

  // Route through edge function if enabled
  if (useEdgeFunctions) {
    const result = await callEdgeFunction<UserWithDetails>('students', { path: String(id) });
    return result;
  }

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
