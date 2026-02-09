/**
 * Auth helpers for Edge Functions.
 * Verifies JWT tokens and extracts user information.
 */

import { getAdminClient, getUserClient } from './db.ts';
import { errorResponse } from './errors.ts';
import type { SupabaseClient } from 'https://esm.sh/@supabase/supabase-js@2';

export interface AuthUser {
  supabaseId: string;
  email: string;
  lmsUserId: number | null;
  role: string;
  permissions: string[];
}

/**
 * Extracts and validates the auth token from the request.
 * Returns the authenticated user info or an error response.
 */
export async function requireAuth(
  req: Request
): Promise<{ user: AuthUser; userClient: SupabaseClient } | Response> {
  const authHeader = req.headers.get('Authorization');
  if (!authHeader) {
    return errorResponse(401, 'Missing Authorization header');
  }

  const userClient = getUserClient(authHeader);
  const {
    data: { user },
    error,
  } = await userClient.auth.getUser();

  if (error || !user) {
    return errorResponse(401, 'Invalid or expired token');
  }

  // Look up the LMS user record
  const adminClient = getAdminClient();
  const { data: lmsUser } = await adminClient
    .from('users')
    .select('id')
    .eq('email', user.email!)
    .maybeSingle();

  const lmsUserId = lmsUser?.id ?? null;

  // Get role
  let role = 'Student';
  if (lmsUserId) {
    const { data: roleData } = await adminClient
      .from('model_has_roles')
      .select('role_id')
      .eq('model_id', lmsUserId)
      .eq('model_type', 'App\\Models\\User')
      .maybeSingle();

    if (roleData?.role_id) {
      const { data: roleInfo } = await adminClient
        .from('roles')
        .select('name')
        .eq('id', roleData.role_id)
        .single();
      if (roleInfo?.name) role = roleInfo.name;
    }
  }

  // Get permissions
  const permissions: string[] = [];
  if (lmsUserId) {
    const { data: roleData } = await adminClient
      .from('model_has_roles')
      .select('role_id')
      .eq('model_id', lmsUserId)
      .eq('model_type', 'App\\Models\\User')
      .maybeSingle();

    if (roleData?.role_id) {
      const { data: permData } = await adminClient
        .from('role_has_permissions')
        .select('permission_id')
        .eq('role_id', roleData.role_id);

      if (permData && permData.length > 0) {
        const permIds = permData.map((p) => p.permission_id);
        const { data: permNames } = await adminClient
          .from('permissions')
          .select('name')
          .in('id', permIds);
        if (permNames) {
          permissions.push(...permNames.map((p) => p.name));
        }
      }
    }
  }

  return {
    user: {
      supabaseId: user.id,
      email: user.email!,
      lmsUserId,
      role,
      permissions,
    },
    userClient,
  };
}

/**
 * Checks if the user has one of the required roles.
 */
export function requireRole(
  user: AuthUser,
  allowedRoles: string[]
): Response | null {
  const normalised = allowedRoles.map((r) => r.toLowerCase());
  if (!normalised.includes(user.role.toLowerCase())) {
    return errorResponse(
      403,
      `Access denied. Required role: ${allowedRoles.join(' or ')}`
    );
  }
  return null;
}

/**
 * Checks if the user has a specific permission.
 */
export function requirePermission(
  user: AuthUser,
  permission: string
): Response | null {
  if (!user.permissions.includes(permission)) {
    return errorResponse(403, `Access denied. Required permission: ${permission}`);
  }
  return null;
}

/**
 * Role hierarchy — returns true if actorRole outranks targetRole.
 */
export function isSuperiorRole(actorRole: string, targetRole: string): boolean {
  const hierarchy: Record<string, number> = {
    root: 100,
    admin: 80,
    moderator: 60,
    'mini admin': 50,
    leader: 40,
    trainer: 30,
    student: 10,
  };
  const actorLevel = hierarchy[actorRole.toLowerCase()] ?? 0;
  const targetLevel = hierarchy[targetRole.toLowerCase()] ?? 0;
  return actorLevel > targetLevel;
}
