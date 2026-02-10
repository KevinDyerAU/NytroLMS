/**
 * Batch Enrolment Dialog — Enrol multiple students into a course at once.
 * Select a course, pick students (with search/filter), set dates, submit.
 */
import { useState, useEffect, useMemo } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  fetchAvailableCourses, fetchStudents, bulkCreateEnrolments,
  type UserWithDetails,
} from '@/lib/api';
import { Search, Loader2, Users, GraduationCap, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface BatchEnrolmentDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved?: () => void;
}

export function BatchEnrolmentDialog({ open, onOpenChange, onSaved }: BatchEnrolmentDialogProps) {
  const [courses, setCourses] = useState<{ id: number; title: string }[]>([]);
  const [students, setStudents] = useState<UserWithDetails[]>([]);
  const [selectedCourseId, setSelectedCourseId] = useState('');
  const [selectedStudentIds, setSelectedStudentIds] = useState<Set<number>>(new Set());
  const [studentSearch, setStudentSearch] = useState('');
  const [courseStartAt, setCourseStartAt] = useState('');
  const [courseEndsAt, setCourseEndsAt] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (!open) return;
    setLoading(true);
    setLoadError(null);
    Promise.all([
      fetchAvailableCourses(),
      fetchStudents({ status: 'active', limit: 500 }),
    ]).then(([c, s]) => {
      setCourses((c ?? []).map(x => ({ id: x.id, title: x.title })));
      setStudents(s?.data ?? []);
    }).catch(() => {
      setLoadError('Failed to load courses or students. Please close and try again.');
    }).finally(() => setLoading(false));
  }, [open]);

  // Reset on close
  useEffect(() => {
    if (!open) {
      setSelectedCourseId('');
      setSelectedStudentIds(new Set());
      setStudentSearch('');
      setCourseStartAt('');
      setCourseEndsAt('');
    }
  }, [open]);

  const filteredStudents = useMemo(() => {
    if (!studentSearch) return students;
    const s = studentSearch.toLowerCase();
    return students.filter(st =>
      `${st.first_name} ${st.last_name}`.toLowerCase().includes(s) ||
      st.email.toLowerCase().includes(s)
    );
  }, [students, studentSearch]);

  const toggleStudent = (id: number) => {
    setSelectedStudentIds(prev => {
      const next = new Set(prev);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  };

  const selectAll = () => {
    setSelectedStudentIds(new Set(filteredStudents.map(s => s.id)));
  };

  const clearAll = () => {
    setSelectedStudentIds(new Set());
  };

  const handleSubmit = async () => {
    if (!selectedCourseId || selectedStudentIds.size === 0) return;
    setSubmitting(true);
    try {
      const result = await bulkCreateEnrolments({
        student_ids: Array.from(selectedStudentIds),
        course_id: parseInt(selectedCourseId, 10),
        course_start_at: courseStartAt || undefined,
        course_ends_at: courseEndsAt || undefined,
      });
      const parts = [];
      if (result.succeeded > 0) parts.push(`${result.succeeded} enrolled`);
      if (result.skipped > 0) parts.push(`${result.skipped} already enrolled`);
      if (result.failed > 0) parts.push(`${result.failed} failed`);
      toast.success(parts.join(', '));
      onSaved?.();
      onOpenChange(false);
    } catch {
      toast.error('Batch enrolment failed');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-[#1e293b]">
            <div className="p-1.5 bg-[#eff6ff] rounded-lg">
              <Users className="w-4 h-4 text-[#3b82f6]" />
            </div>
            Batch Enrolment
          </DialogTitle>
          <DialogDescription className="text-[#64748b]">
            Select a course and choose multiple students to enrol at once.
          </DialogDescription>
        </DialogHeader>

        {loading ? (
          <div className="py-12 text-center">
            <Loader2 className="mx-auto h-7 w-7 animate-spin text-[#3b82f6]" />
            <p className="mt-3 text-sm text-[#64748b]">Loading courses and students...</p>
          </div>
        ) : loadError ? (
          <div className="py-6">
            <div className="flex items-center gap-3 p-4 bg-red-50/80 border border-red-200 rounded-lg">
              <div className="p-2 bg-red-100 rounded-lg">
                <AlertCircle className="w-5 h-5 text-red-600" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-semibold text-red-800">Loading failed</p>
                <p className="text-xs text-red-600 mt-0.5">{loadError}</p>
              </div>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {/* Course Selection */}
            <div className="space-y-1.5">
              <Label className="text-sm font-medium text-[#1e293b]">Course <span className="text-red-400">*</span></Label>
              <Select value={selectedCourseId} onValueChange={setSelectedCourseId}>
                <SelectTrigger className="border-[#e2e8f0]">
                  <SelectValue placeholder="Select a course..." />
                </SelectTrigger>
                <SelectContent>
                  {courses.map(c => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      <GraduationCap className="w-3.5 h-3.5 inline mr-1.5 text-[#94a3b8]" />
                      {c.title}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Date Range */}
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label className="text-sm font-medium">Start Date</Label>
                <Input
                  type="date"
                  value={courseStartAt}
                  onChange={(e) => setCourseStartAt(e.target.value)}
                  className="border-[#e2e8f0]"
                />
              </div>
              <div className="space-y-1.5">
                <Label className="text-sm font-medium">End Date (optional)</Label>
                <Input
                  type="date"
                  value={courseEndsAt}
                  onChange={(e) => setCourseEndsAt(e.target.value)}
                  className="border-[#e2e8f0]"
                />
              </div>
            </div>

            {/* Student Selection */}
            <div className="space-y-1.5">
              <div className="flex items-center justify-between">
                <Label className="text-sm font-medium">
                  Students ({selectedStudentIds.size} selected)
                </Label>
                <div className="flex gap-1">
                  <Button variant="ghost" size="sm" className="h-6 px-2 text-xs text-[#3b82f6] hover:bg-blue-50" onClick={selectAll}>
                    Select All
                  </Button>
                  <Button variant="ghost" size="sm" className="h-6 px-2 text-xs text-[#64748b] hover:bg-[#f1f5f9]" onClick={clearAll}>
                    Clear
                  </Button>
                </div>
              </div>
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#94a3b8]" />
                <Input
                  placeholder="Search students..."
                  value={studentSearch}
                  onChange={(e) => setStudentSearch(e.target.value)}
                  className="pl-8 h-8 text-sm border-[#e2e8f0]"
                />
              </div>
              <ScrollArea className="h-48 rounded-md border border-[#e2e8f0]">
                <div className="p-1">
                  {filteredStudents.length === 0 ? (
                    <p className="text-xs text-[#94a3b8] text-center py-4">No students found</p>
                  ) : (
                    filteredStudents.map(st => (
                      <label
                        key={st.id}
                        className={cn(
                          "flex items-center gap-2.5 px-2.5 py-2 rounded-md cursor-pointer transition-colors",
                          selectedStudentIds.has(st.id)
                            ? 'bg-blue-50/80 border border-blue-200'
                            : 'hover:bg-[#f8fafc] border border-transparent'
                        )}
                      >
                        <Checkbox
                          checked={selectedStudentIds.has(st.id)}
                          onCheckedChange={() => toggleStudent(st.id)}
                        />
                        <div className="flex-1 min-w-0">
                          <p className="text-sm text-[#1e293b] truncate">
                            {st.first_name} {st.last_name}
                          </p>
                          <p className="text-[10px] text-[#94a3b8] truncate">{st.email}</p>
                        </div>
                      </label>
                    ))
                  )}
                </div>
              </ScrollArea>
            </div>
          </div>
        )}

        <DialogFooter className="border-t border-[#e2e8f0] pt-4">
          <Button variant="outline" className="border-[#e2e8f0] text-[#64748b]" onClick={() => onOpenChange(false)}>Cancel</Button>
          <Button
            className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
            disabled={!selectedCourseId || selectedStudentIds.size === 0 || submitting}
            onClick={handleSubmit}
          >
            {submitting ? (
              <><Loader2 className="w-4 h-4 mr-1 animate-spin" /> Enrolling...</>
            ) : (
              <>Enrol {selectedStudentIds.size} Student{selectedStudentIds.size !== 1 ? 's' : ''}</>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
