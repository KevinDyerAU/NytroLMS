/**
 * Enrolments Page — Manage student course enrolments
 * Connected to Supabase: student_course_enrolments, users, courses
 */
import React, { useState, useMemo, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { EnrolmentDetailDialog } from '../components/EnrolmentDetailDialog';
import { NewEnrolmentDialog } from '../components/NewEnrolmentDialog';
import { BatchEnrolmentDialog } from '../components/BatchEnrolmentDialog';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Search, Plus, Download, Eye, Loader2, AlertCircle, Users } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { exportToCSV } from '@/lib/utils';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchEnrolments, fetchAvailableCourses, EnrolmentWithDetails } from '@/lib/api';

const statusColors: Record<string, string> = {
  ACTIVE: 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20',
  COMPLETED: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20',
  WITHDRAWN: 'bg-[#fef2f2] text-[#ef4444] border-[#ef4444]/20',
  DEFERRED: 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20',
  CANCELLED: 'bg-[#f1f5f9] text-[#64748b] border-[#64748b]/20',
};

export default function Enrolments() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [courseFilter, setCourseFilter] = useState('all');
  const [courses, setCourses] = useState<{ id: number; title: string }[]>([]);
  const [page, setPage] = useState(0);
  const [selectedEnrolmentId, setSelectedEnrolmentId] = useState<number | null>(null);
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  const [batchDialogOpen, setBatchDialogOpen] = useState(false);
  const limit = 25;

  useEffect(() => {
    fetchAvailableCourses().then(c => setCourses((c ?? []).map(x => ({ id: x.id, title: x.title }))));
  }, []);

  const courseId = courseFilter !== 'all' ? parseInt(courseFilter, 10) : undefined;

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchEnrolments({ search, status: statusFilter, courseId, limit, offset: page * limit }),
    [search, statusFilter, courseId, page]
  );

  const enrolments = data?.data ?? [];
  const total = data?.total ?? 0;

  return (
    <DashboardLayout title="Enrolments" subtitle="Manage student course enrolments">
      <div className="space-y-4 animate-fade-in-up">
        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="flex items-center gap-3 w-full sm:w-auto">
            <div className="relative w-full sm:w-72">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
              <Input
                placeholder="Search student or course..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(0); }}
                className="pl-9 border-[#e2e8f0] h-9"
              />
            </div>
            <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v); setPage(0); }}>
              <SelectTrigger className="w-36 h-9 border-[#e2e8f0]">
                <SelectValue placeholder="All Statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="ACTIVE">Active</SelectItem>
                <SelectItem value="COMPLETED">Completed</SelectItem>
                <SelectItem value="WITHDRAWN">Withdrawn</SelectItem>
                <SelectItem value="DEFERRED">Deferred</SelectItem>
                <SelectItem value="CANCELLED">Cancelled</SelectItem>
              </SelectContent>
            </Select>
            <Select value={courseFilter} onValueChange={(v) => { setCourseFilter(v); setPage(0); }}>
              <SelectTrigger className="w-44 h-9 border-[#e2e8f0]">
                <SelectValue placeholder="All Courses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Courses</SelectItem>
                {courses.map(c => (
                  <SelectItem key={c.id} value={String(c.id)}>{c.title}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={() => {
              const items = data?.data ?? [];
              if (items.length === 0) {
                toast.error('No data to export');
                return;
              }
              exportToCSV(
                items.map(e => ({
                  id: e.id,
                  student_name: e.student_name,
                  student_email: e.student_email,
                  course_title: e.course_title,
                  status: e.status,
                  course_start_at: e.course_start_at,
                  course_ends_at: e.course_ends_at,
                  created_at: e.created_at,
                })),
                `enrolments-${new Date().toISOString().split('T')[0]}.csv`,
                [
                  { key: 'id', label: 'ID' },
                  { key: 'student_name', label: 'Student Name' },
                  { key: 'student_email', label: 'Student Email' },
                  { key: 'course_title', label: 'Course' },
                  { key: 'status', label: 'Status' },
                  { key: 'course_start_at', label: 'Start Date' },
                  { key: 'course_ends_at', label: 'End Date' },
                  { key: 'created_at', label: 'Enrolled At' },
                ]
              );
              toast.success('Enrolments exported to CSV');
            }}>
              <Download className="w-4 h-4 mr-1.5" /> Export
            </Button>
            <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={() => setBatchDialogOpen(true)}>
              <Users className="w-4 h-4 mr-1.5" /> Batch Enrol
            </Button>
            <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddDialogOpen(true)}>
              <Plus className="w-4 h-4 mr-1.5" /> New Enrolment
            </Button>
          </div>
        </div>

        {/* Error State */}
        {error && (
          <Card className="p-6 border-red-200 bg-red-50">
            <div className="flex items-center gap-3 text-red-700">
              <AlertCircle className="w-5 h-5" />
              <p className="text-sm">{error}</p>
              <Button variant="outline" size="sm" onClick={refetch}>Retry</Button>
            </div>
          </Card>
        )}

        {/* Loading State */}
        {loading && (
          <Card className="p-12 flex items-center justify-center border-[#3b82f6]/20 shadow-card">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
            <span className="ml-3 text-sm text-[#64748b]">Loading enrolments...</span>
          </Card>
        )}

        {/* Data Table */}
        {!loading && !error && (
          <Card className="overflow-hidden border-[#3b82f6]/20 shadow-card">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Student</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Course</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Start Date</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">End Date</th>
                    <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                    <th className="text-right px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#f1f5f9]">
                  {enrolments.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="px-4 py-12 text-center text-sm text-[#94a3b8]">
                        No enrolments found.
                      </td>
                    </tr>
                  ) : (
                    enrolments.map((enrolment) => (
                      <tr key={enrolment.id} className="hover:bg-[#f8fafc] transition-colors">
                        <td className="px-4 py-3">
                          <p className="text-sm font-medium text-[#1e293b]">{enrolment.student_name}</p>
                          <p className="text-xs text-[#94a3b8]">{enrolment.student_email}</p>
                        </td>
                        <td className="px-4 py-3 hidden md:table-cell text-sm text-[#64748b]">{enrolment.course_title}</td>
                        <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">
                          {enrolment.course_start_at ? new Date(enrolment.course_start_at).toLocaleDateString('en-AU') : '—'}
                        </td>
                        <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">
                          {enrolment.course_ends_at ? new Date(enrolment.course_ends_at).toLocaleDateString('en-AU') : '—'}
                        </td>
                        <td className="px-4 py-3">
                          <Badge variant="outline" className={cn("text-xs capitalize font-medium", statusColors[enrolment.status] ?? statusColors.ACTIVE)}>
                            {enrolment.status?.toLowerCase() ?? 'unknown'}
                          </Badge>
                        </td>
                        <td className="px-4 py-3 text-right">
                          <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => setSelectedEnrolmentId(enrolment.id)}>
                            <Eye className="w-4 h-4" />
                          </Button>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            {/* Pagination */}
            {total > limit && (
              <div className="flex items-center justify-between px-4 py-3 border-t border-[#e2e8f0] bg-[#f8fafc]">
                <p className="text-xs text-[#94a3b8]">
                  Showing {page * limit + 1}–{Math.min((page + 1) * limit, total)} of {total}
                </p>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" disabled={page === 0} onClick={() => setPage(p => p - 1)}>Previous</Button>
                  <Button variant="outline" size="sm" disabled={(page + 1) * limit >= total} onClick={() => setPage(p => p + 1)}>Next</Button>
                </div>
              </div>
            )}
          </Card>
        )}
      </div>

      {selectedEnrolmentId !== null && (
        <EnrolmentDetailDialog
          open={true}
          onOpenChange={(open) => { if (!open) setSelectedEnrolmentId(null); }}
          enrolmentId={selectedEnrolmentId}
        />
      )}

      <NewEnrolmentDialog
        open={addDialogOpen}
        onOpenChange={setAddDialogOpen}
        onSaved={() => { refetch(); }}
      />

      <BatchEnrolmentDialog
        open={batchDialogOpen}
        onOpenChange={setBatchDialogOpen}
        onSaved={() => { refetch(); }}
      />
    </DashboardLayout>
  );
}
