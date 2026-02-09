import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient, type SupabaseClient } from 'https://esm.sh/@supabase/supabase-js@2';

// ─── Inlined Shared Code ─────────────────────────────────────────────────────

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
};

function handleCors(req: Request): Response | null {
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders });
  }
  return null;
}

function errorResponse(status: number, message: string, code?: string, details?: unknown): Response {
  const body = { error: message, ...(code && { code }), ...(details && { details }) };
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...corsHeaders, 'Content-Type': 'application/json' },
  });
}

function jsonResponse(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), {
    status,
    headers: { ...corsHeaders, 'Content-Type': 'application/json' },
  });
}

async function withErrorHandler(handler: () => Promise<Response>): Promise<Response> {
  try {
    return await handler();
  } catch (err) {
    console.error('Unhandled error:', err);
    const message = err instanceof Error ? err.message : 'Internal server error';
    return errorResponse(500, message, 'INTERNAL_ERROR');
  }
}

const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!;
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!;
const SUPABASE_ANON_KEY = Deno.env.get('SUPABASE_ANON_KEY')!;

function getAdminClient(): SupabaseClient {
  return createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, {
    auth: { autoRefreshToken: false, persistSession: false },
  });
}

function getUserClient(authHeader: string): SupabaseClient {
  return createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
    global: { headers: { Authorization: authHeader } },
    auth: { autoRefreshToken: false, persistSession: false },
  });
}

interface AuthUser {
  supabaseId: string;
  email: string;
  lmsUserId: number | null;
  role: string;
  permissions: string[];
}

async function requireAuth(req: Request): Promise<{ user: AuthUser; userClient: SupabaseClient } | Response> {
  const authHeader = req.headers.get('Authorization');
  if (!authHeader) return errorResponse(401, 'Missing Authorization header');

  const userClient = getUserClient(authHeader);
  const adminClient = getAdminClient();

  const { data: { user }, error } = await userClient.auth.getUser();
  if (error || !user) return errorResponse(401, 'Invalid or expired token');

  const { data: ctx, error: rpcError } = await adminClient.rpc('get_user_auth_context', { p_email: user.email });
  if (rpcError) {
    console.error('Auth context RPC error:', rpcError.message);
    return errorResponse(500, 'Failed to load auth context');
  }

  return {
    user: {
      supabaseId: user.id,
      email: user.email!,
      lmsUserId: ctx?.user_id ?? null,
      role: ctx?.role ?? 'Student',
      permissions: ctx?.permissions ?? [],
    },
    userClient,
  };
}

function requireRole(user: AuthUser, allowedRoles: string[]): Response | null {
  const normalised = allowedRoles.map((r) => r.toLowerCase());
  if (!normalised.includes(user.role.toLowerCase())) {
    return errorResponse(403, `Access denied. Required role: ${allowedRoles.join(' or ')}`);
  }
  return null;
}

async function writeAuditLog(entry: { logName: string; description: string; subjectType: string; subjectId: number; causerId: number | null; event?: string; properties?: Record<string, unknown> }): Promise<void> {
  const adminClient = getAdminClient();
  const { error } = await adminClient.from('activity_log').insert({
    log_name: entry.logName,
    description: entry.description,
    subject_type: entry.subjectType,
    subject_id: entry.subjectId,
    event: entry.event ?? entry.description,
    causer_type: entry.causerId ? 'user' : null,
    causer_id: entry.causerId,
    properties: entry.properties ? JSON.stringify(entry.properties) : null,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  });
  if (error) console.error('Failed to write audit log:', error.message);
}

// ─── Main Handler ─────────────────────────────────────────────────────────────

