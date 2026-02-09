/**
 * Edge Function Client
 * Helper to call Supabase Edge Functions with the user's auth token.
 * Used when VITE_USE_EDGE_FUNCTIONS is enabled.
 */

import { supabase } from './supabase';

const SUPABASE_URL = import.meta.env.VITE_SUPABASE_URL || 'https://rshmacirxysfwwyrszes.supabase.co';
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

  // Get the current session token
  const { data: { session } } = await supabase.auth.getSession();
  if (!session?.access_token) {
    throw new EdgeFunctionError('Not authenticated', 401);
  }

  // Build URL
  let url = `${EDGE_FUNCTIONS_BASE}/${functionName}`;
  if (path) {
    url += `/${path}`;
  }
  if (params) {
    const searchParams = new URLSearchParams(params);
    url += `?${searchParams.toString()}`;
  }

  // Make request
  const headers: Record<string, string> = {
    'Authorization': `Bearer ${session.access_token}`,
    'Content-Type': 'application/json',
    'apikey': import.meta.env.VITE_SUPABASE_ANON_KEY || '',
  };

  const fetchOptions: RequestInit = {
    method,
    headers,
  };

  if (body && method !== 'GET') {
    fetchOptions.body = JSON.stringify(body);
  }

  const response = await fetch(url, fetchOptions);
  const data = await response.json();

  if (!response.ok) {
    throw new EdgeFunctionError(
      data.error || `Edge function error (${response.status})`,
      response.status,
      data.code,
      data.details
    );
  }

  return data as T;
}
