import { createClient, type SupabaseClient } from '@supabase/supabase-js';

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL || 'https://rshmacirxysfwwyrszes.supabase.co';
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY || '';

/**
 * Supabase client instance.
 * If VITE_SUPABASE_ANON_KEY is not set, a placeholder key is used so the
 * client can be created without crashing. Auth operations will fail gracefully.
 */
const PLACEHOLDER_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBsYWNlaG9sZGVyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MDAwMDAwMDAsImV4cCI6MjAwMDAwMDAwMH0.placeholder';
const effectiveKey = supabaseAnonKey || PLACEHOLDER_KEY;

if (!supabaseAnonKey) {
  console.warn(
    'VITE_SUPABASE_ANON_KEY is not set. Supabase auth will not work. ' +
    'Please add VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY to your project secrets.'
  );
}

export const supabase: SupabaseClient = createClient(supabaseUrl, effectiveKey, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: true,
    storage: window.localStorage,
    storageKey: 'nytrolms_auth',
  },
});

export const isSupabaseConfigured = !!supabaseAnonKey;

export type UserRole = 'Root' | 'Admin' | 'Moderator' | 'Leader' | 'Trainer' | 'Student' | 'Mini Admin';

export interface LmsUser {
  id: number;
  first_name: string;
  last_name: string;
  username: string;
  email: string;
  study_type: string | null;
  is_active: number;
  is_archived: number;
  userable_type: string | null;
  userable_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface LmsUserWithRole extends LmsUser {
  role: UserRole;
  permissions: string[];
}
