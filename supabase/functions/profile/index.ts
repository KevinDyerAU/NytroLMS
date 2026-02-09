import "jsr:@supabase/functions-js/edge-runtime.d.ts";

import { handleCors, corsHeaders } from '../_shared/cors.ts';
import { requireAuth, type AuthUser } from '../_shared/auth.ts';
import { getAdminClient } from '../_shared/db.ts';
import { errorResponse, jsonResponse, withErrorHandler } from '../_shared/errors.ts';
import { writeAuditLog } from '../_shared/audit.ts';

Deno.serve(async (req: Request) => {
  // Handle CORS preflight
  const corsResponse = handleCors(req);
  if (corsResponse) return corsResponse;

  return withErrorHandler(async () => {
    // Authenticate
    const authResult = await requireAuth(req);
    if (authResult instanceof Response) return authResult;
    const { user } = authResult;

    const url = new URL(req.url);
    const path = url.pathname.replace(/^\/profile\/?/, '');

    // Route: POST /profile/password
    if (req.method === 'POST' && path === 'password') {
      return handlePasswordChange(req, user);
    }

    // Route: GET /profile
    if (req.method === 'GET') {
      return handleGetProfile(user);
    }

    // Route: PUT /profile
    if (req.method === 'PUT') {
      return handleUpdateProfile(req, user);
    }

    return errorResponse(405, 'Method not allowed');
  });
});

/**
 * GET /profile — Returns the current user's full profile.
 */
async function handleGetProfile(user: AuthUser): Promise<Response> {
  if (!user.lmsUserId) {
    return errorResponse(404, 'LMS profile not found for this account');
  }

  const adminClient = getAdminClient();

  // Parallel fetch: user record + details + enrolment count
  const [userResult, detailsResult, enrolmentResult] = await Promise.all([
    adminClient
      .from('users')
      .select('id, first_name, last_name, username, email, study_type, is_active, userable_type, created_at')
      .eq('id', user.lmsUserId)
      .single(),
    adminClient
      .from('user_details')
      .select('*')
      .eq('user_id', user.lmsUserId)
      .maybeSingle(),
    adminClient
      .from('student_course_enrolments')
      .select('*', { count: 'exact', head: true })
      .eq('user_id', user.lmsUserId),
  ]);

  if (userResult.error || !userResult.data) {
    return errorResponse(404, 'User not found');
  }

  const lmsUser = userResult.data;

  return jsonResponse({
    id: lmsUser.id,
    first_name: lmsUser.first_name,
    last_name: lmsUser.last_name,
    username: lmsUser.username,
    email: lmsUser.email,
    study_type: lmsUser.study_type,
    is_active: lmsUser.is_active,
    userable_type: lmsUser.userable_type,
    created_at: lmsUser.created_at,
    role: user.role,
    permissions: user.permissions,
    supabase_id: user.supabaseId,
    details: detailsResult.data ?? null,
    enrolment_count: enrolmentResult.count ?? 0,
  });
}

/**
 * PUT /profile — Updates the current user's profile fields.
 */
async function handleUpdateProfile(req: Request, user: AuthUser): Promise<Response> {
  if (!user.lmsUserId) {
    return errorResponse(404, 'LMS profile not found');
  }

  const body = await req.json();
  const adminClient = getAdminClient();

  // Allowed user fields
  const userFields: Record<string, unknown> = {};
  if (body.first_name !== undefined) userFields.first_name = body.first_name;
  if (body.last_name !== undefined) userFields.last_name = body.last_name;
  if (body.study_type !== undefined) userFields.study_type = body.study_type;
  userFields.updated_at = new Date().toISOString();

  if (Object.keys(userFields).length > 1) {
    const { error } = await adminClient
      .from('users')
      .update(userFields)
      .eq('id', user.lmsUserId);
    if (error) return errorResponse(500, 'Failed to update user: ' + error.message);
  }

  // Allowed detail fields
  const detailFields: Record<string, unknown> = {};
  if (body.phone !== undefined) detailFields.phone = body.phone;
  if (body.address !== undefined) detailFields.address = body.address;
  if (body.preferred_language !== undefined) detailFields.preferred_language = body.preferred_language;
  if (body.preferred_name !== undefined) detailFields.preferred_name = body.preferred_name;
  if (body.timezone !== undefined) detailFields.timezone = body.timezone;
  if (body.position !== undefined) detailFields.position = body.position;
  detailFields.updated_at = new Date().toISOString();

  if (Object.keys(detailFields).length > 1) {
    const { error } = await adminClient
      .from('user_details')
      .update(detailFields)
      .eq('user_id', user.lmsUserId);
    if (error) return errorResponse(500, 'Failed to update details: ' + error.message);
  }

  await writeAuditLog({
    logName: 'profile',
    description: 'Profile updated',
    subjectType: 'users',
    subjectId: user.lmsUserId,
    causerId: user.lmsUserId,
    event: 'updated',
    properties: { userFields, detailFields },
  });

  return jsonResponse({ success: true, message: 'Profile updated' });
}

/**
 * POST /profile/password — Changes the user's password.
 */
async function handlePasswordChange(req: Request, user: AuthUser): Promise<Response> {
  const body = await req.json();
  const { new_password } = body;

  if (!new_password || new_password.length < 8) {
    return errorResponse(400, 'Password must be at least 8 characters');
  }

  const adminClient = getAdminClient();

  // Update password via Supabase Auth admin API
  const { error } = await adminClient.auth.admin.updateUserById(user.supabaseId, {
    password: new_password,
  });

  if (error) {
    return errorResponse(500, 'Failed to change password: ' + error.message);
  }

  // Update password_change_at in users table
  if (user.lmsUserId) {
    await adminClient
      .from('users')
      .update({ password_change_at: new Date().toISOString() })
      .eq('id', user.lmsUserId);

    await writeAuditLog({
      logName: 'profile',
      description: 'Password changed',
      subjectType: 'users',
      subjectId: user.lmsUserId,
      causerId: user.lmsUserId,
      event: 'password_changed',
    });
  }

  return jsonResponse({ success: true, message: 'Password changed successfully' });
}
