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
    const pathMatch = url.pathname.match(/\/companies\/?(.*)$/);
    const path = pathMatch ? pathMatch[1] : '';

    // GET /companies
    if (req.method === 'GET' && !path) return handleListCompanies(url, user);

    // GET /companies/:id
    const idMatch = path.match(/^(\d+)$/);
    if (req.method === 'GET' && idMatch) return handleGetCompany(parseInt(idMatch[1]), user);

    // GET /companies/:id/students
    const studentsMatch = path.match(/^(\d+)\/students$/);
    if (req.method === 'GET' && studentsMatch) return handleGetCompanyStudents(parseInt(studentsMatch[1]), url, user);

    // GET /companies/:id/leaders
    const leadersMatch = path.match(/^(\d+)\/leaders$/);
    if (req.method === 'GET' && leadersMatch) return handleGetCompanyLeaders(parseInt(leadersMatch[1]), user);

    // GET /companies/:id/signup-links
    const signupLinksMatch = path.match(/^(\d+)\/signup-links$/);
    if (req.method === 'GET' && signupLinksMatch) return handleGetSignupLinks(parseInt(signupLinksMatch[1]), user);

    // POST /companies
    if (req.method === 'POST' && !path) return handleCreateCompany(req, user);

    // PUT /companies/:id
    if (req.method === 'PUT' && idMatch) return handleUpdateCompany(req, parseInt(idMatch[1]), user);

    // DELETE /companies/:id
    if (req.method === 'DELETE' && idMatch) return handleDeleteCompany(parseInt(idMatch[1]), user);

    // POST /companies/:id/signup-links
    if (req.method === 'POST' && signupLinksMatch) return handleCreateSignupLink(req, parseInt(signupLinksMatch[1]), user);

    // DELETE /companies/signup-links/:id
    const deleteSignupMatch = path.match(/^signup-links\/(\d+)$/);
    if (req.method === 'DELETE' && deleteSignupMatch) return handleDeleteSignupLink(parseInt(deleteSignupMatch[1]), user);

    return errorResponse(404, 'Not found');
  });
});

// ─── Handlers ─────────────────────────────────────────────────────────────────

