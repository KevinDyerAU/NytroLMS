import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { createLesson } from '@/lib/api';

interface AddLessonDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  courseId: number;
  onSaved: () => void;
}

export function AddLessonDialog({ open, onOpenChange, courseId, onSaved }: AddLessonDialogProps) {
  const [saving, setSaving] = useState(false);
  const [title, setTitle] = useState('');
  const [releaseKey, setReleaseKey] = useState('IMMEDIATE');
  const [releaseValue, setReleaseValue] = useState('');
  const [hasWorkPlacement, setHasWorkPlacement] = useState(false);
  const [content, setContent] = useState('');

  const resetForm = () => {
    setTitle('');
    setReleaseKey('IMMEDIATE');
    setReleaseValue('');
    setHasWorkPlacement(false);
    setContent('');
  };

  const handleClose = () => {
    resetForm();
    onOpenChange(false);
  };

  const handleSubmit = async () => {
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
      await createLesson({
        title: title.trim(),
        course_id: courseId,
        release_key: releaseKey,
        release_value: releaseKey === 'IMMEDIATE' ? null : releaseValue || null,
        has_work_placement: hasWorkPlacement ? 1 : 0,
        lb_content: content || null,
      });
      toast.success('Lesson created successfully');
      resetForm();
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create lesson');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) handleClose(); else onOpenChange(v); }}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Add New Lesson</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="lesson-title">Title <span className="text-red-500">*</span></Label>
            <Input
              id="lesson-title"
              placeholder="Enter lesson title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="release-key">Release Schedule</Label>
              <Select value={releaseKey} onValueChange={setReleaseKey}>
                <SelectTrigger id="release-key">
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
                <Label htmlFor="release-days">Days After Start</Label>
                <Input
                  id="release-days"
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
                <Label htmlFor="release-date">Release Date</Label>
                <Input
                  id="release-date"
                  type="date"
                  value={releaseValue}
                  onChange={(e) => setReleaseValue(e.target.value)}
                />
              </div>
            )}
          </div>

          <div className="flex items-center gap-2">
            <Checkbox
              id="work-placement"
              checked={hasWorkPlacement}
              onCheckedChange={(checked) => setHasWorkPlacement(checked === true)}
            />
            <Label htmlFor="work-placement" className="text-sm font-normal cursor-pointer">
              Has Work Placement
            </Label>
          </div>

          <div className="space-y-2">
            <Label htmlFor="lesson-content">Content</Label>
            <Textarea
              id="lesson-content"
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
            Create Lesson
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
