import "jsr:@supabase/functions-js/edge-runtime.d.ts";

import { handleCors } from '../_shared/cors.ts';
import { requireAuth, requireRole, type AuthUser } from '../_shared/auth.ts';
import { getAdminClient } from '../_shared/db.ts';
import { errorResponse, jsonResponse, withErrorHandler } from '../_shared/errors.ts';

Deno.serve(async (req: Request) => {
  const corsResponse = handleCors(req);
  if (corsResponse) return corsResponse;

  return withErrorHandler(async () => {
    const authResult = await requireAuth(req);
    if (authResult instanceof Response) return authResult;
    const { user } = authResult;

    const url = new URL(req.url);
    // Extract path after /students/
    const pathMatch = url.pathname.match(/\/students\/?(.*)$/);
    const path = pathMatch ? pathMatch[1] : '';

    // GET /students — list students
    if (req.method === 'GET' && !path) {
      return handleListStudents(url, user);
    }

    // GET /students/search — search students (for dropdowns)
    if (req.method === 'GET' && path === 'search') {
      return handleSearchStudents(url, user);
    }

    // GET /students/:id — get student detail
    const idMatch = path.match(/^(\d+)$/);
    if (req.method === 'GET' && idMatch) {
      const studentId = parseInt(idMatch[1], 10);
      return handleGetStudent(studentId, user);
    }

    // GET /students/:id/enrolments — get student enrolments
    const enrolmentsMatch = path.match(/^(\d+)\/enrolments$/);
    if (req.method === 'GET' && enrolmentsMatch) {
      const studentId = parseInt(enrolmentsMatch[1], 10);
      return handleGetStudentEnrolments(studentId, user);
    }

    // GET /students/:id/activities — get student activities
    const activitiesMatch = path.match(/^(\d+)\/activities$/);
    if (req.method === 'GET' && activitiesMatch) {
      const studentId = parseInt(activitiesMatch[1], 10);
      return handleGetStudentActivities(studentId, user);
    }

    return errorResponse(404, 'Not found');
  });
});

/**
 * GET /students — List students with filtering, search, pagination.
 */
