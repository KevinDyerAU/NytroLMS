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
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

/**
 * Fetches the LMS user profile and role from the public.users and
 * model_has_roles / roles tables, given the Supabase auth email.
 */
async function fetchLmsProfile(email: string): Promise<User | null> {
  try {
    // Get the user record from public.users
    const { data: lmsUser, error: userError } = await supabase
      .from('users')
      .select('id, first_name, last_name, username, email, is_active, userable_type')
      .eq('email', email)
      .eq('is_active', 1)
      .single();

    if (userError || !lmsUser) {
      console.error('LMS user lookup failed:', userError?.message);
      return null;
    }

    // Get the user's role from model_has_roles → roles
    const { data: roleData, error: roleError } = await supabase
      .from('model_has_roles')
      .select('role_id, roles(name)')
      .eq('model_id', lmsUser.id)
      .eq('model_type', 'App\\Models\\User')
      .limit(1)
      .single();

    let role: UserRole = 'Student'; // default
    if (!roleError && roleData?.roles) {
      role = (roleData.roles as any).name as UserRole;
    }

    // Get permissions for this role
    const permissions: string[] = [];
    if (!roleError && roleData?.role_id) {
      const { data: permData } = await supabase
        .from('role_has_permissions')
        .select('permission_id, permissions(name)')
        .eq('role_id', roleData.role_id);

      if (permData) {
        permData.forEach((p: any) => {
          if (p.permissions?.name) permissions.push(p.permissions.name);
        });
      }
    }

    return {
      id: lmsUser.id,
      name: `${lmsUser.first_name} ${lmsUser.last_name}`.trim(),
      email: lmsUser.email,
      role,
      permissions,
    };
  } catch (err) {
    console.error('Error fetching LMS profile:', err);
    return null;
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [session, setSession] = useState<Session | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Listen for Supabase auth state changes
  useEffect(() => {
    if (!isSupabaseConfigured) {
      console.warn('Supabase is not configured. Auth features are disabled.');
      setIsLoading(false);
      return;
    }

    // Get initial session
    supabase.auth.getSession().then(async ({ data: { session: initialSession } }) => {
      setSession(initialSession);
      if (initialSession?.user?.email) {
        const profile = await fetchLmsProfile(initialSession.user.email);
        if (profile) {
          profile.supabaseId = initialSession.user.id;
          setUser(profile);
        }
      }
      setIsLoading(false);
    }).catch(() => {
      setIsLoading(false);
    });

    // Subscribe to auth changes
    const { data: { subscription } } = supabase.auth.onAuthStateChange(
      async (event, newSession) => {
        setSession(newSession);
        if (event === 'SIGNED_IN' && newSession?.user?.email) {
          const profile = await fetchLmsProfile(newSession.user.email);
          if (profile) {
            profile.supabaseId = newSession.user.id;
            setUser(profile);
          }
        } else if (event === 'SIGNED_OUT') {
          setUser(null);
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

  return (
    <AuthContext.Provider
      value={{
        user,
        session,
        isAuthenticated: !!user && !!session,
        isLoading,
        login,
        logout,
        updateUser,
        signUp,
        resetPassword,
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
