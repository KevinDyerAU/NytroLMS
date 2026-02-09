/**
 * Edge Function Client
 * Helper to call Supabase Edge Functions with the user's auth token.
 * Uses raw fetch with fresh access token from supabase.auth.
 * Used when VITE_USE_EDGE_FUNCTIONS is enabled.
 */

import { supabase } from './supabase';

const SUPABASE_URL = import.meta.env.VITE_SUPABASE_URL || 'https://rshmacirxysfwwyrszes.supabase.co';
const SUPABASE_ANON_KEY = import.meta.env.VITE_SUPABASE_ANON_KEY || '';
const EDGE_FUNCTIONS_BASE = `${SUPABASE_URL}/functions/v1`;

export const useEdgeFunctions = import.meta.env.VITE_USE_EDGE_FUNCTIONS === 'true';

export class EdgeFunctionError extends Error {
  constructor(
    message: string,
    public status: number,
    public code?: string,
    public details?: unknown
  ) {
    super(message);
    this.name = 'EdgeFunctionError';
  }
}

/**
 * Gets a fresh access token, refreshing the session if needed.
 * Uses getUser() to validate the token server-side first.
 */
async function getFreshAccessToken(): Promise<string> {
  // Try current session first
  const { data: { session } } = await supabase.auth.getSession();

  if (session?.access_token) {
    const expiresAt = session.expires_at ?? 0;
    const now = Math.floor(Date.now() / 1000);
    const ttl = expiresAt - now;
    console.debug(`[edge-client] Session found, TTL: ${ttl}s, expires_at: ${expiresAt}, now: ${now}`);
    // Only use cached token if it has > 2 minutes left
    if (ttl > 120) {
      return session.access_token;
    }
    console.debug('[edge-client] Token expiring soon, refreshing...');
  } else {
    console.debug('[edge-client] No session found, refreshing...');
  }

  // Force refresh to get a new token
  const { data: refreshData, error: refreshError } = await supabase.auth.refreshSession();
  if (refreshError || !refreshData.session?.access_token) {
    console.error('[edge-client] Token refresh failed:', refreshError?.message);
    throw new EdgeFunctionError('Not authenticated — please log in again', 401);
  }
  console.debug('[edge-client] Token refreshed successfully');
  return refreshData.session.access_token;
}

/**
 * Calls a Supabase Edge Function with the current user's JWT.
 */
export async function callEdgeFunction<T = unknown>(
  functionName: string,
  options: {
    method?: string;
    path?: string;
    body?: unknown;
    params?: Record<string, string>;
  } = {}
): Promise<T> {
  const { method = 'GET', path = '', body, params } = options;

  const accessToken = await getFreshAccessToken();

  // Build URL
  let url = `${EDGE_FUNCTIONS_BASE}/${functionName}`;
  if (path) {
    url += `/${path}`;
  }
  if (params) {
    const searchParams = new URLSearchParams(params);
    url += `?${searchParams.toString()}`;
  }

  // Make request with fresh token
  const headers: Record<string, string> = {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json',
    'apikey': SUPABASE_ANON_KEY,
  };

  const fetchOptions: RequestInit = {
    method,
    headers,
  };

  if (body && method !== 'GET') {
    fetchOptions.body = JSON.stringify(body);
  }

  const response = await fetch(url, fetchOptions);

  if (!response.ok) {
    let errorData: any = {};
    try {
      errorData = await response.json();
    } catch {
      // Response may not be JSON
    }
    throw new EdgeFunctionError(
      errorData.error || `Edge function error (${response.status})`,
      response.status,
      errorData.code,
      errorData.details
    );
  }

  const data = await response.json();
  return data as T;
}