Deno.serve(async (req: Request) => {
  const corsResponse = handleCors(req);
  if (corsResponse) return corsResponse;

  return withErrorHandler(async () => {
    const authResult = await requireAuth(req);
    if (authResult instanceof Response) return authResult;
    const { user } = authResult;

    const url = new URL(req.url);
    const pathMatch = url.pathname.match(/\/students\/?(.*)$/);
    const path = pathMatch ? pathMatch[1] : '';

    // GET Routes
    if (req.method === 'GET' && !path) return handleListStudents(url, user);
    if (req.method === 'GET' && path === 'search') return handleSearchStudents(url, user);

    const idMatch = path.match(/^(\d+)$/);
    if (req.method === 'GET' && idMatch) return handleGetStudent(parseInt(idMatch[1]), user);

    const enrolmentsMatch = path.match(/^(\d+)\/enrolments$/);
    if (req.method === 'GET' && enrolmentsMatch) return handleGetStudentEnrolments(parseInt(enrolmentsMatch[1]), user);

    const activitiesMatch = path.match(/^(\d+)\/activities$/);
    if (req.method === 'GET' && activitiesMatch) return handleGetStudentActivities(parseInt(activitiesMatch[1]), user);

    const progressMatch = path.match(/^(\d+)\/progress$/);
    if (req.method === 'GET' && progressMatch) return handleGetStudentProgress(parseInt(progressMatch[1]), user);

    // POST Routes
    if (req.method === 'POST' && !path) return handleCreateStudent(req, user);
    if (req.method === 'POST' && enrolmentsMatch) return handleAssignCourse(req, parseInt(enrolmentsMatch[1]), user);

    // PUT Routes
    if (req.method === 'PUT' && idMatch) return handleUpdateStudent(req, parseInt(idMatch[1]), user);

    const enrolmentUpdateMatch = path.match(/^(\d+)\/enrolments\/(\d+)$/);
    if (req.method === 'PUT' && enrolmentUpdateMatch) return handleUpdateEnrolment(req, parseInt(enrolmentUpdateMatch[1]), parseInt(enrolmentUpdateMatch[2]), user);

    // PATCH Routes
    const activateMatch = path.match(/^(\d+)\/activate$/);
    if (req.method === 'PATCH' && activateMatch) return handleActivateStudent(parseInt(activateMatch[1]), user);

    const deactivateMatch = path.match(/^(\d+)\/deactivate$/);
    if (req.method === 'PATCH' && deactivateMatch) return handleDeactivateStudent(parseInt(deactivateMatch[1]), user);

    const archiveMatch = path.match(/^(\d+)\/archive$/);
    if (req.method === 'PATCH' && archiveMatch) return handleArchiveStudent(parseInt(archiveMatch[1]), user);

    // DELETE Routes
    if (req.method === 'DELETE' && idMatch) return handleDeleteStudent(parseInt(idMatch[1]), user);
    if (req.method === 'DELETE' && enrolmentUpdateMatch) return handleRemoveEnrolment(parseInt(enrolmentUpdateMatch[1]), parseInt(enrolmentUpdateMatch[2]), user);

    return errorResponse(404, 'Not found');
  });
});

// ─── Handler Functions ───────────────────────────────────────────────────────

