/**
 * QuizQuestionEditor — Full question management for a quiz.
 * Matches Laravel QuestionController: bulk save, reorder, delete, answer types.
 * Supports: SINGLE, MCQ, TEXT, TEXTAREA, TABLE answer types.
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FeaturedImageUpload } from './FeaturedImageUpload';
import {
  fetchQuizQuestions,
  saveQuestions,
  deleteQuestion,
  reorderQuestions,
  type QuestionData,
} from '@/lib/api';
import { toast } from 'sonner';
import {
  ArrowLeft, Loader2, Plus, Trash2, Save, ArrowUp, ArrowDown,
  GripVertical, FileQuestion, ChevronDown, ChevronUp, X,
} from 'lucide-react';

interface QuizQuestionEditorProps {
  quizId: number;
  quizTitle: string;
  onBack: () => void;
}

const ANSWER_TYPES = [
  { value: 'SINGLE', label: 'Single Choice (Radio)' },
  { value: 'MCQ', label: 'Multiple Choice (Checkbox)' },
  { value: 'TEXT', label: 'Short Text' },
  { value: 'TEXTAREA', label: 'Long Text' },
  { value: 'TABLE', label: 'Table' },
];

interface EditableQuestion extends QuestionData {
  _tempId?: string;
  _expanded?: boolean;
  _dirty?: boolean;
}

export function QuizQuestionEditor({ quizId, quizTitle, onBack }: QuizQuestionEditorProps) {
  const [questions, setQuestions] = useState<EditableQuestion[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [reordering, setReordering] = useState(false);
  const [hasUnsaved, setHasUnsaved] = useState(false);

  const loadQuestions = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchQuizQuestions(quizId);
      setQuestions((data ?? []).map(q => ({ ...q, _expanded: false, _dirty: false })));
      setHasUnsaved(false);
    } catch {
      toast.error('Failed to load questions');
    } finally {
      setLoading(false);
    }
  }, [quizId]);

  useEffect(() => { loadQuestions(); }, [loadQuestions]);

  const updateQuestion = (idx: number, updates: Partial<EditableQuestion>) => {
    setQuestions(prev => prev.map((q, i) => i === idx ? { ...q, ...updates, _dirty: true } : q));
    setHasUnsaved(true);
  };

  const addQuestion = () => {
    const newQ: EditableQuestion = {
      _tempId: `new-${Date.now()}`,
      order: questions.length,
      title: '',
      content: '',
      answer_type: 'TEXT',
      required: 0,
      options: null,
      correct_answer: null,
      table_structure: null,
      _expanded: true,
      _dirty: true,
    };
    setQuestions(prev => [...prev, newQ]);
    setHasUnsaved(true);
  };

  const removeNewQuestion = (idx: number) => {
    const q = questions[idx];
    if (q.id) return; // Can't remove saved questions this way
    setQuestions(prev => prev.filter((_, i) => i !== idx));
  };

  const handleSaveAll = async () => {
    const dirtyQuestions = questions.filter(q => q._dirty);
    if (dirtyQuestions.length === 0) { toast.info('No changes to save'); return; }

    // Validate
    for (const q of dirtyQuestions) {
      if (!q.title.trim()) { toast.error('All questions must have a title'); return; }
      if (!q.content.trim()) { toast.error('All questions must have content'); return; }
    }

    setSaving(true);
    try {
      await saveQuestions(quizId, dirtyQuestions.map((q, _i) => ({
        id: q.id,
        slug: q.slug,
        order: q.order,
        title: q.title,
        content: q.content,
        answer_type: q.answer_type,
        required: q.required,
        options: q.options,
        correct_answer: q.correct_answer,
        table_structure: q.table_structure,
      })));
      toast.success('Questions saved');
      await loadQuestions();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to save');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (idx: number) => {
    const q = questions[idx];
    if (!q.id) {
      removeNewQuestion(idx);
      return;
    }
    if (!confirm(`Delete question "${q.title}"?`)) return;
    setDeletingId(q.id);
    try {
      await deleteQuestion(q.id);
      toast.success('Question deleted');
      await loadQuestions();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to delete');
    } finally {
      setDeletingId(null);
    }
  };

  const handleReorder = async (fromIdx: number, direction: 'up' | 'down') => {
    const toIdx = direction === 'up' ? fromIdx - 1 : fromIdx + 1;
    if (toIdx < 0 || toIdx >= questions.length) return;

    const newQuestions = [...questions];
    [newQuestions[fromIdx], newQuestions[toIdx]] = [newQuestions[toIdx], newQuestions[fromIdx]];
    // Update order fields
    newQuestions.forEach((q, i) => { q.order = i; });
    setQuestions(newQuestions);

    // Only persist reorder for saved questions
    const savedIds = newQuestions.filter(q => q.id).map(q => q.id!);
    if (savedIds.length > 1) {
      setReordering(true);
      try {
        await reorderQuestions(quizId, savedIds);
      } catch {
        toast.error('Failed to reorder');
        loadQuestions();
      } finally {
        setReordering(false);
      }
    }
  };

  const toggleExpand = (idx: number) => {
    setQuestions(prev => prev.map((q, i) => i === idx ? { ...q, _expanded: !q._expanded } : q));
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
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
            <div className="p-2 rounded-lg bg-[#fef3c7]">
              <FileQuestion className="w-5 h-5 text-[#f59e0b]" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">Questions: {quizTitle}</h2>
              <p className="text-sm text-[#64748b]">{questions.length} questions</p>
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Button size="sm" variant="outline" onClick={addQuestion}>
            <Plus className="w-4 h-4 mr-1.5" /> Add Question
          </Button>
          <Button
            size="sm"
            className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
            disabled={saving || !hasUnsaved}
            onClick={handleSaveAll}
          >
            {saving ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
            Save All
          </Button>
        </div>
      </div>

      {/* Featured Image */}
      <FeaturedImageUpload entityType="quiz" entityId={quizId} />

      {/* Questions */}
      {questions.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center">
            <FileQuestion className="w-12 h-12 text-[#94a3b8] mx-auto mb-3" />
            <p className="text-sm text-[#94a3b8] mb-3">No questions yet</p>
            <Button size="sm" onClick={addQuestion}>
              <Plus className="w-4 h-4 mr-1.5" /> Add First Question
            </Button>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-3">
          {questions.map((q, idx) => (
            <Card key={q.id || q._tempId} className={`border ${q._dirty ? 'border-amber-300 bg-amber-50/30' : 'border-[#e2e8f0]'}`}>
              {/* Collapsed header */}
              <div
                className="flex items-center gap-3 p-4 cursor-pointer"
                onClick={() => toggleExpand(idx)}
              >
                <GripVertical className="w-4 h-4 text-[#cbd5e1] flex-shrink-0" />
                <div className="w-7 h-7 rounded-md bg-[#eff6ff] flex items-center justify-center flex-shrink-0">
                  <span className="text-xs font-bold text-[#3b82f6]">{idx + 1}</span>
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-[#1e293b] truncate">{q.title || '(Untitled)'}</p>
                  <div className="flex gap-2 mt-0.5">
                    <Badge variant="outline" className="text-[10px]">{q.answer_type}</Badge>
                    {q._dirty && <Badge className="text-[10px] bg-amber-100 text-amber-700 border-amber-200">Unsaved</Badge>}
                  </div>
                </div>
                <div className="flex items-center gap-1">
                  {questions.length > 1 && (
                    <>
                      <Button variant="ghost" size="sm" className="h-7 w-7 p-0" disabled={idx === 0 || reordering}
                        onClick={(e) => { e.stopPropagation(); handleReorder(idx, 'up'); }}>
                        <ArrowUp className="w-3.5 h-3.5" />
                      </Button>
                      <Button variant="ghost" size="sm" className="h-7 w-7 p-0" disabled={idx === questions.length - 1 || reordering}
                        onClick={(e) => { e.stopPropagation(); handleReorder(idx, 'down'); }}>
                        <ArrowDown className="w-3.5 h-3.5" />
                      </Button>
                    </>
                  )}
                  <Button variant="ghost" size="sm" className="h-7 w-7 p-0 text-[#64748b] hover:text-red-500"
                    disabled={deletingId === q.id}
                    onClick={(e) => { e.stopPropagation(); handleDelete(idx); }}>
                    {deletingId === q.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
                  </Button>
                  {q._expanded ? <ChevronUp className="w-4 h-4 text-[#94a3b8]" /> : <ChevronDown className="w-4 h-4 text-[#94a3b8]" />}
                </div>
              </div>

              {/* Expanded editor */}
              {q._expanded && (
                <CardContent className="pt-0 pb-4 px-4 border-t border-[#f1f5f9]">
                  <div className="space-y-4 mt-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Title <span className="text-red-500">*</span></Label>
                        <Input
                          value={q.title}
                          onChange={(e) => updateQuestion(idx, { title: e.target.value })}
                          placeholder="Question title"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Answer Type</Label>
                        <Select value={q.answer_type} onValueChange={(v) => updateQuestion(idx, { answer_type: v, options: null, correct_answer: null, table_structure: null })}>
                          <SelectTrigger><SelectValue /></SelectTrigger>
                          <SelectContent>
                            {ANSWER_TYPES.map(t => (
                              <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Label>Content <span className="text-red-500">*</span></Label>
                      <Textarea
                        value={q.content}
                        onChange={(e) => updateQuestion(idx, { content: e.target.value })}
                        placeholder="Question content / instructions"
                        rows={3}
                      />
                    </div>

                    {/* Options for SINGLE / MCQ */}
                    {(q.answer_type === 'SINGLE' || q.answer_type === 'MCQ') && (
                      <OptionsEditor
                        answerType={q.answer_type}
                        options={q.options ?? null}
                        correctAnswer={q.correct_answer ?? null}
                        onChange={(opts, correct) => updateQuestion(idx, { options: opts, correct_answer: correct })}
                      />
                    )}

                    {/* Correct answer for TEXT types */}
                    {(q.answer_type === 'TEXT' || q.answer_type === 'TEXTAREA') && (
                      <div className="space-y-2">
                        <Label>Correct Answer (optional, for auto-grading)</Label>
                        <Input
                          value={q.correct_answer || ''}
                          onChange={(e) => updateQuestion(idx, { correct_answer: e.target.value || null })}
                          placeholder="Leave blank for manual review"
                        />
                      </div>
                    )}
                  </div>
                </CardContent>
              )}
            </Card>
          ))}
        </div>
      )}

      {/* Sticky save bar */}
      {hasUnsaved && (
        <div className="sticky bottom-4 flex justify-center">
          <Button
            className="bg-[#3b82f6] hover:bg-[#2563eb] text-white shadow-lg px-8"
            disabled={saving}
            onClick={handleSaveAll}
          >
            {saving ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Save className="w-4 h-4 mr-1.5" />}
            Save All Changes
          </Button>
        </div>
      )}
    </div>
  );
}

// ─── Options Editor (SINGLE/MCQ) ──────────────────────────────────────────

function OptionsEditor({ answerType, options, correctAnswer, onChange }: {
  answerType: string;
  options: Record<string, unknown> | null;
  correctAnswer: string | null;
  onChange: (options: Record<string, unknown>, correctAnswer: string | null) => void;
}) {
  const key = answerType === 'MCQ' ? 'mcq' : 'scq';
  const optionsList: string[] = (() => {
    if (!options) return ['', ''];
    const raw = (options as any)[key];
    if (Array.isArray(raw)) return raw;
    return ['', ''];
  })();

  const correctArr: string[] = (() => {
    if (!correctAnswer) return [];
    try {
      const parsed = JSON.parse(correctAnswer);
      if (Array.isArray(parsed)) return parsed.map(String);
      return [String(correctAnswer)];
    } catch {
      return [String(correctAnswer)];
    }
  })();

  const updateOptions = (newOpts: string[]) => {
    onChange({ [key]: newOpts }, correctAnswer);
  };

  const toggleCorrect = (optValue: string) => {
    if (answerType === 'SINGLE') {
      onChange(options ?? { [key]: optionsList }, optValue);
    } else {
      const newCorrect = correctArr.includes(optValue)
        ? correctArr.filter(c => c !== optValue)
        : [...correctArr, optValue];
      onChange(options ?? { [key]: optionsList }, JSON.stringify(newCorrect));
    }
  };

  const addOption = () => {
    updateOptions([...optionsList, '']);
  };

  const removeOption = (idx: number) => {
    const newOpts = optionsList.filter((_, i) => i !== idx);
    updateOptions(newOpts);
  };

  const updateOption = (idx: number, value: string) => {
    const newOpts = [...optionsList];
    newOpts[idx] = value;
    updateOptions(newOpts);
  };

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <Label>Options</Label>
        <Button size="sm" variant="outline" className="h-7 text-xs" onClick={addOption}>
          <Plus className="w-3 h-3 mr-1" /> Add Option
        </Button>
      </div>
      <div className="space-y-2">
        {optionsList.map((opt, idx) => {
          const optKey = String(idx + 1);
          const isCorrect = answerType === 'SINGLE'
            ? correctAnswer === optKey || correctAnswer === opt
            : correctArr.includes(optKey) || correctArr.includes(opt);

          return (
            <div key={idx} className="flex items-center gap-2">
              <button
                type="button"
                className={`w-5 h-5 rounded-${answerType === 'SINGLE' ? 'full' : 'sm'} border-2 flex-shrink-0 flex items-center justify-center transition-colors ${
                  isCorrect ? 'border-green-500 bg-green-500 text-white' : 'border-[#cbd5e1] hover:border-[#3b82f6]'
                }`}
                title="Mark as correct answer"
                onClick={() => toggleCorrect(optKey)}
              >
                {isCorrect && <span className="text-[10px] font-bold">✓</span>}
              </button>
              <Input
                className="flex-1"
                placeholder={`Option ${idx + 1}`}
                value={opt}
                onChange={(e) => updateOption(idx, e.target.value)}
              />
              {optionsList.length > 2 && (
                <Button variant="ghost" size="sm" className="h-7 w-7 p-0 text-[#94a3b8] hover:text-red-500" onClick={() => removeOption(idx)}>
                  <X className="w-3.5 h-3.5" />
                </Button>
              )}
            </div>
          );
        })}
      </div>
      <p className="text-[11px] text-[#94a3b8]">
        {answerType === 'SINGLE' ? 'Click the circle to mark the correct answer' : 'Click checkboxes to mark correct answers (multiple allowed)'}
      </p>
    </div>
  );
}
