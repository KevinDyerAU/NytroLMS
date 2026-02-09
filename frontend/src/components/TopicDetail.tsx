/**
 * TopicDetail — Drill-down view from LessonDetail showing quizzes.
 * Supports quiz CRUD, reordering, and drill-down to question management.
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { FeaturedImageUpload } from './FeaturedImageUpload';
import {
  fetchTopicFullDetail,
  createQuiz,
  updateQuiz,
  deleteQuiz,
  reorderQuizzes,
  type TopicFullDetail as TopicFullDetailType,
} from '@/lib/api';
import { toast } from 'sonner';
import {
  ArrowLeft, Loader2, Plus, Pencil, Trash2, Layers, Clock,
  ArrowUp, ArrowDown, FileQuestion, Percent, RotateCcw, GripVertical,
} from 'lucide-react';

interface TopicDetailProps {
  topicId: number;
  courseId: number;
  lessonId: number;
  onBack: () => void;
  onOpenQuiz: (quizId: number) => void;
}

export function TopicDetail({ topicId, courseId, lessonId, onBack, onOpenQuiz }: TopicDetailProps) {
  const [topic, setTopic] = useState<TopicFullDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [addQuizOpen, setAddQuizOpen] = useState(false);
  const [editingQuiz, setEditingQuiz] = useState<TopicFullDetailType['quizzes'][0] | null>(null);
  const [deletingQuizId, setDeletingQuizId] = useState<number | null>(null);
  const [reordering, setReordering] = useState(false);

  const loadTopic = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchTopicFullDetail(topicId);
      setTopic(data);
    } catch {
      toast.error('Failed to load topic');
    } finally {
      setLoading(false);
    }
  }, [topicId]);

  useEffect(() => { loadTopic(); }, [loadTopic]);

  const handleReorder = async (fromIdx: number, direction: 'up' | 'down') => {
    if (!topic) return;
    const toIdx = direction === 'up' ? fromIdx - 1 : fromIdx + 1;
    if (toIdx < 0 || toIdx >= topic.quizzes.length) return;

    const newQuizzes = [...topic.quizzes];
    [newQuizzes[fromIdx], newQuizzes[toIdx]] = [newQuizzes[toIdx], newQuizzes[fromIdx]];
    setTopic({ ...topic, quizzes: newQuizzes });

    setReordering(true);
    try {
      await reorderQuizzes(topicId, newQuizzes.map(q => q.id));
      toast.success('Quizzes reordered');
    } catch {
      toast.error('Failed to reorder');
      loadTopic();
    } finally {
      setReordering(false);
    }
  };

  const handleDeleteQuiz = async (quiz: TopicFullDetailType['quizzes'][0]) => {
    if (!confirm(`Delete quiz "${quiz.title}"? This will also delete all questions and attempts.`)) return;
    setDeletingQuizId(quiz.id);
    try {
      await deleteQuiz(quiz.id);
      toast.success('Quiz deleted');
      loadTopic();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to delete');
    } finally {
      setDeletingQuizId(null);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
      </div>
    );
  }

  if (!topic) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="w-4 h-4 mr-1.5" /> Back</Button>
        <Card className="border-red-200 bg-red-50">
          <CardContent className="py-8 text-center text-red-600">Topic not found</CardContent>
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
            <div className="p-2 rounded-lg bg-[#f0fdf4]">
              <Layers className="w-5 h-5 text-[#22c55e]" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">{topic.title}</h2>
              <p className="text-sm text-[#64748b]">{topic.lesson_title} &bull; Topic {topic.order + 1}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Info + Image */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card className="lg:col-span-2">
          <CardHeader className="pb-3">
            <CardTitle className="text-base text-[#3b82f6]">Topic Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <div className="flex gap-2"><span className="text-[#94a3b8] w-32">Est. Time</span><span>{topic.estimated_time} minutes</span></div>
            <div className="flex gap-2"><span className="text-[#94a3b8] w-32">Quizzes</span><span>{topic.quizzes.length}</span></div>
          </CardContent>
        </Card>
        <FeaturedImageUpload entityType="topic" entityId={topicId} />
      </div>

      {/* Quizzes List */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base text-[#3b82f6]">Quizzes ({topic.quizzes.length})</CardTitle>
          <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddQuizOpen(true)}>
            <Plus className="w-4 h-4 mr-1.5" /> Add Quiz
          </Button>
        </CardHeader>
        <CardContent>
          {topic.quizzes.length === 0 ? (
            <div className="text-center py-8">
              <FileQuestion className="w-10 h-10 text-[#94a3b8] mx-auto mb-2" />
              <p className="text-sm text-[#94a3b8]">No quizzes in this topic</p>
              <Button size="sm" variant="outline" className="mt-3" onClick={() => setAddQuizOpen(true)}>
                <Plus className="w-4 h-4 mr-1.5" /> Add First Quiz
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {topic.quizzes.map((quiz, idx) => (
                <div key={quiz.id} className="border border-[#e2e8f0] rounded-lg p-4 hover:border-[#3b82f6]/30 transition-colors">
                  <div className="flex items-start justify-between">
                    <button
                      type="button"
                      className="flex items-start gap-3 text-left flex-1 min-w-0 cursor-pointer hover:opacity-80"
                      onClick={() => onOpenQuiz(quiz.id)}
                    >
                      <div className="flex items-center gap-1 flex-shrink-0">
                        <GripVertical className="w-4 h-4 text-[#cbd5e1]" />
                        <div className="w-8 h-8 rounded-lg bg-[#fef3c7] flex items-center justify-center">
                          <span className="text-sm font-semibold text-[#f59e0b]">{idx + 1}</span>
                        </div>
                      </div>
                      <div>
                        <h4 className="font-medium text-[#1e293b]">{quiz.title}</h4>
                        <div className="flex gap-3 mt-1 text-xs text-[#94a3b8]">
                          <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{quiz.estimated_time}min</span>
                          <span className="flex items-center gap-1"><Percent className="w-3 h-3" />{quiz.passing_percentage}% pass</span>
                          <span className="flex items-center gap-1"><RotateCcw className="w-3 h-3" />{quiz.allowed_attempts} attempts</span>
                          <span className="flex items-center gap-1"><FileQuestion className="w-3 h-3" />{quiz.questions_count} questions</span>
                        </div>
                      </div>
                    </button>
                    <div className="flex items-center gap-1">
                      {quiz.has_checklist === 1 && (
                        <Badge variant="outline" className="text-xs text-purple-600 border-purple-200 mr-1">Checklist</Badge>
                      )}
                      {topic.quizzes.length > 1 && (
                        <>
                          <Button variant="ghost" size="sm" className="h-7 w-7 p-0" disabled={idx === 0 || reordering} onClick={() => handleReorder(idx, 'up')}>
                            <ArrowUp className="w-3.5 h-3.5" />
                          </Button>
                          <Button variant="ghost" size="sm" className="h-7 w-7 p-0" disabled={idx === topic.quizzes.length - 1 || reordering} onClick={() => handleReorder(idx, 'down')}>
                            <ArrowDown className="w-3.5 h-3.5" />
                          </Button>
                        </>
                      )}
                      <Button variant="ghost" size="sm" className="h-7 w-7 p-0 text-[#64748b] hover:text-[#3b82f6]" onClick={() => setEditingQuiz(quiz)}>
                        <Pencil className="w-3.5 h-3.5" />
                      </Button>
                      <Button
                        variant="ghost" size="sm"
                        className="h-7 w-7 p-0 text-[#64748b] hover:text-red-500"
                        disabled={deletingQuizId === quiz.id}
                        onClick={() => handleDeleteQuiz(quiz)}
                      >
                        {deletingQuizId === quiz.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Add Quiz Dialog */}
      <AddQuizDialog
        open={addQuizOpen}
        onOpenChange={setAddQuizOpen}
        courseId={courseId}
        lessonId={lessonId}
        topicId={topicId}
        onSaved={loadTopic}
      />

      {/* Edit Quiz Dialog */}
      {editingQuiz && (
        <EditQuizDialog
          open={!!editingQuiz}
          onOpenChange={(v) => { if (!v) setEditingQuiz(null); }}
          quiz={editingQuiz}
          onSaved={loadTopic}
        />
      )}
    </div>
  );
}

// ─── Add Quiz Dialog ───────────────────────────────────────────────────────

function AddQuizDialog({ open, onOpenChange, courseId, lessonId, topicId, onSaved }: {
  open: boolean; onOpenChange: (v: boolean) => void; courseId: number; lessonId: number; topicId: number; onSaved: () => void;
}) {
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [estimatedTime, setEstimatedTime] = useState('30');
  const [passingPercentage, setPassingPercentage] = useState('50');
  const [allowedAttempts, setAllowedAttempts] = useState('3');
  const [hasChecklist, setHasChecklist] = useState(false);
  const [content, setContent] = useState('');

  const reset = () => { setTitle(''); setEstimatedTime('30'); setPassingPercentage('50'); setAllowedAttempts('3'); setHasChecklist(false); setContent(''); };

  const handleSubmit = async () => {
    if (!title.trim()) { toast.error('Title is required'); return; }
    setSaving(true);
    try {
      await createQuiz({
        title: title.trim(),
        course_id: courseId,
        lesson_id: lessonId,
        topic_id: topicId,
        estimated_time: Number(estimatedTime) || 30,
        passing_percentage: Number(passingPercentage) || 50,
        allowed_attempts: Number(allowedAttempts) || 3,
        has_checklist: hasChecklist ? 1 : 0,
        lb_content: content || null,
      });
      toast.success('Quiz created');
      reset();
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create quiz');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) { reset(); onOpenChange(false); } else onOpenChange(v); }}>
      <DialogContent className="max-w-lg">
        <DialogHeader><DialogTitle>Add New Quiz</DialogTitle></DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Title <span className="text-red-500">*</span></Label>
            <Input placeholder="Enter quiz title" value={title} onChange={(e) => setTitle(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Content <span className="text-red-500">*</span></Label>
            <Textarea placeholder="Quiz instructions/content" rows={3} value={content} onChange={(e) => setContent(e.target.value)} />
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div className="space-y-2">
              <Label>Est. Time (min)</Label>
              <Input type="number" min="1" value={estimatedTime} onChange={(e) => setEstimatedTime(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Passing %</Label>
              <Input type="number" min="0" max="100" value={passingPercentage} onChange={(e) => setPassingPercentage(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Max Attempts</Label>
              <Input type="number" min="1" value={allowedAttempts} onChange={(e) => setAllowedAttempts(e.target.value)} />
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Checkbox id="has-checklist" checked={hasChecklist} onCheckedChange={(c) => setHasChecklist(c === true)} />
            <Label htmlFor="has-checklist" className="text-sm font-normal cursor-pointer">Has Checklist</Label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => { reset(); onOpenChange(false); }} disabled={saving}>Cancel</Button>
          <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving || !title.trim()} onClick={handleSubmit}>
            {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />} Create Quiz
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ─── Edit Quiz Dialog ──────────────────────────────────────────────────────

function EditQuizDialog({ open, onOpenChange, quiz, onSaved }: {
  open: boolean; onOpenChange: (v: boolean) => void; quiz: TopicFullDetailType['quizzes'][0]; onSaved: () => void;
}) {
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState(quiz.title);
  const [estimatedTime, setEstimatedTime] = useState(String(quiz.estimated_time));
  const [passingPercentage, setPassingPercentage] = useState(String(quiz.passing_percentage));
  const [allowedAttempts, setAllowedAttempts] = useState(String(quiz.allowed_attempts));
  const [hasChecklist, setHasChecklist] = useState(quiz.has_checklist === 1);

  useEffect(() => {
    setTitle(quiz.title);
    setEstimatedTime(String(quiz.estimated_time));
    setPassingPercentage(String(quiz.passing_percentage));
    setAllowedAttempts(String(quiz.allowed_attempts));
    setHasChecklist(quiz.has_checklist === 1);
  }, [quiz]);

  const handleSubmit = async () => {
    if (!title.trim()) { toast.error('Title is required'); return; }
    setSaving(true);
    try {
      await updateQuiz(quiz.id, {
        title: title.trim(),
        estimated_time: Number(estimatedTime) || 30,
        passing_percentage: Number(passingPercentage) || 50,
        allowed_attempts: Number(allowedAttempts) || 3,
        has_checklist: hasChecklist ? 1 : 0,
      });
      toast.success('Quiz updated');
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update quiz');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onOpenChange(false); else onOpenChange(v); }}>
      <DialogContent className="max-w-lg">
        <DialogHeader><DialogTitle>Edit Quiz</DialogTitle></DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Title <span className="text-red-500">*</span></Label>
            <Input value={title} onChange={(e) => setTitle(e.target.value)} />
          </div>
          <div className="grid grid-cols-3 gap-3">
            <div className="space-y-2">
              <Label>Est. Time (min)</Label>
              <Input type="number" min="1" value={estimatedTime} onChange={(e) => setEstimatedTime(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Passing %</Label>
              <Input type="number" min="0" max="100" value={passingPercentage} onChange={(e) => setPassingPercentage(e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label>Max Attempts</Label>
              <Input type="number" min="1" value={allowedAttempts} onChange={(e) => setAllowedAttempts(e.target.value)} />
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Checkbox id="edit-has-checklist" checked={hasChecklist} onCheckedChange={(c) => setHasChecklist(c === true)} />
            <Label htmlFor="edit-has-checklist" className="text-sm font-normal cursor-pointer">Has Checklist</Label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>Cancel</Button>
          <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving || !title.trim()} onClick={handleSubmit}>
            {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />} Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