async function handleListStudents(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const search = url.searchParams.get('search') || '';
  const status = url.searchParams.get('status') || '';
  const role = url.searchParams.get('role') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('users').select('*, user_details(*)', { count: 'exact' });
  if (status === 'active') query = query.eq('is_active', 1).eq('is_archived', 0);
  else if (status === 'inactive') query = query.eq('is_active', 0).eq('is_archived', 0);
  else if (status === 'archived') query = query.eq('is_archived', 1);
  else query = query.eq('is_archived', 0);

  if (search) query = query.or(`first_name.ilike.%${search}%,last_name.ilike.%${search}%,email.ilike.%${search}%`);
  query = query.order('created_at', { ascending: false }).range(offset, offset + limit - 1);

  const { data: users, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch students: ' + error.message);

  const userIds = (users ?? []).map((u: any) => u.id);
  const [{ data: roleAssignments }, { data: roles }] = await Promise.all([
    adminClient.from('model_has_roles').select('model_id, role_id').in('model_id', userIds.length > 0 ? userIds : [0]),
    adminClient.from('roles').select('id, name'),
  ]);

  const roleMap = new Map<number, string>();
  (roles ?? []).forEach((r: any) => roleMap.set(r.id, r.name));
  const userRoleMap = new Map<number, string>();
  (roleAssignments ?? []).forEach((mr: any) => userRoleMap.set(mr.model_id, roleMap.get(mr.role_id) ?? 'Student'));

  let enriched = (users ?? []).map((u: any) => ({
    id: u.id, first_name: u.first_name, last_name: u.last_name, username: u.username, email: u.email,
    study_type: u.study_type, is_active: u.is_active, is_archived: u.is_archived, created_at: u.created_at,
    role_name: userRoleMap.get(u.id) ?? 'Student',
    user_details: Array.isArray(u.user_details) ? u.user_details[0] ?? null : u.user_details,
  }));

  if (role && role !== 'all') enriched = enriched.filter((u: any) => u.role_name === role);
  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}

async function handleSearchStudents(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const q = url.searchParams.get('q') || '';
  if (q.length < 2) return jsonResponse([]);

  const adminClient = getAdminClient();
  const { data: users } = await adminClient.from('users').select('id, first_name, last_name, email').eq('is_archived', 0).or(`first_name.ilike.%${q}%,last_name.ilike.%${q}%,email.ilike.%${q}%`).limit(20);
  return jsonResponse((users ?? []).map((u: any) => ({ id: u.id, name: `${u.first_name} ${u.last_name}`.trim(), email: u.email })));
}

async function handleGetStudent(studentId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();
  if (user.role === 'Student' && user.lmsUserId !== studentId) return errorResponse(403, 'Access denied');

  const [studentResult, detailsResult, roleJoinResult, enrolmentsResult, progressResult] = await Promise.all([
    adminClient.from('users').select('*').eq('id', studentId).single(),
    adminClient.from('user_details').select('*').eq('user_id', studentId).maybeSingle(),
    adminClient.from('model_has_roles').select('role_id, roles(name)').eq('model_id', studentId).eq('model_type', 'App\\Models\\User').maybeSingle(),
    adminClient.from('student_course_enrolments').select('*').eq('user_id', studentId).order('created_at', { ascending: false }),
    adminClient.from('course_progress').select('course_id, percentage').eq('user_id', studentId),
  ]);

  if (studentResult.error || !studentResult.data) return errorResponse(404, 'Student not found');

  const student = studentResult.data;
  const enrolments = enrolmentsResult.data ?? [];
  const progress = progressResult.data ?? [];
  const courseIds = [...new Set(enrolments.map((e: any) => e.course_id))];

  const [{ data: courses }, { data: companies }] = await Promise.all([
    adminClient.from('courses').select('id, title').in('id', courseIds.length > 0 ? courseIds : [0]),
    adminClient.from('companies').select('id, name').in('id', detailsResult.data?.signup_links_id ? [detailsResult.data.signup_links_id] : [0]),
  ]);

  const courseMap = new Map<number, string>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c.title));
  const progressMap = new Map<number, string>();
  (progress ?? []).forEach((p: any) => progressMap.set(p.course_id, p.percentage));

  return jsonResponse({
    id: student.id, first_name: student.first_name, last_name: student.last_name, username: student.username, email: student.email,
    study_type: student.study_type, is_active: student.is_active, is_archived: student.is_archived, created_at: student.created_at,
    role_name: (roleJoinResult.data as any)?.roles?.name ?? 'Student',
    details: detailsResult.data ?? null,
    enrolments: enrolments.map((e: any) => ({ ...e, course_title: courseMap.get(e.course_id) ?? 'Unknown', progress_percentage: parseFloat(progressMap.get(e.course_id) ?? '0') })),
    companies: companies ?? [],
    progress: Object.fromEntries(progressMap),
  });
}

async function handleGetStudentEnrolments(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const [enrolmentsResult, progressResult] = await Promise.all([
    adminClient.from('student_course_enrolments').select('*').eq('user_id', studentId).order('created_at', { ascending: false }),
    adminClient.from('course_progress').select('course_id, percentage').eq('user_id', studentId),
  ]);

  if (enrolmentsResult.error) return errorResponse(500, 'Failed to fetch enrolments: ' + enrolmentsResult.error.message);

  const enrolments = enrolmentsResult.data ?? [];
  const courseIds = [...new Set(enrolments.map((e: any) => e.course_id))];
  const { data: courses } = await adminClient.from('courses').select('id, title, course_type, status').in('id', courseIds.length > 0 ? courseIds : [0]);

  const courseMap = new Map<number, any>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c));
  const progressMap = new Map<number, string>();
  (progressResult.data ?? []).forEach((p: any) => progressMap.set(p.course_id, p.percentage));

  return jsonResponse({ data: enrolments.map((e: any) => ({ ...e, course: courseMap.get(e.course_id) ?? null, progress_percentage: parseFloat(progressMap.get(e.course_id) ?? '0') })) });
}

async function handleGetStudentActivities(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: activities, error } = await adminClient.from('student_activities').select('*').eq('user_id', studentId).order('created_at', { ascending: false }).limit(50);
  if (error) return errorResponse(500, 'Failed to fetch activities: ' + error.message);
  return jsonResponse({ data: activities ?? [] });
}

