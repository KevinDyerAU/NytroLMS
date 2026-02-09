/**
 * Assessments Page — Quiz attempts and evaluations from Supabase.
 * NytroAI design: clean data table with status badges and filters.
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { DataTable } from '../components/DataTable';
import { StatusBadge } from '../components/StatusBadge';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchAssessments, type AssessmentSummary } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download, Eye } from 'lucide-react';
import { toast } from 'sonner';

export default function Assessments() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

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
            <Button variant="outline" size="sm" className="border-slate-200 text-slate-600" onClick={() => toast('Export coming soon')}>
              <Download className="w-4 h-4 mr-1.5" /> Export
            </Button>
          }
          actions={(row: AssessmentSummary) => (
            <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => toast(`Viewing assessment #${row.id}`)}>
              <Eye className="w-4 h-4" />
            </Button>
          )}
        />
      </div>
    </DashboardLayout>
  );
}
