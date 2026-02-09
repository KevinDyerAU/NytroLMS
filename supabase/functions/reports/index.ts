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

// ─── Main Handler ─────────────────────────────────────────────────────────────

Deno.serve(async (req: Request) => {
  const corsResponse = handleCors(req);
  if (corsResponse) return corsResponse;

  return withErrorHandler(async () => {
    const authResult = await requireAuth(req);
    if (authResult instanceof Response) return authResult;
    const { user } = authResult;

    const url = new URL(req.url);
    const pathMatch = url.pathname.match(/\/reports\/?(.*)$/);
    const path = pathMatch ? pathMatch[1] : '';

    // GET /reports (admin reports list)
    if (req.method === 'GET' && !path) return handleListReports(url, user);

    // GET /reports/:id
    const idMatch = path.match(/^(\d+)$/);
    if (req.method === 'GET' && idMatch) return handleGetReport(parseInt(idMatch[1]), user);

    // GET /reports/dashboard (dashboard stats)
    if (req.method === 'GET' && path === 'dashboard') return handleDashboardStats(user);

    // GET /reports/course-progress (course progress summary)
    if (req.method === 'GET' && path === 'course-progress') return handleCourseProgressSummary(user);

    // GET /reports/role-distribution
    if (req.method === 'GET' && path === 'role-distribution') return handleRoleDistribution(user);

    // GET /reports/roles (list roles)
    if (req.method === 'GET' && path === 'roles') return handleListRoles(user);

    // GET /reports/settings
    if (req.method === 'GET' && path === 'settings') return handleGetSettings(user);

    // PUT /reports/settings
    if (req.method === 'PUT' && path === 'settings') return handleUpdateSettings(req, user);

    return errorResponse(404, 'Not found');
  });
});

// ─── Handlers ─────────────────────────────────────────────────────────────────

async function handleListReports(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const status = url.searchParams.get('status') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('admin_reports').select('*', { count: 'exact' });
  if (status && status !== 'all') query = query.eq('student_status', status);
  query = query.order('updated_at', { ascending: false }).range(offset, offset + limit - 1);

  const { data, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch reports: ' + error.message);

  return jsonResponse({ data: data ?? [], total: count ?? 0, limit, offset });
}

async function handleGetReport(reportId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: report, error } = await adminClient.from('admin_reports').select('*').eq('id', reportId).single();
  if (error || !report) return errorResponse(404, 'Report not found');

  // Get student and course names
  const [studentResult, courseResult] = await Promise.all([
    adminClient.from('users').select('first_name, last_name, email').eq('id', report.student_id).single(),
    adminClient.from('courses').select('title').eq('id', report.course_id).single(),
  ]);

  return jsonResponse({
    ...report,
    student_name: studentResult.data ? `${studentResult.data.first_name} ${studentResult.data.last_name}` : 'Unknown',
    student_email: studentResult.data?.email ?? '',
    course_title: courseResult.data?.title ?? 'Unknown',
  });
}

async function handleDashboardStats(user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const [
    { count: totalUsers },
    { count: activeUsers },
    { count: totalStudents },
    { count: totalCourses },
    { count: activeCourses },
    { count: totalEnrolments },
    { count: totalCompanies },
    { count: totalAssessments },
    { count: pendingAssessments },
  ] = await Promise.all([
    adminClient.from('users').select('*', { count: 'exact', head: true }).eq('is_archived', 0),
    adminClient.from('users').select('*', { count: 'exact', head: true }).eq('is_active', 1).eq('is_archived', 0),
    adminClient.from('model_has_roles').select('*', { count: 'exact', head: true }).in('role_id', [7]), // Student role
    adminClient.from('courses').select('*', { count: 'exact', head: true }).eq('is_archived', 0),
    adminClient.from('courses').select('*', { count: 'exact', head: true }).eq('status', 'PUBLISHED').eq('is_archived', 0),
    adminClient.from('student_course_enrolments').select('*', { count: 'exact', head: true }),
    adminClient.from('companies').select('*', { count: 'exact', head: true }).is('deleted_at', null),
    adminClient.from('quiz_attempts').select('*', { count: 'exact', head: true }),
    adminClient.from('quiz_attempts').select('*', { count: 'exact', head: true }).in('status', ['SUBMITTED', 'REVIEWING']),
  ]);

  return jsonResponse({
    total_users: totalUsers ?? 0,
    active_users: activeUsers ?? 0,
    total_students: totalStudents ?? 0,
    total_courses: totalCourses ?? 0,
    active_courses: activeCourses ?? 0,
    total_enrolments: totalEnrolments ?? 0,
    total_companies: totalCompanies ?? 0,
    total_assessments: totalAssessments ?? 0,
    pending_assessments: pendingAssessments ?? 0,
  });
}

