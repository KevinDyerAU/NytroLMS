/**
 * Generic hook for Supabase data fetching with loading, error, and refetch support.
 */

import { useState, useEffect, useCallback, useRef } from 'react';
import { isSupabaseConfigured } from '@/lib/supabase';

interface QueryState<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}

const QUERY_TIMEOUT_MS = 10000; // 10 seconds

export function useSupabaseQuery<T>(
  queryFn: () => Promise<T>,
  deps: unknown[] = []
): QueryState<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const mountedRef = useRef(true);

  const execute = useCallback(async () => {
    if (!isSupabaseConfigured) {
      setError('Supabase is not configured');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);

    // Create a timeout promise
    const timeoutPromise = new Promise<never>((_, reject) =>
      setTimeout(() => reject(new Error('Query timed out after 10 seconds')), QUERY_TIMEOUT_MS)
    );

    try {
      const result = await Promise.race([queryFn(), timeoutPromise]);
      if (mountedRef.current) {
        setData(result);
        setError(null);
      }
    } catch (e) {
      if (mountedRef.current) {
        setError(e instanceof Error ? e.message : String(e));
      }
    } finally {
      if (mountedRef.current) {
        setLoading(false);
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);

  useEffect(() => {
    mountedRef.current = true;
    execute();
    return () => { mountedRef.current = false; };
  }, [execute]);

  return { data, loading, error, refetch: execute };
}