async function handleListStudents(url: URL, user: AuthUser): Promise<Response> {
  // Only privileged roles can list students
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const search = url.searchParams.get('search') || '';
  const status = url.searchParams.get('status') || '';
  const role = url.searchParams.get('role') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  // Build user query
  let query = adminClient
    .from('users')
    .select('*, user_details(*)', { count: 'exact' });

  // Status filter
  if (status === 'active') {
    query = query.eq('is_active', 1).eq('is_archived', 0);
  } else if (status === 'inactive') {
    query = query.eq('is_active', 0).eq('is_archived', 0);
  } else if (status === 'archived') {
    query = query.eq('is_archived', 1);
  } else {
    query = query.eq('is_archived', 0);
  }

  // Search filter
  if (search) {
    query = query.or(
      `first_name.ilike.%${search}%,last_name.ilike.%${search}%,email.ilike.%${search}%`
    );
  }

  query = query
    .order('created_at', { ascending: false })
    .range(offset, offset + limit - 1);

  const { data: users, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch students: ' + error.message);

  // Parallel fetch: role assignments + role names
  const userIds = (users ?? []).map((u: any) => u.id);
  const [{ data: roleAssignments }, { data: roles }] = await Promise.all([
    adminClient
      .from('model_has_roles')
      .select('model_id, role_id')
      .in('model_id', userIds.length > 0 ? userIds : [0]),
    adminClient
      .from('roles')
      .select('id, name'),
  ]);

  const roleMap = new Map<number, string>();
  (roles ?? []).forEach((r: any) => roleMap.set(r.id, r.name));

  const userRoleMap = new Map<number, string>();
  (roleAssignments ?? []).forEach((mr: any) => {
    userRoleMap.set(mr.model_id, roleMap.get(mr.role_id) ?? 'Student');
  });

  // Enrich users with role
  let enriched = (users ?? []).map((u: any) => ({
    id: u.id,
    first_name: u.first_name,
    last_name: u.last_name,
    username: u.username,
    email: u.email,
    study_type: u.study_type,
    is_active: u.is_active,
    is_archived: u.is_archived,
    userable_type: u.userable_type,
    created_at: u.created_at,
    role_name: userRoleMap.get(u.id) ?? 'Student',
    user_details: Array.isArray(u.user_details) ? u.user_details[0] ?? null : u.user_details,
  }));

  // Filter by role if specified
  if (role && role !== 'all') {
    enriched = enriched.filter((u: any) => u.role_name === role);
  }

  // If user is a Leader, filter to only their company's students
  if (user.role === 'Leader' && user.lmsUserId) {
    const companyStudentIds = await getLeaderStudentIds(adminClient, user.lmsUserId);
    enriched = enriched.filter((u: any) => companyStudentIds.has(u.id));
  }

  return jsonResponse({
    data: enriched,
    total: count ?? 0,
    limit,
    offset,
  });
}

/**
 * GET /students/search — Quick search for dropdowns/autocomplete.
 */
async function handleSearchStudents(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const q = url.searchParams.get('q') || '';
  if (q.length < 2) {
    return jsonResponse([]);
  }

  const adminClient = getAdminClient();

  const { data: users } = await adminClient
    .from('users')
    .select('id, first_name, last_name, email')
    .eq('is_archived', 0)
    .or(`first_name.ilike.%${q}%,last_name.ilike.%${q}%,email.ilike.%${q}%`)
    .limit(20);

  return jsonResponse(
    (users ?? []).map((u: any) => ({
      id: u.id,
      name: `${u.first_name} ${u.last_name}`.trim(),
      email: u.email,
    }))
  );
}

/**
 * GET /students/:id — Get full student detail.
 */
async function handleGetStudent(studentId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  // Students can only view themselves
  if (user.role === 'Student' && user.lmsUserId !== studentId) {
    return errorResponse(403, 'Access denied');
  }

  // Leaders can only view their company's students
  if (user.role === 'Leader' && user.lmsUserId) {
    const companyStudentIds = await getLeaderStudentIds(adminClient, user.lmsUserId);
    if (!companyStudentIds.has(studentId)) {
      return errorResponse(403, 'Access denied — student not in your company');
    }
  }

  // Parallel fetch: user, details, role, enrolments, progress — all at once
  const [studentResult, detailsResult, roleJoinResult, enrolmentsResult, progressResult] = await Promise.all([
    adminClient.from('users').select('*').eq('id', studentId).single(),
    adminClient.from('user_details').select('*').eq('user_id', studentId).maybeSingle(),
    adminClient.from('model_has_roles').select('role_id, roles(name)')
      .eq('model_id', studentId).eq('model_type', 'App\\Models\\User').maybeSingle(),
    adminClient.from('student_course_enrolments')
      .select('id, course_id, status, course_start_at, course_ends_at, course_completed_at, is_main_course, created_at')
      .eq('user_id', studentId).order('created_at', { ascending: false }),
    adminClient.from('course_progress').select('course_id, percentage').eq('user_id', studentId),
  ]);

  const student = studentResult.data;
  if (studentResult.error || !student) {
    return errorResponse(404, 'Student not found');
  }

  const details = detailsResult.data;
  const roleName = (roleJoinResult.data as any)?.roles?.name ?? 'Student';
  const enrolments = enrolmentsResult.data ?? [];
  const progress = progressResult.data ?? [];

  // Parallel fetch: course titles + companies (depend on enrolments/details results)
  const courseIds = [...new Set(enrolments.map((e: any) => e.course_id))];
  const signupLinkId = details?.signup_links_id ?? 0;

  const [coursesResult, companyLinksResult] = await Promise.all([
    adminClient.from('courses').select('id, title').in('id', courseIds.length > 0 ? courseIds : [0]),
    adminClient.from('signup_links').select('company_id').eq('id', signupLinkId),
  ]);

  const courseMap = new Map<number, string>();
  (coursesResult.data ?? []).forEach((c: any) => courseMap.set(c.id, c.title));

  const enrichedEnrolments = enrolments.map((e: any) => ({
    ...e,
    course_title: courseMap.get(e.course_id) ?? 'Unknown',
  }));

  let companies: any[] = [];
  const companyLinks = companyLinksResult.data;
  if (companyLinks && companyLinks.length > 0) {
    const compIds = companyLinks.map((cl: any) => cl.company_id);
    const { data: companyData } = await adminClient
      .from('companies')
      .select('id, name')
      .in('id', compIds);
    companies = companyData ?? [];
  }

  const progressMap = new Map<number, string>();
  (progress ?? []).forEach((p: any) => progressMap.set(p.course_id, p.percentage));

  return jsonResponse({
    id: student.id,
    first_name: student.first_name,
    last_name: student.last_name,
    username: student.username,
    email: student.email,
    study_type: student.study_type,
    is_active: student.is_active,
    is_archived: student.is_archived,
    userable_type: student.userable_type,
    created_at: student.created_at,
    role_name: roleName,
    details: details ?? null,
    enrolments: enrichedEnrolments,
    companies,
    progress: Object.fromEntries(progressMap),
  });
}

/**
 * GET /students/:id/enrolments — Get student's enrolments with full details.
 */
async function handleGetStudentEnrolments(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const { data: enrolments, error } = await adminClient
    .from('student_course_enrolments')
    .select('*')
    .eq('user_id', studentId)
    .order('created_at', { ascending: false });

  if (error) return errorResponse(500, 'Failed to fetch enrolments: ' + error.message);

  // Get course details
  const courseIds = [...new Set((enrolments ?? []).map((e: any) => e.course_id))];
  const { data: courses } = await adminClient
    .from('courses')
    .select('id, title, course_type, status')
    .in('id', courseIds.length > 0 ? courseIds : [0]);

  const courseMap = new Map<number, any>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c));

  // Get progress
  const { data: progress } = await adminClient
    .from('course_progress')
    .select('course_id, percentage')
    .eq('user_id', studentId);

  const progressMap = new Map<number, string>();
  (progress ?? []).forEach((p: any) => progressMap.set(p.course_id, p.percentage));

  const enriched = (enrolments ?? []).map((e: any) => ({
    ...e,
    course: courseMap.get(e.course_id) ?? null,
    progress_percentage: progressMap.get(e.course_id) ?? '0',
  }));

  return jsonResponse({ data: enriched });
}

