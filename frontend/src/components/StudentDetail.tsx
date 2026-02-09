/**
 * StudentDetail — Full student detail view matching Laravel's students/show.blade.php
 * Tabbed interface: Overview, Courses/Progress, Activities
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatusBadge } from './StatusBadge';
import { StudentTrainingPlan } from './StudentTrainingPlan';
import { OnboardingWizard } from './OnboardingWizard';
import {
  fetchStudentFullDetail,
  activateStudent,
  deactivateStudent,
  fetchStudentActivities,
  fetchStudentDocuments,
  uploadStudentDocument,
  getDocumentDownloadUrl,
  deleteStudentDocument,
  fetchStudentNotes,
  createStudentNote,
  updateStudentNote,
  deleteStudentNote,
  toggleNotePin,
  fetchAvailableCourses,
  type StudentFullDetail as StudentFullDetailType,
  type StudentDocument,
  type StudentNote,
} from '@/lib/api';
import { supabase } from '@/lib/supabase';
import { useAuth } from '@/contexts/AuthContext';
import type { DbActivityLog } from '@/lib/types';
import {
  ArrowLeft, Edit, UserCheck, UserX, Mail, Phone, MapPin,
  Building2, GraduationCap, BookOpen, Clock, Calendar, User,
  Shield, Activity, Loader2, FileText, StickyNote, Pin,
  Trash2, Send, Upload, Download, Archive, Plus, Pencil, X, Check,
} from 'lucide-react';
import { toast } from 'sonner';

interface StudentDetailProps {
  studentId: number;
  onBack: () => void;
  onEdit: (studentId: number) => void;
}

export function StudentDetail({ studentId, onBack, onEdit }: StudentDetailProps) {
  const { user: authUser } = useAuth();
  const [student, setStudent] = useState<StudentFullDetailType | null>(null);
  const [activities, setActivities] = useState<DbActivityLog[]>([]);
  const [documents, setDocuments] = useState<StudentDocument[]>([]);
  const [notes, setNotes] = useState<StudentNote[]>([]);
  const [loading, setLoading] = useState(true);
  const [activitiesLoading, setActivitiesLoading] = useState(false);
  const [documentsLoading, setDocumentsLoading] = useState(false);
  const [notesLoading, setNotesLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState('overview');
  const [newNote, setNewNote] = useState('');
  const [editingNoteId, setEditingNoteId] = useState<number | null>(null);
  const [editingNoteText, setEditingNoteText] = useState('');
  const [archiving, setArchiving] = useState(false);
  const [assignCourseOpen, setAssignCourseOpen] = useState(false);
  const [availableCourses, setAvailableCourses] = useState<{ id: number; title: string; category: string | null }[]>([]);
  const [selectedCourseId, setSelectedCourseId] = useState<number | null>(null);
  const [assigningCourse, setAssigningCourse] = useState(false);

  const loadStudent = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchStudentFullDetail(studentId);
      if (!data) {
        setError('Student not found');
        return;
      }
      setStudent(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load student');
    } finally {
      setLoading(false);
    }
  }, [studentId]);

  useEffect(() => {
    loadStudent();
  }, [loadStudent]);

  const loadActivities = useCallback(async () => {
    if (activitiesLoading || activities.length > 0) return;
    setActivitiesLoading(true);
    try {
      const data = await fetchStudentActivities(studentId);
      setActivities(data);
    } catch {
      // silent
    } finally {
      setActivitiesLoading(false);
    }
  }, [studentId, activitiesLoading, activities.length]);

  const loadDocuments = useCallback(async () => {
    if (documentsLoading) return;
    setDocumentsLoading(true);
    try {
      const data = await fetchStudentDocuments(studentId);
      setDocuments(data);
    } catch {
      // silent
    } finally {
      setDocumentsLoading(false);
    }
  }, [studentId, documentsLoading]);

  const loadNotes = useCallback(async () => {
    setNotesLoading(true);
    try {
      const data = await fetchStudentNotes(studentId);
      setNotes(data);
    } catch {
      // silent
    } finally {
      setNotesLoading(false);
    }
  }, [studentId]);

  const handleUploadDocument = async (file: File) => {
    setUploading(true);
    try {
      const doc = await uploadStudentDocument(studentId, file);
      setDocuments(prev => [doc, ...prev]);
      toast.success(`Uploaded ${file.name}`);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to upload document');
    } finally {
      setUploading(false);
    }
  };

  const handleDownloadDocument = async (doc: StudentDocument) => {
    try {
      const url = await getDocumentDownloadUrl(doc.file_path);
      window.open(url, '_blank');
    } catch {
      toast.error('Failed to get download link');
    }
  };

  const handleDeleteDocument = async (doc: StudentDocument) => {
    try {
      await deleteStudentDocument(doc.id, doc.file_path);
      setDocuments(prev => prev.filter(d => d.id !== doc.id));
      toast.success(`Deleted ${doc.file_name}`);
    } catch {
      toast.error('Failed to delete document');
    }
  };

  const handleAddNote = async () => {
    if (!newNote.trim() || !authUser) return;
    try {
      await createStudentNote(studentId, newNote.trim(), authUser.id);
      setNewNote('');
      toast.success('Note added');
      loadNotes();
    } catch {
      toast.error('Failed to add note');
    }
  };

  const handleDeleteNote = async (noteId: number) => {
    try {
      await deleteStudentNote(noteId);
      setNotes(prev => prev.filter(n => n.id !== noteId));
      toast.success('Note deleted');
    } catch {
      toast.error('Failed to delete note');
    }
  };

  const handleTogglePin = async (noteId: number, currentlyPinned: boolean) => {
    try {
      await toggleNotePin(noteId, !currentlyPinned);
      setNotes(prev => prev.map(n => n.id === noteId ? { ...n, is_pinned: currentlyPinned ? 0 : 1 } : n));
    } catch {
      toast.error('Failed to update note');
    }
  };

  const handleEditNote = async (noteId: number) => {
    if (!editingNoteText.trim()) return;
    try {
      await updateStudentNote(noteId, editingNoteText.trim());
      setNotes(prev => prev.map(n => n.id === noteId ? { ...n, note_body: editingNoteText.trim() } : n));
      setEditingNoteId(null);
      setEditingNoteText('');
      toast.success('Note updated');
    } catch {
      toast.error('Failed to update note');
    }
  };

  const handleArchiveStudent = async () => {
    if (!student || !confirm(`Archive ${student.first_name} ${student.last_name}? This will mark the student as archived.`)) return;
    setArchiving(true);
    try {
      await supabase.from('users').update({ is_archived: 1, is_active: 0 }).eq('id', student.id);
      await supabase.from('user_details').update({ status: 'ARCHIVED' }).eq('user_id', student.id);
      toast.success(`${student.first_name} ${student.last_name} has been archived`);
      loadStudent();
    } catch {
      toast.error('Failed to archive student');
    } finally {
      setArchiving(false);
    }
  };

  const handleAssignCourse = async () => {
    if (!student || !selectedCourseId) return;
    setAssigningCourse(true);
    try {
      const today = new Date().toISOString().split('T')[0];
      // Get course to calculate end date
      const { data: course } = await supabase.from('courses').select('course_length_days').eq('id', selectedCourseId).single();
      const endDate = new Date();
      endDate.setDate(endDate.getDate() + (course?.course_length_days || 90));

      await supabase.from('student_course_enrolments').insert({
        user_id: student.id,
        course_id: selectedCourseId,
        status: 'ENROLLED',
        course_start_at: today + ' 00:00:00',
        course_ends_at: endDate.toISOString().split('T')[0] + ' 00:00:00',
        registered_by: authUser?.id,
      });
      toast.success('Course assigned successfully');
      setAssignCourseOpen(false);
      setSelectedCourseId(null);
      loadStudent();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to assign course');
    } finally {
      setAssigningCourse(false);
    }
  };

  const handleActivate = async () => {
    if (!student) return;
    try {
      await activateStudent(student.id);
      toast.success(`${student.first_name} ${student.last_name} has been activated`);
      loadStudent();
    } catch (err) {
      toast.error('Failed to activate student');
    }
  };

  const handleDeactivate = async () => {
    if (!student) return;
    try {
      await deactivateStudent(student.id);
      toast.success(`${student.first_name} ${student.last_name} has been deactivated`);
      loadStudent();
    } catch (err) {
      toast.error('Failed to deactivate student');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
      </div>
    );
  }

  if (error || !student) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" size="sm" onClick={onBack} className="text-[#64748b]">
          <ArrowLeft className="w-4 h-4 mr-1.5" /> Back to Students
        </Button>
        <Card className="border-red-200 bg-red-50">
          <CardContent className="py-8 text-center">
            <p className="text-red-600">{error || 'Student not found'}</p>
            <Button variant="outline" size="sm" className="mt-4" onClick={loadStudent}>
              Retry
            </Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  const isActive = student.is_active === 1;
  const detail = student.user_details;

  return (
    <div className="space-y-4 animate-fade-in-up">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack} className="text-[#64748b]">
            <ArrowLeft className="w-4 h-4 mr-1.5" /> Back
          </Button>
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-gradient-nytro flex items-center justify-center">
              <span className="text-white text-sm font-semibold">
                {student.first_name?.[0]}{student.last_name?.[0]}
              </span>
            </div>
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">
                {student.first_name} {student.last_name}
              </h2>
              <p className="text-sm text-[#64748b]">{student.email}</p>
            </div>
          </div>
        </div>
        <div className="flex items-center gap-2">
          {isActive ? (
            <Button variant="outline" size="sm" className="text-red-600 border-red-200 hover:bg-red-50" onClick={handleDeactivate}>
              <UserX className="w-4 h-4 mr-1.5" /> Deactivate
            </Button>
          ) : (
            <Button variant="outline" size="sm" className="text-green-600 border-green-200 hover:bg-green-50" onClick={handleActivate}>
              <UserCheck className="w-4 h-4 mr-1.5" /> Activate
            </Button>
          )}
          <Button variant="outline" size="sm" className="text-[#64748b]" onClick={handleArchiveStudent} disabled={archiving || student.is_archived === 1}>
            <Archive className="w-4 h-4 mr-1.5" /> {student.is_archived === 1 ? 'Archived' : 'Archive'}
          </Button>
          <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => onEdit(student.id)}>
            <Edit className="w-4 h-4 mr-1.5" /> Edit
          </Button>
        </div>
      </div>

      {/* Status Banner */}
      {isActive ? (
        <div className="bg-green-50 border border-green-200 rounded-lg px-4 py-2 flex items-center gap-2">
          <UserCheck className="w-4 h-4 text-green-600" />
          <span className="text-sm text-green-700">Student is <strong>Active</strong></span>
        </div>
      ) : (
        <div className="bg-red-50 border border-red-200 rounded-lg px-4 py-2 flex items-center gap-2">
          <UserX className="w-4 h-4 text-red-600" />
          <span className="text-sm text-red-700">Student is <strong>Inactive</strong></span>
        </div>
      )}

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={(v) => {
        setActiveTab(v);
        if (v === 'activities') loadActivities();
        if (v === 'documents') loadDocuments();
        if (v === 'notes') loadNotes();
      }}>
        <TabsList className="bg-white border border-[#e2e8f0] w-full justify-start flex-wrap">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="courses">Courses & Progress</TabsTrigger>
          <TabsTrigger value="onboarding">Onboarding</TabsTrigger>
          <TabsTrigger value="documents">Documents</TabsTrigger>
          <TabsTrigger value="notes">Notes</TabsTrigger>
          <TabsTrigger value="activities">Activities</TabsTrigger>
        </TabsList>

        {/* ── Overview Tab ── */}
        <TabsContent value="overview" className="mt-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {/* Left Column: Student Info */}
            <div className="space-y-4">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base text-[#3b82f6]">Student Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  <InfoRow icon={<User className="w-4 h-4" />} label="Status">
                    <StatusBadge status={isActive ? 'Active' : 'Inactive'} />
                  </InfoRow>
                  {detail?.preferred_name && (
                    <InfoRow icon={<User className="w-4 h-4" />} label="Preferred Name">
                      <span className="font-medium">{detail.preferred_name}</span>
                    </InfoRow>
                  )}
                  <InfoRow icon={<User className="w-4 h-4" />} label="Username">
                    {student.username || '—'}
                  </InfoRow>
                  <InfoRow icon={<Mail className="w-4 h-4" />} label="Email">
                    {student.email}
                  </InfoRow>
                  <InfoRow icon={<Phone className="w-4 h-4" />} label="Phone">
                    {detail?.phone || '—'}
                  </InfoRow>
                  <InfoRow icon={<MapPin className="w-4 h-4" />} label="Address">
                    {detail?.address || '—'}
                  </InfoRow>
                  <InfoRow icon={<Shield className="w-4 h-4" />} label="Role">
                    <StatusBadge status={student.role_name} />
                  </InfoRow>
                  {detail?.purchase_order && (
                    <InfoRow label="Purchase Order">{detail.purchase_order}</InfoRow>
                  )}
                  <InfoRow label="Language">{detail?.language || '—'}</InfoRow>
                  {detail?.preferred_language && (
                    <InfoRow label="Preferred Language">{detail.preferred_language}</InfoRow>
                  )}
                  <InfoRow label="Timezone">{detail?.timezone || '—'}</InfoRow>
                  {student.study_type && (
                    <InfoRow label="Study Type">{student.study_type}</InfoRow>
                  )}
                  <InfoRow icon={<Clock className="w-4 h-4" />} label="Last Sign In">
                    {detail?.last_logged_in
                      ? new Date(detail.last_logged_in).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })
                      : '—'}
                  </InfoRow>
                  <InfoRow icon={<Calendar className="w-4 h-4" />} label="Created">
                    {student.created_at
                      ? new Date(student.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })
                      : '—'}
                  </InfoRow>
                </CardContent>
              </Card>

              {/* More Details Card */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base text-[#3b82f6]">More Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  {student.registered_by_name && (
                    <InfoRow label="Registered By">{student.registered_by_name}</InfoRow>
                  )}
                  <InfoRow icon={<GraduationCap className="w-4 h-4" />} label={student.trainers.length === 1 ? 'Trainer' : 'Trainers'}>
                    {student.trainers.length > 0
                      ? student.trainers.map(t => `${t.first_name} ${t.last_name}`).join(', ')
                      : '—'}
                  </InfoRow>
                  <InfoRow icon={<User className="w-4 h-4" />} label={student.leaders.length === 1 ? 'Leader' : 'Leaders'}>
                    {student.leaders.length > 0
                      ? student.leaders.map(l => (
                          <span key={l.id}>
                            {l.first_name} {l.last_name}
                            {!l.is_active && <span className="text-red-500 text-xs ml-1">(Inactive)</span>}
                          </span>
                        ))
                      : '—'}
                  </InfoRow>
                  <InfoRow icon={<Building2 className="w-4 h-4" />} label="Company/Site">
                    {student.companies.length > 0
                      ? student.companies.map(c => c.name).join(', ')
                      : '—'}
                  </InfoRow>
                </CardContent>
              </Card>
            </div>

            {/* Right Column: Courses Enrolled + Progress */}
            <div className="lg:col-span-2 space-y-4">
              <Card>
                <CardHeader className="pb-3 flex flex-row items-center justify-between">
                  <div className="flex items-center gap-2">
                    <CardTitle className="text-base text-[#3b82f6]">Course(s) Enrolled</CardTitle>
                    <Badge variant="outline" className="text-[#3b82f6] border-[#3b82f6]">
                      {student.enrolments.length} {student.enrolments.length === 1 ? 'course' : 'courses'}
                    </Badge>
                  </div>
                  <Button size="sm" variant="outline" onClick={async () => {
                    setAssignCourseOpen(true);
                    if (availableCourses.length === 0) {
                      try {
                        const courses = await fetchAvailableCourses();
                        setAvailableCourses(courses);
                      } catch { /* silent */ }
                    }
                  }}>
                    <Plus className="w-4 h-4 mr-1" /> Assign Course
                  </Button>
                </CardHeader>
                {/* Assign Course Inline */}
                {assignCourseOpen && (
                  <div className="mx-6 mb-4 p-3 border border-[#3b82f6]/30 rounded-lg bg-[#f8fafc]">
                    <div className="flex items-center gap-2">
                      <select
                        className="flex-1 h-9 rounded-md border border-[#e2e8f0] px-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#3b82f6]"
                        value={selectedCourseId ?? ''}
                        onChange={(e) => setSelectedCourseId(e.target.value ? Number(e.target.value) : null)}
                      >
                        <option value="">Select a course...</option>
                        {availableCourses.map(c => (
                          <option key={c.id} value={c.id}>{c.title}{c.category ? ` (${c.category})` : ''}</option>
                        ))}
                      </select>
                      <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={!selectedCourseId || assigningCourse} onClick={handleAssignCourse}>
                        {assigningCourse ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Assign'}
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => { setAssignCourseOpen(false); setSelectedCourseId(null); }}>
                        <X className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                )}
                <CardContent>
                  {student.enrolments.length === 0 ? (
                    <p className="text-sm text-[#94a3b8]">No courses assigned to this student yet</p>
                  ) : (
                    <div className="space-y-4">
                      {student.enrolments.map((enrolment) => (
                        <div key={enrolment.id} className="border border-[#e2e8f0] rounded-lg p-4">
                          <div className="flex items-start justify-between mb-3">
                            <div>
                              <h4 className="font-medium text-[#1e293b]">{enrolment.course_title}</h4>
                              <StatusBadge status={enrolment.status} />
                            </div>
                            {enrolment.progress_percentage !== null && (
                              <div className="text-right">
                                <span className="text-2xl font-bold text-[#3b82f6]">
                                  {Math.round(enrolment.progress_percentage)}%
                                </span>
                                <p className="text-xs text-[#94a3b8]">Progress</p>
                              </div>
                            )}
                          </div>

                          {/* Progress Bar */}
                          {enrolment.progress_percentage !== null && (
                            <div className="w-full bg-[#e2e8f0] rounded-full h-2 mb-3">
                              <div
                                className="bg-[#3b82f6] h-2 rounded-full transition-all"
                                style={{ width: `${Math.min(100, enrolment.progress_percentage)}%` }}
                              />
                            </div>
                          )}

                          <div className="grid grid-cols-2 gap-2 text-xs text-[#64748b]">
                            <div>
                              <span className="font-medium">Start:</span>{' '}
                              {enrolment.course_start_at
                                ? new Date(enrolment.course_start_at).toLocaleDateString('en-AU')
                                : '—'}
                            </div>
                            <div>
                              <span className="font-medium">End:</span>{' '}
                              {enrolment.course_ends_at
                                ? new Date(enrolment.course_ends_at).toLocaleDateString('en-AU')
                                : '—'}
                            </div>
                            {enrolment.course_expiry && (
                              <div>
                                <span className="font-medium">Expiry:</span>{' '}
                                {new Date(enrolment.course_expiry).toLocaleDateString('en-AU')}
                              </div>
                            )}
                            {enrolment.is_locked === 1 && (
                              <div className="text-red-600 font-medium">Locked Enrollment</div>
                            )}
                            {enrolment.deferred === 1 && (
                              <div className="text-amber-600 font-medium">Deferred</div>
                            )}
                            {enrolment.is_chargeable === 1 && (
                              <div className="text-[#64748b]">Generate Invoice: Yes</div>
                            )}
                            {enrolment.cert_issued === 1 && (
                              <div className="text-green-600 font-medium">
                                Certificate Issued
                                {enrolment.cert_issued_on && ` (${new Date(enrolment.cert_issued_on).toLocaleDateString('en-AU')})`}
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* ── Courses & Progress Tab — Full Training Plan ── */}
        <TabsContent value="courses" className="mt-4">
          <StudentTrainingPlan
            studentId={student.id}
            studentName={`${student.first_name} ${student.last_name}`}
          />
        </TabsContent>

        {/* ── Onboarding Tab ── */}
        <TabsContent value="onboarding" className="mt-4">
          <OnboardingWizard
            studentId={student.id}
            studentName={`${student.first_name} ${student.last_name}`}
            onComplete={() => { loadStudent(); setActiveTab('overview'); }}
          />
        </TabsContent>

        {/* ── Documents Tab ── */}
        <TabsContent value="documents" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="text-base text-[#3b82f6]">
                <FileText className="w-4 h-4 inline mr-2" />
                Student Documents
              </CardTitle>
              <label className="cursor-pointer">
                <input
                  type="file"
                  className="hidden"
                  accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleUploadDocument(file);
                    e.target.value = '';
                  }}
                  disabled={uploading}
                />
                <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={uploading} asChild>
                  <span>
                    {uploading ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Upload className="w-4 h-4 mr-1.5" />}
                    Upload Document
                  </span>
                </Button>
              </label>
            </CardHeader>
            <CardContent>
              {documentsLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                </div>
              ) : documents.length === 0 ? (
                <div className="text-center py-8">
                  <FileText className="w-10 h-10 text-[#94a3b8] mx-auto mb-3" />
                  <p className="text-sm text-[#94a3b8]">No documents uploaded yet</p>
                  <p className="text-xs text-[#94a3b8] mt-1">Upload PDF, Word, Excel, images, or text files (max 10 MB)</p>
                </div>
              ) : (
                <div className="space-y-2">
                  {documents.map((doc) => (
                    <div key={doc.id} className="flex items-center justify-between py-3 px-3 border border-[#e2e8f0] rounded-lg hover:border-[#3b82f6]/30 transition-colors">
                      <div className="flex items-center gap-3 min-w-0">
                        <div className="w-10 h-10 rounded-lg bg-[#eff6ff] flex items-center justify-center flex-shrink-0">
                          <FileText className="w-5 h-5 text-[#3b82f6]" />
                        </div>
                        <div className="min-w-0">
                          <p className="text-sm font-medium text-[#1e293b] truncate">{doc.file_name}</p>
                          <p className="text-xs text-[#94a3b8]">
                            {doc.file_size < 1024
                              ? `${doc.file_size} B`
                              : doc.file_size < 1048576
                              ? `${(doc.file_size / 1024).toFixed(1)} KB`
                              : `${(doc.file_size / 1048576).toFixed(1)} MB`}
                            {doc.created_at && ` · ${new Date(doc.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })}`}
                          </p>
                        </div>
                      </div>
                      <div className="flex items-center gap-1 flex-shrink-0">
                        <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6] h-8 w-8 p-0" onClick={() => handleDownloadDocument(doc)} title="Download">
                          <Download className="w-4 h-4" />
                        </Button>
                        <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#ef4444] h-8 w-8 p-0" onClick={() => handleDeleteDocument(doc)} title="Delete">
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Notes Tab ── */}
        <TabsContent value="notes" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">
                <StickyNote className="w-4 h-4 inline mr-2" />
                Notes
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Add Note Form */}
              <div className="flex gap-2">
                <textarea
                  className="flex-1 min-h-[60px] rounded-md border border-[#e2e8f0] px-3 py-2 text-sm placeholder:text-[#94a3b8] focus:outline-none focus:ring-2 focus:ring-[#3b82f6] focus:border-transparent resize-none"
                  placeholder="Add a note about this student..."
                  value={newNote}
                  onChange={(e) => setNewNote(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) handleAddNote();
                  }}
                />
                <Button
                  size="sm"
                  className="bg-[#3b82f6] hover:bg-[#2563eb] text-white self-end"
                  disabled={!newNote.trim()}
                  onClick={handleAddNote}
                >
                  <Send className="w-4 h-4" />
                </Button>
              </div>

              {notesLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                </div>
              ) : notes.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-6">No notes yet. Add one above.</p>
              ) : (
                <div className="space-y-3">
                  {notes.map((note) => (
                    <div
                      key={note.id}
                      className={`border rounded-lg p-4 ${note.is_pinned ? 'border-amber-300 bg-amber-50/50' : 'border-[#e2e8f0]'}`}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex-1 min-w-0">
                          {note.is_pinned === 1 && (
                            <span className="text-xs text-amber-600 font-medium flex items-center gap-1 mb-1">
                              <Pin className="w-3 h-3" /> Pinned
                            </span>
                          )}
                          {editingNoteId === note.id ? (
                            <div className="space-y-2">
                              <textarea
                                className="w-full min-h-[60px] rounded-md border border-[#e2e8f0] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b82f6] resize-none"
                                value={editingNoteText}
                                onChange={(e) => setEditingNoteText(e.target.value)}
                                autoFocus
                              />
                              <div className="flex gap-1">
                                <Button size="sm" className="h-7 bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => handleEditNote(note.id)}>
                                  <Check className="w-3.5 h-3.5 mr-1" /> Save
                                </Button>
                                <Button size="sm" variant="ghost" className="h-7" onClick={() => { setEditingNoteId(null); setEditingNoteText(''); }}>
                                  <X className="w-3.5 h-3.5 mr-1" /> Cancel
                                </Button>
                              </div>
                            </div>
                          ) : (
                            <p className="text-sm text-[#1e293b] whitespace-pre-wrap">{note.note_body}</p>
                          )}
                          <p className="text-xs text-[#94a3b8] mt-2">
                            {note.author_name}
                            {note.created_at && ` · ${new Date(note.created_at).toLocaleString('en-AU', {
                              day: 'numeric', month: 'short', year: 'numeric',
                              hour: '2-digit', minute: '2-digit',
                            })}`}
                          </p>
                        </div>
                        <div className="flex items-center gap-1 flex-shrink-0">
                          <Button
                            variant="ghost" size="sm"
                            className="text-[#94a3b8] hover:text-[#3b82f6] h-7 w-7 p-0"
                            onClick={() => { setEditingNoteId(note.id); setEditingNoteText(note.note_body); }}
                            title="Edit"
                          >
                            <Pencil className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            variant="ghost" size="sm"
                            className={`h-7 w-7 p-0 ${note.is_pinned ? 'text-amber-500' : 'text-[#94a3b8] hover:text-amber-500'}`}
                            onClick={() => handleTogglePin(note.id, note.is_pinned === 1)}
                            title={note.is_pinned ? 'Unpin' : 'Pin'}
                          >
                            <Pin className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            variant="ghost" size="sm"
                            className="text-[#94a3b8] hover:text-[#ef4444] h-7 w-7 p-0"
                            onClick={() => handleDeleteNote(note.id)}
                            title="Delete"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Activities Tab ── */}
        <TabsContent value="activities" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">
                <Activity className="w-4 h-4 inline mr-2" />
                Activity Log
              </CardTitle>
            </CardHeader>
            <CardContent>
              {activitiesLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                </div>
              ) : activities.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-8">No activities recorded</p>
              ) : (
                <div className="space-y-2">
                  {activities.map((activity) => (
                    <div key={activity.id} className="flex items-start gap-3 py-2 border-b border-[#f1f5f9] last:border-0">
                      <div className="w-8 h-8 rounded-full bg-[#eff6ff] flex items-center justify-center flex-shrink-0 mt-0.5">
                        <Activity className="w-3.5 h-3.5 text-[#3b82f6]" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm text-[#1e293b]">
                          <span className="font-medium">{activity.event || activity.log_name || 'Activity'}</span>
                          {' — '}
                          {activity.description}
                        </p>
                        <p className="text-xs text-[#94a3b8] mt-0.5">
                          {activity.created_at
                            ? new Date(activity.created_at).toLocaleString('en-AU', {
                                day: 'numeric', month: 'short', year: 'numeric',
                                hour: '2-digit', minute: '2-digit',
                              })
                            : '—'}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function InfoRow({ icon, label, children }: { icon?: React.ReactNode; label: string; children: React.ReactNode }) {
  return (
    <div className="flex items-start gap-2">
      {icon && <span className="text-[#94a3b8] mt-0.5 flex-shrink-0">{icon}</span>}
      <div className="flex-1 min-w-0">
        <span className="text-[#94a3b8] text-xs block">{label}</span>
        <span className="text-[#1e293b]">{children}</span>
      </div>
    </div>
  );
}
