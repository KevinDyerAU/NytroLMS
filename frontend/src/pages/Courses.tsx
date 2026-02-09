/**
 * Courses Page — Course management with real Supabase data.
 * NytroAI design: clean cards with progress indicators, grid/list toggle.
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { StatusBadge } from '../components/StatusBadge';
import { CourseDetail } from '../components/CourseDetail';
import { LessonDetail } from '../components/LessonDetail';
import { TopicDetail } from '../components/TopicDetail';
import { QuizQuestionEditor } from '../components/QuizQuestionEditor';
import { AddCourseDialog } from '../components/AddCourseDialog';
import { EditCourseDialog } from '../components/EditCourseDialog';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchCourses, type CourseWithDetails } from '@/lib/api';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Search, Plus, BookOpen, Users, Grid3X3, List,
  ChevronRight, Loader2, AlertCircle, RefreshCw,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

type DrillView =
  | { type: 'course'; courseId: number }
  | { type: 'lesson'; courseId: number; lessonId: number }
  | { type: 'topic'; courseId: number; lessonId: number; topicId: number }
  | { type: 'quiz'; courseId: number; lessonId: number; topicId: number; quizId: number; quizTitle: string };

export default function Courses() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [selectedCourseId, setSelectedCourseId] = useState<number | null>(null);
  const [drillView, setDrillView] = useState<DrillView | null>(null);
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  const [editCourseId, setEditCourseId] = useState<number | null>(null);

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchCourses({ search, status: statusFilter, limit: 100 }),
    [search, statusFilter]
  );

  const courses = data?.data ?? [];

  // ─── Drill-down views ───
  if (drillView) {
    let content: React.ReactNode = null;

    if (drillView.type === 'quiz') {
      content = (
        <QuizQuestionEditor
          quizId={drillView.quizId}
          quizTitle={drillView.quizTitle}
          onBack={() => setDrillView({ type: 'topic', courseId: drillView.courseId, lessonId: drillView.lessonId, topicId: drillView.topicId })}
        />
      );
    } else if (drillView.type === 'topic') {
      content = (
        <TopicDetail
          topicId={drillView.topicId}
          courseId={drillView.courseId}
          lessonId={drillView.lessonId}
          onBack={() => setDrillView({ type: 'lesson', courseId: drillView.courseId, lessonId: drillView.lessonId })}
          onOpenQuiz={(quizId) => {
            // Need quiz title — fetch it from topic detail's quizzes list
            setDrillView({ type: 'quiz', courseId: drillView.courseId, lessonId: drillView.lessonId, topicId: drillView.topicId, quizId, quizTitle: `Quiz #${quizId}` });
          }}
        />
      );
    } else if (drillView.type === 'lesson') {
      content = (
        <LessonDetail
          lessonId={drillView.lessonId}
          courseId={drillView.courseId}
          onBack={() => { setDrillView(null); setSelectedCourseId(drillView.courseId); }}
          onOpenTopic={(topicId) => setDrillView({ type: 'topic', courseId: drillView.courseId, lessonId: drillView.lessonId, topicId })}
        />
      );
    }

    return (
      <DashboardLayout title="Courses" subtitle="Manage training courses and qualifications">
        {content}
      </DashboardLayout>
    );
  }

  if (selectedCourseId !== null) {
    return (
      <DashboardLayout title="Courses" subtitle="Manage training courses and qualifications">
        <CourseDetail
          courseId={selectedCourseId}
          onBack={() => setSelectedCourseId(null)}
          onEdit={(id) => setEditCourseId(id)}
          onOpenLesson={(lessonId) => setDrillView({ type: 'lesson', courseId: selectedCourseId, lessonId })}
        />
        {editCourseId !== null && (
          <EditCourseDialog
            open={true}
            onOpenChange={(open) => { if (!open) setEditCourseId(null); }}
            courseId={editCourseId}
            onSaved={() => { refetch(); setEditCourseId(null); setSelectedCourseId(null); }}
          />
        )}
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout title="Courses" subtitle="Manage training courses and qualifications">
      <div className="space-y-4 animate-fade-in-up">
        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="flex items-center gap-2 w-full sm:w-auto">
            <div className="relative flex-1 sm:w-72">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
              <Input
                placeholder="Search courses..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9 border-[#e2e8f0] h-9 bg-white/60 backdrop-blur-sm"
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[130px] h-9 border-slate-200 bg-white/60">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="PUBLISHED">Published</SelectItem>
                <SelectItem value="DRAFT">Draft</SelectItem>
                <SelectItem value="ARCHIVED">Archived</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="flex items-center gap-2">
            <div className="flex border border-[#e2e8f0] rounded-lg overflow-hidden">
              <button
                onClick={() => setViewMode('grid')}
                className={cn("p-1.5", viewMode === 'grid' ? 'bg-[#eff6ff] text-[#3b82f6]' : 'text-[#94a3b8] hover:bg-[#f8fafc]')}
              >
                <Grid3X3 className="w-4 h-4" />
              </button>
              <button
                onClick={() => setViewMode('list')}
                className={cn("p-1.5", viewMode === 'list' ? 'bg-[#eff6ff] text-[#3b82f6]' : 'text-[#94a3b8] hover:bg-[#f8fafc]')}
              >
                <List className="w-4 h-4" />
              </button>
            </div>
            <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddDialogOpen(true)}>
              <Plus className="w-4 h-4 mr-1.5" /> Add Course
            </Button>
          </div>
        </div>

        {/* Error state */}
        {error && (
          <Card className="p-8 text-center border-red-200 bg-red-50">
            <AlertCircle className="mx-auto mb-3 h-8 w-8 text-red-400" />
            <p className="text-sm font-medium text-red-700">Failed to load courses</p>
            <p className="mt-1 text-xs text-red-500">{error}</p>
            <Button variant="outline" size="sm" className="mt-4" onClick={refetch}>
              <RefreshCw className="mr-2 h-3.5 w-3.5" /> Retry
            </Button>
          </Card>
        )}

        {/* Loading state */}
        {loading && (
          <div className="py-16 text-center">
            <Loader2 className="mx-auto h-6 w-6 animate-spin text-blue-500" />
            <p className="mt-2 text-sm text-muted-foreground">Loading courses...</p>
          </div>
        )}

        {/* Course Grid */}
        {!loading && !error && viewMode === 'grid' && (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {courses.length === 0 ? (
              <Card className="col-span-full p-12 text-center border-slate-200/50">
                <BookOpen className="mx-auto mb-3 h-8 w-8 text-slate-300" />
                <p className="text-sm text-muted-foreground">No courses found</p>
              </Card>
            ) : (
              courses.map((course) => (
                <Card
                  key={course.id}
                  className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow group cursor-pointer"
                  onClick={() => setSelectedCourseId(course.id)}
                >
                  <div className="flex items-start justify-between mb-3">
                    <div className="p-2 rounded-lg bg-[#eff6ff]">
                      <BookOpen className="w-5 h-5 text-[#3b82f6]" />
                    </div>
                    <StatusBadge status={course.status} />
                  </div>
                  <h3 className="font-heading font-semibold text-[#1e293b] text-sm mb-1 group-hover:text-[#3b82f6] transition-colors line-clamp-2">
                    {course.title}
                  </h3>
                  <p className="text-xs text-[#94a3b8] mb-4">
                    {course.slug ?? '—'} &middot; {course.lessons_count} lessons
                    {course.visibility && <> &middot; {course.visibility}</>}
                  </p>
                  <div className="space-y-2">
                    <div className="flex items-center justify-between text-xs">
                      <span className="text-[#64748b]">Type</span>
                      <span className="font-medium text-[#1e293b] capitalize">{course.course_type?.toLowerCase() ?? '—'}</span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between mt-4 pt-3 border-t border-[#f1f5f9]">
                    <div className="flex items-center gap-1 text-xs text-[#94a3b8]">
                      <Users className="w-3.5 h-3.5" /> {course.enrolments_count} enrolled
                    </div>
                    <ChevronRight className="w-4 h-4 text-[#94a3b8] group-hover:text-[#3b82f6] transition-colors" />
                  </div>
                </Card>
              ))
            )}
          </div>
        )}

        {/* Course List */}
        {!loading && !error && viewMode === 'list' && (
          <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Course</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Code</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Enrolled</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Lessons</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#f1f5f9]">
                  {courses.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="py-12 text-center text-sm text-muted-foreground">
                        No courses found
                      </td>
                    </tr>
                  ) : (
                    courses.map((course) => (
                      <tr
                        key={course.id}
                        className="hover:bg-[#f8fafc] transition-colors cursor-pointer"
                        onClick={() => setSelectedCourseId(course.id)}
                      >
                        <td className="px-4 py-3">
                          <p className="text-sm font-medium text-[#1e293b]">{course.title}</p>
                          <p className="text-xs text-[#94a3b8] md:hidden">{course.slug}</p>
                        </td>
                        <td className="px-4 py-3 hidden md:table-cell text-sm text-[#64748b]">{course.slug ?? '—'}</td>
                        <td className="px-4 py-3 text-sm text-[#64748b]">{course.enrolments_count}</td>
                        <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">{course.lessons_count}</td>
                        <td className="px-4 py-3">
                          <StatusBadge status={course.status} />
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </Card>
        )}
      </div>
      <AddCourseDialog
        open={addDialogOpen}
        onOpenChange={setAddDialogOpen}
        onSaved={() => { refetch(); }}
      />

      {editCourseId !== null && (
        <EditCourseDialog
          open={true}
          onOpenChange={(open) => { if (!open) setEditCourseId(null); }}
          courseId={editCourseId}
          onSaved={() => { refetch(); setEditCourseId(null); if (selectedCourseId === editCourseId) setSelectedCourseId(null); }}
        />
      )}
    </DashboardLayout>
  );
}