async function handleGetStudentProgress(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer', 'Student']);
  if (roleCheck) return roleCheck;
  if (user.role === 'Student' && user.lmsUserId !== studentId) return errorResponse(403, 'Access denied');

  const adminClient = getAdminClient();
  const [enrolmentsResult, progressResult, attemptsResult] = await Promise.all([
    adminClient.from('student_course_enrolments').select('course_id, status').eq('user_id', studentId),
    adminClient.from('course_progress').select('*').eq('user_id', studentId),
    adminClient.from('quiz_attempts').select('course_id, status, score').eq('user_id', studentId),
  ]);

  const totalEnrolments = enrolmentsResult.data?.length ?? 0;
  const completedEnrolments = enrolmentsResult.data?.filter((e: any) => e.status === 'COMPLETED').length ?? 0;
  const inProgressEnrolments = enrolmentsResult.data?.filter((e: any) => e.status === 'ACTIVE').length ?? 0;
  const avgProgress = progressResult.data && progressResult.data.length > 0
    ? progressResult.data.reduce((sum: number, p: any) => sum + parseFloat(p.percentage || '0'), 0) / progressResult.data.length
    : 0;
  const totalAttempts = attemptsResult.data?.length ?? 0;
  const passedAttempts = attemptsResult.data?.filter((a: any) => a.status === 'PASSED').length ?? 0;

  return jsonResponse({
    summary: {
      total_courses: totalEnrolments, completed_courses: completedEnrolments, in_progress_courses: inProgressEnrolments,
      average_progress: Math.round(avgProgress * 10) / 10, total_assessments: totalAttempts, passed_assessments: passedAttempts,
    },
    course_progress: progressResult.data ?? [],
    recent_attempts: attemptsResult.data?.slice(0, 10) ?? [],
  });
}

async function handleCreateStudent(req: Request, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { first_name, last_name, email, username, study_type, phone, address, preferred_name, language, preferred_language, timezone, company_id, leader_ids, trainer_ids, course_ids } = body;
  if (!first_name || !last_name || !email) return errorResponse(400, 'first_name, last_name, and email are required');

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('users').select('id').eq('email', email).maybeSingle();
  if (existing) return errorResponse(409, 'A user with this email already exists');

  const { data: newUser, error: userError } = await adminClient.from('users').insert({
    first_name, last_name, email, username: username || null, study_type: study_type || null, is_active: 0, is_archived: 0, userable_type: 'App\\Models\\Student'
  }).select('id, first_name, last_name, email, created_at').single();
  if (userError || !newUser) return errorResponse(500, 'Failed to create student: ' + (userError?.message || 'Unknown error'));

  const studentId = newUser.id;
  await adminClient.from('user_details').insert({ user_id: studentId, phone: phone || null, address: address || null, preferred_name: preferred_name || null, language: language || null, preferred_language: preferred_language || null, timezone: timezone || null });

  const { data: studentRole } = await adminClient.from('roles').select('id').eq('name', 'Student').single();
  if (studentRole) await adminClient.from('model_has_roles').insert({ role_id: studentRole.id, model_type: 'App\\Models\\User', model_id: studentId });

  if (company_id) await adminClient.from('user_has_attachables').insert({ user_id: studentId, attachable_type: 'App\\Models\\Company', attachable_id: company_id });
  if (leader_ids?.length) await adminClient.from('user_has_attachables').insert(leader_ids.map((id: number) => ({ user_id: id, attachable_type: 'App\\Models\\Leader', attachable_id: studentId })));
  if (trainer_ids?.length) await adminClient.from('user_has_attachables').insert(trainer_ids.map((id: number) => ({ user_id: id, attachable_type: 'App\\Models\\Trainer', attachable_id: studentId })));
  if (course_ids?.length) await adminClient.from('student_course_enrolments').insert(course_ids.map((courseId: number) => ({ user_id: studentId, course_id: courseId, status: 'PENDING', version: 1 })));

  await writeAuditLog({ logName: 'student', description: 'Student created', subjectType: 'student', subjectId: studentId, causerId: user.lmsUserId, event: 'created', properties: { first_name, last_name, email, company_id, leader_ids, trainer_ids, course_ids } });
  return jsonResponse({ id: studentId, first_name, last_name, email, message: 'Student created successfully' }, 201);
}

