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
    const pathMatch = url.pathname.match(/\/courses\/?(.*)$/);
    const path = pathMatch ? pathMatch[1] : '';

    // ─── Course Routes ────────────────────────────────────────────────

    // GET /courses
    if (req.method === 'GET' && !path) return handleListCourses(url, user);

    // GET /courses/:id
    const courseIdMatch = path.match(/^(\d+)$/);
    if (req.method === 'GET' && courseIdMatch) return handleGetCourse(parseInt(courseIdMatch[1]), user);

    // GET /courses/:id/lessons
    const courseLessonsMatch = path.match(/^(\d+)\/lessons$/);
    if (req.method === 'GET' && courseLessonsMatch) return handleGetCourseLessons(parseInt(courseLessonsMatch[1]), user);

    // GET /courses/:id/enrolled-students
    const enrolledMatch = path.match(/^(\d+)\/enrolled-students$/);
    if (req.method === 'GET' && enrolledMatch) return handleGetEnrolledStudents(parseInt(enrolledMatch[1]), url, user);

    // POST /courses
    if (req.method === 'POST' && !path) return handleCreateCourse(req, user);

    // PUT /courses/:id
    if (req.method === 'PUT' && courseIdMatch) return handleUpdateCourse(req, parseInt(courseIdMatch[1]), user);

    // PATCH /courses/:id/archive
    const archiveMatch = path.match(/^(\d+)\/archive$/);
    if (req.method === 'PATCH' && archiveMatch) return handleArchiveCourse(parseInt(archiveMatch[1]), user);

    // PATCH /courses/:id/publish
    const publishMatch = path.match(/^(\d+)\/publish$/);
    if (req.method === 'PATCH' && publishMatch) return handlePublishCourse(parseInt(publishMatch[1]), user);

    // POST /courses/:id/reorder-lessons
    const reorderLessonsMatch = path.match(/^(\d+)\/reorder-lessons$/);
    if (req.method === 'POST' && reorderLessonsMatch) return handleReorderLessons(req, parseInt(reorderLessonsMatch[1]), user);

    // DELETE /courses/:id
    if (req.method === 'DELETE' && courseIdMatch) return handleDeleteCourse(parseInt(courseIdMatch[1]), user);

    // ─── Lesson Routes ────────────────────────────────────────────────

    // GET /courses/lessons/:id
    const lessonGetMatch = path.match(/^lessons\/(\d+)$/);
    if (req.method === 'GET' && lessonGetMatch) return handleGetLesson(parseInt(lessonGetMatch[1]), user);

    // POST /courses/:id/lessons
    if (req.method === 'POST' && courseLessonsMatch) return handleCreateLesson(req, parseInt(courseLessonsMatch[1]), user);

    // PUT /courses/lessons/:id
    if (req.method === 'PUT' && lessonGetMatch) return handleUpdateLesson(req, parseInt(lessonGetMatch[1]), user);

    // DELETE /courses/lessons/:id
    if (req.method === 'DELETE' && lessonGetMatch) return handleDeleteLesson(parseInt(lessonGetMatch[1]), user);

    // POST /courses/lessons/:id/reorder-topics
    const reorderTopicsMatch = path.match(/^lessons\/(\d+)\/reorder-topics$/);
    if (req.method === 'POST' && reorderTopicsMatch) return handleReorderTopics(req, parseInt(reorderTopicsMatch[1]), user);

    // ─── Topic Routes ─────────────────────────────────────────────────

    // GET /courses/topics/:id
    const topicGetMatch = path.match(/^topics\/(\d+)$/);
    if (req.method === 'GET' && topicGetMatch) return handleGetTopic(parseInt(topicGetMatch[1]), user);

    // GET /courses/lessons/:id/topics
    const lessonTopicsMatch = path.match(/^lessons\/(\d+)\/topics$/);
    if (req.method === 'GET' && lessonTopicsMatch) return handleGetLessonTopics(parseInt(lessonTopicsMatch[1]), user);

    // POST /courses/lessons/:id/topics
    if (req.method === 'POST' && lessonTopicsMatch) return handleCreateTopic(req, parseInt(lessonTopicsMatch[1]), user);

    // PUT /courses/topics/:id
    if (req.method === 'PUT' && topicGetMatch) return handleUpdateTopic(req, parseInt(topicGetMatch[1]), user);

    // DELETE /courses/topics/:id
    if (req.method === 'DELETE' && topicGetMatch) return handleDeleteTopic(parseInt(topicGetMatch[1]), user);

    return errorResponse(404, 'Not found');
  });
});

