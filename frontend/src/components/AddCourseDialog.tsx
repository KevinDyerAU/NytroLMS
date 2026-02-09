/**
 * AddCourseDialog — Create a new course
 */
import { useState } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { createCourse, createLesson } from '@/lib/api';
import { Loader2, Plus, Trash2, GripVertical } from 'lucide-react';
import { toast } from 'sonner';

interface LessonDraft {
  id: string;
  title: string;
  release_key: string;
  release_value: string;
  has_work_placement: boolean;
}

interface AddCourseDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
}

export function AddCourseDialog({ open, onOpenChange, onSaved }: AddCourseDialogProps) {
  const [saving, setSaving] = useState(false);

  // Form state
  const [title, setTitle] = useState('');
  const [courseType, setCourseType] = useState('Paid');
  const [visibility, setVisibility] = useState('PUBLIC');
  const [status, setStatus] = useState('DRAFT');
  const [category, setCategory] = useState('');
  const [courseLengthDays, setCourseLengthDays] = useState('90');
  const [courseExpiryDays, setCourseExpiryDays] = useState('');
  const [isMainCourse, setIsMainCourse] = useState('1');
  const [lessons, setLessons] = useState<LessonDraft[]>([]);

  const resetForm = () => {
    setTitle('');
    setCourseType('Paid');
    setVisibility('PUBLIC');
    setStatus('DRAFT');
    setCategory('');
    setCourseLengthDays('90');
    setCourseExpiryDays('');
    setIsMainCourse('1');
    setLessons([]);
  };

  const addLessonDraft = () => {
    setLessons(prev => [...prev, {
      id: crypto.randomUUID(),
      title: '',
      release_key: 'IMMEDIATE',
      release_value: '',
      has_work_placement: false,
    }]);
  };

  const updateLessonDraft = (id: string, field: keyof LessonDraft, value: string | boolean) => {
    setLessons(prev => prev.map(l => l.id === id ? { ...l, [field]: value } : l));
  };

  const removeLessonDraft = (id: string) => {
    setLessons(prev => prev.filter(l => l.id !== id));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!title.trim()) {
      toast.error('Course title is required');
      return;
    }

    // Validate lesson titles
    const validLessons = lessons.filter(l => l.title.trim());
    const invalidLessons = lessons.filter(l => !l.title.trim());
    if (invalidLessons.length > 0) {
      toast.error('All lessons must have a title');
      return;
    }

    setSaving(true);
    try {
      const { id: courseId } = await createCourse({
        title: title.trim(),
        course_type: courseType,
        visibility,
        status,
        category: category.trim() || undefined,
        course_length_days: parseInt(courseLengthDays) || 90,
        course_expiry_days: courseExpiryDays ? parseInt(courseExpiryDays) : undefined,
        is_main_course: parseInt(isMainCourse),
      });

      // Create lessons sequentially to preserve order
      for (const lesson of validLessons) {
        await createLesson({
          title: lesson.title.trim(),
          course_id: courseId,
          release_key: lesson.release_key,
          release_value: lesson.release_key === 'IMMEDIATE' ? null : lesson.release_value || null,
          has_work_placement: lesson.has_work_placement ? 1 : 0,
        });
      }

      const lessonMsg = validLessons.length > 0 ? ` with ${validLessons.length} lesson${validLessons.length > 1 ? 's' : ''}` : '';
      toast.success(`Course "${title.trim()}" created${lessonMsg}`);
      resetForm();
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create course');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) resetForm(); onOpenChange(v); }}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Add New Course</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-3">
            <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Course Information</h3>

            <div>
              <Label htmlFor="courseTitle">Title *</Label>
              <Input id="courseTitle" value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Certificate III in Individual Support" required />
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label>Type</Label>
                <Select value={courseType} onValueChange={setCourseType}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Paid">Paid</SelectItem>
                    <SelectItem value="Free">Free</SelectItem>
                    <SelectItem value="Subscription">Subscription</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Visibility</Label>
                <Select value={visibility} onValueChange={setVisibility}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="PUBLIC">Public</SelectItem>
                    <SelectItem value="PRIVATE">Private</SelectItem>
                    <SelectItem value="HIDDEN">Hidden</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label>Status</Label>
                <Select value={status} onValueChange={setStatus}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="DRAFT">Draft</SelectItem>
                    <SelectItem value="PUBLISHED">Published</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label htmlFor="courseCategory">Category</Label>
                <Input id="courseCategory" value={category} onChange={(e) => setCategory(e.target.value)} placeholder="e.g. Health, IT, Business" />
              </div>
            </div>

            <div className="grid grid-cols-3 gap-3">
              <div>
                <Label htmlFor="courseLengthDays">Duration (days) *</Label>
                <Input id="courseLengthDays" type="number" min="1" value={courseLengthDays} onChange={(e) => setCourseLengthDays(e.target.value)} required />
              </div>
              <div>
                <Label htmlFor="courseExpiryDays">Expiry (days)</Label>
                <Input id="courseExpiryDays" type="number" min="0" value={courseExpiryDays} onChange={(e) => setCourseExpiryDays(e.target.value)} placeholder="Optional" />
              </div>
              <div>
                <Label>Main Course</Label>
                <Select value={isMainCourse} onValueChange={setIsMainCourse}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">Yes</SelectItem>
                    <SelectItem value="0">No</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          {/* Lessons Section */}
          <div className="space-y-3 border-t border-[#e2e8f0] pt-4">
            <div className="flex items-center justify-between">
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Lessons</h3>
              <Button type="button" variant="outline" size="sm" onClick={addLessonDraft}>
                <Plus className="w-3.5 h-3.5 mr-1" /> Add Lesson
              </Button>
            </div>

            {lessons.length === 0 ? (
              <p className="text-sm text-[#94a3b8] text-center py-3">No lessons yet. You can add lessons now or later.</p>
            ) : (
              <div className="space-y-3">
                {lessons.map((lesson, idx) => (
                  <div key={lesson.id} className="border border-[#e2e8f0] rounded-lg p-3 space-y-2 bg-[#f8fafc]">
                    <div className="flex items-center gap-2">
                      <span className="text-xs font-semibold text-[#3b82f6] bg-[#eff6ff] rounded px-1.5 py-0.5">{idx + 1}</span>
                      <Input
                        placeholder="Lesson title *"
                        value={lesson.title}
                        onChange={(e) => updateLessonDraft(lesson.id, 'title', e.target.value)}
                        className="flex-1"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="h-8 w-8 p-0 text-[#64748b] hover:text-red-500"
                        onClick={() => removeLessonDraft(lesson.id)}
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </Button>
                    </div>
                    <div className="grid grid-cols-3 gap-2">
                      <Select value={lesson.release_key} onValueChange={(v) => updateLessonDraft(lesson.id, 'release_key', v)}>
                        <SelectTrigger className="text-xs h-8">
                          <SelectValue placeholder="Release" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="IMMEDIATE">Immediate</SelectItem>
                          <SelectItem value="XDAYS">X Days After Start</SelectItem>
                          <SelectItem value="DATE">Specific Date</SelectItem>
                        </SelectContent>
                      </Select>
                      {lesson.release_key === 'XDAYS' && (
                        <Input
                          type="number"
                          min="1"
                          placeholder="Days"
                          className="text-xs h-8"
                          value={lesson.release_value}
                          onChange={(e) => updateLessonDraft(lesson.id, 'release_value', e.target.value)}
                        />
                      )}
                      {lesson.release_key === 'DATE' && (
                        <Input
                          type="date"
                          className="text-xs h-8"
                          value={lesson.release_value}
                          onChange={(e) => updateLessonDraft(lesson.id, 'release_value', e.target.value)}
                        />
                      )}
                      <label className="flex items-center gap-1.5 text-xs text-[#64748b] cursor-pointer">
                        <input
                          type="checkbox"
                          checked={lesson.has_work_placement}
                          onChange={(e) => updateLessonDraft(lesson.id, 'has_work_placement', e.target.checked)}
                          className="rounded border-[#d1d5db]"
                        />
                        Work Placement
                      </label>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => { resetForm(); onOpenChange(false); }} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
              {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
              Create Course
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
