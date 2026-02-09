/**
 * QuizAttemptView — Student-facing quiz attempt UI (question-by-question)
 * Replaces Laravel's frontend.content.lms.quiz blade view.
 * Supports: SINGLE, MCQ, TEXT, TEXTAREA, TABLE answer types.
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import {
  fetchQuizAttemptState,
  saveQuizAnswer,
  type QuizAttemptState,
} from '@/lib/api';
import {
  ChevronLeft, ChevronRight, Loader2, Check, AlertCircle,
  ArrowLeft, Send, RotateCcw,
} from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface QuizAttemptViewProps {
  quizId: number;
  userId: number;
  courseId: number;
  quizTitle?: string;
  onBack: () => void;
  onComplete: (result: { status: string; attemptId: number }) => void;
}

export function QuizAttemptView({
  quizId, userId, courseId, quizTitle, onBack, onComplete,
}: QuizAttemptViewProps) {
  const [state, setState] = useState<QuizAttemptState | null>(null);
  const [currentQ, setCurrentQ] = useState(0);
  const [currentAnswer, setCurrentAnswer] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const loadState = useCallback(async () => {
    setLoading(true);
    try {
      const s = await fetchQuizAttemptState(quizId, userId, courseId);
      setState(s);
      setCurrentQ(s.currentQuestionIndex);
    } catch {
      toast.error('Failed to load quiz');
    } finally {
      setLoading(false);
    }
  }, [quizId, userId, courseId]);

  useEffect(() => { loadState(); }, [loadState]);

  if (loading) {
    return (
      <div className="py-16 text-center">
        <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
        <p className="mt-2 text-sm text-[#64748b]">Loading quiz...</p>
      </div>
    );
  }

  if (!state) {
    return (
      <Card className="p-8 text-center">
        <AlertCircle className="mx-auto h-10 w-10 text-red-400 mb-3" />
        <p className="text-sm text-[#64748b]">Failed to load quiz state.</p>
        <Button variant="outline" className="mt-4" onClick={onBack}>Go Back</Button>
      </Card>
    );
  }

  // Cannot attempt
  if (!state.canAttempt && !state.isComplete) {
    return (
      <Card className="p-8 text-center border-amber-200 bg-amber-50">
        <AlertCircle className="mx-auto h-10 w-10 text-amber-500 mb-3" />
        <h3 className="font-semibold text-[#1e293b]">{state.reason ?? 'Cannot attempt this quiz'}</h3>
        {state.lastAttemptStatus && (
          <p className="text-sm text-[#64748b] mt-2">
            Last attempt: <span className="font-medium">{state.lastAttemptStatus}</span>
          </p>
        )}
        <Button variant="outline" className="mt-4" onClick={onBack}>
          <ArrowLeft className="w-4 h-4 mr-1" /> Back to Course
        </Button>
      </Card>
    );
  }

  const questions = state.questions;
  const question = questions[currentQ];
  const answeredCount = Object.keys(state.submittedAnswers).length;
  const progressPct = questions.length > 0 ? Math.round((answeredCount / questions.length) * 100) : 0;

  // Get the current saved answer for this question
  const savedAnswer = question ? state.submittedAnswers[String(question.id)] : undefined;
  const displayAnswer = currentAnswer ?? savedAnswer;

  async function handleSaveAnswer() {
    if (!question || currentAnswer == null) return;
    setSaving(true);
    try {
      const result = await saveQuizAnswer({
        userId,
        quizId: state!.quizId,
        courseId: state!.courseId,
        lessonId: state!.lessonId,
        topicId: state!.topicId,
        questionId: question.id,
        answer: currentAnswer,
        attemptId: state!.attemptId,
        attemptNumber: state!.attemptNumber,
        questions: state!.questions,
      });

      // Update local state
      setState(prev => prev ? {
        ...prev,
        attemptId: result.attemptId,
        submittedAnswers: result.submittedAnswers,
        isComplete: result.isComplete,
        status: result.status,
        systemResult: result.systemResult,
      } : null);

      setCurrentAnswer(null);

      if (result.isComplete) {
        if (result.status === 'SATISFACTORY') {
          toast.success('Quiz completed! Result: SATISFACTORY');
        } else if (result.status === 'FAIL') {
          toast.error('Quiz completed. Result: NOT SATISFACTORY');
        } else {
          toast.success('Quiz submitted! Awaiting review.');
        }

        onComplete({ status: result.status, attemptId: result.attemptId });
      } else {
        // Move to next question
        setCurrentQ(prev => Math.min(prev + 1, questions.length - 1));
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save answer');
    } finally {
      setSaving(false);
    }
  }

  if (!question) {
    return (
      <Card className="p-8 text-center">
        <p className="text-sm text-[#64748b]">No questions in this quiz.</p>
        <Button variant="outline" className="mt-4" onClick={onBack}>Go Back</Button>
      </Card>
    );
  }

  return (
    <div className="space-y-4 animate-fade-in-up">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={onBack} className="gap-1 text-[#64748b]">
          <ArrowLeft className="w-4 h-4" /> Back
        </Button>
        <div className="flex-1">
          <h3 className="font-semibold text-[#1e293b]">{quizTitle ?? 'Quiz'}</h3>
          <p className="text-xs text-[#94a3b8]">
            Attempt #{state.attemptNumber} &middot; Question {currentQ + 1} of {questions.length}
          </p>
        </div>
      </div>

      {/* Progress */}
      <div className="flex items-center gap-3">
        <Progress value={progressPct} className="h-2 flex-1" />
        <span className="text-xs font-medium text-[#3b82f6] w-10 text-right">{progressPct}%</span>
      </div>

      {/* Question navigation dots */}
      <div className="flex flex-wrap gap-1.5">
        {questions.map((q: any, idx: number) => {
          const isAnswered = state.submittedAnswers[String(q.id)] != null;
          const isCurrent = idx === currentQ;
          return (
            <button
              key={q.id}
              onClick={() => { setCurrentQ(idx); setCurrentAnswer(null); }}
              className={cn(
                'w-7 h-7 rounded-full text-[10px] font-medium transition-all',
                isCurrent && 'ring-2 ring-[#3b82f6] ring-offset-1',
                isAnswered ? 'bg-emerald-100 text-emerald-700' : 'bg-[#f1f5f9] text-[#94a3b8]',
              )}
            >
              {isAnswered ? <Check className="w-3 h-3 mx-auto" /> : idx + 1}
            </button>
          );
        })}
      </div>

      {/* Question Card */}
      <Card className="border-[#e2e8f0]/50 shadow-card">
        <CardHeader className="pb-2">
          <CardTitle className="text-sm text-[#3b82f6]">
            Question {currentQ + 1}
            {question.required === 1 && <span className="text-red-500 ml-1">*</span>}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Question title */}
          <h4 className="font-medium text-[#1e293b]">{question.title}</h4>

          {/* Question content (HTML) */}
          {question.content && (
            <div className="text-sm text-[#64748b] prose prose-sm max-w-none"
              dangerouslySetInnerHTML={{ __html: question.content }} />
          )}

          {/* Answer input based on type */}
          <AnswerInput
            question={question}
            value={displayAnswer}
            onChange={setCurrentAnswer}
            disabled={saving}
          />

          {/* Previously returned/failed feedback */}
          {state.lastAttemptStatus === 'RETURNED' && (
            <div className="text-xs text-orange-600 bg-orange-50 rounded-lg p-3">
              <AlertCircle className="w-3.5 h-3.5 inline mr-1" />
              This quiz was returned for revision. Please update your answers.
            </div>
          )}
        </CardContent>
      </Card>

      {/* Navigation */}
      <div className="flex items-center justify-between">
        <Button
          variant="outline"
          size="sm"
          disabled={currentQ === 0}
          onClick={() => { setCurrentQ(prev => prev - 1); setCurrentAnswer(null); }}
        >
          <ChevronLeft className="w-4 h-4 mr-1" /> Previous
        </Button>

        <div className="flex gap-2">
          {/* Save answer button */}
          {currentAnswer != null && (
            <Button size="sm" onClick={handleSaveAnswer} disabled={saving}>
              {saving && <Loader2 className="w-3.5 h-3.5 animate-spin mr-1" />}
              <Send className="w-3.5 h-3.5 mr-1" />
              {answeredCount + 1 >= questions.length ? 'Submit Quiz' : 'Save & Next'}
            </Button>
          )}

          {/* Next button (when already answered) */}
          {currentAnswer == null && savedAnswer != null && currentQ < questions.length - 1 && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => { setCurrentQ(prev => prev + 1); setCurrentAnswer(null); }}
            >
              Next <ChevronRight className="w-4 h-4 ml-1" />
            </Button>
          )}
        </div>
      </div>
    </div>
  );
}