// ─── Course Handlers ──────────────────────────────────────────────────────────

async function handleListCourses(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer', 'Student']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const search = url.searchParams.get('search') || '';
  const status = url.searchParams.get('status') || '';
  const archived = url.searchParams.get('archived') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('courses').select('*', { count: 'exact' });

  if (archived === '1') {
    query = query.eq('is_archived', 1);
  } else {
    query = query.eq('is_archived', 0);
  }

  if (status && status !== 'all') query = query.eq('status', status);
  if (search) query = query.ilike('title', `%${search}%`);

  query = query.order('created_at', { ascending: false }).range(offset, offset + limit - 1);

  const { data: courses, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch courses: ' + error.message);

  const courseIds = (courses ?? []).map((c: any) => c.id);

  // Get lesson counts and enrolment counts in parallel
  const [{ data: lessons }, { data: enrolments }] = await Promise.all([
    adminClient.from('lessons').select('course_id').in('course_id', courseIds.length > 0 ? courseIds : [0]),
    adminClient.from('student_course_enrolments').select('course_id').in('course_id', courseIds.length > 0 ? courseIds : [0]),
  ]);

  const lessonCountMap = new Map<number, number>();
  (lessons ?? []).forEach((l: any) => lessonCountMap.set(l.course_id, (lessonCountMap.get(l.course_id) ?? 0) + 1));

  const enrolmentCountMap = new Map<number, number>();
  (enrolments ?? []).forEach((e: any) => enrolmentCountMap.set(e.course_id, (enrolmentCountMap.get(e.course_id) ?? 0) + 1));

  const enriched = (courses ?? []).map((c: any) => ({
    ...c,
    lessons_count: lessonCountMap.get(c.id) ?? 0,
    enrolments_count: enrolmentCountMap.get(c.id) ?? 0,
  }));

  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}

async function handleGetCourse(courseId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const [courseResult, lessonsResult, enrolmentsResult] = await Promise.all([
    adminClient.from('courses').select('*').eq('id', courseId).single(),
    adminClient.from('lessons').select('*').eq('course_id', courseId).order('order', { ascending: true }),
    adminClient.from('student_course_enrolments').select('user_id, status').eq('course_id', courseId),
  ]);

  if (courseResult.error || !courseResult.data) return errorResponse(404, 'Course not found');

  // Get topic counts per lesson
  const lessonIds = (lessonsResult.data ?? []).map((l: any) => l.id);
  let topicCountMap = new Map<number, number>();
  if (lessonIds.length > 0) {
    const { data: topics } = await adminClient.from('topics').select('lesson_id').in('lesson_id', lessonIds);
    (topics ?? []).forEach((t: any) => topicCountMap.set(t.lesson_id, (topicCountMap.get(t.lesson_id) ?? 0) + 1));
  }

  // Get enrolled student names (limit 50)
  const studentIds = Array.from(new Set((enrolmentsResult.data ?? []).map((e: any) => e.user_id)));
  let enrolledStudents: any[] = [];
  if (studentIds.length > 0) {
    const { data: students } = await adminClient.from('users').select('id, first_name, last_name').in('id', studentIds.slice(0, 50));
    const statusMap = new Map<number, string>();
    (enrolmentsResult.data ?? []).forEach((e: any) => statusMap.set(e.user_id, e.status));
    enrolledStudents = (students ?? []).map((s: any) => ({ ...s, status: statusMap.get(s.id) ?? 'ENROLLED' }));
  }

  return jsonResponse({
    ...courseResult.data,
    lessons: (lessonsResult.data ?? []).map((l: any) => ({ ...l, topics_count: topicCountMap.get(l.id) ?? 0 })),
    enrolments_count: enrolmentsResult.data?.length ?? 0,
    enrolled_students: enrolledStudents,
  });
}

async function handleGetCourseLessons(courseId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const { data: lessons, error } = await adminClient
    .from('lessons')
    .select('*')
    .eq('course_id', courseId)
    .order('order', { ascending: true });
  if (error) return errorResponse(500, 'Failed to fetch lessons: ' + error.message);

  // Get topic counts
  const lessonIds = (lessons ?? []).map((l: any) => l.id);
  let topicCountMap = new Map<number, number>();
  if (lessonIds.length > 0) {
    const { data: topics } = await adminClient.from('topics').select('lesson_id').in('lesson_id', lessonIds);
    (topics ?? []).forEach((t: any) => topicCountMap.set(t.lesson_id, (topicCountMap.get(t.lesson_id) ?? 0) + 1));
  }

  return jsonResponse({
    data: (lessons ?? []).map((l: any) => ({ ...l, topics_count: topicCountMap.get(l.id) ?? 0 })),
  });
}

async function handleGetEnrolledStudents(courseId: number, url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const limit = parseInt(url.searchParams.get('limit') || '50', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  const { data: enrolments, error, count } = await adminClient
    .from('student_course_enrolments')
    .select('*', { count: 'exact' })
    .eq('course_id', courseId)
    .order('created_at', { ascending: false })
    .range(offset, offset + limit - 1);
  if (error) return errorResponse(500, 'Failed to fetch enrolled students: ' + error.message);

  const userIds = (enrolments ?? []).map((e: any) => e.user_id);
  const { data: users } = await adminClient.from('users').select('id, first_name, last_name, email, is_active').in('id', userIds.length > 0 ? userIds : [0]);

  const userMap = new Map<number, any>();
  (users ?? []).forEach((u: any) => userMap.set(u.id, u));

  // Get progress data
  const { data: progressData } = await adminClient.from('course_progress').select('user_id, percentage').eq('course_id', courseId).in('user_id', userIds.length > 0 ? userIds : [0]);
  const progressMap = new Map<number, number>();
  (progressData ?? []).forEach((p: any) => progressMap.set(p.user_id, parseFloat(p.percentage || '0')));

  const enriched = (enrolments ?? []).map((e: any) => ({
    ...e,
    student: userMap.get(e.user_id) ?? null,
    progress_percentage: progressMap.get(e.user_id) ?? 0,
  }));

  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}

async function handleCreateCourse(req: Request, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { title, course_type, course_length_days, visibility, status, category, course_expiry_days, is_main_course, lb_content, next_course, next_course_after_days, auto_register_next_course, version, restricted_roles } = body;

  if (!title) return errorResponse(400, 'title is required');

  const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  const isSemester2 = title.toLowerCase().includes('semester 2');

  const adminClient = getAdminClient();
  const now = new Date().toISOString();

  const insertData: Record<string, unknown> = {
    title,
    slug,
    course_type: course_type || null,
    course_length_days: course_length_days ?? 90,
    course_expiry_days: course_expiry_days ?? 0,
    visibility: visibility || 'PRIVATE',
    status: status || 'DRAFT',
    category: category || null,
    is_main_course: isSemester2 ? 0 : (is_main_course ?? 1),
    is_archived: 0,
    version: version ?? 1,
    revisions: 0,
    next_course: next_course ?? 0,
    next_course_after_days: next_course_after_days ?? 0,
    auto_register_next_course: auto_register_next_course ?? 0,
    lb_content: lb_content || null,
    restricted_roles: restricted_roles ? JSON.stringify(restricted_roles) : null,
    created_at: now,
    updated_at: now,
  };

  if (status === 'PUBLISHED') {
    insertData.published_at = now;
  }

  const { data: newCourse, error } = await adminClient.from('courses').insert(insertData).select('id, title, slug, status, created_at').single();
  if (error) return errorResponse(500, 'Failed to create course: ' + error.message);

  await writeAuditLog({ logName: 'course', description: 'Course created', subjectType: 'course', subjectId: newCourse.id, causerId: user.lmsUserId, event: 'created', properties: { title, status: insertData.status } });
  return jsonResponse({ ...newCourse, message: 'Course created successfully' }, 201);
}

async function handleUpdateCourse(req: Request, courseId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const adminClient = getAdminClient();

  const { data: existing } = await adminClient.from('courses').select('id, status, revisions, published_at').eq('id', courseId).single();
  if (!existing) return errorResponse(404, 'Course not found');

  const updates: Record<string, unknown> = {};
  const { title, course_type, course_length_days, course_expiry_days, visibility, status, category, is_main_course, is_archived, lb_content, next_course, next_course_after_days, auto_register_next_course, version, restricted_roles } = body;

  if (title !== undefined) {
    updates.title = title;
    updates.slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const isSemester2 = title.toLowerCase().includes('semester 2');
    if (isSemester2) updates.is_main_course = 0;
    else if (is_main_course !== undefined) updates.is_main_course = is_main_course;
  } else if (is_main_course !== undefined) {
    updates.is_main_course = is_main_course;
  }

  if (course_type !== undefined) updates.course_type = course_type;
  if (course_length_days !== undefined) updates.course_length_days = course_length_days;
  if (course_expiry_days !== undefined) updates.course_expiry_days = course_expiry_days;
  if (visibility !== undefined) updates.visibility = visibility;
  if (category !== undefined) updates.category = category;
  if (is_archived !== undefined) updates.is_archived = is_archived;
  if (lb_content !== undefined) updates.lb_content = lb_content;
  if (next_course !== undefined) updates.next_course = next_course;
  if (next_course_after_days !== undefined) updates.next_course_after_days = next_course_after_days;
  if (auto_register_next_course !== undefined) updates.auto_register_next_course = auto_register_next_course;
  if (version !== undefined) updates.version = version;
  if (restricted_roles !== undefined) updates.restricted_roles = JSON.stringify(restricted_roles);

  if (status !== undefined) {
    updates.status = status;
    if (status === 'PUBLISHED' && existing.status !== 'PUBLISHED') {
      updates.published_at = new Date().toISOString();
    } else if (status === 'DRAFT') {
      updates.published_at = null;
    }
  }

  updates.revisions = (existing.revisions ?? 0) + 1;
  updates.updated_at = new Date().toISOString();

  if (Object.keys(updates).length === 0) return errorResponse(400, 'No fields to update');

  const { error } = await adminClient.from('courses').update(updates).eq('id', courseId);
  if (error) return errorResponse(500, 'Failed to update course: ' + error.message);

  await writeAuditLog({ logName: 'course', description: 'Course updated', subjectType: 'course', subjectId: courseId, causerId: user.lmsUserId, event: 'updated', properties: updates });
  return jsonResponse({ id: courseId, message: 'Course updated successfully' });
}

async function handleArchiveCourse(courseId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('courses').select('id, is_archived').eq('id', courseId).single();
  if (!existing) return errorResponse(404, 'Course not found');

  const newArchived = existing.is_archived === 1 ? 0 : 1;
  const { error } = await adminClient.from('courses').update({ is_archived: newArchived, updated_at: new Date().toISOString() }).eq('id', courseId);
  if (error) return errorResponse(500, 'Failed to archive course: ' + error.message);

  const action = newArchived === 1 ? 'archived' : 'unarchived';
  await writeAuditLog({ logName: 'course', description: `Course ${action}`, subjectType: 'course', subjectId: courseId, causerId: user.lmsUserId, event: action, properties: {} });
  return jsonResponse({ id: courseId, is_archived: newArchived, message: `Course ${action} successfully` });
}

async function handlePublishCourse(courseId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('courses').select('id, status').eq('id', courseId).single();
  if (!existing) return errorResponse(404, 'Course not found');

  const now = new Date().toISOString();
  const newStatus = existing.status === 'PUBLISHED' ? 'DRAFT' : 'PUBLISHED';
  const updates: Record<string, unknown> = { status: newStatus, updated_at: now };
  if (newStatus === 'PUBLISHED') updates.published_at = now;
  else updates.published_at = null;

  const { error } = await adminClient.from('courses').update(updates).eq('id', courseId);
  if (error) return errorResponse(500, 'Failed to update course status: ' + error.message);

  await writeAuditLog({ logName: 'course_status', description: `Course ${newStatus}`, subjectType: 'course', subjectId: courseId, causerId: user.lmsUserId, event: newStatus, properties: { published_at: updates.published_at } });
  return jsonResponse({ id: courseId, status: newStatus, message: `Course ${newStatus.toLowerCase()} successfully` });
}

async function handleReorderLessons(req: Request, courseId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { order } = body;
  if (!Array.isArray(order)) return errorResponse(400, 'order must be an array of lesson IDs');

  const adminClient = getAdminClient();
  const updates = order.map((lessonId: number, pos: number) =>
    adminClient.from('lessons').update({ order: pos }).eq('id', lessonId).eq('course_id', courseId)
  );
  await Promise.all(updates);

  return jsonResponse({ message: 'Lessons re-ordered successfully' });
}

async function handleDeleteCourse(courseId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('courses').select('id, title').eq('id', courseId).single();
  if (!existing) return errorResponse(404, 'Course not found');

  // Soft delete: archive and mark as deleted
  const { error } = await adminClient.from('courses').update({ is_archived: 1, status: 'DELETED', updated_at: new Date().toISOString() }).eq('id', courseId);
  if (error) return errorResponse(500, 'Failed to delete course: ' + error.message);

  await writeAuditLog({ logName: 'course', description: 'Course deleted', subjectType: 'course', subjectId: courseId, causerId: user.lmsUserId, event: 'deleted', properties: { title: existing.title } });
  return jsonResponse({ id: courseId, message: 'Course deleted successfully' });
}

// ─── Lesson Handlers ──────────────────────────────────────────────────────────

async function handleGetLesson(lessonId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const [lessonResult, topicsResult] = await Promise.all([
    adminClient.from('lessons').select('*').eq('id', lessonId).single(),
    adminClient.from('topics').select('*').eq('lesson_id', lessonId).order('order', { ascending: true }),
  ]);

  if (lessonResult.error || !lessonResult.data) return errorResponse(404, 'Lesson not found');

  // Get quiz counts per topic
  const topicIds = (topicsResult.data ?? []).map((t: any) => t.id);
  let quizCountMap = new Map<number, number>();
  if (topicIds.length > 0) {
    const { data: quizzes } = await adminClient.from('quizzes').select('topic_id').in('topic_id', topicIds);
    (quizzes ?? []).forEach((q: any) => quizCountMap.set(q.topic_id, (quizCountMap.get(q.topic_id) ?? 0) + 1));
  }

  return jsonResponse({
    ...lessonResult.data,
    topics: (topicsResult.data ?? []).map((t: any) => ({ ...t, quizzes_count: quizCountMap.get(t.id) ?? 0 })),
  });
}

async function handleCreateLesson(req: Request, courseId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { title, lb_content, release_key, release_value, has_work_placement } = body;
  if (!title) return errorResponse(400, 'title is required');

  const adminClient = getAdminClient();

  // Verify course exists
  const { data: course } = await adminClient.from('courses').select('id').eq('id', courseId).single();
  if (!course) return errorResponse(404, 'Course not found');

  // Get current lesson count for ordering
  const { count } = await adminClient.from('lessons').select('id', { count: 'exact', head: true }).eq('course_id', courseId);
  const order = count ?? 0;

  const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  const now = new Date().toISOString();

  const { data: newLesson, error } = await adminClient.from('lessons').insert({
    title,
    slug,
    course_id: courseId,
    order,
    release_key: release_key || 'IMMEDIATELY',
    release_value: release_value || null,
    has_work_placement: has_work_placement ?? 0,
    has_topic: false,
    lb_content: lb_content || null,
    created_at: now,
    updated_at: now,
  }).select('id, title, slug, order, created_at').single();
  if (error) return errorResponse(500, 'Failed to create lesson: ' + error.message);

  await writeAuditLog({ logName: 'lesson', description: 'Lesson created', subjectType: 'lesson', subjectId: newLesson.id, causerId: user.lmsUserId, event: 'created', properties: { title, course_id: courseId } });
  return jsonResponse({ ...newLesson, message: 'Lesson created successfully' }, 201);
}

async function handleUpdateLesson(req: Request, lessonId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const adminClient = getAdminClient();

  const { data: existing } = await adminClient.from('lessons').select('id, title').eq('id', lessonId).single();
  if (!existing) return errorResponse(404, 'Lesson not found');

  const updates: Record<string, unknown> = {};
  const { title, lb_content, course_id, release_key, release_value, has_work_placement } = body;

  if (title !== undefined) {
    updates.title = title;
    if (title !== existing.title) {
      updates.slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
  }
  if (lb_content !== undefined) updates.lb_content = lb_content;
  if (course_id !== undefined) updates.course_id = course_id;
  if (release_key !== undefined) updates.release_key = release_key;
  if (release_value !== undefined) updates.release_value = release_value;
  if (has_work_placement !== undefined) updates.has_work_placement = has_work_placement;
  updates.updated_at = new Date().toISOString();

  const { error } = await adminClient.from('lessons').update(updates).eq('id', lessonId);
  if (error) return errorResponse(500, 'Failed to update lesson: ' + error.message);

  await writeAuditLog({ logName: 'lesson', description: 'Lesson updated', subjectType: 'lesson', subjectId: lessonId, causerId: user.lmsUserId, event: 'updated', properties: updates });
  return jsonResponse({ id: lessonId, message: 'Lesson updated successfully' });
}

async function handleDeleteLesson(lessonId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('lessons').select('id, title, course_id').eq('id', lessonId).single();
  if (!existing) return errorResponse(404, 'Lesson not found');

  // Check for associated topics
  const { count: topicCount } = await adminClient.from('topics').select('id', { count: 'exact', head: true }).eq('lesson_id', lessonId);
  if (topicCount && topicCount > 0) return errorResponse(403, 'Delete associated topics first.');

  const { error } = await adminClient.from('lessons').delete().eq('id', lessonId);
  if (error) return errorResponse(500, 'Failed to delete lesson: ' + error.message);

  await writeAuditLog({ logName: 'lesson', description: 'Lesson deleted', subjectType: 'lesson', subjectId: lessonId, causerId: user.lmsUserId, event: 'deleted', properties: { title: existing.title, course_id: existing.course_id } });
  return jsonResponse({ id: lessonId, message: 'Lesson deleted successfully' });
}

async function handleReorderTopics(req: Request, lessonId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { order } = body;
  if (!Array.isArray(order)) return errorResponse(400, 'order must be an array of topic IDs');

  const adminClient = getAdminClient();
  const updates = order.map((topicId: number, pos: number) =>
    adminClient.from('topics').update({ order: pos }).eq('id', topicId).eq('lesson_id', lessonId)
  );
  await Promise.all(updates);

  return jsonResponse({ message: 'Topics re-ordered successfully' });
}

// ─── Topic Handlers ───────────────────────────────────────────────────────────

async function handleGetTopic(topicId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const [topicResult, quizzesResult] = await Promise.all([
    adminClient.from('topics').select('*').eq('id', topicId).single(),
    adminClient.from('quizzes').select('*').eq('topic_id', topicId).order('order', { ascending: true }),
  ]);

  if (topicResult.error || !topicResult.data) return errorResponse(404, 'Topic not found');

  return jsonResponse({
    ...topicResult.data,
    quizzes: quizzesResult.data ?? [],
  });
}

async function handleGetLessonTopics(lessonId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const { data: topics, error } = await adminClient
    .from('topics')
    .select('*')
    .eq('lesson_id', lessonId)
    .order('order', { ascending: true });
  if (error) return errorResponse(500, 'Failed to fetch topics: ' + error.message);

  // Get quiz counts
  const topicIds = (topics ?? []).map((t: any) => t.id);
  let quizCountMap = new Map<number, number>();
  if (topicIds.length > 0) {
    const { data: quizzes } = await adminClient.from('quizzes').select('topic_id').in('topic_id', topicIds);
    (quizzes ?? []).forEach((q: any) => quizCountMap.set(q.topic_id, (quizCountMap.get(q.topic_id) ?? 0) + 1));
  }

  return jsonResponse({
    data: (topics ?? []).map((t: any) => ({ ...t, quizzes_count: quizCountMap.get(t.id) ?? 0 })),
  });
}

async function handleCreateTopic(req: Request, lessonId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { title, lb_content, course_id, estimated_time } = body;
  if (!title) return errorResponse(400, 'title is required');

  const adminClient = getAdminClient();

  // Verify lesson exists
  const { data: lesson } = await adminClient.from('lessons').select('id, course_id').eq('id', lessonId).single();
  if (!lesson) return errorResponse(404, 'Lesson not found');

  const effectiveCourseId = course_id || lesson.course_id;

  // Get current topic count for ordering
  const { count } = await adminClient.from('topics').select('id', { count: 'exact', head: true }).eq('lesson_id', lessonId);
  const order = count ?? 0;

  const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  const now = new Date().toISOString();

  const { data: newTopic, error } = await adminClient.from('topics').insert({
    title,
    slug,
    lesson_id: lessonId,
    course_id: effectiveCourseId,
    order,
    estimated_time: estimated_time ?? 0,
    has_quiz: 0,
    lb_content: lb_content || null,
    created_at: now,
    updated_at: now,
  }).select('id, title, slug, order, created_at').single();
  if (error) return errorResponse(500, 'Failed to create topic: ' + error.message);

  // Update lesson has_topic flag
  await adminClient.from('lessons').update({ has_topic: true }).eq('id', lessonId);

  await writeAuditLog({ logName: 'topic', description: 'Topic created', subjectType: 'topic', subjectId: newTopic.id, causerId: user.lmsUserId, event: 'created', properties: { title, lesson_id: lessonId, course_id: effectiveCourseId } });
  return jsonResponse({ ...newTopic, message: 'Topic created successfully' }, 201);
}

async function handleUpdateTopic(req: Request, topicId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const adminClient = getAdminClient();

  const { data: existing } = await adminClient.from('topics').select('id, title, estimated_time').eq('id', topicId).single();
  if (!existing) return errorResponse(404, 'Topic not found');

  const updates: Record<string, unknown> = {};
  const { title, lb_content, course_id, lesson_id, estimated_time } = body;

  if (title !== undefined) {
    updates.title = title;
    if (title !== existing.title) {
      updates.slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
  }
  if (lb_content !== undefined) updates.lb_content = lb_content;
  if (course_id !== undefined) updates.course_id = course_id;
  if (lesson_id !== undefined) updates.lesson_id = lesson_id;
  if (estimated_time !== undefined) updates.estimated_time = estimated_time;
  updates.updated_at = new Date().toISOString();

  const { error } = await adminClient.from('topics').update(updates).eq('id', topicId);
  if (error) return errorResponse(500, 'Failed to update topic: ' + error.message);

  await writeAuditLog({ logName: 'topic', description: 'Topic updated', subjectType: 'topic', subjectId: topicId, causerId: user.lmsUserId, event: 'updated', properties: updates });
  return jsonResponse({ id: topicId, message: 'Topic updated successfully' });
}

async function handleDeleteTopic(topicId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('topics').select('id, title, lesson_id, course_id').eq('id', topicId).single();
  if (!existing) return errorResponse(404, 'Topic not found');

  // Check for associated quizzes
  const { count: quizCount } = await adminClient.from('quizzes').select('id', { count: 'exact', head: true }).eq('topic_id', topicId);
  if (quizCount && quizCount > 0) return errorResponse(403, 'Delete associated quizzes first.');

  const { error } = await adminClient.from('topics').delete().eq('id', topicId);
  if (error) return errorResponse(500, 'Failed to delete topic: ' + error.message);

  // Check if lesson still has topics
  const { count: remainingTopics } = await adminClient.from('topics').select('id', { count: 'exact', head: true }).eq('lesson_id', existing.lesson_id);
  if (remainingTopics === 0) {
    await adminClient.from('lessons').update({ has_topic: false }).eq('id', existing.lesson_id);
  }

  await writeAuditLog({ logName: 'topic', description: 'Topic deleted', subjectType: 'topic', subjectId: topicId, causerId: user.lmsUserId, event: 'deleted', properties: { title: existing.title, lesson_id: existing.lesson_id, course_id: existing.course_id } });
  return jsonResponse({ id: topicId, message: 'Topic deleted successfully' });
}