async function handleListCompanies(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const search = url.searchParams.get('search') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('companies').select('*', { count: 'exact' }).is('deleted_at', null);
  if (search) query = query.or(`name.ilike.%${search}%,email.ilike.%${search}%`);
  query = query.order('name', { ascending: true }).range(offset, offset + limit - 1);

  const { data: companies, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch companies: ' + error.message);

  const companyIds = (companies ?? []).map((c: any) => c.id);

  // Get student counts via user_has_attachables
  const { data: attachables } = await adminClient
    .from('user_has_attachables')
    .select('attachable_id')
    .eq('attachable_type', 'App\\Models\\Company')
    .in('attachable_id', companyIds.length > 0 ? companyIds : [0]);

  const studentCountMap = new Map<number, number>();
  (attachables ?? []).forEach((a: any) => studentCountMap.set(a.attachable_id, (studentCountMap.get(a.attachable_id) ?? 0) + 1));

  // Get POC user names
  const pocIds = (companies ?? []).map((c: any) => c.poc_user_id).filter(Boolean);
  let pocMap = new Map<number, string>();
  if (pocIds.length > 0) {
    const { data: pocUsers } = await adminClient.from('users').select('id, first_name, last_name').in('id', pocIds);
    (pocUsers ?? []).forEach((u: any) => pocMap.set(u.id, `${u.first_name} ${u.last_name}`));
  }

  const enriched = (companies ?? []).map((c: any) => ({
    ...c,
    student_count: studentCountMap.get(c.id) ?? 0,
    leader_name: c.poc_user_id ? pocMap.get(c.poc_user_id) ?? null : null,
  }));

  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}

async function handleGetCompany(companyId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  const { data: company, error } = await adminClient.from('companies').select('*').eq('id', companyId).single();
  if (error || !company) return errorResponse(404, 'Company not found');

  // Get all users linked to this company
  const { data: attachables } = await adminClient
    .from('user_has_attachables')
    .select('user_id')
    .eq('attachable_type', 'App\\Models\\Company')
    .eq('attachable_id', companyId);

  const linkedUserIds = (attachables ?? []).map((a: any) => a.user_id);

  let leaders: any[] = [];
  let students: any[] = [];

  if (linkedUserIds.length > 0) {
    const [usersResult, rolesResult] = await Promise.all([
      adminClient.from('users').select('id, first_name, last_name, email, is_active').in('id', linkedUserIds),
      adminClient.from('model_has_roles').select('model_id, role_id').in('model_id', linkedUserIds),
    ]);

    const { data: allRoles } = await adminClient.from('roles').select('id, name');
    const roleNameMap = new Map<number, string>();
    (allRoles ?? []).forEach((r: any) => roleNameMap.set(r.id, r.name));

    const userRoleMap = new Map<number, string>();
    (rolesResult.data ?? []).forEach((mr: any) => userRoleMap.set(mr.model_id, roleNameMap.get(mr.role_id) ?? 'Student'));

    (usersResult.data ?? []).forEach((u: any) => {
      const role = userRoleMap.get(u.id) ?? 'Student';
      if (role === 'Leader') leaders.push(u);
      else students.push(u);
    });
  }

  // Get signup links
  const { data: links } = await adminClient.from('signup_links').select('id, key, course_id, is_active, created_at').eq('company_id', companyId);

  let signupLinks: any[] = [];
  if (links && links.length > 0) {
    const courseIds = Array.from(new Set(links.map((l: any) => l.course_id)));
    const { data: courses } = await adminClient.from('courses').select('id, title').in('id', courseIds);
    const courseMap = new Map<number, string>();
    (courses ?? []).forEach((c: any) => courseMap.set(c.id, c.title));
    signupLinks = links.map((l: any) => ({
      id: l.id, key: l.key, course_title: courseMap.get(l.course_id) ?? 'Unknown', is_active: l.is_active, created_at: l.created_at,
    }));
  }

  // Get POC and BM user names
  let poc_user_name: string | null = null;
  let bm_user_name: string | null = null;
  const specialIds = [company.poc_user_id, company.bm_user_id].filter(Boolean);
  if (specialIds.length > 0) {
    const { data: specialUsers } = await adminClient.from('users').select('id, first_name, last_name').in('id', specialIds);
    (specialUsers ?? []).forEach((u: any) => {
      if (u.id === company.poc_user_id) poc_user_name = `${u.first_name} ${u.last_name}`;
      if (u.id === company.bm_user_id) bm_user_name = `${u.first_name} ${u.last_name}`;
    });
  }

  return jsonResponse({ ...company, poc_user_name, bm_user_name, leaders, students, signup_links: signupLinks });
}

async function handleGetCompanyStudents(companyId: number, url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const limit = parseInt(url.searchParams.get('limit') || '50', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  const { data: attachables } = await adminClient
    .from('user_has_attachables')
    .select('user_id')
    .eq('attachable_type', 'App\\Models\\Company')
    .eq('attachable_id', companyId);

  const userIds = (attachables ?? []).map((a: any) => a.user_id);
  if (userIds.length === 0) return jsonResponse({ data: [], total: 0, limit, offset });

  const { data: users, error } = await adminClient
    .from('users')
    .select('id, first_name, last_name, email, is_active, is_archived, created_at')
    .in('id', userIds)
    .order('first_name', { ascending: true })
    .range(offset, offset + limit - 1);
  if (error) return errorResponse(500, 'Failed to fetch students: ' + error.message);

  return jsonResponse({ data: users ?? [], total: userIds.length, limit, offset });
}

async function handleGetCompanyLeaders(companyId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();

  // Get leader role ID
  const { data: leaderRole } = await adminClient.from('roles').select('id').eq('name', 'Leader').single();
  if (!leaderRole) return jsonResponse({ data: [] });

  // Get all users linked to this company
  const { data: attachables } = await adminClient
    .from('user_has_attachables')
    .select('user_id')
    .eq('attachable_type', 'App\\Models\\Company')
    .eq('attachable_id', companyId);

  const userIds = (attachables ?? []).map((a: any) => a.user_id);
  if (userIds.length === 0) return jsonResponse({ data: [] });

  // Filter to only leaders
  const { data: leaderAssignments } = await adminClient
    .from('model_has_roles')
    .select('model_id')
    .eq('role_id', leaderRole.id)
    .in('model_id', userIds);

  const leaderIds = (leaderAssignments ?? []).map((a: any) => a.model_id);
  if (leaderIds.length === 0) return jsonResponse({ data: [] });

  const { data: leaders } = await adminClient
    .from('users')
    .select('id, first_name, last_name, email, is_active')
    .in('id', leaderIds);

  return jsonResponse({ data: leaders ?? [] });
}

async function handleGetSignupLinks(companyId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: links, error } = await adminClient.from('signup_links').select('*').eq('company_id', companyId).order('created_at', { ascending: false });
  if (error) return errorResponse(500, 'Failed to fetch signup links: ' + error.message);

  // Get course titles
  const courseIds = Array.from(new Set((links ?? []).map((l: any) => l.course_id)));
  const { data: courses } = await adminClient.from('courses').select('id, title').in('id', courseIds.length > 0 ? courseIds : [0]);
  const courseMap = new Map<number, string>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c.title));

  const enriched = (links ?? []).map((l: any) => ({ ...l, course_title: courseMap.get(l.course_id) ?? 'Unknown' }));
  return jsonResponse({ data: enriched });
}