async function handleAssignCourse(req: Request, studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { course_id, status = 'PENDING', course_start_at, course_ends_at, is_main_course = 0 } = body;
  if (!course_id) return errorResponse(400, 'course_id is required');

  const adminClient = getAdminClient();
  const { data: student } = await adminClient.from('users').select('id').eq('id', studentId).single();
  if (!student) return errorResponse(404, 'Student not found');

  const { data: existing } = await adminClient.from('student_course_enrolments').select('id').eq('user_id', studentId).eq('course_id', course_id).maybeSingle();
  if (existing) return errorResponse(409, 'Student is already enrolled in this course');

  const { data: versionData } = await adminClient.from('student_course_enrolments').select('version').eq('user_id', studentId).eq('course_id', course_id).order('version', { ascending: false }).limit(1).maybeSingle();
  const version = (versionData?.version ?? 0) + 1;

  const { data: enrolment, error } = await adminClient.from('student_course_enrolments').insert({
    user_id: studentId, course_id, status, version, course_start_at: course_start_at || null, course_ends_at: course_ends_at || null, is_main_course
  }).select('id, course_id, status, version, created_at').single();
  if (error) return errorResponse(500, 'Failed to assign course: ' + error.message);

  await writeAuditLog({ logName: 'enrolment', description: 'Course assigned', subjectType: 'enrolment', subjectId: enrolment.id, causerId: user.lmsUserId, event: 'created', properties: { student_id: studentId, course_id, status } });
  return jsonResponse({ ...enrolment, message: 'Course assigned successfully' }, 201);
}

async function handleUpdateStudent(req: Request, studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { first_name, last_name, username, study_type, phone, address, preferred_name, language, preferred_language, timezone, position } = body;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('users').select('id').eq('id', studentId).single();
  if (!existing) return errorResponse(404, 'Student not found');

  const userUpdates: Record<string, unknown> = {};
  if (first_name !== undefined) userUpdates.first_name = first_name;
  if (last_name !== undefined) userUpdates.last_name = last_name;
  if (username !== undefined) userUpdates.username = username || null;
  if (study_type !== undefined) userUpdates.study_type = study_type || null;
  if (Object.keys(userUpdates).length > 0) {
    const { error } = await adminClient.from('users').update(userUpdates).eq('id', studentId);
    if (error) return errorResponse(500, 'Failed to update student: ' + error.message);
  }

  const detailUpdates: Record<string, unknown> = {};
  if (phone !== undefined) detailUpdates.phone = phone || null;
  if (address !== undefined) detailUpdates.address = address || null;
  if (preferred_name !== undefined) detailUpdates.preferred_name = preferred_name || null;
  if (language !== undefined) detailUpdates.language = language || null;
  if (preferred_language !== undefined) detailUpdates.preferred_language = preferred_language || null;
  if (timezone !== undefined) detailUpdates.timezone = timezone || null;
  if (position !== undefined) detailUpdates.position = position || null;
  if (Object.keys(detailUpdates).length > 0) {
    const { error } = await adminClient.from('user_details').update(detailUpdates).eq('user_id', studentId);
    if (error) return errorResponse(500, 'Failed to update student details: ' + error.message);
  }

  await writeAuditLog({ logName: 'student', description: 'Student updated', subjectType: 'student', subjectId: studentId, causerId: user.lmsUserId, event: 'updated', properties: { ...userUpdates, ...detailUpdates } });
  return jsonResponse({ id: studentId, message: 'Student updated successfully' });
}

async function handleUpdateEnrolment(req: Request, studentId: number, enrolmentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { status, course_start_at, course_ends_at, course_expiry, is_main_course, is_locked, deferred, is_chargeable } = body;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('student_course_enrolments').select('id, user_id').eq('id', enrolmentId).single();
  if (!existing) return errorResponse(404, 'Enrolment not found');
  if (existing.user_id !== studentId) return errorResponse(403, 'Enrolment does not belong to this student');

  const updates: Record<string, unknown> = {};
  if (status !== undefined) updates.status = status;
  if (course_start_at !== undefined) updates.course_start_at = course_start_at;
  if (course_ends_at !== undefined) updates.course_ends_at = course_ends_at;
  if (course_expiry !== undefined) updates.course_expiry = course_expiry;
  if (is_main_course !== undefined) updates.is_main_course = is_main_course;
  if (is_locked !== undefined) updates.is_locked = is_locked;
  if (deferred !== undefined) updates.deferred = deferred;
  if (is_chargeable !== undefined) updates.is_chargeable = is_chargeable;

  if (Object.keys(updates).length === 0) return errorResponse(400, 'No fields to update');
  updates.updated_at = new Date().toISOString();

  const { data: enrolment, error } = await adminClient.from('student_course_enrolments').update(updates).eq('id', enrolmentId).select('id, course_id, status, updated_at').single();
  if (error) return errorResponse(500, 'Failed to update enrolment: ' + error.message);

  await writeAuditLog({ logName: 'enrolment', description: 'Enrolment updated', subjectType: 'enrolment', subjectId: enrolmentId, causerId: user.lmsUserId, event: 'updated', properties: { student_id: studentId, ...updates } });
  return jsonResponse({ ...enrolment, message: 'Enrolment updated successfully' });
}

