/**
 * StudentTrainingPlan — Hierarchical training plan accordion
 * Replaces Laravel's server-rendered accordion with a React component.
 * Course → Lesson → Topic → Quiz → Attempt hierarchy with admin actions.
 */
import { useState, useCallback } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { StatusBadge } from './StatusBadge';
import {
  fetchStudentTrainingPlan,
  markLessonComplete,
  markTopicComplete,
  markLessonCompetent,
  unlockLesson,
  lockLesson,
  markWorkPlacementComplete,
  type StudentTrainingPlan as TrainingPlanType,
  type TrainingPlanCourse,
  type TrainingPlanLesson,
  type TrainingPlanTopic,
  type TrainingPlanQuiz,
  type TrainingPlanAttempt,
} from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';
import {
  ChevronDown, ChevronRight, BookOpen, FileText, ClipboardCheck,
  Award, Lock, Unlock, CheckCircle2, XCircle, Clock, AlertTriangle,
  Loader2, RefreshCw, Briefcase, Eye, Calendar,
} from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface StudentTrainingPlanProps {
  studentId: number;
  studentName?: string;
}

const statusConfig: Record<string, { color: string; bg: string; icon: typeof CheckCircle2 }> = {
  COMPLETED: { color: 'text-emerald-600', bg: 'bg-emerald-500', icon: CheckCircle2 },
  SATISFACTORY: { color: 'text-emerald-600', bg: 'bg-emerald-500', icon: CheckCircle2 },
  SUBMITTED: { color: 'text-blue-600', bg: 'bg-blue-500', icon: Clock },
  ATTEMPTING: { color: 'text-gray-400', bg: 'bg-gray-300', icon: Clock },
  'NOT SATISFACTORY': { color: 'text-red-600', bg: 'bg-red-500', icon: XCircle },
};

function StatusDot({ status }: { status: string }) {
  const cfg = statusConfig[status] ?? statusConfig.ATTEMPTING;
  return <div className={cn('w-2.5 h-2.5 rounded-full flex-shrink-0', cfg.bg)} />;
}

function formatDate(d: string | null): string {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' });
}

// ─── Attempt Row ────────────────────────────────────────────────────────────

function AttemptRow({ attempt }: { attempt: TrainingPlanAttempt }) {
  const displayStatus = ['RETURNED', 'FAIL'].includes(attempt.status) ? 'NOT SATISFACTORY' : attempt.status;
  const cfg = statusConfig[displayStatus] ?? statusConfig.ATTEMPTING;
  const Icon = cfg.icon;

  return (
    <div className="flex items-center justify-between py-2 px-3 rounded-md bg-[#f8fafc] border border-[#f1f5f9]">
      <div className="flex items-center gap-2">
        <Icon className={cn('w-3.5 h-3.5', cfg.color)} />
        <span className="text-xs font-medium text-[#1e293b]">Attempt #{attempt.attempt}</span>
        <Badge variant="outline" className={cn('text-[10px] px-1.5 py-0', cfg.color)}>
          {displayStatus}
        </Badge>
      </div>
      <span className="text-[10px] text-[#94a3b8]">
        {formatDate(attempt.submitted_at ?? attempt.accessed_at ?? attempt.created_at)}
      </span>
    </div>
  );
}

// ─── Quiz Row ───────────────────────────────────────────────────────────────

