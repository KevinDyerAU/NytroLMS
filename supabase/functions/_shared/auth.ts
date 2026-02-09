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
 *
 * Optimized: uses a single DB function (get_user_auth_context) to fetch
 * user ID, role, and permissions in one round-trip instead of 6.
 */
export async function requireAuth(
  req: Request
): Promise<{ user: AuthUser; userClient: SupabaseClient } | Response> {
  const authHeader = req.headers.get('Authorization');
  if (!authHeader) {
    return errorResponse(401, 'Missing Authorization header');
  }

  const userClient = getUserClient(authHeader);
  const adminClient = getAdminClient();

  // 1. Verify JWT (required network call)
  const {
    data: { user },
    error,
  } = await userClient.auth.getUser();

  if (error || !user) {
    return errorResponse(401, 'Invalid or expired token');
  }

  // 2. Single RPC call to get user_id + role + permissions
  const { data: ctx, error: rpcError } = await adminClient.rpc(
    'get_user_auth_context',
    { p_email: user.email }
  );

  if (rpcError) {
    console.error('Auth context RPC error:', rpcError.message);
    return errorResponse(500, 'Failed to load auth context');
  }

  const lmsUserId = ctx?.user_id ?? null;
  const role = ctx?.role ?? 'Student';
  const permissions: string[] = ctx?.permissions ?? [];

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