async function handleActivateStudent(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('users').select('id, is_active').eq('id', studentId).single();
  if (!existing) return errorResponse(404, 'Student not found');
  if (existing.is_active === 1) return errorResponse(400, 'Student is already active');

  const { error } = await adminClient.from('users').update({ is_active: 1, updated_at: new Date().toISOString() }).eq('id', studentId);
  if (error) return errorResponse(500, 'Failed to activate student: ' + error.message);

  await writeAuditLog({ logName: 'student', description: 'Student activated', subjectType: 'student', subjectId: studentId, causerId: user.lmsUserId, event: 'activated', properties: {} });
  return jsonResponse({ id: studentId, is_active: 1, message: 'Student activated successfully' });
}

async function handleDeactivateStudent(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('users').select('id, is_active').eq('id', studentId).single();
  if (!existing) return errorResponse(404, 'Student not found');
  if (existing.is_active === 0) return errorResponse(400, 'Student is already inactive');

  const { error } = await adminClient.from('users').update({ is_active: 0, updated_at: new Date().toISOString() }).eq('id', studentId);
  if (error) return errorResponse(500, 'Failed to deactivate student: ' + error.message);

  await writeAuditLog({ logName: 'student', description: 'Student deactivated', subjectType: 'student', subjectId: studentId, causerId: user.lmsUserId, event: 'deactivated', properties: {} });
  return jsonResponse({ id: studentId, is_active: 0, message: 'Student deactivated successfully' });
}

async function handleArchiveStudent(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('users').select('id').eq('id', studentId).single();
  if (!existing) return errorResponse(404, 'Student not found');

  const { error } = await adminClient.from('users').update({ is_archived: 1, is_active: 0, updated_at: new Date().toISOString() }).eq('id', studentId);
  if (error) return errorResponse(500, 'Failed to archive student: ' + error.message);

  await writeAuditLog({ logName: 'student', description: 'Student archived', subjectType: 'student', subjectId: studentId, causerId: user.lmsUserId, event: 'archived', properties: {} });
  return jsonResponse({ id: studentId, is_archived: 1, message: 'Student archived successfully' });
}

async function handleDeleteStudent(studentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('users').select('id, first_name, last_name, email').eq('id', studentId).single();
  if (!existing) return errorResponse(404, 'Student not found');

  const { error } = await adminClient.from('users').update({ is_archived: 1, is_active: 0, email: `${existing.email}.archived.${Date.now()}`, updated_at: new Date().toISOString() }).eq('id', studentId);
  if (error) return errorResponse(500, 'Failed to delete student: ' + error.message);

  await writeAuditLog({ logName: 'student', description: 'Student deleted', subjectType: 'student', subjectId: studentId, causerId: user.lmsUserId, event: 'deleted', properties: { first_name: existing.first_name, last_name: existing.last_name } });
  return jsonResponse({ id: studentId, message: 'Student deleted successfully' });
}

async function handleRemoveEnrolment(studentId: number, enrolmentId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('student_course_enrolments').select('id, user_id, course_id').eq('id', enrolmentId).single();
  if (!existing) return errorResponse(404, 'Enrolment not found');
  if (existing.user_id !== studentId) return errorResponse(403, 'Enrolment does not belong to this student');

  const { error } = await adminClient.from('student_course_enrolments').delete().eq('id', enrolmentId);
  if (error) return errorResponse(500, 'Failed to remove enrolment: ' + error.message);

  await writeAuditLog({ logName: 'enrolment', description: 'Enrolment removed', subjectType: 'enrolment', subjectId: enrolmentId, causerId: user.lmsUserId, event: 'deleted', properties: { student_id: studentId, course_id: existing.course_id } });
  return jsonResponse({ id: enrolmentId, message: 'Enrolment removed successfully' });
}
