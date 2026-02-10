/**
 * Progress Comparison — Visual comparison of student progress within a course.
 * Horizontal bar chart per student, sortable, with quiz pass rate indicators.
 */
import { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  fetchAvailableCourses, fetchProgressComparison,
  type ProgressComparisonRow,
} from '@/lib/api';
import {
  Loader2, BarChart3, Search, Users, TrendingUp, AlertCircle, RefreshCw,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const statusColors: Record<string, string> = {
  ACTIVE: 'bg-emerald-100 text-emerald-700',
  COMPLETED: 'bg-blue-100 text-blue-700',
  WITHDRAWN: 'bg-red-100 text-red-700',
  DEFERRED: 'bg-amber-100 text-amber-700',
};

function progressBarColor(pct: number): string {
  if (pct >= 80) return 'bg-emerald-500';
  if (pct >= 50) return 'bg-blue-500';
  if (pct >= 25) return 'bg-amber-500';
  return 'bg-red-400';
}

export function ProgressComparison() {
  const [courses, setCourses] = useState<{ id: number; title: string }[]>([]);
  const [selectedCourseId, setSelectedCourseId] = useState('');
  const [data, setData] = useState<ProgressComparisonRow[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');

  useEffect(() => {
    fetchAvailableCourses().then(c => setCourses((c ?? []).map(x => ({ id: x.id, title: x.title }))));
  }, []);

  const loadProgress = (courseId: string) => {
    setLoading(true);
    setError(null);
    fetchProgressComparison(parseInt(courseId, 10))
      .then(d => setData(d ?? []))
      .catch((e) => {
        setData([]);
        setError(e?.message ?? 'Failed to load progress data. Please try again.');
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (!selectedCourseId) { setData([]); setError(null); return; }
    loadProgress(selectedCourseId);
  }, [selectedCourseId]);

  const filtered = data.filter(s =>
    !search || s.student_name.toLowerCase().includes(search.toLowerCase())
  );

  const avgProgress = filtered.length > 0
    ? Math.round(filtered.reduce((a, b) => a + b.progress_percentage, 0) / filtered.length)
    : 0;

  return (
    <Card className="border-[#e2e8f0] shadow-card">
      <CardHeader>
        <div className="flex items-center justify-between gap-3 flex-wrap">
          <CardTitle className="text-base text-[#3b82f6] flex items-center gap-2">
            <BarChart3 className="w-4 h-4" />
            Progress Comparison
          </CardTitle>
          <div className="flex items-center gap-2 flex-wrap">
            <Select value={selectedCourseId} onValueChange={setSelectedCourseId}>
              <SelectTrigger className="w-60 h-9 border-[#e2e8f0]">
                <SelectValue placeholder="Select a course..." />
              </SelectTrigger>
              <SelectContent>
                {courses.map(c => (
                  <SelectItem key={c.id} value={String(c.id)}>{c.title}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            {data.length > 0 && (
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#94a3b8]" />
                <Input
                  placeholder="Filter students..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="pl-8 h-9 w-48 border-[#e2e8f0]"
                />
              </div>
            )}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        {!selectedCourseId ? (
          <div className="py-12 text-center">
            <div className="inline-flex p-3 bg-[#f1f5f9] rounded-xl mb-4">
              <BarChart3 className="h-8 w-8 text-[#94a3b8]" />
            </div>
            <h3 className="text-base font-semibold text-[#1e293b]">Select a Course</h3>
            <p className="mt-1 text-sm text-[#64748b] max-w-xs mx-auto">Choose a course above to compare student progress side by side.</p>
          </div>
        ) : loading ? (
          <div className="py-12 text-center">
            <Loader2 className="mx-auto h-7 w-7 animate-spin text-[#3b82f6]" />
            <p className="mt-3 text-sm text-[#64748b]">Loading progress data...</p>
          </div>
        ) : error ? (
          <div className="py-8">
            <div className="flex items-center gap-3 p-4 bg-red-50/80 border border-red-200 rounded-lg">
              <div className="p-2 bg-red-100 rounded-lg">
                <AlertCircle className="w-5 h-5 text-red-600" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-semibold text-red-800">Failed to load progress data</p>
                <p className="text-xs text-red-600 mt-0.5">{error}</p>
              </div>
              <Button variant="outline" size="sm" className="border-red-200 text-red-700 hover:bg-red-100" onClick={() => loadProgress(selectedCourseId)}>
                <RefreshCw className="w-3.5 h-3.5 mr-1" /> Retry
              </Button>
            </div>
          </div>
        ) : data.length === 0 ? (
          <div className="py-12 text-center">
            <div className="inline-flex p-3 bg-[#f1f5f9] rounded-xl mb-4">
              <Users className="h-8 w-8 text-[#94a3b8]" />
            </div>
            <h3 className="text-base font-semibold text-[#1e293b]">No Students Enrolled</h3>
            <p className="mt-1 text-sm text-[#64748b]">No students are currently enrolled in this course.</p>
          </div>
        ) : (
          <>
            {/* Summary stats */}
            <div className="flex items-center gap-6 mb-5 pb-4 border-b border-[#e2e8f0]">
              <div className="flex items-center gap-2">
                <Users className="w-4 h-4 text-[#94a3b8]" />
                <span className="text-sm text-[#64748b]">{filtered.length} students</span>
              </div>
              <div className="flex items-center gap-2">
                <TrendingUp className="w-4 h-4 text-[#94a3b8]" />
                <span className="text-sm text-[#64748b]">Avg: <strong className="text-[#1e293b]">{avgProgress}%</strong></span>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-sm text-[#64748b]">
                  Completed: <strong className="text-[#1e293b]">{filtered.filter(s => s.status === 'COMPLETED').length}</strong>
                </span>
              </div>
            </div>

            {/* Student bars */}
            <div className="space-y-2.5">
              {filtered.map(student => {
                const quizPct = student.quizzes_total > 0
                  ? Math.round((student.quizzes_passed / student.quizzes_total) * 100)
                  : 0;
                return (
                  <div key={student.student_id} className="group rounded-lg px-3 py-2 -mx-3 hover:bg-[#f8fafc] transition-colors">
                    <div className="flex items-center gap-3">
                      {/* Name */}
                      <div className="w-40 flex-shrink-0 truncate">
                        <p className="text-sm font-medium text-[#1e293b] truncate">{student.student_name}</p>
                      </div>

                      {/* Progress bar */}
                      <div className="flex-1 flex items-center gap-2">
                        <div className="flex-1 h-7 bg-[#f1f5f9] rounded-lg overflow-hidden relative border border-[#e2e8f0]">
                          <div
                            className={cn("h-full rounded-lg transition-all duration-700", progressBarColor(student.progress_percentage))}
                            style={{ width: `${Math.max(student.progress_percentage, 2)}%` }}
                          />
                          <span className="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-[#1e293b] drop-shadow-[0_0_2px_rgba(255,255,255,0.8)]">
                            {student.progress_percentage}%
                          </span>
                        </div>
                      </div>

                      {/* Quiz pass rate */}
                      <div className="w-20 flex-shrink-0 text-right">
                        <span className="text-[10px] text-[#64748b]">
                          {student.quizzes_passed}/{student.quizzes_total} quizzes
                        </span>
                      </div>

                      {/* Status badge */}
                      <Badge
                        variant="outline"
                        className={cn("text-[10px] capitalize font-medium w-20 justify-center", statusColors[student.status] ?? statusColors.ACTIVE)}
                      >
                        {student.status?.toLowerCase()}
                      </Badge>
                    </div>
                  </div>
                );
              })}
            </div>
          </>
        )}
      </CardContent>
    </Card>
  );
}
