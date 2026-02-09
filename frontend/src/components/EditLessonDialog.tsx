import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { updateLesson } from '@/lib/api';
import type { DbLesson } from '@/lib/types';

interface EditLessonDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  lesson: (DbLesson & { topics_count: number; lb_content?: string | null }) | null;
  onSaved: () => void;
}

export function EditLessonDialog({ open, onOpenChange, lesson, onSaved }: EditLessonDialogProps) {
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [releaseKey, setReleaseKey] = useState('IMMEDIATE');
  const [releaseValue, setReleaseValue] = useState('');
  const [hasWorkPlacement, setHasWorkPlacement] = useState(false);
  const [content, setContent] = useState('');

  useEffect(() => {
    if (lesson && open) {
      setTitle(lesson.title);
      setReleaseKey(lesson.release_key || 'IMMEDIATE');
      setReleaseValue(lesson.release_value || '');
      setHasWorkPlacement(lesson.has_work_placement === 1);
      setContent((lesson as any).lb_content || '');
    }
  }, [lesson, open]);

  const handleClose = () => {
    onOpenChange(false);
  };

  const handleSubmit = async () => {
    if (!lesson) return;

    if (!title.trim()) {
      toast.error('Title is required');
      return;
    }

    if (releaseKey === 'XDAYS' && !releaseValue) {
      toast.error('Number of days is required for X Days release');
      return;
    }

    if (releaseKey === 'DATE' && !releaseValue) {
      toast.error('Date is required for date-based release');
      return;
    }

    setSaving(true);
    try {
      await updateLesson(lesson.id, {
        title: title.trim(),
        release_key: releaseKey,
        release_value: releaseKey === 'IMMEDIATE' ? null : releaseValue || null,
        has_work_placement: hasWorkPlacement ? 1 : 0,
        lb_content: content || null,
      });
      toast.success('Lesson updated successfully');
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update lesson');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) handleClose(); else onOpenChange(v); }}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit Lesson</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="edit-lesson-title">Title <span className="text-red-500">*</span></Label>
            <Input
              id="edit-lesson-title"
              placeholder="Enter lesson title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="edit-release-key">Release Schedule</Label>
              <Select value={releaseKey} onValueChange={setReleaseKey}>
                <SelectTrigger id="edit-release-key">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="IMMEDIATE">Immediate</SelectItem>
                  <SelectItem value="XDAYS">X Days After Start</SelectItem>
                  <SelectItem value="DATE">Specific Date</SelectItem>
                </SelectContent>
              </Select>
            </div>

            {releaseKey === 'XDAYS' && (
              <div className="space-y-2">
                <Label htmlFor="edit-release-days">Days After Start</Label>
                <Input
                  id="edit-release-days"
                  type="number"
                  min="1"
                  placeholder="e.g. 7"
                  value={releaseValue}
                  onChange={(e) => setReleaseValue(e.target.value)}
                />
              </div>
            )}

            {releaseKey === 'DATE' && (
              <div className="space-y-2">
                <Label htmlFor="edit-release-date">Release Date</Label>
                <Input
                  id="edit-release-date"
                  type="date"
                  value={releaseValue}
                  onChange={(e) => setReleaseValue(e.target.value)}
                />
              </div>
            )}
          </div>

          <div className="flex items-center gap-2">
            <Checkbox
              id="edit-work-placement"
              checked={hasWorkPlacement}
              onCheckedChange={(checked) => setHasWorkPlacement(checked === true)}
            />
            <Label htmlFor="edit-work-placement" className="text-sm font-normal cursor-pointer">
              Has Work Placement
            </Label>
          </div>

          <div className="space-y-2">
            <Label htmlFor="edit-lesson-content">Content</Label>
            <Textarea
              id="edit-lesson-content"
              placeholder="Lesson content (optional)"
              rows={4}
              value={content}
              onChange={(e) => setContent(e.target.value)}
            />
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={handleClose} disabled={saving}>
            Cancel
          </Button>
          <Button
            className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
            disabled={saving || !title.trim()}
            onClick={handleSubmit}
          >
            {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