// ─── Answer Input Components ────────────────────────────────────────────────

function AnswerInput({ question, value, onChange, disabled }: {
  question: any;
  value: any;
  onChange: (v: any) => void;
  disabled: boolean;
}) {
  const { answer_type, options, table_structure } = question;

  if (answer_type === 'SINGLE' && options) {
    return (
      <div className="space-y-2">
        {Object.entries(options).map(([key, label]) => (
          <button
            key={key}
            onClick={() => onChange(key)}
            disabled={disabled}
            className={cn(
              'w-full text-left px-4 py-3 rounded-lg border transition-colors text-sm',
              String(value) === key
                ? 'border-[#3b82f6] bg-blue-50 text-[#1e293b] font-medium'
                : 'border-[#e2e8f0] hover:border-[#94a3b8] text-[#64748b]',
            )}
          >
            <span className="inline-flex items-center gap-2">
              <span className={cn(
                'w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0',
                String(value) === key ? 'border-[#3b82f6]' : 'border-[#cbd5e1]',
              )}>
                {String(value) === key && <span className="w-2.5 h-2.5 rounded-full bg-[#3b82f6]" />}
              </span>
              {String(label)}
            </span>
          </button>
        ))}
      </div>
    );
  }

  if (answer_type === 'MCQ' && options) {
    const selected: string[] = Array.isArray(value) ? value : [];
    return (
      <div className="space-y-2">
        <p className="text-xs text-[#94a3b8]">Select all that apply</p>
        {Object.entries(options).map(([key, label]) => {
          const isChecked = selected.includes(key);
          return (
            <button
              key={key}
              onClick={() => {
                const next = isChecked ? selected.filter(k => k !== key) : [...selected, key];
                onChange(next);
              }}
              disabled={disabled}
              className={cn(
                'w-full text-left px-4 py-3 rounded-lg border transition-colors text-sm',
                isChecked
                  ? 'border-[#3b82f6] bg-blue-50 text-[#1e293b]'
                  : 'border-[#e2e8f0] hover:border-[#94a3b8] text-[#64748b]',
              )}
            >
              <span className="inline-flex items-center gap-2">
                <span className={cn(
                  'w-5 h-5 rounded border flex items-center justify-center flex-shrink-0',
                  isChecked ? 'bg-[#3b82f6] border-[#3b82f6]' : 'border-[#cbd5e1]',
                )}>
                  {isChecked && <Check className="w-3 h-3 text-white" />}
                </span>
                {String(label)}
              </span>
            </button>
          );
        })}
      </div>
    );
  }

  if (answer_type === 'TEXTAREA') {
    return (
      <textarea
        className="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm min-h-[120px] focus:outline-none focus:ring-2 focus:ring-[#3b82f6] focus:ring-offset-0"
        placeholder="Type your answer here..."
        value={value ?? ''}
        onChange={e => onChange(e.target.value)}
        disabled={disabled}
      />
    );
  }

  if (answer_type === 'TEXT') {
    return (
      <input
        type="text"
        className="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b82f6] focus:ring-offset-0"
        placeholder="Type your answer here..."
        value={value ?? ''}
        onChange={e => onChange(e.target.value)}
        disabled={disabled}
      />
    );
  }

  if (answer_type === 'TABLE' && table_structure) {
    return <TableAnswerInput tableStructure={table_structure} value={value} onChange={onChange} disabled={disabled} />;
  }

  // Fallback — TEXT
  return (
    <textarea
      className="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm min-h-[80px] focus:outline-none focus:ring-2 focus:ring-[#3b82f6]"
      placeholder="Type your answer here..."
      value={value ?? ''}
      onChange={e => onChange(e.target.value)}
      disabled={disabled}
    />
  );
}

