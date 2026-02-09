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
    const pathMatch = url.pathname.match(/\/assessments\/?(.*)$/);
    const path = pathMatch ? pathMatch[1] : '';

    // ─── Quiz Attempt Routes ──────────────────────────────────────────

    // GET /assessments (list quiz attempts)
    if (req.method === 'GET' && !path) return handleListAssessments(url, user);

    // GET /assessments/:id (quiz attempt detail)
    const attemptIdMatch = path.match(/^(\d+)$/);
    if (req.method === 'GET' && attemptIdMatch) return handleGetAssessment(parseInt(attemptIdMatch[1]), user);

    // POST /assessments/:id/evaluate (mark a question)
    const evaluateMatch = path.match(/^(\d+)\/evaluate$/);
    if (req.method === 'POST' && evaluateMatch) return handleEvaluateQuestion(req, parseInt(evaluateMatch[1]), user);

    // POST /assessments/:id/feedback (submit final feedback/status)
    const feedbackMatch = path.match(/^(\d+)\/feedback$/);
    if (req.method === 'POST' && feedbackMatch) return handleSubmitFeedback(req, parseInt(feedbackMatch[1]), user);

    // PATCH /assessments/:id/return (return to student)
    const returnMatch = path.match(/^(\d+)\/return$/);
    if (req.method === 'PATCH' && returnMatch) return handleReturnAssessment(parseInt(returnMatch[1]), user);

    // PATCH /assessments/:id/status (update status)
    const statusMatch = path.match(/^(\d+)\/status$/);
    if (req.method === 'PATCH' && statusMatch) return handleUpdateStatus(req, parseInt(statusMatch[1]), user);

    // ─── Quiz CRUD Routes ─────────────────────────────────────────────

    // GET /assessments/quizzes (list quizzes)
    if (req.method === 'GET' && path === 'quizzes') return handleListQuizzes(url, user);

    // GET /assessments/quizzes/:id
    const quizGetMatch = path.match(/^quizzes\/(\d+)$/);
    if (req.method === 'GET' && quizGetMatch) return handleGetQuiz(parseInt(quizGetMatch[1]), user);

    // POST /assessments/quizzes
    if (req.method === 'POST' && path === 'quizzes') return handleCreateQuiz(req, user);

    // PUT /assessments/quizzes/:id
    if (req.method === 'PUT' && quizGetMatch) return handleUpdateQuiz(req, parseInt(quizGetMatch[1]), user);

    // DELETE /assessments/quizzes/:id
    if (req.method === 'DELETE' && quizGetMatch) return handleDeleteQuiz(parseInt(quizGetMatch[1]), user);

    // POST /assessments/quizzes/:id/reorder-questions
    const reorderQMatch = path.match(/^quizzes\/(\d+)\/reorder-questions$/);
    if (req.method === 'POST' && reorderQMatch) return handleReorderQuestions(req, parseInt(reorderQMatch[1]), user);

    // ─── Enrolment Routes ─────────────────────────────────────────────

    // GET /assessments/enrolments (list enrolments)
    if (req.method === 'GET' && path === 'enrolments') return handleListEnrolments(url, user);

    return errorResponse(404, 'Not found');
  });
});

// ─── Quiz Attempt Handlers ────────────────────────────────────────────────────

