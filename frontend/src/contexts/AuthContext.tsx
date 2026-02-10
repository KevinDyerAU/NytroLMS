/**
 * AuthContext - Supabase Authentication
 * Integrates with Supabase Auth and the existing LMS users/roles tables.
 * Session persistence is handled by Supabase's built-in token storage.
 */
import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { supabase, isSupabaseConfigured, type LmsUserWithRole, type UserRole } from '@/lib/supabase';
import type { Session, AuthError } from '@supabase/supabase-js';

export interface User {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  avatar?: string;
  permissions: string[];
  supabaseId?: string;
}

interface AuthContextType {
  user: User | null;
  session: Session | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
  logout: () => Promise<void>;
  updateUser: (user: User) => void;
  signUp: (email: string, password: string, firstName: string, lastName: string) => Promise<{ success: boolean; error?: string }>;
  resetPassword: (email: string) => Promise<{ success: boolean; error?: string }>;
  impersonatedUser: User | null;
  impersonate: (user: User) => void;
  stopImpersonating: () => void;
  isImpersonating: boolean;
  realUser: User | null;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * Fetches the LMS user profile and role from the public.users and
 * model_has_roles / roles tables, given the Supabase auth email.
 */
async function fetchLmsProfile(email: string): Promise<User | null> {
  try {
    // Timeout to prevent hanging forever
    const timeout = new Promise<never>((_, reject) =>
      setTimeout(() => reject(new Error('Profile fetch timed out')), 8000)
    );

    const fetchProfile = async () => {
      // Parallel fetch: user record + auth context (role + permissions) in 2 calls
      const [userResult, authCtxResult] = await Promise.all([
        supabase
          .from('users')
          .select('id, first_name, last_name, username, email, is_active, userable_type')
          .eq('email', email)
          .maybeSingle(),
        supabase.rpc('get_user_auth_context', { p_email: email }),
      ]);

      if (userResult.error || !userResult.data) {
        console.error('LMS user lookup failed:', userResult.error?.message);
        return null;
      }

      if (authCtxResult.error) {
        console.error('Auth context RPC failed:', authCtxResult.error.message);
      }

      const lmsUser = userResult.data;
      const ctx = authCtxResult.data;
      const role = (ctx?.role ?? 'Student') as UserRole;
      const permissions: string[] = ctx?.permissions ?? [];

      return {
        id: lmsUser.id,
        name: `${lmsUser.first_name} ${lmsUser.last_name}`.trim(),
        email: lmsUser.email,
        role,
        permissions,
      };
    };

    return await Promise.race([fetchProfile(), timeout]);
  } catch (err) {
    console.error('Error fetching LMS profile:', err);
    return null;
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [impersonatedUser, setImpersonatedUser] = useState<User | null>(null);

  const impersonate = useCallback((targetUser: User) => {
    setImpersonatedUser(targetUser);
  }, []);

  const stopImpersonating = useCallback(() => {
    setImpersonatedUser(null);
  }, []);

  // Listen for Supabase auth state changes
  useEffect(() => {
    if (!isSupabaseConfigured) {
      console.warn('Supabase is not configured. Auth features are disabled.');
      setIsLoading(false);
      return;
    }

    let mounted = true;
    const controller = new AbortController();
    
    // Hard timeout - never stay loading for more than 5 seconds
    const hardTimeout = setTimeout(() => {
      if (mounted) {
        console.warn('Auth init hard timeout - clearing stale session');
        controller.abort();
        window.localStorage.removeItem('nytrolms_auth');
        setSession(null);
        setUser(null);
        setIsLoading(false);
      }
    }, 5000);

    // Check if there's a stored session and if its token is still valid
    const storedSession = window.localStorage.getItem('nytrolms_auth');
    let tokenExpired = false;
    if (storedSession) {
      try {
        const parsed = JSON.parse(storedSession);
        const expiresAt = parsed?.expires_at;
        if (expiresAt && expiresAt < Math.floor(Date.now() / 1000)) {
          tokenExpired = true;
        }
      } catch {
        tokenExpired = true;
      }
    }

    if (!storedSession || tokenExpired) {
      // No session or expired token - clear and skip network call
      if (tokenExpired) {
        console.warn('Stored session expired, clearing');
        window.localStorage.removeItem('nytrolms_auth');
      }
      clearTimeout(hardTimeout);
      setIsLoading(false);
    } else {
      // Get initial session
      supabase.auth.getSession().then(async ({ data: { session: initialSession }, error }) => {
        if (!mounted) return;
        clearTimeout(hardTimeout);
        if (error) {
          console.error('Session error:', error.message);
          window.localStorage.removeItem('nytrolms_auth');
          setIsLoading(false);
          return;
        }
        setSession(initialSession);
        if (initialSession?.user?.email) {
          const profile = await fetchLmsProfile(initialSession.user.email);
          if (mounted && profile) {
            profile.supabaseId = initialSession.user.id;
            setUser(profile);
          }
        }
        if (mounted) setIsLoading(false);
      }).catch((err) => {
        if (!mounted) return;
        clearTimeout(hardTimeout);
        console.error('Auth init error:', err);
        window.localStorage.removeItem('nytrolms_auth');
        setIsLoading(false);
      });
    }

    return () => { 
      mounted = false; 
      clearTimeout(hardTimeout);
      controller.abort();
    };
  }, []);

  // Subscribe to auth state changes
  useEffect(() => {
    if (!isSupabaseConfigured) return;
    
    const { data: { subscription } } = supabase.auth.onAuthStateChange(
      async (event, newSession) => {
        setSession(newSession);
        // Profile is already fetched in login() — only handle sign-out and
        // token refresh events here to avoid duplicate fetches that race.
        if (event === 'SIGNED_OUT') {
          setUser(null);
        } else if (event === 'TOKEN_REFRESHED' && newSession) {
          setSession(newSession);
        }
      }
    );

    return () => {
      subscription.unsubscribe();
    };
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    if (!isSupabaseConfigured) {
      return {
        success: false,
        error: 'Supabase is not configured. Please add VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY to your project secrets.',
      };
    }
    setIsLoading(true);
    try {
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });

      if (error) {
        setIsLoading(false);
        return { success: false, error: mapAuthError(error) };
      }

      if (data.user?.email) {
        const profile = await fetchLmsProfile(data.user.email);
        if (profile) {
          profile.supabaseId = data.user.id;
          setUser(profile);
          setIsLoading(false);
          return { success: true };
        } else {
          // Auth succeeded but no LMS profile found — sign out
          await supabase.auth.signOut();
          setIsLoading(false);
          return {
            success: false,
            error: 'Your account is not configured in NytroLMS. Please contact your administrator.',
          };
        }
      }

      setIsLoading(false);
      return { success: false, error: 'An unexpected error occurred. Please try again.' };
    } catch (err) {
      setIsLoading(false);
      return { success: false, error: 'Network error. Please check your connection and try again.' };
    }
  }, []);