async function handleCourseProgressSummary(user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const [{ data: courses }, { data: enrolments }, { data: progress }] = await Promise.all([
    adminClient.from('courses').select('id, title').eq('is_archived', 0),
    adminClient.from('student_course_enrolments').select('course_id, status'),
    adminClient.from('course_progress').select('course_id, percentage'),
  ]);

  const courseMap = new Map<number, string>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c.title));

  const enrolmentStats = new Map<number, { total: number; completed: number; active: number }>();
  (enrolments ?? []).forEach((e: any) => {
    const stats = enrolmentStats.get(e.course_id) ?? { total: 0, completed: 0, active: 0 };
    stats.total++;
    if (e.status === 'COMPLETED') stats.completed++;
    if (e.status === 'ACTIVE') stats.active++;
    enrolmentStats.set(e.course_id, stats);
  });

  const progressStats = new Map<number, number[]>();
  (progress ?? []).forEach((p: any) => {
    const pct = parseFloat(p.percentage) || 0;
    const arr = progressStats.get(p.course_id) ?? [];
    arr.push(pct);
    progressStats.set(p.course_id, arr);
  });

  const summary = (courses ?? []).map((c: any) => {
    const stats = enrolmentStats.get(c.id) ?? { total: 0, completed: 0, active: 0 };
    const pcts = progressStats.get(c.id) ?? [];
    const avg = pcts.length > 0 ? pcts.reduce((a: number, b: number) => a + b, 0) / pcts.length : 0;

    return {
      course_id: c.id,
      course_title: c.title,
      total_enrolled: stats.total,
      completed: stats.completed,
      in_progress: stats.active,
      not_started: stats.total - stats.completed - stats.active,
      avg_progress: Math.round(avg),
    };
  }).filter((c: any) => c.total_enrolled > 0);

  return jsonResponse(summary);
}

async function handleRoleDistribution(user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const [{ data: roleAssignments }, { data: roles }] = await Promise.all([
    adminClient.from('model_has_roles').select('role_id'),
    adminClient.from('roles').select('id, name'),
  ]);

  const roleMap = new Map<number, string>();
  (roles ?? []).forEach((r: any) => roleMap.set(r.id, r.name));

  const counts = new Map<string, number>();
  (roleAssignments ?? []).forEach((ra: any) => {
    const name = roleMap.get(ra.role_id) ?? 'Unknown';
    counts.set(name, (counts.get(name) ?? 0) + 1);
  });

  return jsonResponse(Array.from(counts.entries()).map(([role, count]) => ({ role, count })));
}

async function handleListRoles(user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();
  const { data, error } = await adminClient.from('roles').select('id, name').order('id', { ascending: true });
  if (error) return errorResponse(500, 'Failed to fetch roles: ' + error.message);
  return jsonResponse(data ?? []);
}

async function handleGetSettings(user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data, error } = await adminClient.from('settings').select('key, value');
  if (error) return errorResponse(500, 'Failed to fetch settings: ' + error.message);

  const settings: Record<string, string> = {};
  (data ?? []).forEach((s: any) => { settings[s.key] = s.value; });
  return jsonResponse(settings);
}

async function handleUpdateSettings(req: Request, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const adminClient = getAdminClient();

  // body should be { key: value, key2: value2, ... }
  const entries = Object.entries(body);
  if (entries.length === 0) return errorResponse(400, 'No settings to update');

  const upserts = entries.map(([key, value]) => ({
    key,
    value: String(value),
  }));

  const { error } = await adminClient.from('settings').upsert(upserts, { onConflict: 'key' });
  if (error) return errorResponse(500, 'Failed to update settings: ' + error.message);

  return jsonResponse({ message: 'Settings updated successfully', updated: entries.length });
}