async function handleListAssessments(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer', 'Student']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const status = url.searchParams.get('status') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('quiz_attempts').select('*', { count: 'exact' });

  // Students can only see their own
  if (user.role === 'Student' && user.lmsUserId) {
    query = query.eq('user_id', user.lmsUserId);
  }

  if (status && status !== 'all') query = query.eq('status', status);
  query = query.order('created_at', { ascending: false }).range(offset, offset + limit - 1);

  const { data: attempts, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch assessments: ' + error.message);

  // Get student names and course titles
  const studentIds = Array.from(new Set((attempts ?? []).map((a: any) => a.user_id)));
  const courseIds = Array.from(new Set((attempts ?? []).map((a: any) => a.course_id)));

  const [{ data: students }, { data: courses }] = await Promise.all([
    adminClient.from('users').select('id, first_name, last_name').in('id', studentIds.length > 0 ? studentIds : [0]),
    adminClient.from('courses').select('id, title').in('id', courseIds.length > 0 ? courseIds : [0]),
  ]);

  const studentMap = new Map<number, string>();
  (students ?? []).forEach((s: any) => studentMap.set(s.id, `${s.first_name} ${s.last_name}`));

  const courseMap = new Map<number, string>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c.title));

  const enriched = (attempts ?? []).map((a: any) => ({
    id: a.id,
    type: 'quiz_attempt',
    student_id: a.user_id,
    student_name: studentMap.get(a.user_id) ?? 'Unknown',
    course_id: a.course_id,
    course_title: courseMap.get(a.course_id) ?? 'Unknown',
    quiz_id: a.quiz_id,
    status: a.status,
    score: a.score,
    system_result: a.system_result,
    created_at: a.created_at,
    updated_at: a.updated_at,
  }));

  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}

async function handleGetAssessment(attemptId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const { data: attempt, error } = await adminClient.from('quiz_attempts').select('*').eq('id', attemptId).single();
  if (error || !attempt) return errorResponse(404, 'Assessment not found');

  // Students can only see their own
  if (user.role === 'Student' && user.lmsUserId !== attempt.user_id) {
    return errorResponse(403, 'Access denied');
  }

  // Get related data in parallel
  const [studentResult, courseResult, lessonResult, topicResult, quizResult, evaluationsResult] = await Promise.all([
    adminClient.from('users').select('first_name, last_name, email').eq('id', attempt.user_id).single(),
    adminClient.from('courses').select('title').eq('id', attempt.course_id).single(),
    adminClient.from('lessons').select('title').eq('id', attempt.lesson_id).single(),
    adminClient.from('topics').select('title').eq('id', attempt.topic_id).single(),
    adminClient.from('quizzes').select('title, passing_percentage, allowed_attempts, questions(id, title, answer_type, options, correct_answer, order, is_deleted)').eq('id', attempt.quiz_id).single(),
    adminClient.from('evaluations').select('*').eq('quiz_attempt_id', attemptId).order('created_at', { ascending: false }),
  ]);

  // If status is SUBMITTED, auto-update to REVIEWING
  if (attempt.status === 'SUBMITTED') {
    await adminClient.from('quiz_attempts').update({ status: 'REVIEWING' }).eq('id', attemptId);
    attempt.status = 'REVIEWING';
  }

  // Get feedbacks
  const { data: feedbacks } = await adminClient.from('feedbacks').select('*').eq('attachable_id', attempt.quiz_id).eq('user_id', attempt.user_id).order('created_at', { ascending: false });

  return jsonResponse({
    ...attempt,
    student_name: studentResult.data ? `${studentResult.data.first_name} ${studentResult.data.last_name}` : 'Unknown',
    student_email: studentResult.data?.email ?? '',
    course_title: courseResult.data?.title ?? 'Unknown',
    lesson_title: lessonResult.data?.title ?? 'Unknown',
    topic_title: topicResult.data?.title ?? 'Unknown',
    quiz_title: quizResult.data?.title ?? 'Unknown',
    passing_percentage: quizResult.data?.passing_percentage ?? 0,
    allowed_attempts: quizResult.data?.allowed_attempts ?? 999,
    questions: quizResult.data?.questions ?? [],
    evaluations: evaluationsResult.data ?? [],
    feedbacks: feedbacks ?? [],
  });
}

