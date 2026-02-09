/**
 * CourseDetail — Full course detail view matching Laravel's lms/post/show.blade.php
 * Shows course info, lessons list, and enrolled students
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatusBadge } from './StatusBadge';
import { AddLessonDialog } from './AddLessonDialog';
import { EditLessonDialog } from './EditLessonDialog';
import { fetchCourseFullDetail, deleteLesson, type CourseFullDetail as CourseFullDetailType } from '@/lib/api';
import type { DbLesson } from '@/lib/types';
import { toast } from 'sonner';
import {
  ArrowLeft, Edit, BookOpen, Users, Clock, Calendar, Eye,
  FileText, Layers, Loader2, Hash, Plus, Trash2, Pencil,
} from 'lucide-react';

interface CourseDetailProps {
  courseId: number;
  onBack: () => void;
  onEdit: (courseId: number) => void;
}

export function CourseDetail({ courseId, onBack, onEdit }: CourseDetailProps) {
  const [course, setCourse] = useState<CourseFullDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [addLessonOpen, setAddLessonOpen] = useState(false);
  const [editLesson, setEditLesson] = useState<(DbLesson & { topics_count: number }) | null>(null);
  const [deletingLessonId, setDeletingLessonId] = useState<number | null>(null);

  const loadCourse = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchCourseFullDetail(courseId);
      if (!data) {
        setError('Course not found');
        return;
      }
      setCourse(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load course');
    } finally {
      setLoading(false);
    }
  }, [courseId]);

  useEffect(() => {
    loadCourse();
  }, [loadCourse]);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
      </div>
    );
  }

  if (error || !course) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" size="sm" onClick={onBack} className="text-[#64748b]">
          <ArrowLeft className="w-4 h-4 mr-1.5" /> Back to Courses
        </Button>
        <Card className="border-red-200 bg-red-50">
          <CardContent className="py-8 text-center">
            <p className="text-red-600">{error || 'Course not found'}</p>
            <Button variant="outline" size="sm" className="mt-4" onClick={loadCourse}>Retry</Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-4 animate-fade-in-up">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack} className="text-[#64748b]">
            <ArrowLeft className="w-4 h-4 mr-1.5" /> Back
          </Button>
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-lg bg-[#eff6ff]">
              <BookOpen className="w-5 h-5 text-[#3b82f6]" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">{course.title}</h2>
              <p className="text-sm text-[#64748b]">{course.slug}</p>
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <StatusBadge status={course.status} />
          <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => onEdit(course.id)}>
            <Edit className="w-4 h-4 mr-1.5" /> Edit
          </Button>
        </div>
      </div>

      {/* Tabs */}
      <Tabs defaultValue="overview">
        <TabsList className="bg-white border border-[#e2e8f0] w-full justify-start">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="lessons">Lessons ({course.lessons.length})</TabsTrigger>
          <TabsTrigger value="students">Enrolled Students ({course.enrolments_count})</TabsTrigger>
        </TabsList>

        {/* ── Overview Tab ── */}
        <TabsContent value="overview" className="mt-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {/* Stats Cards */}
            <div className="lg:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-3">
              <StatCard icon={<Layers className="w-4 h-4" />} label="Lessons" value={course.lessons.length} />
              <StatCard icon={<Users className="w-4 h-4" />} label="Enrolled" value={course.enrolments_count} />
              <StatCard icon={<Clock className="w-4 h-4" />} label="Duration (days)" value={course.course_length_days} />
              <StatCard icon={<Hash className="w-4 h-4" />} label="Version" value={course.version} />
            </div>

            {/* Course Info */}
            <div className="lg:col-span-2">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base text-[#3b82f6]">Course Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  <InfoRow label="Title">{course.title}</InfoRow>
                  <InfoRow label="Slug">{course.slug}</InfoRow>
                  <InfoRow label="Type">
                    <span className="capitalize">{course.course_type?.toLowerCase()}</span>
                  </InfoRow>
                  {course.category && <InfoRow label="Category">{course.category}</InfoRow>}
                  <InfoRow label="Visibility">{course.visibility}</InfoRow>
                  <InfoRow label="Status"><StatusBadge status={course.status} /></InfoRow>
                  <InfoRow label="Course Length">{course.course_length_days} days</InfoRow>
                  {course.course_expiry_days && (
                    <InfoRow label="Expiry">{course.course_expiry_days} days after completion</InfoRow>
                  )}
                  <InfoRow label="Main Course">{course.is_main_course ? 'Yes' : 'No'}</InfoRow>
                  <InfoRow label="Version">{course.version}</InfoRow>
                  {course.next_course && (
                    <InfoRow label="Next Course ID">{course.next_course}</InfoRow>
                  )}
                  <InfoRow label="Auto Register Next">{course.auto_register_next_course ? 'Yes' : 'No'}</InfoRow>
                  {course.published_at && (
                    <InfoRow label="Published">
                      {new Date(course.published_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })}
                    </InfoRow>
                  )}
                  <InfoRow label="Created">
                    {course.created_at
                      ? new Date(course.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })
                      : '—'}
                  </InfoRow>
                </CardContent>
              </Card>
            </div>

            {/* Quick Lesson List */}
            <div>
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base text-[#3b82f6]">Lessons</CardTitle>
                </CardHeader>
                <CardContent>
                  {course.lessons.length === 0 ? (
                    <p className="text-sm text-[#94a3b8]">No lessons yet</p>
                  ) : (
                    <div className="space-y-2">
                      {course.lessons.map((lesson, idx) => (
                        <div key={lesson.id} className="flex items-center gap-2 py-1.5 border-b border-[#f1f5f9] last:border-0">
                          <span className="text-xs text-[#94a3b8] w-5 text-right">{idx + 1}.</span>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm text-[#1e293b] truncate">{lesson.title}</p>
                            <p className="text-xs text-[#94a3b8]">{lesson.topics_count} topics</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* ── Lessons Tab ── */}
        <TabsContent value="lessons" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="text-base text-[#3b82f6]">Course Lessons</CardTitle>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddLessonOpen(true)}>
                <Plus className="w-4 h-4 mr-1.5" /> Add Lesson
              </Button>
            </CardHeader>
            <CardContent>
              {course.lessons.length === 0 ? (
                <div className="text-center py-8">
                  <Layers className="w-10 h-10 text-[#94a3b8] mx-auto mb-2" />
                  <p className="text-sm text-[#94a3b8]">No lessons in this course</p>
                  <Button size="sm" variant="outline" className="mt-3" onClick={() => setAddLessonOpen(true)}>
                    <Plus className="w-4 h-4 mr-1.5" /> Add First Lesson
                  </Button>
                </div>
              ) : (
                <div className="space-y-3">
                  {course.lessons.map((lesson, idx) => (
                    <div key={lesson.id} className="border border-[#e2e8f0] rounded-lg p-4 hover:border-[#3b82f6]/30 transition-colors">
                      <div className="flex items-start justify-between">
                        <div className="flex items-start gap-3">
                          <div className="w-8 h-8 rounded-lg bg-[#eff6ff] flex items-center justify-center flex-shrink-0">
                            <span className="text-sm font-semibold text-[#3b82f6]">{idx + 1}</span>
                          </div>
                          <div>
                            <h4 className="font-medium text-[#1e293b]">{lesson.title}</h4>
                            <p className="text-xs text-[#94a3b8] mt-0.5">{lesson.slug}</p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge variant="outline" className="text-xs">
                            {lesson.topics_count} topics
                          </Badge>
                          {lesson.has_work_placement === 1 && (
                            <Badge variant="outline" className="text-xs text-amber-600 border-amber-200">
                              Work Placement
                            </Badge>
                          )}
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 w-7 p-0 text-[#64748b] hover:text-[#3b82f6]"
                            onClick={() => setEditLesson(lesson)}
                          >
                            <Pencil className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 w-7 p-0 text-[#64748b] hover:text-red-500"
                            disabled={deletingLessonId === lesson.id || lesson.topics_count > 0}
                            title={lesson.topics_count > 0 ? 'Delete associated topics first' : 'Delete lesson'}
                            onClick={async () => {
                              if (!confirm(`Delete lesson "${lesson.title}"?`)) return;
                              setDeletingLessonId(lesson.id);
                              try {
                                await deleteLesson(lesson.id);
                                toast.success('Lesson deleted');
                                loadCourse();
                              } catch (err) {
                                toast.error(err instanceof Error ? err.message : 'Failed to delete lesson');
                              } finally {
                                setDeletingLessonId(null);
                              }
                            }}
                          >
                            {deletingLessonId === lesson.id ? (
                              <Loader2 className="w-3.5 h-3.5 animate-spin" />
                            ) : (
                              <Trash2 className="w-3.5 h-3.5" />
                            )}
                          </Button>
                        </div>
                      </div>
                      <div className="flex gap-4 mt-2 text-xs text-[#94a3b8]">
                        <span>Release: {lesson.release_key}</span>
                        {lesson.has_topic === 1 && <span>Has Topics</span>}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Students Tab ── */}
        <TabsContent value="students" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">
                Enrolled Students ({course.enrolments_count})
              </CardTitle>
            </CardHeader>
            <CardContent>
              {course.enrolled_students.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-8">No students enrolled</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-[#e2e8f0]">
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase">Student</th>
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#f1f5f9]">
                      {course.enrolled_students.map((student) => (
                        <tr key={student.id} className="hover:bg-[#f8fafc]">
                          <td className="px-3 py-2 text-sm text-[#1e293b]">
                            {student.first_name} {student.last_name}
                          </td>
                          <td className="px-3 py-2">
                            <StatusBadge status={student.status} />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {course.enrolments_count > 50 && (
                    <p className="text-xs text-[#94a3b8] text-center py-2">
                      Showing first 50 of {course.enrolments_count} students
                    </p>
                  )}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Lesson Dialogs */}
      <AddLessonDialog
        open={addLessonOpen}
        onOpenChange={setAddLessonOpen}
        courseId={courseId}
        onSaved={loadCourse}
      />
      <EditLessonDialog
        open={editLesson !== null}
        onOpenChange={(open) => { if (!open) setEditLesson(null); }}
        lesson={editLesson}
        onSaved={loadCourse}
      />
    </div>
  );
}

function StatCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
  return (
    <Card className="p-4">
      <div className="flex items-center gap-2 mb-1">
        <span className="text-[#3b82f6]">{icon}</span>
        <span className="text-xs text-[#94a3b8]">{label}</span>
      </div>
      <p className="text-2xl font-bold text-[#1e293b]">{value}</p>
    </Card>
  );
}

function InfoRow({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex items-start gap-2">
      <span className="text-[#94a3b8] text-xs w-32 flex-shrink-0 pt-0.5">{label}</span>
      <span className="text-[#1e293b]">{children}</span>
    </div>
  );
}
