/**
 * AssessmentDetailDialog — Full quiz attempt review with questions, answers,
 * evaluation results, feedback, and marking actions.
 * Matches Laravel AssessmentsController::show() review page.
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatusBadge } from './StatusBadge';
import { useAuth } from '@/contexts/AuthContext';
import {
  fetchQuizAttemptFullReview,
  evaluateQuestion,
  submitAssessmentFeedback,
  returnAssessment,
  emailAssessment,
  type QuizAttemptFullReview,
  type QuizQuestion,
  type EvaluationResult,
} from '@/lib/api';
import {
  Loader2, CheckCircle2, XCircle, HelpCircle, MessageSquare,
  ClipboardCheck, RotateCcw, Send, ChevronDown, ChevronUp, Mail,
} from 'lucide-react';
import { toast } from 'sonner';

interface AssessmentDetailDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  attemptId: number;
}

export function AssessmentDetailDialog({ open, onOpenChange, attemptId }: AssessmentDetailDialogProps) {
  const { user: authUser } = useAuth();
  const [data, setData] = useState<QuizAttemptFullReview | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('questions');
  // Marking state
  const [markingQuestionId, setMarkingQuestionId] = useState<string | null>(null);
  const [markingStatus, setMarkingStatus] = useState<string>('');
  const [markingComment, setMarkingComment] = useState('');
  const [submittingMark, setSubmittingMark] = useState(false);
  // Feedback state
  const [feedbackText, setFeedbackText] = useState('');
  const [overallStatus, setOverallStatus] = useState<'SATISFACTORY' | 'FAIL'>('SATISFACTORY');
  const [submittingFeedback, setSubmittingFeedback] = useState(false);
  const [returning, setReturning] = useState(false);
  const [assisted, setAssisted] = useState(false);
  const [emailing, setEmailing] = useState(false);
  // Expand/collapse questions
  const [expandedQuestions, setExpandedQuestions] = useState<Set<number>>(new Set());

  useEffect(() => {
    if (!open || !attemptId) return;
    setLoading(true);
    setActiveTab('questions');
    fetchQuizAttemptFullReview(attemptId)
      .then((d) => {
        setData(d);
        // Expand all questions by default
        if (d?.questions) {
          setExpandedQuestions(new Set(d.questions.map(q => q.id)));
        }
      })
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, [open, attemptId]);

  const canMark = data && ['SUBMITTED', 'REVIEWING'].includes(data.status);

  const handleMarkQuestion = async (questionId: string) => {
    if (!data || !authUser || !markingStatus) return;
    setSubmittingMark(true);
    try {
      await evaluateQuestion(data.id, questionId, markingStatus, markingComment, data.user_id, authUser.id);
      toast.success('Question evaluated');
      setMarkingQuestionId(null);
      setMarkingStatus('');
      setMarkingComment('');
      // Refresh
      const refreshed = await fetchQuizAttemptFullReview(attemptId);
      if (refreshed) setData(refreshed);
    } catch {
      toast.error('Failed to evaluate question');
    } finally {
      setSubmittingMark(false);
    }
  };

  const handleSubmitFeedback = async () => {
    if (!data || !authUser || !feedbackText.trim()) return;
    setSubmittingFeedback(true);
    try {
      const result = await submitAssessmentFeedback(
        data.id, data.quiz_id, data.user_id, authUser.id,
        feedbackText.trim(), overallStatus, assisted,
      );
      if (result?.autoReturned) {
        toast.info('Assessment marked as NOT SATISFACTORY — automatically returned to student');
      } else {
        toast.success(`Assessment marked as ${overallStatus}`);
      }
      setFeedbackText('');
      setAssisted(false);
      const refreshed = await fetchQuizAttemptFullReview(attemptId);
      if (refreshed) setData(refreshed);
    } catch {
      toast.error('Failed to submit feedback');
    } finally {
      setSubmittingFeedback(false);
    }
  };

  const handleReturn = async () => {
    if (!data || !authUser) return;
    setReturning(true);
    try {
      await returnAssessment(data.id, authUser.id);
      toast.success('Assessment returned to student');
      const refreshed = await fetchQuizAttemptFullReview(attemptId);
      if (refreshed) setData(refreshed);
    } catch {
      toast.error('Failed to return assessment');
    } finally {
      setReturning(false);
    }
  };

  const handleEmailAssessment = async () => {
    if (!data || !authUser) return;
    setEmailing(true);
    try {
      await emailAssessment(data.id, authUser.id);
      toast.success('Assessment emailed to student');
    } catch {
      toast.error('Failed to email assessment');
    } finally {
      setEmailing(false);
    }
  };

  const toggleQuestion = (qId: number) => {
    setExpandedQuestions(prev => {
      const next = new Set(prev);
      if (next.has(qId)) next.delete(qId);
      else next.add(qId);
      return next;
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle className="text-lg">Assessment Review</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-16">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : !data ? (
          <p className="text-sm text-red-500 text-center py-12">Assessment not found</p>
        ) : (
          <div className="space-y-4">
            {/* Header Info */}
            <div className="flex items-start justify-between gap-4 flex-wrap">
              <div>
                <h3 className="font-semibold text-[#1e293b] text-base">{data.student_name}</h3>
                <p className="text-sm text-[#64748b]">{data.quiz_title}</p>
                <p className="text-xs text-[#94a3b8] mt-0.5">
                  {data.course_title} &rsaquo; {data.lesson_title} &rsaquo; {data.topic_title}
                </p>
              </div>
              <div className="flex items-center gap-2 flex-shrink-0">
                <StatusBadge status={data.status} />
                {data.system_result && data.system_result !== data.status && (
                  <Badge variant="outline" className="text-xs">{data.system_result}</Badge>
                )}
              </div>
            </div>

            {/* Metadata Row */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs bg-[#f8fafc] rounded-lg p-3 border border-[#3b82f6]/10">
              <div><span className="text-[#94a3b8]">Attempt</span> <span className="font-medium text-[#1e293b] ml-1">#{data.attempt}</span></div>
              <div><span className="text-[#94a3b8]">Assisted</span> <span className="font-medium text-[#1e293b] ml-1">{data.assisted === 1 ? 'Yes' : 'No'}</span></div>
              {data.passing_percentage != null && data.passing_percentage > 0 && (
                <div><span className="text-[#94a3b8]">Pass %</span> <span className="font-medium text-[#1e293b] ml-1">{data.passing_percentage}%</span></div>
              )}
              {data.submitted_at && (
                <div><span className="text-[#94a3b8]">Submitted</span> <span className="font-medium text-[#1e293b] ml-1">{new Date(data.submitted_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })}</span></div>
              )}
              {data.evaluation?.evaluator_name && (
                <div><span className="text-[#94a3b8]">Marked by</span> <span className="font-medium text-[#1e293b] ml-1">{data.evaluation.evaluator_name}</span></div>
              )}
              {data.evaluation?.status && (
                <div><span className="text-[#94a3b8]">Result</span> <span className="font-medium ml-1"><StatusBadge status={data.evaluation.status} /></span></div>
              )}
            </div>

            {/* Email Assessment button — shown when assessment has been marked */}
            {data.evaluation?.status && (
              <div className="flex justify-end">
                <Button
                  size="sm"
                  variant="outline"
                  className="text-xs gap-1"
                  disabled={emailing}
                  onClick={handleEmailAssessment}
                >
                  {emailing ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Mail className="w-3.5 h-3.5" />}
                  Email Assessment to Student
                </Button>
              </div>
            )}

            {/* Tabs: Questions | Feedback | Actions */}
            <Tabs value={activeTab} onValueChange={setActiveTab}>
              <TabsList className="bg-white border border-[#3b82f6]/20 w-full justify-start">
                <TabsTrigger value="questions" className="text-xs">
                  <ClipboardCheck className="w-3.5 h-3.5 mr-1" /> Questions ({data.questions.length})
                </TabsTrigger>
                <TabsTrigger value="feedback" className="text-xs">
                  <MessageSquare className="w-3.5 h-3.5 mr-1" /> Feedback ({data.feedbacks.length})
                </TabsTrigger>
                {canMark && (
                  <TabsTrigger value="mark" className="text-xs">
                    <CheckCircle2 className="w-3.5 h-3.5 mr-1" /> Mark Assessment
                  </TabsTrigger>
                )}
              </TabsList>

              {/* ── Questions Tab ── */}
              <TabsContent value="questions" className="mt-3 space-y-2">
                {data.questions.length === 0 ? (
                  <p className="text-sm text-[#94a3b8] text-center py-6">No questions recorded for this attempt</p>
                ) : (
                  data.questions
                    .sort((a, b) => Number(a.order) - Number(b.order))
                    .map((q, idx) => (
                      <QuestionCard
                        key={q.id}
                        question={q}
                        index={idx}
                        answer={data.submitted_answers[String(q.id)]}
                        evalResult={data.evaluation?.results?.[String(q.id)] ?? null}
                        expanded={expandedQuestions.has(q.id)}
                        onToggle={() => toggleQuestion(q.id)}
                        canMark={!!canMark}
                        isMarking={markingQuestionId === String(q.id)}
                        onStartMark={() => { setMarkingQuestionId(String(q.id)); setMarkingStatus(''); setMarkingComment(''); }}
                        onCancelMark={() => setMarkingQuestionId(null)}
                        markingStatus={markingStatus}
                        markingComment={markingComment}
                        onMarkingStatusChange={setMarkingStatus}
                        onMarkingCommentChange={setMarkingComment}
                        onSubmitMark={() => handleMarkQuestion(String(q.id))}
                        submittingMark={submittingMark}
                      />
                    ))
                )}
              </TabsContent>

              {/* ── Feedback Tab ── */}
              <TabsContent value="feedback" className="mt-3 space-y-3">
                {data.feedbacks.length === 0 ? (
                  <p className="text-sm text-[#94a3b8] text-center py-6">No feedback yet</p>
                ) : (
                  data.feedbacks.map((fb) => (
                    <Card key={fb.id} className="border-[#3b82f6]/20">
                      <CardContent className="p-4">
                        <div className="flex items-start justify-between gap-2">
                          <div className="flex-1 min-w-0">
                            {(() => {
                              const msg = fb.body?.message;
                              if (typeof msg === 'string' && msg) {
                                return <div className="text-sm text-[#1e293b] prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: msg }} />;
                              }
                              return null;
                            })()}
                            {(() => {
                              const obtained = fb.body?.obtained;
                              const passing = fb.body?.passing;
                              if (obtained != null) {
                                return <p className="text-xs text-[#64748b] mt-2">Score: {String(obtained)}% (Passing: {String(passing)}%)</p>;
                              }
                              return null;
                            })()}
                          </div>
                        </div>
                        <p className="text-xs text-[#94a3b8] mt-2">
                          {fb.owner_name ?? 'Unknown'}
                          {fb.updated_at && ` · ${new Date(fb.updated_at).toLocaleString('en-AU', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}`}
                        </p>
                      </CardContent>
                    </Card>
                  ))
                )}
              </TabsContent>

              {/* ── Mark Assessment Tab ── */}
              {canMark && (
                <TabsContent value="mark" className="mt-3 space-y-4">
                  <Card className="border-[#3b82f6]/20">
                    <CardContent className="p-4 space-y-4">
                      <h4 className="font-medium text-[#1e293b] text-sm">Submit Overall Assessment</h4>
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant={overallStatus === 'SATISFACTORY' ? 'default' : 'outline'}
                          className={overallStatus === 'SATISFACTORY' ? 'bg-green-600 hover:bg-green-700 text-white' : ''}
                          onClick={() => setOverallStatus('SATISFACTORY')}
                        >
                          <CheckCircle2 className="w-3.5 h-3.5 mr-1" /> Satisfactory
                        </Button>
                        <Button
                          size="sm"
                          variant={overallStatus === 'FAIL' ? 'default' : 'outline'}
                          className={overallStatus === 'FAIL' ? 'bg-red-600 hover:bg-red-700 text-white' : ''}
                          onClick={() => setOverallStatus('FAIL')}
                        >
                          <XCircle className="w-3.5 h-3.5 mr-1" /> Not Satisfactory
                        </Button>
                      </div>
                      <textarea
                        className="w-full min-h-[80px] rounded-md border border-[#3b82f6]/10 px-3 py-2 text-sm placeholder:text-[#94a3b8] focus:outline-none focus:ring-2 focus:ring-[#3b82f6] resize-none"
                        placeholder="Add feedback for the student..."
                        value={feedbackText}
                        onChange={(e) => setFeedbackText(e.target.value)}
                      />
                      {/* Assisted checkbox */}
                      <label className="flex items-center gap-2 text-sm text-[#64748b] cursor-pointer">
                        <input
                          type="checkbox"
                          checked={assisted}
                          onChange={(e) => setAssisted(e.target.checked)}
                          className="w-4 h-4 rounded border-[#cbd5e1] text-[#3b82f6] focus:ring-[#3b82f6]"
                        />
                        Student required assistance
                      </label>

                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                          disabled={!feedbackText.trim() || submittingFeedback}
                          onClick={handleSubmitFeedback}
                        >
                          {submittingFeedback ? <Loader2 className="w-4 h-4 mr-1 animate-spin" /> : <Send className="w-4 h-4 mr-1" />}
                          Submit Assessment
                        </Button>
                        <Button
                          size="sm"
                          variant="outline"
                          className="text-amber-600 border-amber-200 hover:bg-amber-50"
                          disabled={returning}
                          onClick={handleReturn}
                        >
                          {returning ? <Loader2 className="w-4 h-4 mr-1 animate-spin" /> : <RotateCcw className="w-4 h-4 mr-1" />}
                          Return to Student
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>
              )}
            </Tabs>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

/* ── Question Card Component ── */

interface QuestionCardProps {
  question: QuizQuestion;
  index: number;
  answer: unknown;
  evalResult: EvaluationResult | null;
  expanded: boolean;
  onToggle: () => void;
  canMark: boolean;
  isMarking: boolean;
  onStartMark: () => void;
  onCancelMark: () => void;
  markingStatus: string;
  markingComment: string;
  onMarkingStatusChange: (s: string) => void;
  onMarkingCommentChange: (c: string) => void;
  onSubmitMark: () => void;
  submittingMark: boolean;
}

function QuestionCard({
  question, index, answer, evalResult, expanded, onToggle,
  canMark, isMarking, onStartMark, onCancelMark,
  markingStatus, markingComment, onMarkingStatusChange, onMarkingCommentChange,
  onSubmitMark, submittingMark,
}: QuestionCardProps) {
  const evalIcon = evalResult
    ? (evalResult.status === 'satisfactory' || evalResult.status === 'correct')
      ? <CheckCircle2 className="w-4 h-4 text-green-500" />
      : <XCircle className="w-4 h-4 text-red-500" />
    : <HelpCircle className="w-4 h-4 text-[#94a3b8]" />;

  const evalBorder = evalResult
    ? (evalResult.status === 'satisfactory' || evalResult.status === 'correct')
      ? 'border-green-200 bg-green-50/30'
      : 'border-red-200 bg-red-50/30'
    : 'border-[#3b82f6]/20';

  return (
    <div className={`border rounded-lg ${evalBorder} transition-colors`}>
      {/* Question Header */}
      <button
        className="w-full flex items-center justify-between px-4 py-3 text-left"
        onClick={onToggle}
      >
        <div className="flex items-center gap-2 min-w-0">
          {evalIcon}
          <span className="text-sm font-medium text-[#1e293b]">Q{index + 1}.</span>
          <span className="text-sm text-[#64748b] truncate">
            {question.title || stripHtml(question.content).slice(0, 80)}
          </span>
          <Badge variant="outline" className="text-[10px] ml-1 flex-shrink-0">{question.answer_type}</Badge>
        </div>
        {expanded ? <ChevronUp className="w-4 h-4 text-[#94a3b8]" /> : <ChevronDown className="w-4 h-4 text-[#94a3b8]" />}
      </button>

      {/* Expanded Content */}
      {expanded && (
        <div className="px-4 pb-4 space-y-3 border-t border-[#3b82f6]/10">
          {/* Question Content */}
          <div className="pt-3">
            <p className="text-xs font-medium text-[#94a3b8] uppercase mb-1">Question</p>
            <div
              className="text-sm text-[#1e293b] prose prose-sm max-w-none"
              dangerouslySetInnerHTML={{ __html: String(question.content ?? '') }}
            />
          </div>

          {/* MCQ Options (if applicable) */}
          {(() => {
            const opts = question.options;
            if (!opts || Array.isArray(opts) || typeof opts !== 'object') return null;
            const mcq = (opts as Record<string, unknown>).mcq;
            if (!mcq || typeof mcq !== 'object') return null;
            return (
              <div>
                <p className="text-xs font-medium text-[#94a3b8] uppercase mb-1">Options</p>
                <div className="space-y-1">
                  {Object.entries(mcq as Record<string, string>).map(([key, label]) => {
                    const isSelected = isAnswerSelected(answer, key);
                    return (
                      <div
                        key={key}
                        className={`text-xs px-2 py-1.5 rounded ${isSelected ? 'bg-[#3b82f6]/10 text-[#3b82f6] font-medium border border-[#3b82f6]/20' : 'text-[#64748b]'}`}
                      >
                        {isSelected && '✓ '}{key}. {label}
                      </div>
                    );
                  })}
                </div>
              </div>
            );
          })()}

          {/* Student's Answer */}
          <div>
            <p className="text-xs font-medium text-[#94a3b8] uppercase mb-1">Student&apos;s Answer</p>
            <div className="text-sm text-[#1e293b] bg-white rounded-md border border-[#3b82f6]/10 px-3 py-2">
              {formatAnswer(answer)}
            </div>
          </div>

          {/* Evaluation Result */}
          {evalResult && (
            <div>
              <p className="text-xs font-medium text-[#94a3b8] uppercase mb-1">Evaluation</p>
              <div className="flex items-center gap-2">
                <StatusBadge status={evalResult.status} />
                {evalResult.comment && (
                  <span className="text-xs text-[#64748b]">{evalResult.comment}</span>
                )}
              </div>
            </div>
          )}

          {/* Marking UI */}
          {canMark && !isMarking && (
            <Button size="sm" variant="outline" className="text-xs h-7" onClick={onStartMark}>
              <ClipboardCheck className="w-3 h-3 mr-1" /> Mark Question
            </Button>
          )}
          {isMarking && (
            <div className="border border-[#3b82f6]/30 rounded-lg p-3 bg-[#f8fafc] space-y-2">
              <div className="flex gap-2">
                <Button
                  size="sm"
                  variant={markingStatus === 'satisfactory' ? 'default' : 'outline'}
                  className={`text-xs h-7 ${markingStatus === 'satisfactory' ? 'bg-green-600 hover:bg-green-700 text-white' : ''}`}
                  onClick={() => onMarkingStatusChange('satisfactory')}
                >
                  Satisfactory
                </Button>
                <Button
                  size="sm"
                  variant={markingStatus === 'not satisfactory' ? 'default' : 'outline'}
                  className={`text-xs h-7 ${markingStatus === 'not satisfactory' ? 'bg-red-600 hover:bg-red-700 text-white' : ''}`}
                  onClick={() => onMarkingStatusChange('not satisfactory')}
                >
                  Not Satisfactory
                </Button>
              </div>
              <input
                className="w-full h-8 rounded-md border border-[#3b82f6]/10 px-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#3b82f6]"
                placeholder="Comment (optional)"
                value={markingComment}
                onChange={(e) => onMarkingCommentChange(e.target.value)}
              />
              <div className="flex gap-1">
                <Button size="sm" className="text-xs h-7 bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={!markingStatus || submittingMark} onClick={onSubmitMark}>
                  {submittingMark ? <Loader2 className="w-3 h-3 animate-spin" /> : 'Save'}
                </Button>
                <Button size="sm" variant="ghost" className="text-xs h-7" onClick={onCancelMark}>Cancel</Button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

/* ── Helpers ── */

function stripHtml(html: string): string {
  return html.replace(/<[^>]*>/g, '').trim();
}

function formatAnswer(answer: unknown): string {
  if (answer === null || answer === undefined) return '—';
  if (typeof answer === 'string') return answer || '—';
  if (Array.isArray(answer)) return answer.length > 0 ? answer.join(', ') : '(empty)';
  if (typeof answer === 'object') {
    const entries = Object.entries(answer as Record<string, unknown>);
    if (entries.length === 0) return '(empty)';
    return entries.map(([k, v]) => `${k}: ${v}`).join(', ');
  }
  return String(answer);
}

function isAnswerSelected(answer: unknown, optionKey: string): boolean {
  if (!answer) return false;
  if (typeof answer === 'object' && !Array.isArray(answer)) {
    return optionKey in (answer as Record<string, unknown>);
  }
  if (Array.isArray(answer)) {
    return answer.includes(optionKey) || answer.includes(Number(optionKey));
  }
  return String(answer) === optionKey;
}