async function handleEvaluateQuestion(req: Request, attemptId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Trainer']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { question, status: qStatus, comment } = body;
  if (!question) return errorResponse(400, 'question ID is required');

  const adminClient = getAdminClient();
  const { data: attempt } = await adminClient.from('quiz_attempts').select('id, user_id, quiz_id').eq('id', attemptId).single();
  if (!attempt) return errorResponse(404, 'Assessment not found');

  // Get latest evaluation or create new
  const { data: existingEval } = await adminClient
    .from('evaluations')
    .select('*')
    .eq('quiz_attempt_id', attemptId)
    .order('created_at', { ascending: false })
    .limit(1)
    .maybeSingle();

  const now = new Date().toISOString();

  if (existingEval && !existingEval.status) {
    // Update existing incomplete evaluation
    const existingResults = existingEval.results ? (typeof existingEval.results === 'string' ? JSON.parse(existingEval.results) : existingEval.results) : {};
    existingResults[question] = { status: qStatus ?? '', comment: comment ?? '' };

    const { error } = await adminClient.from('evaluations').update({
      results: JSON.stringify(existingResults),
      updated_at: now,
    }).eq('id', existingEval.id);
    if (error) return errorResponse(500, 'Failed to update evaluation: ' + error.message);

    return jsonResponse({ evaluation_id: existingEval.id, message: 'Question evaluated successfully' });
  } else {
    // Create new evaluation
    const results = { [question]: { status: qStatus ?? '', comment: comment ?? '' } };
    const { data: newEval, error } = await adminClient.from('evaluations').insert({
      quiz_attempt_id: attemptId,
      student_id: attempt.user_id,
      evaluator_id: user.lmsUserId,
      results: JSON.stringify(results),
      created_at: now,
      updated_at: now,
    }).select('id').single();
    if (error) return errorResponse(500, 'Failed to create evaluation: ' + error.message);

    return jsonResponse({ evaluation_id: newEval.id, message: 'Question evaluated successfully' });
  }
}

async function handleSubmitFeedback(req: Request, attemptId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Trainer']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { status: finalStatus, feedback, assisted } = body;
  if (!finalStatus) return errorResponse(400, 'status is required (satisfactory or fail)');

  const adminClient = getAdminClient();
  const { data: attempt } = await adminClient.from('quiz_attempts').select('*').eq('id', attemptId).single();
  if (!attempt) return errorResponse(404, 'Assessment not found');

  const now = new Date().toISOString();
  const upperStatus = finalStatus.toUpperCase();

  // Update the latest evaluation with final status
  const { data: latestEval } = await adminClient
    .from('evaluations')
    .select('*')
    .eq('quiz_attempt_id', attemptId)
    .order('created_at', { ascending: false })
    .limit(1)
    .maybeSingle();

  if (latestEval) {
    await adminClient.from('evaluations').update({
      status: upperStatus,
      updated_at: now,
    }).eq('id', latestEval.id);
  }

  // Save feedback
  if (feedback) {
    await adminClient.from('feedbacks').insert({
      body: JSON.stringify({ message: feedback, evaluation_id: latestEval?.id, attempt_id: attemptId }),
      user_id: attempt.user_id,
      owner_id: user.lmsUserId,
      attachable_type: 'App\\Models\\Quiz',
      attachable_id: attempt.quiz_id,
      created_at: now,
      updated_at: now,
    });
  }

  // Update quiz attempt status
  const attemptStatus = upperStatus === 'SATISFACTORY' ? 'SATISFACTORY' : 'FAIL';
  await adminClient.from('quiz_attempts').update({
    status: attemptStatus,
    assisted: assisted ?? false,
    accessed_at: now,
    accessor_id: user.lmsUserId,
    is_valid_accessor: true,
    updated_at: now,
  }).eq('id', attemptId);

  await writeAuditLog({
    logName: 'assessment',
    description: `Assessment marked as ${attemptStatus}`,
    subjectType: 'quiz_attempt',
    subjectId: attemptId,
    causerId: user.lmsUserId,
    event: 'marked',
    properties: { status: attemptStatus, student_id: attempt.user_id, quiz_id: attempt.quiz_id },
  });

  return jsonResponse({ id: attemptId, status: attemptStatus, message: `Assessment marked as ${attemptStatus.toLowerCase()}` });
}

