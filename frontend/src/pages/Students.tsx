/**
 * Students Page — Student management with real Supabase data.
 * NytroAI design: clean white cards, blue accents, sortable table.
 */
import { useState, useMemo, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { DataTable } from '../components/DataTable';
import { StatusBadge } from '../components/StatusBadge';
import { StudentDetail } from '../components/StudentDetail';
import { AddStudentDialog } from '../components/AddStudentDialog';
import { EditStudentDialog } from '../components/EditStudentDialog';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchStudents, bulkActivateStudents, bulkDeactivateStudents, fetchAllCompanies, fetchStudentIdsByCompany, type UserWithDetails } from '@/lib/api';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Download, Eye, UserCheck, UserX, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { exportToCSV } from '@/lib/utils';

export default function Students() {
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState<'active' | 'inactive' | 'archived'>('active');
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  const [editStudentId, setEditStudentId] = useState<number | null>(null);
  const [bulkLoading, setBulkLoading] = useState(false);
  const [companyFilter, setCompanyFilter] = useState('all');
  const [companies, setCompanies] = useState<{ id: number; name: string }[]>([]);
  const [companyStudentIds, setCompanyStudentIds] = useState<Set<number> | null>(null);

  useEffect(() => {
    fetchAllCompanies().then(c => setCompanies(c ?? []));
  }, []);

  useEffect(() => {
    if (companyFilter === 'all') {
      setCompanyStudentIds(null);
    } else {
      fetchStudentIdsByCompany(parseInt(companyFilter, 10)).then((ids: number[]) => setCompanyStudentIds(new Set(ids)));
    }
  }, [companyFilter]);

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchStudents({ search, status: statusFilter, limit: 200 }),
    [search, statusFilter]
  );

  const filteredData = useMemo(() => {
    if (!data?.data) return [];
    let result = data.data;
    if (roleFilter !== 'all') {
      result = result.filter(u => u.role_name === roleFilter);
    }
    if (companyStudentIds !== null) {
      result = result.filter(u => companyStudentIds.has(u.id));
    }
    return result;
  }, [data, roleFilter, companyStudentIds]);

  // If a student is selected, show the detail view
  if (selectedStudentId !== null) {
    return (
      <DashboardLayout title="Students" subtitle="Manage student records and enrolments">
        <StudentDetail
          studentId={selectedStudentId}
          onBack={() => setSelectedStudentId(null)}
          onEdit={(id) => setEditStudentId(id)}
        />
        {editStudentId !== null && (
          <EditStudentDialog
            open={true}
            onOpenChange={(open) => { if (!open) setEditStudentId(null); }}
            studentId={editStudentId}
            onSaved={() => {
              setEditStudentId(null);
              // Force re-mount of StudentDetail to refresh data
              const id = selectedStudentId;
              setSelectedStudentId(null);
              setTimeout(() => setSelectedStudentId(id), 50);
            }}
          />
        )}
      </DashboardLayout>
    );
  }

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
              <Select value={companyFilter} onValueChange={setCompanyFilter}>
                <SelectTrigger className="w-[160px] h-9 border-slate-200 bg-white/60">
                  <SelectValue placeholder="Company" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Companies</SelectItem>
                  {companies.map(c => (
                    <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          }
          headerActions={
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" className="border-slate-200 text-slate-600" onClick={() => {
                if (!filteredData || filteredData.length === 0) {
                  toast.error('No data to export');
                  return;
                }
                exportToCSV(
                  filteredData.map(s => ({
                    id: s.id,
                    first_name: s.first_name,
                    last_name: s.last_name,
                    email: s.email,
                    role: s.role_name,
                    status: s.user_details?.status ?? (s.is_active ? 'Active' : 'Inactive'),
                    phone: s.user_details?.phone ?? '',
                    address: s.user_details?.address ?? '',
                    created_at: s.created_at,
                  })),
                  `students-${new Date().toISOString().split('T')[0]}.csv`,
                  [
                    { key: 'id', label: 'ID' },
                    { key: 'first_name', label: 'First Name' },
                    { key: 'last_name', label: 'Last Name' },
                    { key: 'email', label: 'Email' },
                    { key: 'role', label: 'Role' },
                    { key: 'status', label: 'Status' },
                    { key: 'phone', label: 'Phone' },
                    { key: 'address', label: 'Address' },
                    { key: 'created_at', label: 'Created At' },
                  ]
                );
                toast.success('Students exported to CSV');
              }}>
                <Download className="w-4 h-4 mr-1.5" /> Export
              </Button>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddDialogOpen(true)}>
                <Plus className="w-4 h-4 mr-1.5" /> Add Student
              </Button>
            </div>
          }
          selectable
          bulkActions={(selectedIds, clearSelection) => (
            <>
              <Button
                size="sm"
                variant="outline"
                className="h-7 text-xs border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                disabled={bulkLoading}
                onClick={async () => {
                  setBulkLoading(true);
                  try {
                    const result = await bulkActivateStudents(selectedIds as number[]);
                    toast.success(`${result.succeeded} student(s) activated${result.failed ? `, ${result.failed} failed` : ''}`);
                    clearSelection();
                    refetch();
                  } catch { toast.error('Bulk activate failed'); }
                  finally { setBulkLoading(false); }
                }}
              >
                {bulkLoading ? <Loader2 className="w-3 h-3 mr-1 animate-spin" /> : <UserCheck className="w-3 h-3 mr-1" />}
                Activate
              </Button>
              <Button
                size="sm"
                variant="outline"
                className="h-7 text-xs border-red-200 text-red-700 hover:bg-red-50"
                disabled={bulkLoading}
                onClick={async () => {
                  if (!confirm(`Deactivate ${selectedIds.length} student(s)?`)) return;
                  setBulkLoading(true);
                  try {
                    const result = await bulkDeactivateStudents(selectedIds as number[]);
                    toast.success(`${result.succeeded} student(s) deactivated${result.failed ? `, ${result.failed} failed` : ''}`);
                    clearSelection();
                    refetch();
                  } catch { toast.error('Bulk deactivate failed'); }
                  finally { setBulkLoading(false); }
                }}
              >
                {bulkLoading ? <Loader2 className="w-3 h-3 mr-1 animate-spin" /> : <UserX className="w-3 h-3 mr-1" />}
                Deactivate
              </Button>
            </>
          )}
          actions={(row: UserWithDetails) => (
            <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => setSelectedStudentId(row.id)}>
              <Eye className="w-4 h-4" />
            </Button>
          )}
        />
      </div>

      <AddStudentDialog
        open={addDialogOpen}
        onOpenChange={setAddDialogOpen}
        onCreated={(id) => {
          refetch();
          setSelectedStudentId(id);
        }}
      />

      {editStudentId !== null && (
        <EditStudentDialog
          open={true}
          onOpenChange={(open) => { if (!open) setEditStudentId(null); }}
          studentId={editStudentId}
          onSaved={() => {
            setEditStudentId(null);
            refetch();
            // Re-open detail view to see changes
            if (selectedStudentId === editStudentId) {
              setSelectedStudentId(null);
              setTimeout(() => setSelectedStudentId(editStudentId), 50);
            }
          }}
        />
      )}
    </DashboardLayout>
  );
}
