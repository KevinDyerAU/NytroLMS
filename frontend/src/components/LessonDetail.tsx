/**
 * LessonDetail — Drill-down view from CourseDetail showing topics + quizzes.
 * Supports topic CRUD, reordering, and drill-down to quiz question management.
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { FeaturedImageUpload } from './FeaturedImageUpload';
import {
  fetchLessonFullDetail,
  createTopic,
  updateTopic,
  deleteTopic,
  reorderTopics,
  type LessonFullDetail as LessonFullDetailType,
} from '@/lib/api';
import { toast } from 'sonner';
import {
  ArrowLeft, Loader2, Plus, Pencil, Trash2, Layers, Clock,
  GripVertical, ArrowUp, ArrowDown, FileText, BookOpen,
} from 'lucide-react';

interface LessonDetailProps {
  lessonId: number;
  courseId: number;
  onBack: () => void;
  onOpenTopic: (topicId: number) => void;
}

export function LessonDetail({ lessonId, courseId, onBack, onOpenTopic }: LessonDetailProps) {
  const [lesson, setLesson] = useState<LessonFullDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [addTopicOpen, setAddTopicOpen] = useState(false);
  const [editingTopic, setEditingTopic] = useState<LessonFullDetailType['topics'][0] | null>(null);
  const [deletingTopicId, setDeletingTopicId] = useState<number | null>(null);
  const [reordering, setReordering] = useState(false);

  const loadLesson = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchLessonFullDetail(lessonId);
      setLesson(data);
    } catch {
      toast.error('Failed to load lesson');
    } finally {
      setLoading(false);
    }
  }, [lessonId]);

  useEffect(() => { loadLesson(); }, [loadLesson]);

  const handleReorder = async (fromIdx: number, direction: 'up' | 'down') => {
    if (!lesson) return;
    const toIdx = direction === 'up' ? fromIdx - 1 : fromIdx + 1;
    if (toIdx < 0 || toIdx >= lesson.topics.length) return;

    const newTopics = [...lesson.topics];
    [newTopics[fromIdx], newTopics[toIdx]] = [newTopics[toIdx], newTopics[fromIdx]];
    setLesson({ ...lesson, topics: newTopics });

    setReordering(true);
    try {
      await reorderTopics(lessonId, newTopics.map(t => t.id));
      toast.success('Topics reordered');
    } catch {
      toast.error('Failed to reorder');
      loadLesson();
    } finally {
      setReordering(false);
    }
  };

  const handleDeleteTopic = async (topic: LessonFullDetailType['topics'][0]) => {
    if (!confirm(`Delete topic "${topic.title}"?`)) return;
    setDeletingTopicId(topic.id);
    try {
      await deleteTopic(topic.id);
      toast.success('Topic deleted');
      loadLesson();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to delete');
    } finally {
      setDeletingTopicId(null);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
      </div>
    );
  }

  if (!lesson) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" size="sm" onClick={onBack}><ArrowLeft className="w-4 h-4 mr-1.5" /> Back</Button>
        <Card className="border-red-200 bg-red-50">
          <CardContent className="py-8 text-center text-red-600">Lesson not found</CardContent>
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
              <FileText className="w-5 h-5 text-[#3b82f6]" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">{lesson.title}</h2>
              <p className="text-sm text-[#64748b]">{lesson.course_title} &bull; Lesson {lesson.order + 1}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Info + Image */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card className="lg:col-span-2">
          <CardHeader className="pb-3">
            <CardTitle className="text-base text-[#3b82f6]">Lesson Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <div className="flex gap-2"><span className="text-[#94a3b8] w-32">Release</span><span>{lesson.release_key}{lesson.release_value ? `: ${lesson.release_value}` : ''}</span></div>
            <div className="flex gap-2"><span className="text-[#94a3b8] w-32">Work Placement</span><span>{lesson.has_work_placement ? 'Yes' : 'No'}</span></div>
            <div className="flex gap-2"><span className="text-[#94a3b8] w-32">Topics</span><span>{lesson.topics.length}</span></div>
          </CardContent>
        </Card>
        <FeaturedImageUpload entityType="lesson" entityId={lessonId} />
      </div>

      {/* Topics List */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-base text-[#3b82f6]">Topics ({lesson.topics.length})</CardTitle>
          <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddTopicOpen(true)}>
            <Plus className="w-4 h-4 mr-1.5" /> Add Topic
          </Button>
        </CardHeader>
        <CardContent>
          {lesson.topics.length === 0 ? (
            <div className="text-center py-8">
              <Layers className="w-10 h-10 text-[#94a3b8] mx-auto mb-2" />
              <p className="text-sm text-[#94a3b8]">No topics in this lesson</p>
              <Button size="sm" variant="outline" className="mt-3" onClick={() => setAddTopicOpen(true)}>
                <Plus className="w-4 h-4 mr-1.5" /> Add First Topic
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {lesson.topics.map((topic, idx) => (
                <div key={topic.id} className="border border-[#e2e8f0] rounded-lg p-4 hover:border-[#3b82f6]/30 transition-colors">
                  <div className="flex items-start justify-between">
                    <button
                      type="button"
                      className="flex items-start gap-3 text-left flex-1 min-w-0 cursor-pointer hover:opacity-80"
                      onClick={() => onOpenTopic(topic.id)}
                    >
                      <div className="flex items-center gap-1 flex-shrink-0">
                        <GripVertical className="w-4 h-4 text-[#cbd5e1]" />
                        <div className="w-8 h-8 rounded-lg bg-[#eff6ff] flex items-center justify-center">
                          <span className="text-sm font-semibold text-[#3b82f6]">{idx + 1}</span>
                        </div>
                      </div>
                      <div>
                        <h4 className="font-medium text-[#1e293b]">{topic.title}</h4>
                        <div className="flex gap-3 mt-1 text-xs text-[#94a3b8]">
                          <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{topic.estimated_time}min</span>
                          <span className="flex items-center gap-1"><BookOpen className="w-3 h-3" />{topic.quizzes_count} quizzes</span>
                        </div>
                      </div>
                    </button>
                    <div className="flex items-center gap-1">
                      {lesson.topics.length > 1 && (
                        <>
                          <Button variant="ghost" size="sm" className="h-7 w-7 p-0" disabled={idx === 0 || reordering} onClick={() => handleReorder(idx, 'up')}>
                            <ArrowUp className="w-3.5 h-3.5" />
                          </Button>
                          <Button variant="ghost" size="sm" className="h-7 w-7 p-0" disabled={idx === lesson.topics.length - 1 || reordering} onClick={() => handleReorder(idx, 'down')}>
                            <ArrowDown className="w-3.5 h-3.5" />
                          </Button>
                        </>
                      )}
                      <Button variant="ghost" size="sm" className="h-7 w-7 p-0 text-[#64748b] hover:text-[#3b82f6]" onClick={() => setEditingTopic(topic)}>
                        <Pencil className="w-3.5 h-3.5" />
                      </Button>
                      <Button
                        variant="ghost" size="sm"
                        className="h-7 w-7 p-0 text-[#64748b] hover:text-red-500"
                        disabled={deletingTopicId === topic.id || topic.quizzes_count > 0}
                        title={topic.quizzes_count > 0 ? 'Delete associated quizzes first' : 'Delete topic'}
                        onClick={() => handleDeleteTopic(topic)}
                      >
                        {deletingTopicId === topic.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Add Topic Dialog */}
      <AddTopicDialog
        open={addTopicOpen}
        onOpenChange={setAddTopicOpen}
        courseId={courseId}
        lessonId={lessonId}
        onSaved={loadLesson}
      />

      {/* Edit Topic Dialog */}
      {editingTopic && (
        <EditTopicDialog
          open={!!editingTopic}
          onOpenChange={(v) => { if (!v) setEditingTopic(null); }}
          topic={editingTopic}
          onSaved={loadLesson}
        />
      )}
    </div>
  );
}

// ─── Add Topic Dialog ──────────────────────────────────────────────────────

function AddTopicDialog({ open, onOpenChange, courseId, lessonId, onSaved }: {
  open: boolean; onOpenChange: (v: boolean) => void; courseId: number; lessonId: number; onSaved: () => void;
}) {
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [estimatedTime, setEstimatedTime] = useState('30');
  const [content, setContent] = useState('');

  const reset = () => { setTitle(''); setEstimatedTime('30'); setContent(''); };

  const handleSubmit = async () => {
    if (!title.trim()) { toast.error('Title is required'); return; }
    setSaving(true);
    try {
      await createTopic({ title: title.trim(), course_id: courseId, lesson_id: lessonId, estimated_time: Number(estimatedTime) || 30, lb_content: content || null });
      toast.success('Topic created');
      reset();
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create topic');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) { reset(); onOpenChange(false); } else onOpenChange(v); }}>
      <DialogContent className="max-w-lg">
        <DialogHeader><DialogTitle>Add New Topic</DialogTitle></DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Title <span className="text-red-500">*</span></Label>
            <Input placeholder="Enter topic title" value={title} onChange={(e) => setTitle(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Estimated Time (minutes) <span className="text-red-500">*</span></Label>
            <Input type="number" min="1" value={estimatedTime} onChange={(e) => setEstimatedTime(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Content</Label>
            <Textarea placeholder="Topic content (optional)" rows={3} value={content} onChange={(e) => setContent(e.target.value)} />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => { reset(); onOpenChange(false); }} disabled={saving}>Cancel</Button>
          <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving || !title.trim()} onClick={handleSubmit}>
            {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />} Create Topic
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ─── Edit Topic Dialog ─────────────────────────────────────────────────────

function EditTopicDialog({ open, onOpenChange, topic, onSaved }: {
  open: boolean; onOpenChange: (v: boolean) => void; topic: LessonFullDetailType['topics'][0]; onSaved: () => void;
}) {
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState(topic.title);
  const [estimatedTime, setEstimatedTime] = useState(String(topic.estimated_time));

  useEffect(() => {
    setTitle(topic.title);
    setEstimatedTime(String(topic.estimated_time));
  }, [topic]);

  const handleSubmit = async () => {
    if (!title.trim()) { toast.error('Title is required'); return; }
    setSaving(true);
    try {
      await updateTopic(topic.id, { title: title.trim(), estimated_time: Number(estimatedTime) || 30 });
      toast.success('Topic updated');
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update topic');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) onOpenChange(false); else onOpenChange(v); }}>
      <DialogContent className="max-w-lg">
        <DialogHeader><DialogTitle>Edit Topic</DialogTitle></DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Title <span className="text-red-500">*</span></Label>
            <Input value={title} onChange={(e) => setTitle(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Estimated Time (minutes)</Label>
            <Input type="number" min="1" value={estimatedTime} onChange={(e) => setEstimatedTime(e.target.value)} />
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