  const signUp = useCallback(async (email: string, password: string, firstName: string, lastName: string) => {
    setIsLoading(true);
    try {
      const { error } = await supabase.auth.signUp({
        email,
        password,
        options: {
          data: {
            first_name: firstName,
            last_name: lastName,
          },
        },
      });

      if (error) {
        setIsLoading(false);
        return { success: false, error: mapAuthError(error) };
      }

      setIsLoading(false);
      return { success: true };
    } catch (err) {
      setIsLoading(false);
      return { success: false, error: 'Network error. Please check your connection and try again.' };
    }
  }, []);

  const resetPassword = useCallback(async (email: string) => {
    try {
      const { error } = await supabase.auth.resetPasswordForEmail(email, {
        redirectTo: `${window.location.origin}/reset-password`,
      });

      if (error) {
        return { success: false, error: mapAuthError(error) };
      }

      return { success: true };
    } catch (err) {
      return { success: false, error: 'Network error. Please check your connection and try again.' };
    }
  }, []);

  const logout = useCallback(async () => {
    await supabase.auth.signOut();
    setUser(null);
    setSession(null);
  }, []);

  const updateUser = useCallback((updatedUser: User) => {
    setUser(updatedUser);
  }, []);

  const effectiveUser = impersonatedUser ?? user;

  return (
    <AuthContext.Provider
      value={{
        user: effectiveUser,
        session,
        isAuthenticated: !!user && !!session,
        isLoading,
        login,
        logout,
        updateUser,
        signUp,
        resetPassword,
        impersonatedUser,
        impersonate,
        stopImpersonating,
        isImpersonating: !!impersonatedUser,
        realUser: user,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within an AuthProvider');
  return context;
}

/** Maps Supabase AuthError to user-friendly messages */
function mapAuthError(error: AuthError): string {
  const msg = error.message.toLowerCase();
  if (msg.includes('invalid login credentials') || msg.includes('invalid_credentials')) {
    return 'Invalid email or password. Please check your credentials and try again.';
  }
  if (msg.includes('email not confirmed')) {
    return 'Please verify your email address before signing in. Check your inbox for a confirmation link.';
  }
  if (msg.includes('user already registered')) {
    return 'An account with this email already exists. Please sign in instead.';
  }
  if (msg.includes('rate limit') || msg.includes('too many requests')) {
    return 'Too many login attempts. Please wait a moment and try again.';
  }
  if (msg.includes('signup is disabled')) {
    return 'New registrations are currently disabled. Please contact your administrator.';
  }
  return error.message;
}
