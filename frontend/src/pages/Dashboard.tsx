/**
 * Dashboard Page — Widget-based dashboard with actionable KPI metrics.
 * Replaces activity feed with structured widget rows per Cloud Assess patterns.
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { KPIWidget } from '../components/KPIWidget';
import { useAuth } from '../contexts/AuthContext';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchDashboardStats, fetchCourseProgressSummary } from '@/lib/api';
import { isSupabaseConfigured } from '@/lib/supabase';
import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import {
  Users, GraduationCap, BookOpen, ClipboardCheck,
  TrendingUp, ArrowRight, CheckSquare, Calendar,
  UserPlus, Zap, Loader2, AlertCircle,
  Building2, BarChart3,
} from 'lucide-react';
import { Link } from 'react-router-dom';

export default function Dashboard() {
  const { user } = useAuth();

  const { data: stats, loading: statsLoading, error: statsError } = useSupabaseQuery(
    () => fetchDashboardStats(),
    []
  );

  const { data: courseProgress, loading: coursesLoading } = useSupabaseQuery(
    () => fetchCourseProgressSummary(),
    []
  );

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

  // Compute completion rate from course progress data
  const completionRate = (() => {
    if (!courseProgress || courseProgress.length === 0) return 0;
    const totalEnrolled = courseProgress.reduce((sum, c) => sum + c.total_enrolled, 0);
    const totalCompleted = courseProgress.reduce((sum, c) => sum + c.completed, 0);
    return totalEnrolled > 0 ? Math.round((totalCompleted / totalEnrolled) * 100) : 0;
  })();

  return (
    <DashboardLayout title="Dashboard" subtitle={`Welcome back, ${user?.name || 'User'}`}>
      <div className="space-y-6 animate-fade-in-up">

        {/* Row 1: Primary KPIs */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {statsLoading ? (
            Array.from({ length: 4 }).map((_, i) => (
              <Card key={i} className="p-5 border-[#3b82f6]/20 shadow-card animate-pulse">
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
                label="Total Learners"
                value={stats.activeStudents.toLocaleString()}
                icon={Users}
                subtitle={`${stats.totalStudents} total across all statuses`}
                color="blue"
                link="/students"
              />
              <KPIWidget
                label="Active Enrolments"
                value={stats.activeEnrolments.toLocaleString()}
                icon={GraduationCap}
                subtitle={`${stats.completedEnrolments} completed`}
                color="teal"
                link="/enrolments"
              />
              <KPIWidget
                label="Courses"
                value={stats.publishedCourses.toLocaleString()}
                icon={BookOpen}
                subtitle={`${stats.totalCourses} total, ${stats.totalCourses - stats.publishedCourses} draft`}
                color="amber"
                link="/courses"
              />
              <KPIWidget
                label="Pending Assessments"
                value={stats.pendingAssessments.toLocaleString()}
                icon={ClipboardCheck}
                subtitle="Awaiting marking or review"
                color="red"
                link="/assessments"
              />
            </>
          ) : null}
        </div>

        {/* Row 2: Activity & Task Widgets */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <KPIWidget
            label="To Do"
            value="—"
            icon={CheckSquare}
            subtitle="Tasks pending"
            color="orange"
            link="/training/todo"
          />
          <KPIWidget
            label="Completion Rate"
            value={`${completionRate}%`}
            icon={TrendingUp}
            subtitle="Across active courses"
            color="green"
          />
          <KPIWidget
            label="Companies"
            value={stats?.totalCompanies?.toLocaleString() ?? '—'}
            icon={Building2}
            subtitle="Registered organisations"
            color="purple"
            link="/companies"
          />
          <KPIWidget
            label="Calendar"
            value="—"
            icon={Calendar}
            subtitle="Upcoming events"
            color="blue"
            link="/training/calendar"
          />
        </div>

        {/* Row 3: Top Courses & Quick Actions side by side */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Top Courses by Enrolment */}
          <Card className="lg:col-span-2 p-0 overflow-hidden border-[#3b82f6]/20 shadow-card">
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

          {/* Quick Actions */}
          <Card className="p-5 border-[#3b82f6]/20 shadow-card">
            <div className="flex items-center gap-2 mb-4">
              <Zap className="w-4 h-4 text-[#f59e0b]" />
              <h3 className="font-heading font-semibold text-[#1e293b]">Quick Actions</h3>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <Link to="/students">
                <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#3b82f6]/20 hover:border-[#3b82f6] hover:bg-[#eff6ff] transition-all">
                  <UserPlus className="w-5 h-5 text-[#3b82f6]" />
                  <span className="text-xs font-medium">Add Learner</span>
                </Button>
              </Link>
              <Link to="/courses">
                <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#3b82f6]/20 hover:border-[#14b8a6] hover:bg-[#f0fdfa] transition-all">
                  <BookOpen className="w-5 h-5 text-[#14b8a6]" />
                  <span className="text-xs font-medium">New Journey</span>
                </Button>
              </Link>
              <Link to="/assessments">
                <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#3b82f6]/20 hover:border-[#f59e0b] hover:bg-[#fffbeb] transition-all">
                  <ClipboardCheck className="w-5 h-5 text-[#f59e0b]" />
                  <span className="text-xs font-medium">Mark Assessment</span>
                </Button>
              </Link>
              <Link to="/reports">
                <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#3b82f6]/20 hover:border-[#8b5cf6] hover:bg-[#f5f3ff] transition-all">
                  <BarChart3 className="w-5 h-5 text-[#8b5cf6]" />
                  <span className="text-xs font-medium">View Reports</span>
                </Button>
              </Link>
            </div>
          </Card>
        </div>

      </div>
    </DashboardLayout>
  );
}
