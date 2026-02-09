/**
 * QuizResultView — Student-facing quiz result viewer
 * Replaces Laravel's frontend.content.assessments.review blade view (student side).
 * Shows: submitted answers, evaluation results, feedback/score.
 */
import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  fetchQuizResult,
  type QuizResultData,
} from '@/lib/api';
import {
  ArrowLeft, Loader2, CheckCircle2, XCircle, AlertCircle,
  ChevronDown, ChevronRight, MessageSquare,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface QuizResultViewProps {
  quizId: number;
  attemptId: number;
  userId: number;
  onBack: () => void;
}

export function QuizResultView({ quizId, attemptId, userId, onBack }: QuizResultViewProps) {
  const [result, setResult] = useState<QuizResultData | null>(null);
  const [loading, setLoading] = useState(true);
  const [expandedQs, setExpandedQs] = useState<Set<number>>(new Set());

  useEffect(() => {
    (async () => {
      try {
        const data = await fetchQuizResult(quizId, attemptId, userId);
        setResult(data);
        // Auto-expand all questions
        setExpandedQs(new Set(data.attempt.questions.map((_: any, i: number) => i)));
      } catch {
        // handled in UI
      } finally {
        setLoading(false);
      }
    })();
  }, [quizId, attemptId, userId]);

  if (loading) {
    return (
      <div className="py-16 text-center">
        <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
        <p className="mt-2 text-sm text-[#64748b]">Loading results...</p>
      </div>
    );
  }

  if (!result) {
    return (
      <Card className="p-8 text-center">
        <AlertCircle className="mx-auto h-10 w-10 text-red-400 mb-3" />
        <p className="text-sm text-[#64748b]">Failed to load quiz results.</p>
        <Button variant="outline" className="mt-4" onClick={onBack}>Go Back</Button>
      </Card>
    );
  }

  const { attempt, evaluation, feedback, quiz, correctAnswers } = result;
  const questions = attempt.questions;
  const answers = attempt.submitted_answers;
  const evalResults = evaluation?.results ?? {};

  const isAutoGraded = quiz.passing_percentage > 0;
  const statusColor = attempt.status === 'SATISFACTORY' ? 'emerald' :
    attempt.status === 'FAIL' ? 'red' :
    attempt.status === 'RETURNED' ? 'orange' : 'blue';

  const toggleQ = (idx: number) => {
    setExpandedQs(prev => {
      const next = new Set(prev);
      next.has(idx) ? next.delete(idx) : next.add(idx);
      return next;
    });
  };

  return (
    <div className="space-y-4 animate-fade-in-up">
      {/* Header */}
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="sm" onClick={onBack} className="gap-1 text-[#64748b]">
          <ArrowLeft className="w-4 h-4" /> Back
        </Button>
      </div>

      {/* Summary Card */}
      <Card className={cn(
        'border-l-4',
        statusColor === 'emerald' ? 'border-l-emerald-500' :
        statusColor === 'red' ? 'border-l-red-500' :
        statusColor === 'orange' ? 'border-l-orange-500' : 'border-l-blue-500',
      )}>
        <CardContent className="pt-6">
          <div className="flex items-start justify-between">
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">{quiz.title}</h2>
              <p className="text-xs text-[#94a3b8] mt-1">
                Attempt #{attempt.attempt}
                {attempt.submitted_at && ` — Submitted ${new Date(attempt.submitted_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })}`}
              </p>
            </div>
            <Badge className={cn(
              'text-xs',
              statusColor === 'emerald' ? 'bg-emerald-100 text-emerald-700' :
              statusColor === 'red' ? 'bg-red-100 text-red-700' :
              statusColor === 'orange' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700',
            )}>
              {attempt.status}
            </Badge>
          </div>

          {/* Score display */}
          {feedback && isAutoGraded && (
            <div className="mt-4 p-4 rounded-lg bg-[#f8fafc] border border-[#e2e8f0]">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-[#64748b]">Your Score</p>
                  <p className="text-2xl font-bold text-[#1e293b]">{feedback.obtained}%</p>
                </div>
                <div className="text-right">
                  <p className="text-sm text-[#64748b]">Passing</p>
                  <p className="text-2xl font-bold text-[#94a3b8]">{feedback.passing}%</p>
                </div>
                <div>
                  {feedback.obtained >= feedback.passing ? (
                    <CheckCircle2 className="w-10 h-10 text-emerald-500" />
                  ) : (
                    <XCircle className="w-10 h-10 text-red-500" />
                  )}
                </div>
              </div>
              {feedback.message && (
                <p className="text-sm text-[#64748b] mt-2">{feedback.message}</p>
              )}
            </div>
          )}

          {/* Feedback for non-auto-graded */}
          {feedback && !isAutoGraded && feedback.message && (
            <div className="mt-4 p-4 rounded-lg bg-blue-50 border border-blue-200">
              <div className="flex items-center gap-2 mb-1">
                <MessageSquare className="w-4 h-4 text-blue-500" />
                <span className="text-sm font-medium text-blue-700">Feedback</span>
              </div>
              <p className="text-sm text-blue-800">{feedback.message}</p>
            </div>
          )}

          {/* Status-specific messages */}
          {attempt.status === 'SUBMITTED' && (
            <p className="mt-3 text-sm text-blue-600">Your answers have been submitted and are awaiting review by your trainer.</p>
          )}
          {attempt.status === 'RETURNED' && (
            <p className="mt-3 text-sm text-orange-600">This assessment has been returned for revision. Please review the feedback and re-attempt.</p>
          )}
        </CardContent>
      </Card>

      {/* Questions */}
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-[#1e293b]">Questions & Answers</h3>
        <div className="flex gap-2">
          <Button variant="ghost" size="sm" onClick={() => setExpandedQs(new Set(questions.map((_: any, i: number) => i)))} className="text-xs">
            Expand All
          </Button>
          <Button variant="ghost" size="sm" onClick={() => setExpandedQs(new Set())} className="text-xs">
            Collapse All
          </Button>
        </div>
      </div>

      <div className="space-y-2">
        {questions.map((q: any, idx: number) => {
          const qId = String(q.id);
          const answer = answers[qId];
          const evalResult = evalResults[qId];
          const isExpanded = expandedQs.has(idx);
          const isCorrect = evalResult?.status === 'correct';
          const isIncorrect = evalResult?.status === 'incorrect';

          return (
            <Card key={q.id} className={cn(
              'overflow-hidden border',
              isCorrect ? 'border-emerald-200' :
              isIncorrect ? 'border-red-200' : 'border-[#e2e8f0]',
            )}>
              <button
                onClick={() => toggleQ(idx)}
                className="w-full flex items-center justify-between px-4 py-3 hover:bg-[#fafbfc] transition-colors"
              >
                <div className="flex items-center gap-3">
                  {isExpanded ? <ChevronDown className="w-4 h-4 text-[#94a3b8]" /> : <ChevronRight className="w-4 h-4 text-[#94a3b8]" />}
                  {evalResult && (
                    isCorrect
                      ? <CheckCircle2 className="w-4 h-4 text-emerald-500" />
                      : <XCircle className="w-4 h-4 text-red-500" />
                  )}
                  <span className="text-sm font-medium text-[#1e293b] text-left">
                    Q{idx + 1}: {q.title}
                  </span>
                </div>
                {evalResult && (
                  <span className={cn(
                    'text-[10px] font-medium px-2 py-0.5 rounded',
                    isCorrect ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600',
                  )}>
                    {isCorrect ? 'Correct' : 'Incorrect'}
                  </span>
                )}
              </button>

              {isExpanded && (
                <div className="px-4 pb-4 border-t border-[#f1f5f9]">
                  {/* Question content */}
                  {q.content && (
                    <div className="text-sm text-[#64748b] mt-3 prose prose-sm max-w-none"
                      dangerouslySetInnerHTML={{ __html: q.content }} />
                  )}

                  {/* Answer display */}
                  <div className="mt-3">
                    <p className="text-xs font-medium text-[#94a3b8] mb-1">Your Answer:</p>
                    <AnswerDisplay question={q} answer={answer} evalResult={evalResult} correctAnswer={correctAnswers[qId]} />
                  </div>

                  {/* Evaluation comment */}
                  {evalResult?.comment && (
                    <div className="mt-2 text-xs text-[#64748b] italic">{evalResult.comment}</div>
                  )}
                </div>
              )}
            </Card>
          );
        })}
      </div>
    </div>
  );
}

// ─── Answer Display ─────────────────────────────────────────────────────────

function AnswerDisplay({ question, answer, evalResult, correctAnswer }: {
  question: any;
  answer: any;
  evalResult: any;
  correctAnswer: any;
}) {
  const { answer_type, options } = question;

  if (answer_type === 'SINGLE' && options) {
    return (
      <div className="space-y-1">
        {Object.entries(options).map(([key, label]) => {
          const isSelected = String(answer) === key;
          const isCorrectOption = correctAnswer != null && String(correctAnswer) === key;
          return (
            <div
              key={key}
              className={cn(
                'flex items-center gap-2 px-3 py-2 rounded text-sm',
                isSelected && evalResult?.status === 'correct' && 'bg-emerald-50 border border-emerald-200',
                isSelected && evalResult?.status === 'incorrect' && 'bg-red-50 border border-red-200',
                isSelected && !evalResult && 'bg-blue-50 border border-blue-200',
                !isSelected && isCorrectOption && 'bg-emerald-50/50 border border-emerald-100',
                !isSelected && !isCorrectOption && 'text-[#94a3b8]',
              )}
            >
              {isSelected ? (
                evalResult?.status === 'correct'
                  ? <CheckCircle2 className="w-4 h-4 text-emerald-500 flex-shrink-0" />
                  : evalResult?.status === 'incorrect'
                    ? <XCircle className="w-4 h-4 text-red-500 flex-shrink-0" />
                    : <div className="w-4 h-4 rounded-full border-2 border-blue-400 flex items-center justify-center flex-shrink-0"><div className="w-2 h-2 bg-blue-400 rounded-full" /></div>
              ) : isCorrectOption ? (
                <CheckCircle2 className="w-4 h-4 text-emerald-400 flex-shrink-0" />
              ) : (
                <div className="w-4 h-4 rounded-full border-2 border-[#e2e8f0] flex-shrink-0" />
              )}
              <span>{String(label)}</span>
              {isCorrectOption && !isSelected && <span className="text-[10px] text-emerald-600 ml-auto">(correct)</span>}
            </div>
          );
        })}
      </div>
    );
  }

  if (answer_type === 'MCQ' && options) {
    const selected = Array.isArray(answer) ? answer : [];
    return (
      <div className="space-y-1">
        {Object.entries(options).map(([key, label]) => {
          const isSelected = selected.includes(key);
          return (
            <div key={key} className={cn(
              'flex items-center gap-2 px-3 py-2 rounded text-sm',
              isSelected ? 'bg-blue-50 border border-blue-200 text-[#1e293b]' : 'text-[#94a3b8]',
            )}>
              <div className={cn(
                'w-4 h-4 rounded border flex items-center justify-center flex-shrink-0',
                isSelected ? 'bg-[#3b82f6] border-[#3b82f6]' : 'border-[#e2e8f0]',
              )}>
                {isSelected && <CheckCircle2 className="w-3 h-3 text-white" />}
              </div>
              <span>{String(label)}</span>
            </div>
          );
        })}
      </div>
    );
  }

  if (answer_type === 'TABLE') {
    if (typeof answer === 'object' && answer !== null) {
      return (
        <div className="overflow-x-auto">
          <table className="w-full text-xs border-collapse">
            <tbody>
              {Object.entries(answer).map(([rowIdx, rowAnswer]: [string, any]) => {
                if (Array.isArray(rowAnswer)) {
                  return (
                    <tr key={rowIdx}>
                      <td className="border px-2 py-1 font-medium">{rowAnswer[0]?.question ?? `Row ${Number(rowIdx) + 1}`}</td>
                      <td className="border px-2 py-1">{rowAnswer.map((a: any) => a.column ?? a.user_response).join(', ')}</td>
                    </tr>
                  );
                }
                return (
                  <tr key={rowIdx}>
                    <td className="border px-2 py-1 font-medium">{rowAnswer?.question ?? `Row ${Number(rowIdx) + 1}`}</td>
                    <td className="border px-2 py-1">{rowAnswer?.answer ?? rowAnswer?.user_response ?? JSON.stringify(rowAnswer)}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      );
    }
    return <p className="text-sm text-[#94a3b8]">No answer provided</p>;
  }

  // TEXT / TEXTAREA
  if (answer != null && answer !== '') {
    return (
      <div className="bg-[#f8fafc] border border-[#e2e8f0] rounded-lg p-3 text-sm text-[#1e293b] whitespace-pre-wrap">
        {String(answer)}
      </div>
    );
  }

  return <p className="text-sm text-[#94a3b8] italic">No answer provided</p>;
}
