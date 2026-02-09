/**
 * My Courses Page — Student-facing LMS view
 * Shows enrolled courses with progress, expandable lessons/topics/quizzes
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/contexts/AuthContext';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import {
  fetchMyEnrolledCourses,
  fetchCourseLessonsForStudent,
  type StudentEnrolledCourse,
  type StudentLessonView,
} from '@/lib/api';
import { StatusBadge } from '@/components/StatusBadge';
import {
  BookOpen, ChevronDown, ChevronRight, GraduationCap,
  Loader2, CheckCircle2, Clock, FileText, ClipboardCheck,
  ArrowLeft, AlertCircle,
} from 'lucide-react';
import { cn } from '@/lib/utils';

function QuizStatusIcon({ status }: { status: string | null }) {
  if (!status) return <div className="w-2 h-2 rounded-full bg-[#cbd5e1]" />;
  if (status === 'SATISFACTORY') return <CheckCircle2 className="w-4 h-4 text-emerald-500" />;
  if (status === 'SUBMITTED') return <Clock className="w-4 h-4 text-blue-500" />;
  if (status === 'RETURNED') return <AlertCircle className="w-4 h-4 text-orange-500" />;
  if (status === 'FAIL') return <AlertCircle className="w-4 h-4 text-red-500" />;
  if (status === 'ATTEMPTING') return <Clock className="w-4 h-4 text-amber-500" />;
  return <div className="w-2 h-2 rounded-full bg-[#cbd5e1]" />;
}

function CourseDetailView({ course, userId, onBack }: {
  course: StudentEnrolledCourse;
  userId: number;
  onBack: () => void;
}) {
  const { data: lessons, loading } = useSupabaseQuery(
    () => fetchCourseLessonsForStudent(course.course_id, userId),
    [course.course_id, userId]
  );
  const [expandedLessons, setExpandedLessons] = useState<Set<number>>(new Set());

  const toggleLesson = (id: number) => {
    setExpandedLessons(prev => {
      const next = new Set(prev);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  };

  const expandAll = () => {
    if (lessons) setExpandedLessons(new Set(lessons.map(l => l.id)));
  };
  const collapseAll = () => setExpandedLessons(new Set());

  return (
    <div className="space-y-4 animate-fade-in-up">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={onBack} className="gap-1 text-[#64748b]">
          <ArrowLeft className="w-4 h-4" /> Back
        </Button>
      </div>

      <Card className="p-6 border-[#e2e8f0]/50 shadow-card">
        <div className="flex items-start justify-between">
          <div>
            <h2 className="text-xl font-bold text-[#1e293b]">{course.course_title}</h2>
            {course.course_code && (
              <p className="text-sm text-[#64748b] mt-1">{course.course_code}</p>
            )}
          </div>
          <StatusBadge status={course.status === 'COMPLETED' ? 'completed' : 'active'} />
        </div>
        <div className="mt-4">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-[#64748b]">
              {course.quizzes_passed} of {course.quiz_count} assessments passed
            </span>
            <span className="text-sm font-semibold text-[#1e293b]">{course.progress_percentage}%</span>
          </div>
          <Progress value={course.progress_percentage} className="h-2" />
        </div>
        <div className="flex items-center gap-4 mt-3 text-xs text-[#94a3b8]">
          <span>{course.lesson_count} lessons</span>
          <span>{course.topic_count} topics</span>
          <span>{course.quiz_count} quizzes</span>
        </div>
      </Card>

      {/* Controls */}
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-[#1e293b]">Lessons & Topics</h3>
        <div className="flex gap-2">
          <Button variant="ghost" size="sm" onClick={expandAll} className="text-xs text-[#64748b]">
            Expand All
          </Button>
          <Button variant="ghost" size="sm" onClick={collapseAll} className="text-xs text-[#64748b]">
            Collapse All
          </Button>
        </div>
      </div>

      {/* Lessons */}
      {loading ? (
        <div className="py-12 text-center">
          <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
          <p className="mt-2 text-sm text-[#64748b]">Loading lessons...</p>
        </div>
      ) : (
        <div className="space-y-2">
          {(lessons ?? []).map((lesson) => {
            const isExpanded = expandedLessons.has(lesson.id);
            const totalQuizzes = lesson.topics.reduce((sum, t) => sum + t.quizzes.length, 0);
            const passedQuizzes = lesson.topics.reduce(
              (sum, t) => sum + t.quizzes.filter(q => q.status === 'SATISFACTORY').length, 0
            );

            return (
              <Card key={lesson.id} className="overflow-hidden border-[#e2e8f0]/50">
                <button
                  onClick={() => toggleLesson(lesson.id)}
                  className="w-full flex items-center justify-between px-5 py-3.5 hover:bg-[#f8fafc] transition-colors"
                >
                  <div className="flex items-center gap-3">
                    {isExpanded
                      ? <ChevronDown className="w-4 h-4 text-[#94a3b8]" />
                      : <ChevronRight className="w-4 h-4 text-[#94a3b8]" />
                    }
                    <div className="text-left">
                      <p className="text-sm font-medium text-[#1e293b]">
                        Unit {lesson.order}: {lesson.title}
                      </p>
                      <p className="text-xs text-[#94a3b8] mt-0.5">
                        {lesson.topics.length} topics &middot; {totalQuizzes} quizzes
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {passedQuizzes === totalQuizzes && totalQuizzes > 0 ? (
                      <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                    ) : (
                      <span className="text-xs text-[#94a3b8]">
                        {passedQuizzes}/{totalQuizzes}
                      </span>
                    )}
                  </div>
                </button>

                {isExpanded && (
                  <div className="border-t border-[#f1f5f9] bg-[#fafbfc]">
                    {lesson.topics.map((topic) => (
                      <div key={topic.id} className="px-5 py-3 border-b border-[#f1f5f9] last:border-b-0">
                        <div className="flex items-center gap-2 mb-2">
                          <FileText className="w-3.5 h-3.5 text-[#94a3b8]" />
                          <span className="text-sm text-[#1e293b]">{topic.title}</span>
                          {topic.estimated_time && (
                            <span className="text-[10px] text-[#94a3b8] ml-auto">
                              ~{topic.estimated_time} min
                            </span>
                          )}
                        </div>
                        {topic.quizzes.length > 0 && (
                          <div className="ml-5 space-y-1.5">
                            {topic.quizzes.map((quiz) => (
                              <div key={quiz.id} className="flex items-center gap-2 text-xs">
                                <QuizStatusIcon status={quiz.status} />
                                <ClipboardCheck className="w-3 h-3 text-[#94a3b8]" />
                                <span className={cn(
                                  "flex-1",
                                  quiz.status === 'SATISFACTORY' ? 'text-emerald-600' : 'text-[#64748b]'
                                )}>
                                  {quiz.title}
                                </span>
                                {quiz.status && (
                                  <span className={cn(
                                    "text-[10px] font-medium px-1.5 py-0.5 rounded",
                                    quiz.status === 'SATISFACTORY' ? 'bg-emerald-50 text-emerald-600' :
                                    quiz.status === 'SUBMITTED' ? 'bg-blue-50 text-blue-600' :
                                    quiz.status === 'RETURNED' ? 'bg-orange-50 text-orange-600' :
                                    quiz.status === 'FAIL' ? 'bg-red-50 text-red-600' :
                                    'bg-slate-50 text-slate-600'
                                  )}>
                                    {quiz.status}
                                  </span>
                                )}
                                {quiz.attempts > 0 && (
                                  <span className="text-[10px] text-[#94a3b8]">
                                    Attempt {quiz.attempts}
                                  </span>
                                )}
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}

export default function MyCourses() {
  const { user } = useAuth();
  const [selectedCourse, setSelectedCourse] = useState<StudentEnrolledCourse | null>(null);

  const lmsUserId = (user as any)?.lms_user_id ?? (user as any)?.id;

  const { data: courses, loading, error } = useSupabaseQuery(
    () => lmsUserId ? fetchMyEnrolledCourses(lmsUserId) : Promise.resolve([]),
    [lmsUserId]
  );

  if (selectedCourse && lmsUserId) {
    return (
      <DashboardLayout title="My Courses" subtitle={selectedCourse.course_title}>
        <CourseDetailView
          course={selectedCourse}
          userId={lmsUserId}
          onBack={() => setSelectedCourse(null)}
        />
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout title="My Courses" subtitle="Your enrolled courses and progress">
      <div className="space-y-4 animate-fade-in-up">
        {loading ? (
          <div className="py-16 text-center">
            <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
            <p className="mt-2 text-sm text-[#64748b]">Loading your courses...</p>
          </div>
        ) : error ? (
          <Card className="p-5 text-center text-red-500 text-sm">Failed to load: {error}</Card>
        ) : !courses || courses.length === 0 ? (
          <Card className="p-8 text-center border-[#e2e8f0]/50">
            <GraduationCap className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
            <h3 className="text-lg font-semibold text-[#1e293b]">No courses yet</h3>
            <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
              You haven't been enrolled in any courses yet. Contact your trainer or administrator.
            </p>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {courses.map((course) => (
              <Card
                key={course.enrolment_id}
                className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow cursor-pointer"
                onClick={() => setSelectedCourse(course)}
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className={cn(
                      "w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0",
                      course.status === 'COMPLETED' ? "bg-emerald-100" : "bg-[#eff6ff]"
                    )}>
                      {course.status === 'COMPLETED'
                        ? <CheckCircle2 className="w-5 h-5 text-emerald-500" />
                        : <BookOpen className="w-5 h-5 text-[#3b82f6]" />
                      }
                    </div>
                    <div className="min-w-0">
                      <h4 className="text-sm font-semibold text-[#1e293b] line-clamp-2">
                        {course.course_title}
                      </h4>
                      {course.course_code && (
                        <p className="text-xs text-[#94a3b8] mt-0.5">{course.course_code}</p>
                      )}
                    </div>
                  </div>
                </div>

                <div className="space-y-2">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-[#64748b]">
                      {course.quizzes_passed}/{course.quiz_count} passed
                    </span>
                    <span className="font-semibold text-[#1e293b]">{course.progress_percentage}%</span>
                  </div>
                  <Progress value={course.progress_percentage} className="h-1.5" />
                </div>

                <div className="flex items-center gap-3 mt-3 text-[10px] text-[#94a3b8]">
                  <span>{course.lesson_count} lessons</span>
                  <span>{course.topic_count} topics</span>
                  <StatusBadge status={course.status === 'COMPLETED' ? 'completed' : 'active'} />
                </div>
              </Card>
            ))}
          </div>
        )}
      </div>
    </DashboardLayout>
  );
}
