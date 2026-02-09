/**
 * Standardised error responses for Edge Functions.
 */

import { corsHeaders } from './cors.ts';

export interface ErrorBody {
  error: string;
  code?: string;
  details?: unknown;
}

/**
 * Returns a JSON error response with CORS headers.
 */
export function errorResponse(
  status: number,
  message: string,
  code?: string,
  details?: unknown
): Response {
  const body: ErrorBody = { error: message };
  if (code) body.code = code;
  if (details) body.details = details;

  return new Response(JSON.stringify(body), {
    status,
    headers: { ...corsHeaders, 'Content-Type': 'application/json' },
  });
}

/**
 * Returns a JSON success response with CORS headers.
 */
export function jsonResponse(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), {
    status,
    headers: { ...corsHeaders, 'Content-Type': 'application/json' },
  });
}

/**
 * Wraps an async handler with try/catch and returns a 500 on unhandled errors.
 */
export async function withErrorHandler(
  handler: () => Promise<Response>
): Promise<Response> {
  try {
    return await handler();
  } catch (err) {
    console.error('Unhandled error:', err);
    const message =
      err instanceof Error ? err.message : 'Internal server error';
    return errorResponse(500, message, 'INTERNAL_ERROR');
  }
}