/**
 * GET /students/:id/activities — Get student's recent activities.
 */
async function handleGetStudentActivities(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const { data: activities, error } = await adminClient
    .from('student_activities')
    .select('*')
    .eq('user_id', studentId)
    .order('created_at', { ascending: false })
    .limit(50);

  if (error) return errorResponse(500, 'Failed to fetch activities: ' + error.message);

  return jsonResponse({ data: activities ?? [] });
}

/**
 * Helper: Get all student IDs that belong to a leader's companies.
 */
async function getLeaderStudentIds(
  adminClient: any,
  leaderUserId: number
): Promise<Set<number>> {
  // Get leader's company IDs via signup_links
  const { data: leaderLinks } = await adminClient
    .from('signup_links')
    .select('company_id')
    .eq('leader_id', leaderUserId);

  if (!leaderLinks || leaderLinks.length === 0) return new Set();

  const companyIds = [...new Set(leaderLinks.map((l: any) => l.company_id))];

  // Get all signup link IDs for these companies
  const { data: companySignupLinks } = await adminClient
    .from('signup_links')
    .select('id')
    .in('company_id', companyIds);

  if (!companySignupLinks || companySignupLinks.length === 0) return new Set();

  const slIds = companySignupLinks.map((sl: any) => sl.id);

  // Get user IDs linked via user_details.signup_links_id
  const { data: userDetails } = await adminClient
    .from('user_details')
    .select('user_id')
    .in('signup_links_id', slIds);

  return new Set((userDetails ?? []).map((ud: any) => ud.user_id));
}
