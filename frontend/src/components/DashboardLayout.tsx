/**
 * DashboardLayout - Main application shell
 * NytroAI design: sidebar + scrollable main content area
 */
import React, { useState, useEffect } from 'react';
import { Sidebar } from './Sidebar';
import { ScrollToTop } from './ScrollToTop';
import { Bell, Search } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

interface DashboardLayoutProps {
  children: React.ReactNode;
  title?: string;
  subtitle?: string;
}

export function DashboardLayout({ children, title, subtitle }: DashboardLayoutProps) {
  const { user, isImpersonating } = useAuth();

  return (
    <div className="flex min-h-screen bg-[#f8f9fb]">
      <Sidebar />
      <div className="flex-1 flex flex-col min-w-0">
        {/* Top bar */}
        <header className={`sticky z-30 bg-white/80 backdrop-blur-md border-b border-[#e2e8f0] ${isImpersonating ? 'top-[42px]' : 'top-0'}`}>
          <div className="flex items-center justify-between px-4 lg:px-8 py-3">
            <div className="pl-12 lg:pl-0">
              {title && (
                <div>
                  <h1 className="font-heading text-xl font-bold text-[#1e293b]">{title}</h1>
                  {subtitle && <p className="text-sm text-[#64748b]">{subtitle}</p>}
                </div>
              )}
            </div>
            <div className="flex items-center gap-3">
              {/* Search */}
              <div className="hidden md:flex items-center gap-2 bg-[#f1f5f9] rounded-lg px-3 py-2 w-64">
                <Search className="w-4 h-4 text-[#94a3b8]" />
                <input
                  type="text"
                  placeholder="Search..."
                  className="bg-transparent text-sm text-[#1e293b] placeholder:text-[#94a3b8] outline-none w-full"
                />
              </div>
              {/* Notifications */}
              <button className="relative p-2 rounded-lg hover:bg-[#f1f5f9] transition-colors">
                <Bell className="w-5 h-5 text-[#64748b]" />
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-[#3b82f6] rounded-full" />
              </button>
              {/* User avatar */}
              <div className="hidden md:flex items-center gap-2 pl-2 border-l border-[#e2e8f0]">
                <div className="w-8 h-8 rounded-full bg-gradient-nytro flex items-center justify-center">
                  <span className="text-white text-xs font-semibold">
                    {user?.name?.split(' ').map(n => n[0]).join('') || 'U'}
                  </span>
                </div>
                <span className="text-sm font-medium text-[#1e293b]">{user?.name}</span>
              </div>
            </div>
          </div>
        </header>

        {/* Main content */}
        <main className="flex-1 p-4 lg:p-8">
          {children}
        </main>
      </div>

      {/* Return to top button - gold styled */}
      <ScrollToTop />
    </div>
  );
}
