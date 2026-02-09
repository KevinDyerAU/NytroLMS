/**
 * EditCourseDialog — Edit an existing course's fields
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { fetchCourseById, updateCourse } from '@/lib/api';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface EditCourseDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  courseId: number;
  onSaved: () => void;
}

export function EditCourseDialog({ open, onOpenChange, courseId, onSaved }: EditCourseDialogProps) {
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  // Form state
  const [title, setTitle] = useState('');
  const [courseType, setCourseType] = useState('Paid');
  const [visibility, setVisibility] = useState('PUBLIC');
  const [status, setStatus] = useState('DRAFT');
  const [category, setCategory] = useState('');
  const [courseLengthDays, setCourseLengthDays] = useState('90');
  const [courseExpiryDays, setCourseExpiryDays] = useState('');
  const [isMainCourse, setIsMainCourse] = useState('1');
  const [version, setVersion] = useState('1');
  const [autoRegisterNext, setAutoRegisterNext] = useState('0');

  useEffect(() => {
    if (open && courseId) {
      setLoading(true);
      fetchCourseById(courseId)
        .then((data) => {
          if (data) {
            setTitle(data.title || '');
            setCourseType(data.course_type || 'Paid');
            setVisibility(data.visibility || 'PUBLIC');
            setStatus(data.status || 'DRAFT');
            setCategory(data.category || '');
            setCourseLengthDays(String(data.course_length_days ?? 90));
            setCourseExpiryDays(data.course_expiry_days ? String(data.course_expiry_days) : '');
            setIsMainCourse(String(data.is_main_course ?? 1));
            setVersion(String(data.version ?? 1));
            setAutoRegisterNext(String(data.auto_register_next_course ?? 0));
          }
        })
        .catch(() => toast.error('Failed to load course data'))
        .finally(() => setLoading(false));
    }
  }, [open, courseId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!title.trim()) {
      toast.error('Course title is required');
      return;
    }

    setSaving(true);
    try {
      await updateCourse(courseId, {
        title: title.trim(),
        course_type: courseType,
        visibility,
        status,
        category: category.trim() || null,
        course_length_days: parseInt(courseLengthDays) || 90,
        course_expiry_days: courseExpiryDays ? parseInt(courseExpiryDays) : null,
        is_main_course: parseInt(isMainCourse),
      });

      toast.success(`Course "${title.trim()}" updated successfully`);
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update course');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Course {title ? `— ${title}` : ''}</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-3">
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider">Course Information</h3>

              <div>
                <Label htmlFor="editCourseTitle">Title *</Label>
                <Input id="editCourseTitle" value={title} onChange={(e) => setTitle(e.target.value)} required />
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
                      <SelectItem value="ARCHIVED">Archived</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div>
                  <Label htmlFor="editCourseCategory">Category</Label>
                  <Input id="editCourseCategory" value={category} onChange={(e) => setCategory(e.target.value)} placeholder="e.g. Health, IT, Business" />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <Label htmlFor="editCourseLengthDays">Duration (days) *</Label>
                  <Input id="editCourseLengthDays" type="number" min="1" value={courseLengthDays} onChange={(e) => setCourseLengthDays(e.target.value)} required />
                </div>
                <div>
                  <Label htmlFor="editCourseExpiryDays">Expiry (days)</Label>
                  <Input id="editCourseExpiryDays" type="number" min="0" value={courseExpiryDays} onChange={(e) => setCourseExpiryDays(e.target.value)} placeholder="Optional" />
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

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="editCourseVersion">Version</Label>
                  <Input id="editCourseVersion" type="number" min="1" value={version} onChange={(e) => setVersion(e.target.value)} disabled className="bg-[#f8fafc]" />
                </div>
                <div>
                  <Label>Auto Register Next Course</Label>
                  <Select value={autoRegisterNext} onValueChange={setAutoRegisterNext} disabled>
                    <SelectTrigger className="bg-[#f8fafc]"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1">Yes</SelectItem>
                      <SelectItem value="0">No</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
                Cancel
              </Button>
              <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
                {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
                Save Changes
              </Button>
            </DialogFooter>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
}
