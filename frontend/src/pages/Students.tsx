/**
 * Students Page — Student management with real Supabase data.
 * NytroAI design: clean white cards, blue accents, sortable table.
 */
import { useState, useMemo } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { DataTable } from '../components/DataTable';
import { StatusBadge } from '../components/StatusBadge';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchStudents, type UserWithDetails } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Download, Eye } from 'lucide-react';
import { toast } from 'sonner';

export default function Students() {
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState<'active' | 'inactive' | 'archived'>('active');

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchStudents({ search, status: statusFilter, limit: 200 }),
    [search, statusFilter]
  );

  const filteredData = useMemo(() => {
    if (!data?.data) return [];
    if (roleFilter === 'all') return data.data;
    return data.data.filter(u => u.role_name === roleFilter);
  }, [data, roleFilter]);

  const columns = [
    {
      key: 'name',
      label: 'Student',
      render: (row: UserWithDetails) => (
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-gradient-nytro flex items-center justify-center flex-shrink-0">
            <span className="text-white text-xs font-semibold">
              {row.first_name?.[0]}{row.last_name?.[0]}
            </span>
          </div>
          <div>
            <p className="text-sm font-medium text-[#1e293b]">{row.first_name} {row.last_name}</p>
            <p className="text-xs text-[#94a3b8]">{row.email}</p>
          </div>
        </div>
      ),
    },
    {
      key: 'role_name',
      label: 'Role',
      render: (row: UserWithDetails) => (
        <StatusBadge status={row.role_name} />
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row: UserWithDetails) => (
        <StatusBadge status={row.user_details?.status ?? (row.is_active ? 'Active' : 'Inactive')} />
      ),
    },
    {
      key: 'phone',
      label: 'Phone',
      className: 'hidden lg:table-cell',
      render: (row: UserWithDetails) => (
        <span className="text-sm text-[#64748b]">{row.user_details?.phone ?? '—'}</span>
      ),
    },
    {
      key: 'last_logged_in',
      label: 'Last Login',
      className: 'hidden xl:table-cell',
      render: (row: UserWithDetails) => (
        <span className="text-sm text-[#64748b]">
          {row.user_details?.last_logged_in
            ? new Date(row.user_details.last_logged_in).toLocaleDateString('en-AU')
            : '—'}
        </span>
      ),
    },
    {
      key: 'created_at',
      label: 'Joined',
      className: 'hidden lg:table-cell',
      render: (row: UserWithDetails) => (
        <span className="text-sm text-[#64748b]">
          {row.created_at ? new Date(row.created_at).toLocaleDateString('en-AU') : '—'}
        </span>
      ),
    },
  ];

  return (
    <DashboardLayout title="Students" subtitle="Manage student records and enrolments">
      <div className="space-y-4 animate-fade-in-up">
        <DataTable
          columns={columns}
          data={filteredData}
          total={data?.total}
          loading={loading}
          error={error}
          searchPlaceholder="Search students by name or email..."
          onSearch={setSearch}
          onRefetch={refetch}
          emptyMessage="No students found"
          filterSlot={
            <div className="flex items-center gap-2">
              <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v as 'active' | 'inactive' | 'archived')}>
                <SelectTrigger className="w-[130px] h-9 border-slate-200 bg-white/60">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="archived">Archived</SelectItem>
                </SelectContent>
              </Select>
              <Select value={roleFilter} onValueChange={setRoleFilter}>
                <SelectTrigger className="w-[130px] h-9 border-slate-200 bg-white/60">
                  <SelectValue placeholder="Role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Roles</SelectItem>
                  <SelectItem value="Student">Student</SelectItem>
                  <SelectItem value="Leader">Leader</SelectItem>
                  <SelectItem value="Trainer">Trainer</SelectItem>
                  <SelectItem value="Admin">Admin</SelectItem>
                  <SelectItem value="Root">Root</SelectItem>
                </SelectContent>
              </Select>
            </div>
          }
          headerActions={
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" className="border-slate-200 text-slate-600" onClick={() => toast('Export coming soon')}>
                <Download className="w-4 h-4 mr-1.5" /> Export
              </Button>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add student coming soon')}>
                <Plus className="w-4 h-4 mr-1.5" /> Add Student
              </Button>
            </div>
          }
          actions={(row: UserWithDetails) => (
            <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => toast(`Viewing ${row.first_name} ${row.last_name}`)}>
              <Eye className="w-4 h-4" />
            </Button>
          )}
        />
      </div>
    </DashboardLayout>
  );
}
