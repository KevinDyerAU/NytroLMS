/**
 * NewEnrolmentDialog — Enrol a student in a course
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { createEnrolment } from '@/lib/api';
import { supabase } from '@/lib/supabase';
import { Loader2, Search } from 'lucide-react';
import { toast } from 'sonner';

interface NewEnrolmentDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
}

interface StudentOption {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
}

interface CourseOption {
  id: number;
  title: string;
}

export function NewEnrolmentDialog({ open, onOpenChange, onSaved }: NewEnrolmentDialogProps) {
  const [saving, setSaving] = useState(false);
  const [loadingStudents, setLoadingStudents] = useState(false);
  const [loadingCourses, setLoadingCourses] = useState(false);

  const [students, setStudents] = useState<StudentOption[]>([]);
  const [courses, setCourses] = useState<CourseOption[]>([]);
  const [studentSearch, setStudentSearch] = useState('');

  const [selectedStudentId, setSelectedStudentId] = useState('');
  const [selectedCourseId, setSelectedCourseId] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  // Load courses on open
  useEffect(() => {
    if (open) {
      setLoadingCourses(true);
      Promise.resolve(
        supabase
          .from('courses')
          .select('id, title')
          .eq('is_archived', 0)
          .eq('status', 'PUBLISHED')
          .order('title', { ascending: true })
          .limit(200)
      )
        .then(({ data }) => setCourses(data ?? []))
        .finally(() => setLoadingCourses(false));
    }
  }, [open]);

  // Search students with debounce
  useEffect(() => {
    if (!open || studentSearch.length < 2) {
      setStudents([]);
      return;
    }

    const timer = setTimeout(() => {
      setLoadingStudents(true);
      Promise.resolve(
        supabase
          .from('users')
          .select('id, first_name, last_name, email')
          .eq('is_archived', 0)
          .or(`first_name.ilike.%${studentSearch}%,last_name.ilike.%${studentSearch}%,email.ilike.%${studentSearch}%`)
          .limit(20)
      )
        .then(({ data }) => setStudents(data ?? []))
        .finally(() => setLoadingStudents(false));
    }, 300);

    return () => clearTimeout(timer);
  }, [open, studentSearch]);

  const resetForm = () => {
    setSelectedStudentId('');
    setSelectedCourseId('');
    setStartDate('');
    setEndDate('');
    setStudentSearch('');
    setStudents([]);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedStudentId || !selectedCourseId) {
      toast.error('Please select both a student and a course');
      return;
    }

    setSaving(true);
    try {
      await createEnrolment({
        user_id: parseInt(selectedStudentId),
        course_id: parseInt(selectedCourseId),
        course_start_at: startDate || undefined,
        course_ends_at: endDate || undefined,
      });

      const student = students.find(s => s.id === parseInt(selectedStudentId));
      const course = courses.find(c => c.id === parseInt(selectedCourseId));
      toast.success(`${student?.first_name ?? 'Student'} enrolled in ${course?.title ?? 'course'}`);
      resetForm();
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create enrolment');
    } finally {
      setSaving(false);
    }
  };

  const selectedStudent = students.find(s => s.id === parseInt(selectedStudentId));

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) resetForm(); onOpenChange(v); }}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>New Enrolment</DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-3">
            {/* Student Search */}
            <div>
              <Label>Student *</Label>
              {selectedStudent ? (
                <div className="flex items-center justify-between p-2.5 border border-[#e2e8f0] rounded-md bg-[#f8fafc]">
                  <div>
                    <p className="text-sm font-medium text-[#1e293b]">
                      {selectedStudent.first_name} {selectedStudent.last_name}
                    </p>
                    <p className="text-xs text-[#94a3b8]">{selectedStudent.email}</p>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="text-xs text-[#64748b]"
                    onClick={() => { setSelectedStudentId(''); setStudentSearch(''); }}
                  >
                    Change
                  </Button>
                </div>
              ) : (
                <div className="space-y-1">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
                    <Input
                      placeholder="Search by name or email (min 2 chars)..."
                      value={studentSearch}
                      onChange={(e) => setStudentSearch(e.target.value)}
                      className="pl-9"
                    />
                  </div>
                  {loadingStudents && (
                    <div className="flex items-center gap-2 p-2 text-xs text-[#64748b]">
                      <Loader2 className="w-3 h-3 animate-spin" /> Searching...
                    </div>
                  )}
                  {!loadingStudents && students.length > 0 && (
                    <div className="border border-[#e2e8f0] rounded-md max-h-40 overflow-y-auto">
                      {students.map((s) => (
                        <button
                          key={s.id}
                          type="button"
                          className="w-full text-left px-3 py-2 hover:bg-[#f8fafc] transition-colors border-b border-[#f1f5f9] last:border-0"
                          onClick={() => setSelectedStudentId(String(s.id))}
                        >
                          <p className="text-sm font-medium text-[#1e293b]">{s.first_name} {s.last_name}</p>
                          <p className="text-xs text-[#94a3b8]">{s.email}</p>
                        </button>
                      ))}
                    </div>
                  )}
                  {!loadingStudents && studentSearch.length >= 2 && students.length === 0 && (
                    <p className="text-xs text-[#94a3b8] p-2">No students found</p>
                  )}
                </div>
              )}
            </div>

            {/* Course Select */}
            <div>
              <Label>Course *</Label>
              {loadingCourses ? (
                <div className="flex items-center gap-2 p-2 text-xs text-[#64748b]">
                  <Loader2 className="w-3 h-3 animate-spin" /> Loading courses...
                </div>
              ) : (
                <Select value={selectedCourseId} onValueChange={setSelectedCourseId}>
                  <SelectTrigger><SelectValue placeholder="Select a course..." /></SelectTrigger>
                  <SelectContent>
                    {courses.map((c) => (
                      <SelectItem key={c.id} value={String(c.id)}>{c.title}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            </div>

            {/* Dates */}
            <div className="grid grid-cols-2 gap-3">
              <div>
                <Label htmlFor="enrolStartDate">Start Date</Label>
                <Input
                  id="enrolStartDate"
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                />
              </div>
              <div>
                <Label htmlFor="enrolEndDate">End Date</Label>
                <Input
                  id="enrolEndDate"
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => { resetForm(); onOpenChange(false); }} disabled={saving}>
              Cancel
            </Button>
            <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving || !selectedStudentId || !selectedCourseId}>
              {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
              Create Enrolment
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