async function handleReturnAssessment(attemptId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: attempt } = await adminClient.from('quiz_attempts').select('id, user_id, quiz_id').eq('id', attemptId).single();
  if (!attempt) return errorResponse(404, 'Assessment not found');

  const { error } = await adminClient.from('quiz_attempts').update({
    status: 'RETURNED',
    updated_at: new Date().toISOString(),
  }).eq('id', attemptId);
  if (error) return errorResponse(500, 'Failed to return assessment: ' + error.message);

  await writeAuditLog({
    logName: 'assessment',
    description: 'Assessment returned to student',
    subjectType: 'quiz_attempt',
    subjectId: attemptId,
    causerId: user.lmsUserId,
    event: 'returned',
    properties: { student_id: attempt.user_id },
  });

  return jsonResponse({ id: attemptId, status: 'RETURNED', message: 'Assessment returned to student' });
}

async function handleUpdateStatus(req: Request, attemptId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Trainer']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { status: newStatus } = body;
  if (!newStatus) return errorResponse(400, 'status is required');

  const adminClient = getAdminClient();
  const { data: attempt } = await adminClient.from('quiz_attempts').select('id').eq('id', attemptId).single();
  if (!attempt) return errorResponse(404, 'Assessment not found');

  const { error } = await adminClient.from('quiz_attempts').update({
    status: newStatus.toUpperCase(),
    updated_at: new Date().toISOString(),
  }).eq('id', attemptId);
  if (error) return errorResponse(500, 'Failed to update status: ' + error.message);

  return jsonResponse({ id: attemptId, status: newStatus.toUpperCase(), message: 'Status updated successfully' });
}

// ─── Quiz CRUD Handlers ──────────────────────────────────────────────────────

