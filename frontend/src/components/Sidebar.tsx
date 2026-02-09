/**
 * Sidebar Navigation - NytroAI design language
 * Clean white sidebar with blue active states, Nytro branding
 */
import React, { useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import {
  LayoutDashboard,
  Users,
  GraduationCap,
  BookOpen,
  ClipboardCheck,
  BarChart3,
  Settings,
  LogOut,
  ChevronLeft,
  ChevronRight,
  Building2,
  UserCog,
  FileText,
  Menu,
  X,
  type LucideIcon,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface NavItem {
  label: string;
  icon: LucideIcon;
  path: string;
  roles?: string[];
  children?: { label: string; path: string }[];
}

const navItems: NavItem[] = [
  { label: 'Dashboard', icon: LayoutDashboard, path: '/dashboard' },
  { label: 'Students', icon: Users, path: '/students', roles: ['admin', 'trainer'] },
  { label: 'Courses', icon: BookOpen, path: '/courses' },
  { label: 'Assessments', icon: ClipboardCheck, path: '/assessments' },
  { label: 'Enrolments', icon: GraduationCap, path: '/enrolments', roles: ['admin', 'trainer'] },
  { label: 'Companies', icon: Building2, path: '/companies', roles: ['admin'] },
  { label: 'Reports', icon: BarChart3, path: '/reports', roles: ['admin', 'trainer'] },
  { label: 'User Management', icon: UserCog, path: '/user-management', roles: ['admin'] },
  { label: 'Settings', icon: Settings, path: '/settings', roles: ['admin'] },
];

export function Sidebar() {
  const location = useLocation();
  const { user, logout } = useAuth();
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  const filteredItems = navItems.filter(
    (item) => !item.roles || (user && item.roles.includes(user.role))
  );

  const isActive = (path: string) => location.pathname === path || location.pathname.startsWith(path + '/');

  const NavContent = () => (
    <div className="flex flex-col h-full">
      {/* Logo */}
      <div className={cn(
        "flex items-center border-b border-[#e2e8f0] transition-all duration-300",
        collapsed ? "px-3 py-4 justify-center" : "px-5 py-4"
      )}>
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-lg bg-gradient-nytro flex items-center justify-center flex-shrink-0">
            <span className="text-white font-bold text-sm font-heading">N</span>
          </div>
          {!collapsed && (
            <div className="animate-fade-in">
              <h1 className="font-heading font-bold text-[#1e293b] text-lg leading-tight">NytroLMS</h1>
              <p className="text-[10px] text-[#94a3b8] font-medium tracking-wide uppercase">Nytro Powered</p>
            </div>
          )}
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 py-3 px-2 space-y-0.5 overflow-y-auto">
        {filteredItems.map((item) => {
          const Icon = item.icon;
          const active = isActive(item.path);
          return (
            <Link
              key={item.path}
              to={item.path}
              onClick={() => setMobileOpen(false)}
              className={cn(
                "flex items-center gap-3 rounded-lg transition-all duration-200 group relative",
                collapsed ? "px-3 py-2.5 justify-center" : "px-3 py-2.5",
                active
                  ? "bg-[#eff6ff] text-[#3b82f6]"
                  : "text-[#64748b] hover:bg-[#f8fafc] hover:text-[#1e293b]"
              )}
            >
              <Icon className={cn(
                "w-5 h-5 flex-shrink-0 transition-colors",
                active ? "text-[#3b82f6]" : "text-[#94a3b8] group-hover:text-[#64748b]"
              )} />
              {!collapsed && (
                <span className={cn(
                  "text-sm font-medium transition-colors",
                  active ? "text-[#3b82f6] font-semibold" : ""
                )}>
                  {item.label}
                </span>
              )}
              {active && (
                <div className="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-6 bg-[#3b82f6] rounded-r-full" />
              )}
              {collapsed && (
                <div className="absolute left-full ml-2 px-2 py-1 bg-[#1e293b] text-white text-xs rounded-md opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
                  {item.label}
                </div>
              )}
            </Link>
          );
        })}
      </nav>

      {/* User section */}
      <div className={cn(
        "border-t border-[#e2e8f0] transition-all duration-300",
        collapsed ? "px-2 py-3" : "px-3 py-3"
      )}>
        {!collapsed && user && (
          <div className="flex items-center gap-3 px-2 py-2 mb-2">
            <div className="w-8 h-8 rounded-full bg-gradient-nytro flex items-center justify-center flex-shrink-0">
              <span className="text-white text-xs font-semibold">
                {user.name.split(' ').map(n => n[0]).join('')}
              </span>
            </div>
            <div className="min-w-0">
              <p className="text-sm font-medium text-[#1e293b] truncate">{user.name}</p>
              <p className="text-xs text-[#94a3b8] truncate capitalize">{user.role}</p>
            </div>
          </div>
        )}
        <button
          onClick={() => logout()}
          className={cn(
            "flex items-center gap-3 rounded-lg text-[#64748b] hover:bg-red-50 hover:text-red-600 transition-all duration-200 w-full",
            collapsed ? "px-3 py-2.5 justify-center" : "px-3 py-2.5"
          )}
        >
          <LogOut className="w-5 h-5 flex-shrink-0" />
          {!collapsed && <span className="text-sm font-medium">Sign Out</span>}
        </button>
      </div>

      {/* Collapse toggle - desktop only */}
      <button
        onClick={() => setCollapsed(!collapsed)}
        className="hidden lg:flex items-center justify-center py-2 border-t border-[#e2e8f0] text-[#94a3b8] hover:text-[#64748b] transition-colors"
      >
        {collapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
      </button>
    </div>
  );

  return (
    <>
      {/* Mobile hamburger */}
      <button
        onClick={() => setMobileOpen(true)}
        className="lg:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-card border border-[#e2e8f0]"
      >
        <Menu className="w-5 h-5 text-[#64748b]" />
      </button>

      {/* Mobile overlay */}
      {mobileOpen && (
        <div
          className="lg:hidden fixed inset-0 bg-black/30 z-40 backdrop-blur-sm"
          onClick={() => setMobileOpen(false)}
        />
      )}

      {/* Mobile sidebar */}
      <aside className={cn(
        "lg:hidden fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl transform transition-transform duration-300",
        mobileOpen ? "translate-x-0" : "-translate-x-full"
      )}>
        <button
          onClick={() => setMobileOpen(false)}
          className="absolute top-4 right-4 p-1 text-[#94a3b8] hover:text-[#64748b]"
        >
          <X className="w-5 h-5" />
        </button>
        <NavContent />
      </aside>

      {/* Desktop sidebar */}
      <aside className={cn(
        "hidden lg:flex flex-col bg-white border-r border-[#e2e8f0] h-screen sticky top-0 transition-all duration-300",
        collapsed ? "w-[68px]" : "w-[240px]"
      )}>
        <NavContent />
      </aside>
    </>
  );
}