function QuizRow({ quiz, onViewAttempt }: { quiz: TrainingPlanQuiz; onViewAttempt?: (id: number) => void }) {
  const [open, setOpen] = useState(false);
  const hasAttempts = quiz.attempts.length > 0;

  return (
    <div className="border-l-2 border-[#e2e8f0] ml-4 pl-3">
      <button
        onClick={() => hasAttempts && setOpen(!open)}
        className={cn(
          'flex items-center gap-2 w-full text-left py-1.5 group',
          hasAttempts && 'cursor-pointer hover:bg-[#f8fafc] rounded-md px-2 -mx-2'
        )}
      >
        {hasAttempts ? (
          open ? <ChevronDown className="w-3 h-3 text-[#94a3b8]" /> : <ChevronRight className="w-3 h-3 text-[#94a3b8]" />
        ) : (
          <span className="w-3" />
        )}
        <StatusDot status={quiz.status} />
        <ClipboardCheck className="w-3.5 h-3.5 text-[#94a3b8]" />
        <span className="text-xs text-[#1e293b] flex-1">{quiz.title}</span>
        <Badge variant="outline" className="text-[10px] px-1.5 py-0">
          {quiz.status}
        </Badge>
        {quiz.checklist && (
          <Badge variant="outline" className={cn(
            'text-[10px] px-1.5 py-0 ml-1',
            quiz.checklist.status === 'COMPLETED' ? 'text-emerald-600 border-emerald-300' :
            quiz.checklist.status === 'FAILED' ? 'text-red-600 border-red-300' : 'text-gray-500'
          )}>
            CL: {quiz.checklist.status}
          </Badge>
        )}
        {hasAttempts && (
          <span className="text-[10px] text-[#94a3b8]">{quiz.attempts.length} attempt{quiz.attempts.length > 1 ? 's' : ''}</span>
        )}
      </button>

      {open && hasAttempts && (
        <div className="ml-5 space-y-1 pb-2 pt-1">
          {quiz.attempts.map(a => (
            <div key={a.id} className="flex items-center gap-1">
              <div className="flex-1">
                <AttemptRow attempt={a} />
              </div>
              {onViewAttempt && (
                <Button variant="ghost" size="sm" className="h-6 w-6 p-0" onClick={() => onViewAttempt(a.id)}>
                  <Eye className="w-3 h-3 text-[#64748b]" />
                </Button>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// ─── Topic Row ──────────────────────────────────────────────────────────────

function TopicRow({ topic, studentId, courseId, onRefresh, onViewAttempt }: {
  topic: TrainingPlanTopic;
  studentId: number;
  courseId: number;
  onRefresh: () => void;
  onViewAttempt?: (id: number) => void;
}) {
  const [open, setOpen] = useState(false);
  const [marking, setMarking] = useState(false);
  const hasQuizzes = topic.quizzes.length > 0;

  async function handleMarkComplete() {
    setMarking(true);
    try {
      await markTopicComplete(studentId, topic.id, topic.lesson_id, courseId);
      toast.success(`Topic "${topic.title}" marked complete`);
      onRefresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed');
    } finally {
      setMarking(false);
    }
  }

  return (
    <div className="border-l-2 border-[#e2e8f0] ml-3 pl-3">
      <button
        onClick={() => hasQuizzes && setOpen(!open)}
        className={cn(
          'flex items-center gap-2 w-full text-left py-2 group',
          hasQuizzes && 'cursor-pointer hover:bg-[#f8fafc] rounded-md px-2 -mx-2'
        )}
      >
        {hasQuizzes ? (
          open ? <ChevronDown className="w-3.5 h-3.5 text-[#94a3b8]" /> : <ChevronRight className="w-3.5 h-3.5 text-[#94a3b8]" />
        ) : (
          <span className="w-3.5" />
        )}
        <StatusDot status={topic.status} />
        <FileText className="w-3.5 h-3.5 text-[#94a3b8]" />
        <span className="text-sm text-[#1e293b] flex-1">{topic.title}</span>
        <Badge variant="outline" className="text-[10px] px-1.5 py-0">
          {topic.status}
        </Badge>
        {topic.status !== 'COMPLETED' && (
          <Button
            variant="ghost" size="sm"
            className="h-6 text-[10px] px-2 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50"
            disabled={marking}
            onClick={(e) => { e.stopPropagation(); handleMarkComplete(); }}
          >
            {marking ? <Loader2 className="w-3 h-3 animate-spin" /> : 'Mark Complete'}
          </Button>
        )}
      </button>

      {open && hasQuizzes && (
        <div className="space-y-1 pb-2 pt-1">
          {topic.quizzes.map(q => (
            <QuizRow key={q.id} quiz={q} onViewAttempt={onViewAttempt} />
          ))}
        </div>
      )}
    </div>
  );
}

// ─── Lesson Row ─────────────────────────────────────────────────────────────

function LessonRow({ lesson, studentId, onRefresh, onViewAttempt }: {
  lesson: TrainingPlanLesson;
  studentId: number;
  onRefresh: () => void;
  onViewAttempt?: (id: number) => void;
}) {
  const { user: authUser } = useAuth();
  const [open, setOpen] = useState(false);
  const [marking, setMarking] = useState(false);
  const [markingCompetent, setMarkingCompetent] = useState(false);
  const [toggling, setToggling] = useState(false);
  const [markingWP, setMarkingWP] = useState(false);
  const hasTopics = lesson.topics.length > 0;

  const isCompetent = lesson.competency?.is_competent;
  const isLocked = lesson.release_key && lesson.release_key !== 'IMMEDIATE' && !lesson.is_unlocked && lesson.status !== 'COMPLETED';

  async function handleMarkComplete() {
    setMarking(true);
    try {
      await markLessonComplete(studentId, lesson.id, lesson.course_id);
      toast.success(`Lesson "${lesson.title}" marked complete`);
      onRefresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed');
    } finally {
      setMarking(false);
    }
  }

  async function handleMarkCompetent() {
    setMarkingCompetent(true);
    try {
      await markLessonCompetent(studentId, lesson.id, lesson.course_id, {
        competent_on: new Date().toISOString().split('T')[0],
      });
      toast.success(`Lesson "${lesson.title}" marked competent`);
      onRefresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed');
    } finally {
      setMarkingCompetent(false);
    }
  }

  async function handleToggleLock() {
    setToggling(true);
    try {
      if (lesson.is_unlocked) {
        await lockLesson(lesson.id, studentId, lesson.course_id);
        toast.success('Lesson locked');
      } else {
        await unlockLesson(lesson.id, studentId, lesson.course_id, authUser?.id ?? 0);
        toast.success('Lesson unlocked');
      }
      onRefresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed');
    } finally {
      setToggling(false);
    }
  }

  async function handleMarkWP() {
    setMarkingWP(true);
    try {
      await markWorkPlacementComplete(studentId, lesson.id, authUser?.id ?? 0);
      toast.success('Work placement marked complete');
      onRefresh();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed');
    } finally {
      setMarkingWP(false);
    }
  }

  return (
    <div className="border border-[#e2e8f0] rounded-lg overflow-hidden">
      {/* Lesson Header */}
      <button
        onClick={() => setOpen(!open)}
        className="flex items-center gap-3 w-full text-left px-4 py-3 bg-white hover:bg-[#f8fafc] transition-colors"
      >
        {open ? <ChevronDown className="w-4 h-4 text-[#64748b]" /> : <ChevronRight className="w-4 h-4 text-[#64748b]" />}
        <StatusDot status={lesson.status} />
        <BookOpen className="w-4 h-4 text-[#64748b]" />
        <div className="flex-1 min-w-0">
          <span className="text-sm font-medium text-[#1e293b] truncate block">{lesson.title}</span>
          <div className="flex items-center gap-2 mt-0.5">
            {lesson.is_marked_complete && (
              <span className="text-[10px] text-emerald-600 font-medium">Marked Complete</span>
            )}
            {isCompetent && (
              <span className="text-[10px] text-purple-600 font-medium flex items-center gap-0.5">
                <Award className="w-3 h-3" /> Competency Achieved
                {lesson.competency?.competent_on && ` (${formatDate(lesson.competency.competent_on)})`}
              </span>
            )}
            {isLocked && !lesson.is_unlocked && (
              <span className="text-[10px] text-red-500 font-medium flex items-center gap-0.5">
                <Lock className="w-3 h-3" /> {lesson.release_key === 'XDAYS' ? `Releases after ${lesson.release_value ?? '?'} days` : lesson.release_key}
              </span>
            )}
            {lesson.is_unlocked && (
              <span className="text-[10px] text-emerald-500 font-medium flex items-center gap-0.5">
                <Unlock className="w-3 h-3" /> Unlocked
              </span>
            )}
            {lesson.has_work_placement === 1 && (
              <span className={cn('text-[10px] font-medium flex items-center gap-0.5',
                lesson.work_placement_complete ? 'text-blue-600' : 'text-amber-600'
              )}>
                <Briefcase className="w-3 h-3" />
                WP: {lesson.work_placement_complete ? 'Complete' : 'Required'}
              </span>
            )}
          </div>
        </div>
        <Badge variant="outline" className="text-xs px-2 py-0.5 flex-shrink-0">
          {lesson.status}
        </Badge>
      </button>

      {/* Lesson Body */}
      {open && (
        <div className="border-t border-[#e2e8f0] bg-[#fafbfc] px-4 py-3 space-y-2">
          {/* Admin action buttons */}
          <div className="flex flex-wrap gap-2 mb-2">
            {lesson.status !== 'COMPLETED' && (
              <Button size="sm" variant="outline"
                className="text-xs h-7 text-emerald-600 border-emerald-200 hover:bg-emerald-50"
                disabled={marking} onClick={handleMarkComplete}
              >
                {marking ? <Loader2 className="w-3 h-3 animate-spin mr-1" /> : <CheckCircle2 className="w-3 h-3 mr-1" />}
                Mark Lesson Complete
              </Button>
            )}
            {lesson.status === 'COMPLETED' && !isCompetent && (
              <Button size="sm" variant="outline"
                className="text-xs h-7 text-purple-600 border-purple-200 hover:bg-purple-50"
                disabled={markingCompetent} onClick={handleMarkCompetent}
              >
                {markingCompetent ? <Loader2 className="w-3 h-3 animate-spin mr-1" /> : <Award className="w-3 h-3 mr-1" />}
                Mark Competent
              </Button>
            )}
            {lesson.release_key && lesson.release_key !== 'IMMEDIATE' && lesson.status !== 'COMPLETED' && (
              <Button size="sm" variant="outline"
                className={cn('text-xs h-7', lesson.is_unlocked
                  ? 'text-amber-600 border-amber-200 hover:bg-amber-50'
                  : 'text-emerald-600 border-emerald-200 hover:bg-emerald-50'
                )}
                disabled={toggling} onClick={handleToggleLock}
              >
                {toggling ? <Loader2 className="w-3 h-3 animate-spin mr-1" /> :
                  lesson.is_unlocked ? <Lock className="w-3 h-3 mr-1" /> : <Unlock className="w-3 h-3 mr-1" />}
                {lesson.is_unlocked ? 'Lock' : 'Unlock'}
              </Button>
            )}
            {lesson.has_work_placement === 1 && !lesson.work_placement_complete && (
              <Button size="sm" variant="outline"
                className="text-xs h-7 text-blue-600 border-blue-200 hover:bg-blue-50"
                disabled={markingWP} onClick={handleMarkWP}
              >
                {markingWP ? <Loader2 className="w-3 h-3 animate-spin mr-1" /> : <Briefcase className="w-3 h-3 mr-1" />}
                Mark Work Placement
              </Button>
            )}
          </div>

          {/* Topics */}
          {hasTopics ? (
            <div className="space-y-1">
              {lesson.topics.map(t => (
                <TopicRow
                  key={t.id}
                  topic={t}
                  studentId={studentId}
                  courseId={lesson.course_id}
                  onRefresh={onRefresh}
                  onViewAttempt={onViewAttempt}
                />
              ))}
            </div>
          ) : (
            <p className="text-xs text-[#94a3b8] italic">No topics for this lesson</p>
          )}
        </div>
      )}
    </div>
  );
}

// ─── Course Section ─────────────────────────────────────────────────────────

function CourseSection({ course, studentId, onRefresh, onViewAttempt }: {
  course: TrainingPlanCourse;
  studentId: number;
  onRefresh: () => void;
  onViewAttempt?: (id: number) => void;
}) {
  const [open, setOpen] = useState(true);

  const pctColor = course.percentage >= 100 ? 'text-emerald-600' :
    course.percentage >= 50 ? 'text-blue-600' : 'text-amber-600';

  return (
    <Card className="border-[#e2e8f0]/50 shadow-card overflow-hidden">
      {/* Course Header */}
      <button
        onClick={() => setOpen(!open)}
        className="flex items-center gap-3 w-full text-left px-5 py-4 bg-white hover:bg-[#f8fafc] transition-colors"
      >
        {open ? <ChevronDown className="w-5 h-5 text-[#64748b]" /> : <ChevronRight className="w-5 h-5 text-[#64748b]" />}
        <StatusDot status={course.status} />
        <div className="flex-1 min-w-0">
          <h3 className="font-heading text-sm font-semibold text-[#1e293b] truncate">{course.course_title}</h3>
          <div className="flex items-center gap-3 mt-1">
            <StatusBadge status={course.enrolment?.status ?? course.status} />
            {course.enrolment?.course_start_at && (
              <span className="text-[10px] text-[#94a3b8] flex items-center gap-0.5">
                <Calendar className="w-3 h-3" />
                {formatDate(course.enrolment.course_start_at)} — {formatDate(course.enrolment?.course_ends_at)}
              </span>
            )}
          </div>
        </div>
        <div className="text-right flex-shrink-0">
          <span className={cn('text-xl font-bold', pctColor)}>{Math.round(course.percentage)}%</span>
          {course.expected_percentage > 0 && (
            <p className="text-[10px] text-[#94a3b8]">Expected: {course.expected_percentage}%</p>
          )}
        </div>
      </button>

      {/* Progress bars */}
      <div className="px-5 pb-2">
        <div className="flex items-center gap-2">
          <span className="text-[10px] text-[#94a3b8] w-16">Actual</span>
          <div className="flex-1">
            <Progress value={course.percentage} className="h-2" />
          </div>
        </div>
        {course.expected_percentage > 0 && (
          <div className="flex items-center gap-2 mt-1">
            <span className="text-[10px] text-[#94a3b8] w-16">Expected</span>
            <div className="flex-1 h-2 bg-[#e2e8f0] rounded-full overflow-hidden">
              <div
                className="h-full bg-amber-300 rounded-full"
                style={{ width: `${Math.min(100, course.expected_percentage)}%` }}
              />
            </div>
          </div>
        )}
      </div>

      {/* Lessons */}
      {open && (
        <div className="border-t border-[#e2e8f0] bg-[#fafbfc] p-4 space-y-3">
          {course.lessons.length === 0 ? (
            <p className="text-sm text-[#94a3b8] text-center py-4">No lessons in this course</p>
          ) : (
            course.lessons.map(l => (
              <LessonRow
                key={l.id}
                lesson={l}
                studentId={studentId}
                onRefresh={onRefresh}
                onViewAttempt={onViewAttempt}
              />
            ))
          )}
        </div>
      )}
    </Card>
  );
}

// ─── Main Component ─────────────────────────────────────────────────────────

export function StudentTrainingPlan({ studentId, studentName }: StudentTrainingPlanProps) {
  const [plan, setPlan] = useState<TrainingPlanType | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loaded, setLoaded] = useState(false);

  const loadPlan = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchStudentTrainingPlan(studentId);
      setPlan(data);
      setLoaded(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load training plan');
    } finally {
      setLoading(false);
    }
  }, [studentId]);

  // Load on first render
  if (!loaded && !loading) {
    loadPlan();
  }

  if (loading && !plan) {
    return (
      <div className="py-12 text-center">
        <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
        <p className="mt-2 text-sm text-[#64748b]">Loading training plan...</p>
      </div>
    );
  }

  if (error) {
    return (
      <Card className="p-6 border-red-200 bg-red-50">
        <div className="flex items-center gap-3">
          <AlertTriangle className="w-5 h-5 text-red-500" />
          <p className="text-sm text-red-700 flex-1">{error}</p>
          <Button variant="outline" size="sm" onClick={loadPlan}>
            <RefreshCw className="w-3.5 h-3.5 mr-1" /> Retry
          </Button>
        </div>
      </Card>
    );
  }

  if (!plan || plan.length === 0) {
    return (
      <Card className="p-8 text-center border-[#e2e8f0]/50">
        <BookOpen className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
        <h3 className="text-lg font-semibold text-[#1e293b]">No training plan data</h3>
        <p className="mt-1 text-sm text-[#64748b]">
          This student has no active enrolments or progress data.
        </p>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="font-heading font-semibold text-[#1e293b]">
            Training Plan{studentName ? ` — ${studentName}` : ''}
          </h3>
          <p className="text-xs text-[#94a3b8]">{plan.length} course{plan.length > 1 ? 's' : ''} enrolled</p>
        </div>
        <Button variant="outline" size="sm" onClick={loadPlan} disabled={loading}>
          <RefreshCw className={cn('w-3.5 h-3.5 mr-1', loading && 'animate-spin')} />
          Refresh
        </Button>
      </div>

      {plan.map(course => (
        <CourseSection
          key={course.course_id}
          course={course}
          studentId={studentId}
          onRefresh={loadPlan}
        />
      ))}
    </div>
  );
}
