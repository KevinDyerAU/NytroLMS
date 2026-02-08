/**
 * AuthContext - Authentication state management
 * Design: NytroAI-style auth flow with Laravel API backend
 */
import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar?: string;
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
  logout: () => void;
  updateUser: (user: User) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

// For demo purposes, simulate auth with localStorage
const DEMO_USERS: Record<string, { password: string; user: User }> = {
  'admin@keylms.com': {
    password: 'admin123',
    user: { id: 1, name: 'Kevin Dyer', email: 'admin@keylms.com', role: 'admin' },
  },
  'trainer@keylms.com': {
    password: 'trainer123',
    user: { id: 2, name: 'Sarah Mitchell', email: 'trainer@keylms.com', role: 'trainer' },
  },
  'student@keylms.com': {
    password: 'student123',
    user: { id: 3, name: 'Alex Johnson', email: 'student@keylms.com', role: 'student' },
  },
};

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check for existing session
    const stored = localStorage.getItem('keylms_user');
    if (stored) {
      try {
        setUser(JSON.parse(stored));
      } catch {
        localStorage.removeItem('keylms_user');
      }
    }
    setIsLoading(false);
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    setIsLoading(true);
    // Simulate API call delay
    await new Promise((resolve) => setTimeout(resolve, 800));

    // TODO: Replace with actual Laravel API call
    // const response = await fetch('/api/login', { method: 'POST', body: JSON.stringify({ email, password }) });
    const demoUser = DEMO_USERS[email.toLowerCase()];
    if (demoUser && demoUser.password === password) {
      setUser(demoUser.user);
      localStorage.setItem('keylms_user', JSON.stringify(demoUser.user));
      setIsLoading(false);
      return { success: true };
    }

    setIsLoading(false);
    return { success: false, error: 'Invalid email or password. Please check your credentials and try again.' };
  }, []);

  const logout = useCallback(() => {
    setUser(null);
    localStorage.removeItem('keylms_user');
  }, []);

  const updateUser = useCallback((updatedUser: User) => {
    setUser(updatedUser);
    localStorage.setItem('keylms_user', JSON.stringify(updatedUser));
  }, []);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: !!user, isLoading, login, logout, updateUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within an AuthProvider');
  return context;
}
