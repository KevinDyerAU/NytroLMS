/**
 * User Management Page — Manage users, roles, and permissions
 * Connected to Supabase: users, model_has_roles, roles, permissions
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Search, Plus, UserCog, Shield, Edit, Trash2, Loader2, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchStudents, fetchRoles, fetchUserRoleDistribution, UserWithDetails } from '@/lib/api';

const roleColors: Record<string, string> = {
  Root: 'bg-[#fef2f2] text-[#ef4444] border-[#ef4444]/20',
  Admin: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20',
  'Mini Admin': 'bg-[#f5f3ff] text-[#8b5cf6] border-[#8b5cf6]/20',
  Leader: 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20',
  Trainer: 'bg-[#f0fdfa] text-[#14b8a6] border-[#14b8a6]/20',
  Student: 'bg-[#f1f5f9] text-[#64748b] border-[#64748b]/20',
  Moderator: 'bg-[#fff7ed] text-[#f97316] border-[#f97316]/20',
};

export default function UserManagement() {
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState<'active' | 'inactive' | 'archived'>('active');
  const [page, setPage] = useState(0);
  const limit = 25;

  const { data: usersData, loading: usersLoading, error: usersError, refetch: refetchUsers } = useSupabaseQuery(
    () => fetchStudents({ search, role: roleFilter, status: statusFilter, limit, offset: page * limit }),
    [search, roleFilter, statusFilter, page]
  );

  const { data: rolesData, loading: rolesLoading } = useSupabaseQuery(
    () => fetchRoles(),
    []
  );

  const { data: roleDistribution, loading: distLoading } = useSupabaseQuery(
    () => fetchUserRoleDistribution(),
    []
  );

  const users = usersData?.data ?? [];
  const total = usersData?.total ?? 0;
  const roles = rolesData ?? [];
  const distribution = roleDistribution ?? [];

  return (
    <DashboardLayout title="User Management" subtitle="Manage users, roles, and permissions">
      <div className="space-y-4 animate-fade-in-up">
        <Tabs defaultValue="users">
          <TabsList className="bg-[#f1f5f9] p-1 border border-[#e2e8f0]">
            <TabsTrigger value="users" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">
              <UserCog className="w-3.5 h-3.5 mr-1.5" /> Users
            </TabsTrigger>
            <TabsTrigger value="roles" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">
              <Shield className="w-3.5 h-3.5 mr-1.5" /> Roles & Permissions
            </TabsTrigger>
          </TabsList>

          {/* Users Tab */}
          <TabsContent value="users" className="mt-4 space-y-4">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div className="flex items-center gap-3 w-full sm:w-auto flex-wrap">
                <div className="relative w-full sm:w-64">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
                  <Input
                    placeholder="Search users..."
                    value={search}
                    onChange={(e) => { setSearch(e.target.value); setPage(0); }}
                    className="pl-9 border-[#e2e8f0] h-9"
                  />
                </div>
                <Select value={roleFilter} onValueChange={(v) => { setRoleFilter(v); setPage(0); }}>
                  <SelectTrigger className="w-32 h-9 border-[#e2e8f0]">
                    <SelectValue placeholder="All Roles" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Roles</SelectItem>
                    {roles.map(r => (
                      <SelectItem key={r.id} value={r.name}>{r.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Select value={statusFilter} onValueChange={(v) => { setStatusFilter(v as 'active' | 'inactive' | 'archived'); setPage(0); }}>
                  <SelectTrigger className="w-32 h-9 border-[#e2e8f0]">
                    <SelectValue placeholder="Status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                    <SelectItem value="archived">Archived</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add user coming soon')}>
                <Plus className="w-4 h-4 mr-1.5" /> Add User
              </Button>
            </div>

            {usersError && (
              <Card className="p-6 border-red-200 bg-red-50">
                <div className="flex items-center gap-3 text-red-700">
                  <AlertCircle className="w-5 h-5" />
                  <p className="text-sm">{usersError}</p>
                  <Button variant="outline" size="sm" onClick={refetchUsers}>Retry</Button>
                </div>
              </Card>
            )}

            {usersLoading ? (
              <Card className="p-12 flex items-center justify-center border-[#e2e8f0]/50 shadow-card">
                <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                <span className="ml-3 text-sm text-[#64748b]">Loading users...</span>
              </Card>
            ) : (
              <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">User</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Role</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Last Login</th>
                        <th className="text-right px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#f1f5f9]">
                      {users.length === 0 ? (
                        <tr>
                          <td colSpan={5} className="px-4 py-12 text-center text-sm text-[#94a3b8]">No users found.</td>
                        </tr>
                      ) : (
                        users.map((user) => (
                          <tr key={user.id} className="hover:bg-[#f8fafc] transition-colors">
                            <td className="px-4 py-3">
                              <div className="flex items-center gap-3">
                                <div className="w-8 h-8 rounded-full bg-gradient-nytro flex items-center justify-center flex-shrink-0">
                                  <span className="text-white text-xs font-semibold">
                                    {(user.first_name?.[0] ?? '') + (user.last_name?.[0] ?? '')}
                                  </span>
                                </div>
                                <div>
                                  <p className="text-sm font-medium text-[#1e293b]">{user.first_name} {user.last_name}</p>
                                  <p className="text-xs text-[#94a3b8]">{user.email}</p>
                                </div>
                              </div>
                            </td>
                            <td className="px-4 py-3 hidden md:table-cell">
                              <Badge variant="outline" className={cn("text-xs font-medium", roleColors[user.role_name] ?? roleColors.Student)}>
                                {user.role_name}
                              </Badge>
                            </td>
                            <td className="px-4 py-3">
                              <Badge variant="outline" className={cn("text-xs capitalize",
                                user.is_active ? 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20' : 'bg-[#f1f5f9] text-[#94a3b8] border-[#94a3b8]/20'
                              )}>
                                {user.is_active ? 'active' : 'inactive'}
                              </Badge>
                            </td>
                            <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">
                              {user.user_details?.last_logged_in ? new Date(user.user_details.last_logged_in).toLocaleDateString('en-AU') : '—'}
                            </td>
                            <td className="px-4 py-3 text-right">
                              <div className="flex items-center justify-end gap-1">
                                <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6] h-8 w-8 p-0" onClick={() => toast('Edit user coming soon')}>
                                  <Edit className="w-4 h-4" />
                                </Button>
                                <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#ef4444] h-8 w-8 p-0" onClick={() => toast('Archive user coming soon')}>
                                  <Trash2 className="w-4 h-4" />
                                </Button>
                              </div>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
                {total > limit && (
                  <div className="flex items-center justify-between px-4 py-3 border-t border-[#e2e8f0] bg-[#f8fafc]">
                    <p className="text-xs text-[#94a3b8]">Showing {page * limit + 1}–{Math.min((page + 1) * limit, total)} of {total}</p>
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" disabled={page === 0} onClick={() => setPage(p => p - 1)}>Previous</Button>
                      <Button variant="outline" size="sm" disabled={(page + 1) * limit >= total} onClick={() => setPage(p => p + 1)}>Next</Button>
                    </div>
                  </div>
                )}
              </Card>
            )}
          </TabsContent>

          {/* Roles Tab */}
          <TabsContent value="roles" className="mt-4 space-y-4">
            <div className="flex items-center justify-between">
              <p className="text-sm text-[#64748b]">Manage roles and their associated permissions</p>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add role coming soon')}>
                <Plus className="w-4 h-4 mr-1.5" /> Add Role
              </Button>
            </div>

            {distLoading ? (
              <Card className="p-12 flex items-center justify-center border-[#e2e8f0]/50">
                <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                <span className="ml-3 text-sm text-[#64748b]">Loading roles...</span>
              </Card>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {distribution.map((role) => (
                  <Card key={role.role} className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow">
                    <div className="flex items-start justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <Shield className="w-4 h-4 text-[#3b82f6]" />
                        <h3 className="font-heading font-semibold text-[#1e293b]">{role.role}</h3>
                      </div>
                      <Badge variant="outline" className="text-xs bg-[#f1f5f9] text-[#64748b] border-[#e2e8f0]">
                        {role.count} users
                      </Badge>
                    </div>
                    <p className="text-sm text-[#64748b]">
                      {role.role === 'Root' && 'Full system access — superadmin privileges'}
                      {role.role === 'Admin' && 'Manage students, courses, assessments, and reports'}
                      {role.role === 'Mini Admin' && 'Limited admin access to assigned areas'}
                      {role.role === 'Leader' && 'Company leader — manage company students and enrolments'}
                      {role.role === 'Trainer' && 'View students, manage assessments, mark submissions'}
                      {role.role === 'Student' && 'View courses, submit assessments, track progress'}
                      {role.role === 'Moderator' && 'Content moderation and quality assurance'}
                    </p>
                  </Card>
                ))}
              </div>
            )}
          </TabsContent>
        </Tabs>
      </div>
    </DashboardLayout>
  );
}
