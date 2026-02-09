/**
 * Assessments Page — Quiz attempts and evaluations from Supabase.
 * NytroAI design: clean data table with status badges and filters.
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { DataTable } from '../components/DataTable';
import { StatusBadge } from '../components/StatusBadge';
import { AssessmentDetailDialog } from '../components/AssessmentDetailDialog';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchAssessments, type AssessmentSummary } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, Eye } from 'lucide-react';
import { toast } from 'sonner';
import { exportToCSV } from '@/lib/utils';

export default function Assessments() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedAttemptId, setSelectedAttemptId] = useState<number | null>(null);

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchAssessments({ search, status: statusFilter, limit: 100 }),
    [search, statusFilter]
  );

  const columns = [
    {
      key: 'student_name',
      label: 'Student',
      render: (row: AssessmentSummary) => (
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-gradient-to-br from-[#14b8a6] to-[#3b82f6] flex items-center justify-center flex-shrink-0">
            <span className="text-white text-xs font-semibold">
              {(row.student_name ?? '').split(' ').map(n => n[0]).join('').slice(0, 2)}
            </span>
          </div>
          <div>
            <p className="text-sm font-medium text-[#1e293b]">{row.student_name}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'course_title',
      label: 'Course',
      className: 'hidden md:table-cell',
      render: (row: AssessmentSummary) => (
        <span className="text-sm text-[#64748b] line-clamp-1">{row.course_title}</span>
      ),
    },
    {
      key: 'type',
      label: 'Type',
      className: 'hidden lg:table-cell',
      render: (row: AssessmentSummary) => (
        <span className="text-sm text-[#64748b] capitalize">{row.type.replace('_', ' ')}</span>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row: AssessmentSummary) => <StatusBadge status={row.status} />,
    },
    {
      key: 'created_at',
      label: 'Submitted',
      className: 'hidden lg:table-cell',
      render: (row: AssessmentSummary) => (
        <span className="text-sm text-[#64748b]">
          {row.created_at ? new Date(row.created_at).toLocaleDateString('en-AU') : '—'}
        </span>
      ),
    },
  ];

  return (
    <DashboardLayout title="Assessments" subtitle="Review and manage student assessments">
      <div className="space-y-4 animate-fade-in-up">
        <DataTable
          columns={columns}
          data={data?.data ?? []}
          total={data?.total}
          loading={loading}
          error={error}
          searchPlaceholder="Search assessments..."
          onSearch={setSearch}
          onRefetch={refetch}
          emptyMessage="No assessments found"
          filterSlot={
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px] h-9 border-slate-200 bg-white/60">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="SUBMITTED">Submitted</SelectItem>
                <SelectItem value="PASSED">Passed</SelectItem>
                <SelectItem value="FAILED">Failed</SelectItem>
                <SelectItem value="IN_PROGRESS">In Progress</SelectItem>
              </SelectContent>
            </Select>
          }
          headerActions={
            <Button variant="outline" size="sm" className="border-slate-200 text-slate-600" onClick={() => {
              const items = data?.data ?? [];
              if (items.length === 0) {
                toast.error('No data to export');
                return;
              }
              exportToCSV(
                items.map(a => ({
                  id: a.id,
                  student_name: a.student_name,
                  course_title: a.course_title,
                  type: a.type,
                  status: a.status,
                  created_at: a.created_at,
                })),
                `assessments-${new Date().toISOString().split('T')[0]}.csv`,
                [
                  { key: 'id', label: 'ID' },
                  { key: 'student_name', label: 'Student' },
                  { key: 'course_title', label: 'Course' },
                  { key: 'type', label: 'Type' },
                  { key: 'status', label: 'Status' },
                  { key: 'created_at', label: 'Submitted At' },
                ]
              );
              toast.success('Assessments exported to CSV');
            }}>
              <Download className="w-4 h-4 mr-1.5" /> Export
            </Button>
          }
          actions={(row: AssessmentSummary) => (
            <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => setSelectedAttemptId(row.id)}>
              <Eye className="w-4 h-4" />
            </Button>
          )}
        />
      </div>

      {selectedAttemptId !== null && (
        <AssessmentDetailDialog
          open={true}
          onOpenChange={(open) => { if (!open) setSelectedAttemptId(null); }}
          attemptId={selectedAttemptId}
        />
      )}
    </DashboardLayout>
  );
}