async function handleCreateCompany(req: Request, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { name, email, address, number: phone, poc_user_id, bm_user_id } = body;
  if (!name || !email) return errorResponse(400, 'name and email are required');

  const adminClient = getAdminClient();
  const now = new Date().toISOString();

  const { data: newCompany, error } = await adminClient.from('companies').insert({
    name,
    email,
    address: address || null,
    number: phone || '',
    poc_user_id: poc_user_id || null,
    bm_user_id: bm_user_id || null,
    created_by: user.lmsUserId?.toString() ?? '',
    modified_by: '[]',
    created_at: now,
    updated_at: now,
  }).select('id, name, email, created_at').single();
  if (error) return errorResponse(500, 'Failed to create company: ' + error.message);

  await writeAuditLog({ logName: 'company', description: 'Company created', subjectType: 'company', subjectId: newCompany.id, causerId: user.lmsUserId, event: 'created', properties: { name, email } });
  return jsonResponse({ ...newCompany, message: 'Company created successfully' }, 201);
}

async function handleUpdateCompany(req: Request, companyId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const adminClient = getAdminClient();

  const { data: existing } = await adminClient.from('companies').select('id').eq('id', companyId).single();
  if (!existing) return errorResponse(404, 'Company not found');

  const updates: Record<string, unknown> = {};
  const { name, email, address, number: phone, poc_user_id, bm_user_id } = body;

  if (name !== undefined) updates.name = name;
  if (email !== undefined) updates.email = email;
  if (address !== undefined) updates.address = address;
  if (phone !== undefined) updates.number = phone;
  if (poc_user_id !== undefined) updates.poc_user_id = poc_user_id;
  if (bm_user_id !== undefined) updates.bm_user_id = bm_user_id;
  updates.updated_at = new Date().toISOString();

  if (Object.keys(updates).length <= 1) return errorResponse(400, 'No fields to update');

  const { error } = await adminClient.from('companies').update(updates).eq('id', companyId);
  if (error) return errorResponse(500, 'Failed to update company: ' + error.message);

  await writeAuditLog({ logName: 'company', description: 'Company updated', subjectType: 'company', subjectId: companyId, causerId: user.lmsUserId, event: 'updated', properties: updates });
  return jsonResponse({ id: companyId, message: 'Company updated successfully' });
}

async function handleDeleteCompany(companyId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('companies').select('id, name').eq('id', companyId).single();
  if (!existing) return errorResponse(404, 'Company not found');

  // Soft delete
  const { error } = await adminClient.from('companies').update({ deleted_at: new Date().toISOString() }).eq('id', companyId);
  if (error) return errorResponse(500, 'Failed to delete company: ' + error.message);

  await writeAuditLog({ logName: 'company', description: 'Company deleted', subjectType: 'company', subjectId: companyId, causerId: user.lmsUserId, event: 'deleted', properties: { name: existing.name } });
  return jsonResponse({ id: companyId, message: 'Company deleted successfully' });
}

async function handleCreateSignupLink(req: Request, companyId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { course_id, leader_id } = body;
  if (!course_id) return errorResponse(400, 'course_id is required');

  const adminClient = getAdminClient();

  // Generate unique key
  const key = crypto.randomUUID().replace(/-/g, '').substring(0, 16);
  const now = new Date().toISOString();

  const { data: newLink, error } = await adminClient.from('signup_links').insert({
    key,
    company_id: companyId,
    course_id,
    leader_id: leader_id || null,
    creator_id: user.lmsUserId,
    is_active: 1,
    created_at: now,
    updated_at: now,
  }).select('id, key, course_id, is_active, created_at').single();
  if (error) return errorResponse(500, 'Failed to create signup link: ' + error.message);

  await writeAuditLog({ logName: 'signup_link', description: 'Signup link created', subjectType: 'signup_link', subjectId: newLink.id, causerId: user.lmsUserId, event: 'created', properties: { company_id: companyId, course_id } });
  return jsonResponse({ ...newLink, message: 'Signup link created successfully' }, 201);
}

async function handleDeleteSignupLink(linkId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('signup_links').select('id, company_id').eq('id', linkId).single();
  if (!existing) return errorResponse(404, 'Signup link not found');

  const { error } = await adminClient.from('signup_links').delete().eq('id', linkId);
  if (error) return errorResponse(500, 'Failed to delete signup link: ' + error.message);

  await writeAuditLog({ logName: 'signup_link', description: 'Signup link deleted', subjectType: 'signup_link', subjectId: linkId, causerId: user.lmsUserId, event: 'deleted', properties: { company_id: existing.company_id } });
  return jsonResponse({ id: linkId, message: 'Signup link deleted successfully' });
}
