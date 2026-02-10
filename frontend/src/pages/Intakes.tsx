/**
 * Intakes Page — Enrolment cohorts grouped by month
 * Shows real data from student_course_enrolments grouped by start date
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { supabase, isSupabaseConfigured } from '@/lib/supabase';
import { Users, Calendar, BookOpen, Loader2, TrendingUp, CheckCircle2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface IntakeCohort {
  month: string;
  label: string;
  enrolment_count: number;
  student_count: number;
  course_count: number;
  completed_count: number;
}

async function fetchIntakeCohorts(): Promise<IntakeCohort[]> {
  if (!isSupabaseConfigured) return [];

  const { data, error } = await supabase
    .from('student_course_enrolments')
    .select('course_start_at, user_id, course_id, status');

  if (error) throw error;
  if (!data || data.length === 0) return [];

  const cohortMap = new Map<string, {
    students: Set<number>;
    courses: Set<number>;
    total: number;
    completed: number;
  }>();

  data.forEach(e => {
    const startDate = e.course_start_at ? new Date(e.course_start_at) : null;
    const key = startDate
      ? `${startDate.getFullYear()}-${String(startDate.getMonth() + 1).padStart(2, '0')}`
      : 'no-date';

    if (!cohortMap.has(key)) {
      cohortMap.set(key, { students: new Set(), courses: new Set(), total: 0, completed: 0 });
    }
    const c = cohortMap.get(key)!;
    c.students.add(e.user_id);
    c.courses.add(e.course_id);
    c.total++;
    if (e.status === 'COMPLETED') c.completed++;
  });

  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  return Array.from(cohortMap.entries())
    .map(([key, val]) => {
      const label = key === 'no-date'
        ? 'No Start Date'
        : `${months[parseInt(key.split('-')[1]) - 1]} ${key.split('-')[0]}`;
      return {
        month: key,
        label,
        enrolment_count: val.total,
        student_count: val.students.size,
        course_count: val.courses.size,
        completed_count: val.completed,
      };
    })
    .sort((a, b) => b.month.localeCompare(a.month));
}

export default function Intakes() {
  const { data: cohorts, loading, error } = useSupabaseQuery(() => fetchIntakeCohorts(), []);

  const totalEnrolments = (cohorts ?? []).reduce((s, c) => s + c.enrolment_count, 0);
  const totalCompleted = (cohorts ?? []).reduce((s, c) => s + c.completed_count, 0);

  return (
    <DashboardLayout title="Intakes" subtitle="Enrolment cohorts by start period">
      <div className="space-y-4 animate-fade-in-up">
        {/* Summary row */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Card className="p-4 border-[#3b82f6]/20">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-lg bg-[#eff6ff] flex items-center justify-center">
                <Calendar className="w-4 h-4 text-[#3b82f6]" />
              </div>
              <div>
                <p className="text-xl font-bold text-[#1e293b]">{(cohorts ?? []).filter(c => c.month !== 'no-date').length}</p>
                <p className="text-xs text-[#64748b]">Intake periods</p>
              </div>
            </div>
          </Card>
          <Card className="p-4 border-[#3b82f6]/20">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-lg bg-[#f0fdfa] flex items-center justify-center">
                <Users className="w-4 h-4 text-[#14b8a6]" />
              </div>
              <div>
                <p className="text-xl font-bold text-[#1e293b]">{totalEnrolments}</p>
                <p className="text-xs text-[#64748b]">Total enrolments</p>
              </div>
            </div>
          </Card>
          <Card className="p-4 border-[#3b82f6]/20">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-lg bg-[#f0fdf4] flex items-center justify-center">
                <CheckCircle2 className="w-4 h-4 text-[#22c55e]" />
              </div>
              <div>
                <p className="text-xl font-bold text-[#1e293b]">{totalCompleted}</p>
                <p className="text-xs text-[#64748b]">Completed</p>
              </div>
            </div>
          </Card>
        </div>

        {/* Cohort list */}
        {loading ? (
          <div className="py-16 text-center">
            <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
            <p className="mt-2 text-sm text-[#64748b]">Loading intakes...</p>
          </div>
        ) : error ? (
          <Card className="p-5 text-center text-red-500 text-sm">Failed to load: {error}</Card>
        ) : !cohorts || cohorts.length === 0 ? (
          <Card className="p-8 text-center border-[#3b82f6]/20">
            <Users className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
            <h3 className="text-lg font-semibold text-[#1e293b]">No intake data</h3>
            <p className="mt-1 text-sm text-[#64748b]">
              Enrolments with start dates will be grouped into cohorts here.
            </p>
          </Card>
        ) : (
          <div className="space-y-3">
            {cohorts.map((cohort) => {
              const completionRate = cohort.enrolment_count > 0
                ? Math.round((cohort.completed_count / cohort.enrolment_count) * 100)
                : 0;

              return (
                <Card key={cohort.month} className="p-4 border-[#3b82f6]/20 shadow-card hover:shadow-md transition-shadow">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <div className={cn(
                        "w-10 h-10 rounded-lg flex items-center justify-center",
                        cohort.month === 'no-date' ? "bg-[#f1f5f9]" : "bg-[#eff6ff]"
                      )}>
                        <Calendar className={cn(
                          "w-5 h-5",
                          cohort.month === 'no-date' ? "text-[#94a3b8]" : "text-[#3b82f6]"
                        )} />
                      </div>
                      <div>
                        <h4 className="text-sm font-semibold text-[#1e293b]">{cohort.label}</h4>
                        <div className="flex items-center gap-3 mt-0.5 text-xs text-[#64748b]">
                          <span className="flex items-center gap-1">
                            <Users className="w-3 h-3" /> {cohort.student_count} learners
                          </span>
                          <span className="flex items-center gap-1">
                            <BookOpen className="w-3 h-3" /> {cohort.course_count} courses
                          </span>
                          <span>{cohort.enrolment_count} enrolments</span>
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center gap-4">
                      <div className="text-right">
                        <div className="flex items-center gap-1.5">
                          <TrendingUp className={cn(
                            "w-3.5 h-3.5",
                            completionRate >= 80 ? "text-emerald-500" :
                            completionRate >= 40 ? "text-amber-500" : "text-[#94a3b8]"
                          )} />
                          <span className="text-sm font-semibold text-[#1e293b]">{completionRate}%</span>
                        </div>
                        <p className="text-[10px] text-[#94a3b8]">
                          {cohort.completed_count}/{cohort.enrolment_count} completed
                        </p>
                      </div>
                      {/* Progress bar */}
                      <div className="w-24 h-1.5 bg-[#e2e8f0] rounded-full overflow-hidden hidden sm:block">
                        <div
                          className={cn(
                            "h-full rounded-full transition-all",
                            completionRate >= 80 ? "bg-emerald-500" :
                            completionRate >= 40 ? "bg-amber-500" : "bg-[#3b82f6]"
                          )}
                          style={{ width: `${completionRate}%` }}
                        />
                      </div>
                    </div>
                  </div>
                </Card>
              );
            })}
          </div>
        )}
      </div>
    </DashboardLayout>
  );
}