async function handleListQuizzes(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const search = url.searchParams.get('search') || '';
  const courseId = url.searchParams.get('course_id') || '';
  const topicId = url.searchParams.get('topic_id') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('quizzes').select('*', { count: 'exact' });
  if (search) query = query.ilike('title', `%${search}%`);
  if (courseId) query = query.eq('course_id', parseInt(courseId));
  if (topicId) query = query.eq('topic_id', parseInt(topicId));
  query = query.order('created_at', { ascending: false }).range(offset, offset + limit - 1);

  const { data: quizzes, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch quizzes: ' + error.message);

  // Get question counts
  const quizIds = (quizzes ?? []).map((q: any) => q.id);
  const { data: questions } = await adminClient.from('questions').select('quiz_id').in('quiz_id', quizIds.length > 0 ? quizIds : [0]);

  const questionCountMap = new Map<number, number>();
  (questions ?? []).forEach((q: any) => questionCountMap.set(q.quiz_id, (questionCountMap.get(q.quiz_id) ?? 0) + 1));

  const enriched = (quizzes ?? []).map((q: any) => ({
    ...q,
    questions_count: questionCountMap.get(q.id) ?? 0,
  }));

  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}

async function handleGetQuiz(quizId: number, user: AuthUser): Promise<Response> {
  const adminClient = getAdminClient();

  const [quizResult, questionsResult, attemptsResult] = await Promise.all([
    adminClient.from('quizzes').select('*').eq('id', quizId).single(),
    adminClient.from('questions').select('*').eq('quiz_id', quizId).order('order', { ascending: true }),
    adminClient.from('quiz_attempts').select('id, user_id, status, score, created_at').eq('quiz_id', quizId).order('created_at', { ascending: false }).limit(20),
  ]);

  if (quizResult.error || !quizResult.data) return errorResponse(404, 'Quiz not found');

  // Get course and topic titles
  const [courseResult, topicResult] = await Promise.all([
    adminClient.from('courses').select('title').eq('id', quizResult.data.course_id).single(),
    adminClient.from('topics').select('title').eq('id', quizResult.data.topic_id).single(),
  ]);

  return jsonResponse({
    ...quizResult.data,
    course_title: courseResult.data?.title ?? 'Unknown',
    topic_title: topicResult.data?.title ?? 'Unknown',
    questions: questionsResult.data ?? [],
    recent_attempts: attemptsResult.data ?? [],
  });
}

async function handleCreateQuiz(req: Request, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { title, lb_content, course_id, lesson_id, topic_id, estimated_time, passing_percentage, allowed_attempts, has_checklist } = body;
  if (!title || !course_id || !lesson_id || !topic_id) return errorResponse(400, 'title, course_id, lesson_id, and topic_id are required');

  const adminClient = getAdminClient();

  // Get current quiz count for ordering
  const { count } = await adminClient.from('quizzes').select('id', { count: 'exact', head: true }).eq('topic_id', topic_id);
  const order = count ?? 0;

  const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  const now = new Date().toISOString();

  const { data: newQuiz, error } = await adminClient.from('quizzes').insert({
    title,
    slug,
    course_id,
    lesson_id,
    topic_id,
    order,
    estimated_time: estimated_time ?? 0,
    passing_percentage: passing_percentage ?? 50,
    allowed_attempts: allowed_attempts ?? 999,
    has_checklist: has_checklist ?? 0,
    lb_content: lb_content || null,
    created_at: now,
    updated_at: now,
  }).select('id, title, slug, order, created_at').single();
  if (error) return errorResponse(500, 'Failed to create quiz: ' + error.message);

  // Update topic has_quiz flag
  await adminClient.from('topics').update({ has_quiz: 1 }).eq('id', topic_id);

  await writeAuditLog({ logName: 'quiz', description: 'Quiz created', subjectType: 'quiz', subjectId: newQuiz.id, causerId: user.lmsUserId, event: 'created', properties: { title, course_id, topic_id } });
  return jsonResponse({ ...newQuiz, message: 'Quiz created successfully' }, 201);
}

async function handleUpdateQuiz(req: Request, quizId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const adminClient = getAdminClient();

  const { data: existing } = await adminClient.from('quizzes').select('id, title').eq('id', quizId).single();
  if (!existing) return errorResponse(404, 'Quiz not found');

  const updates: Record<string, unknown> = {};
  const { title, lb_content, course_id, lesson_id, topic_id, estimated_time, passing_percentage, allowed_attempts, has_checklist } = body;

  if (title !== undefined) {
    updates.title = title;
    if (title !== existing.title) updates.slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  }
  if (lb_content !== undefined) updates.lb_content = lb_content;
  if (course_id !== undefined) updates.course_id = course_id;
  if (lesson_id !== undefined) updates.lesson_id = lesson_id;
  if (topic_id !== undefined) updates.topic_id = topic_id;
  if (estimated_time !== undefined) updates.estimated_time = estimated_time;
  if (passing_percentage !== undefined) updates.passing_percentage = passing_percentage;
  if (allowed_attempts !== undefined) updates.allowed_attempts = allowed_attempts;
  if (has_checklist !== undefined) updates.has_checklist = has_checklist;
  updates.updated_at = new Date().toISOString();

  const { error } = await adminClient.from('quizzes').update(updates).eq('id', quizId);
  if (error) return errorResponse(500, 'Failed to update quiz: ' + error.message);

  await writeAuditLog({ logName: 'quiz', description: 'Quiz updated', subjectType: 'quiz', subjectId: quizId, causerId: user.lmsUserId, event: 'updated', properties: updates });
  return jsonResponse({ id: quizId, message: 'Quiz updated successfully' });
}

async function handleDeleteQuiz(quizId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const { data: existing } = await adminClient.from('quizzes').select('id, title, topic_id').eq('id', quizId).single();
  if (!existing) return errorResponse(404, 'Quiz not found');

  // Delete associated quiz attempts
  await adminClient.from('quiz_attempts').delete().eq('quiz_id', quizId);

  // Delete the quiz
  const { error } = await adminClient.from('quizzes').delete().eq('id', quizId);
  if (error) return errorResponse(500, 'Failed to delete quiz: ' + error.message);

  // Check if topic still has quizzes
  const { count: remainingQuizzes } = await adminClient.from('quizzes').select('id', { count: 'exact', head: true }).eq('topic_id', existing.topic_id);
  if (remainingQuizzes === 0) {
    await adminClient.from('topics').update({ has_quiz: 0 }).eq('id', existing.topic_id);
  }

  await writeAuditLog({ logName: 'quiz', description: 'Quiz deleted', subjectType: 'quiz', subjectId: quizId, causerId: user.lmsUserId, event: 'deleted', properties: { title: existing.title, topic_id: existing.topic_id } });
  return jsonResponse({ id: quizId, message: 'Quiz deleted successfully' });
}

async function handleReorderQuestions(req: Request, quizId: number, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin']);
  if (roleCheck) return roleCheck;

  const body = await req.json();
  const { order } = body;
  if (!Array.isArray(order)) return errorResponse(400, 'order must be an array of question IDs');

  const adminClient = getAdminClient();
  const updates = order.map((questionId: number, pos: number) =>
    adminClient.from('questions').update({ order: pos }).eq('id', questionId).eq('quiz_id', quizId)
  );
  await Promise.all(updates);

  return jsonResponse({ message: 'Questions re-ordered successfully' });
}

// ─── Enrolment Handler ────────────────────────────────────────────────────────

async function handleListEnrolments(url: URL, user: AuthUser): Promise<Response> {
  const roleCheck = requireRole(user, ['Root', 'Admin', 'Moderator', 'Mini Admin', 'Leader', 'Trainer', 'Student']);
  if (roleCheck) return roleCheck;

  const adminClient = getAdminClient();
  const status = url.searchParams.get('status') || '';
  const search = url.searchParams.get('search') || '';
  const limit = parseInt(url.searchParams.get('limit') || '25', 10);
  const offset = parseInt(url.searchParams.get('offset') || '0', 10);

  let query = adminClient.from('student_course_enrolments').select('*', { count: 'exact' });

  if (user.role === 'Student' && user.lmsUserId) {
    query = query.eq('user_id', user.lmsUserId);
  }

  if (status && status !== 'all') query = query.eq('status', status);
  query = query.order('created_at', { ascending: false }).range(offset, offset + limit - 1);

  const { data: enrolments, error, count } = await query;
  if (error) return errorResponse(500, 'Failed to fetch enrolments: ' + error.message);

  const studentIds = Array.from(new Set((enrolments ?? []).map((e: any) => e.user_id)));
  const courseIds = Array.from(new Set((enrolments ?? []).map((e: any) => e.course_id)));

  const [{ data: students }, { data: courses }] = await Promise.all([
    adminClient.from('users').select('id, first_name, last_name, email').in('id', studentIds.length > 0 ? studentIds : [0]),
    adminClient.from('courses').select('id, title').in('id', courseIds.length > 0 ? courseIds : [0]),
  ]);

  const studentMap = new Map<number, { name: string; email: string }>();
  (students ?? []).forEach((s: any) => studentMap.set(s.id, { name: `${s.first_name} ${s.last_name}`, email: s.email }));

  const courseMap = new Map<number, string>();
  (courses ?? []).forEach((c: any) => courseMap.set(c.id, c.title));

  let enriched = (enrolments ?? []).map((e: any) => ({
    ...e,
    student_name: studentMap.get(e.user_id)?.name ?? 'Unknown',
    student_email: studentMap.get(e.user_id)?.email ?? '',
    course_title: courseMap.get(e.course_id) ?? 'Unknown',
  }));

  // Client-side search filter
  if (search) {
    const s = search.toLowerCase();
    enriched = enriched.filter((e: any) =>
      e.student_name.toLowerCase().includes(s) ||
      e.student_email.toLowerCase().includes(s) ||
      e.course_title.toLowerCase().includes(s)
    );
  }

  return jsonResponse({ data: enriched, total: count ?? 0, limit, offset });
}
