/**
 * Dashboard Page — NytroAI-style dashboard with real Supabase data.
 * KPI widgets, recent activity feed, and top courses with live progress.
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { KPIWidget } from '../components/KPIWidget';
import { StatusBadge } from '../components/StatusBadge';
import { useAuth } from '../contexts/AuthContext';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchDashboardStats, fetchRecentActivity, fetchCourseProgressSummary } from '@/lib/api';
import { isSupabaseConfigured } from '@/lib/supabase';
import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import {
  Users, GraduationCap, BookOpen, ClipboardCheck, Activity,
  TrendingUp, Clock, AlertTriangle, CheckCircle2, ArrowRight,
  FileText, UserPlus, Zap, Loader2, AlertCircle,
} from 'lucide-react';
import { Link } from 'react-router-dom';

export default function Dashboard() {
  const { user } = useAuth();

  const { data: stats, loading: statsLoading, error: statsError } = useSupabaseQuery(
    () => fetchDashboardStats(),
    []
  );

  const { data: activity, loading: activityLoading } = useSupabaseQuery(
    () => fetchRecentActivity(8),
    []
  );

  const { data: courseProgress, loading: coursesLoading } = useSupabaseQuery(
    () => fetchCourseProgressSummary(),
    []
  );

  // Map activity log events to icons and colors
  const getActivityIcon = (event: string | null, description: string) => {
    const desc = (description ?? '').toLowerCase();
    if (desc.includes('enrol') || desc.includes('register')) return { icon: UserPlus, color: 'text-[#3b82f6]' };
    if (desc.includes('complet')) return { icon: CheckCircle2, color: 'text-[#22c55e]' };
    if (desc.includes('submit') || desc.includes('assess') || desc.includes('quiz')) return { icon: ClipboardCheck, color: 'text-[#14b8a6]' };
    if (desc.includes('alert') || desc.includes('warn') || desc.includes('risk')) return { icon: AlertTriangle, color: 'text-[#f59e0b]' };
    return { icon: FileText, color: 'text-[#3b82f6]' };
  };

  const formatTimeAgo = (dateStr: string | null) => {
    if (!dateStr) return '';
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return `${mins} min ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs} hr${hrs > 1 ? 's' : ''} ago`;
    const days = Math.floor(hrs / 24);
    return `${days} day${days > 1 ? 's' : ''} ago`;
  };

  if (!isSupabaseConfigured) {
    return (
      <DashboardLayout title="Dashboard" subtitle="Welcome">
        <Card className="p-8 text-center border-amber-200 bg-amber-50">
          <AlertCircle className="mx-auto mb-3 h-8 w-8 text-amber-500" />
          <p className="font-medium text-amber-800">Supabase Not Configured</p>
          <p className="mt-1 text-sm text-amber-600">
            Add VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY to your project secrets to enable live data.
          </p>
        </Card>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout title="Dashboard" subtitle={`Welcome back, ${user?.name || 'User'}`}>
      <div className="space-y-6 animate-fade-in-up">
        {/* KPI Widgets */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {statsLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <Card key={i} className="p-5 border-slate-200/50 shadow-card animate-pulse">
                <div className="h-4 w-24 bg-slate-200 rounded mb-3" />
                <div className="h-8 w-16 bg-slate-200 rounded" />
              </Card>
            ))
          ) : statsError ? (
            <Card className="col-span-full p-5 text-center text-red-500 text-sm">
              Failed to load stats: {statsError}
            </Card>
          ) : stats ? (
            <>
              <KPIWidget
                label="Total Students"
                value={stats.activeStudents.toLocaleString()}
                icon={Users}
                trend={{ value: stats.totalStudents - stats.activeStudents, label: `${stats.totalStudents} total` }}
                color="blue"
              />
              <KPIWidget
                label="Active Enrolments"
                value={stats.activeEnrolments.toLocaleString()}
                icon={GraduationCap}
                trend={{ value: stats.completedEnrolments, label: `${stats.completedEnrolments} completed` }}
                color="teal"
              />
              <KPIWidget
                label="Courses"
                value={stats.publishedCourses.toLocaleString()}
                icon={BookOpen}
                trend={{ value: stats.totalCourses - stats.publishedCourses, label: `${stats.totalCourses} total` }}
                color="amber"
              />
              <KPIWidget
                label="Pending Assessments"
                value={stats.pendingAssessments.toLocaleString()}
                icon={ClipboardCheck}
                trend={{ value: stats.totalCompanies, label: `${stats.totalCompanies} companies` }}
                color="red"
              />
            </>
          ) : null}
        </div>

        {/* Main content grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Recent Activity */}
          <Card className="lg:col-span-2 p-0 overflow-hidden border-[#e2e8f0]/50 shadow-card">
            <div className="flex items-center justify-between px-5 py-4 border-b border-[#e2e8f0]">
              <div className="flex items-center gap-2">
                <Activity className="w-4 h-4 text-[#3b82f6]" />
                <h3 className="font-heading font-semibold text-[#1e293b]">Recent Activity</h3>
              </div>
              <Link to="/reports" className="text-sm text-[#3b82f6] hover:underline flex items-center gap-1">
                View all <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            </div>
            <div className="divide-y divide-[#f1f5f9]">
              {activityLoading ? (
                <div className="py-12 text-center">
                  <Loader2 className="mx-auto h-5 w-5 animate-spin text-blue-500" />
                  <p className="mt-2 text-xs text-muted-foreground">Loading activity...</p>
                </div>
              ) : (activity ?? []).length === 0 ? (
                <div className="py-12 text-center text-sm text-muted-foreground">
                  No recent activity
                </div>
              ) : (
                (activity ?? []).map((item) => {
                  const { icon: Icon, color } = getActivityIcon(item.event, item.description);
                  return (
                    <div key={item.id} className="flex items-start gap-3 px-5 py-3.5 hover:bg-[#f8fafc] transition-colors">
                      <div className="mt-0.5">
                        <Icon className={`w-4 h-4 ${color}`} />
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm text-[#1e293b]">{item.description}</p>
                        <p className="text-xs text-[#94a3b8] mt-0.5 flex items-center gap-1">
                          <Clock className="w-3 h-3" /> {formatTimeAgo(item.created_at)}
                        </p>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </Card>

          {/* Top Courses by Enrolment */}
          <Card className="p-0 overflow-hidden border-[#e2e8f0]/50 shadow-card">
            <div className="flex items-center justify-between px-5 py-4 border-b border-[#e2e8f0]">
              <div className="flex items-center gap-2">
                <TrendingUp className="w-4 h-4 text-[#14b8a6]" />
                <h3 className="font-heading font-semibold text-[#1e293b]">Top Courses</h3>
              </div>
              <Link to="/courses" className="text-sm text-[#3b82f6] hover:underline flex items-center gap-1">
                View all <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            </div>
            <div className="divide-y divide-[#f1f5f9]">
              {coursesLoading ? (
                <div className="py-12 text-center">
                  <Loader2 className="mx-auto h-5 w-5 animate-spin text-blue-500" />
                </div>
              ) : (courseProgress ?? []).length === 0 ? (
                <div className="py-12 text-center text-sm text-muted-foreground">
                  No course data available
                </div>
              ) : (
                (courseProgress ?? [])
                  .sort((a, b) => b.total_enrolled - a.total_enrolled)
                  .slice(0, 5)
                  .map((course) => (
                    <div key={course.course_id} className="px-5 py-3.5 hover:bg-[#f8fafc] transition-colors">
                      <div className="flex items-center justify-between mb-1.5">
                        <p className="text-sm font-medium text-[#1e293b] truncate pr-2">{course.course_title}</p>
                        <span className="text-xs text-[#94a3b8] flex-shrink-0">{course.total_enrolled} enrolled</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <Progress value={course.avg_progress} className="h-1.5 flex-1" />
                        <span className="text-xs font-medium text-[#64748b] w-8 text-right">{course.avg_progress}%</span>
                      </div>
                      <div className="flex items-center gap-3 mt-1">
                        <span className="text-[10px] text-emerald-600">{course.completed} completed</span>
                        <span className="text-[10px] text-blue-600">{course.in_progress} active</span>
                      </div>
                    </div>
                  ))
              )}
            </div>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card className="p-5 border-[#e2e8f0]/50 shadow-card">
          <div className="flex items-center gap-2 mb-4">
            <Zap className="w-4 h-4 text-[#f59e0b]" />
            <h3 className="font-heading font-semibold text-[#1e293b]">Quick Actions</h3>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Link to="/students">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#3b82f6] hover:bg-[#eff6ff] transition-all">
                <UserPlus className="w-5 h-5 text-[#3b82f6]" />
                <span className="text-xs font-medium">Add Student</span>
              </Button>
            </Link>
            <Link to="/courses">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#14b8a6] hover:bg-[#f0fdfa] transition-all">
                <BookOpen className="w-5 h-5 text-[#14b8a6]" />
                <span className="text-xs font-medium">New Course</span>
              </Button>
            </Link>
            <Link to="/assessments">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#f59e0b] hover:bg-[#fffbeb] transition-all">
                <ClipboardCheck className="w-5 h-5 text-[#f59e0b]" />
                <span className="text-xs font-medium">Review Assessment</span>
              </Button>
            </Link>
            <Link to="/reports">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#8b5cf6] hover:bg-[#f5f3ff] transition-all">
                <FileText className="w-5 h-5 text-[#8b5cf6]" />
                <span className="text-xs font-medium">Generate Report</span>
              </Button>
            </Link>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