// ─── TABLE Answer Type ──────────────────────────────────────────────────────

function TableAnswerInput({ tableStructure, value, onChange, disabled }: {
  tableStructure: any;
  value: any;
  onChange: (v: any) => void;
  disabled: boolean;
}) {
  const rows = tableStructure?.rows ?? [];
  const columns = tableStructure?.columns ?? [];
  const inputType = tableStructure?.input_type ?? 'radio';

  const answers: Record<number, any> = value ?? {};

  function setRow(rowIndex: number, val: any) {
    onChange({ ...answers, [rowIndex]: val });
  }

  if (rows.length === 0 || columns.length === 0) {
    return <p className="text-sm text-[#94a3b8]">No table structure available.</p>;
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm border-collapse">
        <thead>
          <tr>
            <th className="border border-[#e2e8f0] px-3 py-2 bg-[#f8fafc] text-left text-xs font-medium text-[#64748b]">
              {tableStructure.row_heading ?? 'Question'}
            </th>
            {columns.map((col: any, ci: number) => (
              <th key={ci} className="border border-[#e2e8f0] px-3 py-2 bg-[#f8fafc] text-center text-xs font-medium text-[#64748b]">
                {col.heading}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map((row: any, ri: number) => (
            <tr key={ri} className="hover:bg-[#fafbfc]">
              <td className="border border-[#e2e8f0] px-3 py-2 text-[#1e293b]">{row.heading}</td>
              {columns.map((_col: any, ci: number) => (
                <td key={ci} className="border border-[#e2e8f0] px-3 py-2 text-center">
                  {inputType === 'radio' ? (
                    <input
                      type="radio"
                      name={`table-row-${ri}`}
                      checked={answers[ri]?.user_response === ci || answers[ri] === ci}
                      onChange={() => setRow(ri, { question: row.heading, answer: columns[ci]?.heading, user_response: ci })}
                      disabled={disabled}
                      className="w-4 h-4 text-[#3b82f6]"
                    />
                  ) : inputType === 'checkbox' ? (
                    <input
                      type="checkbox"
                      checked={Array.isArray(answers[ri]) && answers[ri].some((a: any) => a.user_response === String(ci))}
                      onChange={e => {
                        const prev = Array.isArray(answers[ri]) ? answers[ri] : [];
                        const entry = { question: row.heading, column: columns[ci]?.heading, user_response: String(ci) };
                        const next = e.target.checked
                          ? [...prev, entry]
                          : prev.filter((a: any) => a.user_response !== String(ci));
                        setRow(ri, next);
                      }}
                      disabled={disabled}
                      className="w-4 h-4 text-[#3b82f6]"
                    />
                  ) : (
                    <input
                      type="text"
                      value={answers[ri]?.[ci]?.user_response ?? ''}
                      onChange={e => {
                        const prev = answers[ri] ?? {};
                        setRow(ri, { ...prev, [ci]: { question: row.heading, column: columns[ci]?.heading, user_response: e.target.value } });
                      }}
                      disabled={disabled}
                      className="w-full border rounded px-2 py-1 text-xs"
                    />
                  )}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
