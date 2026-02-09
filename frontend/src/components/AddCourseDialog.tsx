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
import { createCourse } from '@/lib/api';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

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

  const resetForm = () => {
    setTitle('');
    setCourseType('Paid');
    setVisibility('PUBLIC');
    setStatus('DRAFT');
    setCategory('');
    setCourseLengthDays('90');
    setCourseExpiryDays('');
    setIsMainCourse('1');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!title.trim()) {
      toast.error('Course title is required');
      return;
    }

    setSaving(true);
    try {
      await createCourse({
        title: title.trim(),
        course_type: courseType,
        visibility,
        status,
        category: category.trim() || undefined,
        course_length_days: parseInt(courseLengthDays) || 90,
        course_expiry_days: courseExpiryDays ? parseInt(courseExpiryDays) : undefined,
        is_main_course: parseInt(isMainCourse),
      });

      toast.success(`Course "${title.trim()}" created successfully`);
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
