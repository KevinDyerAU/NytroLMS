/**
 * User Management Page - Manage users, roles, and permissions
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Search, Plus, UserCog, Shield, Eye, Edit, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const mockUsers = [
  { id: 1, name: 'Kevin Dyer', email: 'kevin@keycompany.com', role: 'Super Admin', status: 'active', lastLogin: '2026-02-08' },
  { id: 2, name: 'Sarah Mitchell', email: 'sarah@keycompany.com', role: 'Trainer', status: 'active', lastLogin: '2026-02-07' },
  { id: 3, name: 'Tom Richards', email: 'tom@keycompany.com', role: 'Account Manager', status: 'active', lastLogin: '2026-02-06' },
  { id: 4, name: 'Jane Cooper', email: 'jane@keycompany.com', role: 'Admin', status: 'active', lastLogin: '2026-02-05' },
  { id: 5, name: 'Mike Thompson', email: 'mike@keycompany.com', role: 'Trainer', status: 'inactive', lastLogin: '2026-01-15' },
];

const mockRoles = [
  { id: 1, name: 'Super Admin', users: 1, permissions: 'Full access to all features' },
  { id: 2, name: 'Admin', users: 2, permissions: 'Manage students, courses, assessments, reports' },
  { id: 3, name: 'Trainer', users: 5, permissions: 'View students, manage assessments, view reports' },
  { id: 4, name: 'Account Manager', users: 3, permissions: 'Manage companies, enrolments, work placements' },
  { id: 5, name: 'Student', users: 2847, permissions: 'View courses, submit assessments, view progress' },
];

export default function UserManagement() {
  const [search, setSearch] = useState('');
  const filtered = mockUsers.filter(u => u.name.toLowerCase().includes(search.toLowerCase()) || u.email.toLowerCase().includes(search.toLowerCase()));

  return (
    <DashboardLayout title="User Management" subtitle="Manage users, roles, and permissions">
      <div className="space-y-4 animate-fade-in-up">
        <Tabs defaultValue="users">
          <TabsList className="bg-[#f1f5f9] p-1">
            <TabsTrigger value="users" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">
              <UserCog className="w-3.5 h-3.5 mr-1.5" /> Users
            </TabsTrigger>
            <TabsTrigger value="roles" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">
              <Shield className="w-3.5 h-3.5 mr-1.5" /> Roles & Permissions
            </TabsTrigger>
          </TabsList>

          <TabsContent value="users" className="mt-4 space-y-4">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div className="relative w-full sm:w-72">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
                <Input placeholder="Search users..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9 border-[#e2e8f0] h-9" />
              </div>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add user coming soon')}><Plus className="w-4 h-4 mr-1.5" /> Add User</Button>
            </div>

            <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
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
                  {filtered.map((user) => (
                    <tr key={user.id} className="hover:bg-[#f8fafc] transition-colors">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-full bg-gradient-nytro flex items-center justify-center flex-shrink-0">
                            <span className="text-white text-xs font-semibold">{user.name.split(' ').map(n => n[0]).join('')}</span>
                          </div>
                          <div>
                            <p className="text-sm font-medium text-[#1e293b]">{user.name}</p>
                            <p className="text-xs text-[#94a3b8]">{user.email}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3 hidden md:table-cell">
                        <Badge variant="outline" className="text-xs bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20">{user.role}</Badge>
                      </td>
                      <td className="px-4 py-3">
                        <Badge variant="outline" className={cn("text-xs capitalize", user.status === 'active' ? 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20' : 'bg-[#f1f5f9] text-[#94a3b8] border-[#94a3b8]/20')}>
                          {user.status}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">{new Date(user.lastLogin).toLocaleDateString('en-AU')}</td>
                      <td className="px-4 py-3 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6] h-8 w-8 p-0" onClick={() => toast('Edit user coming soon')}><Edit className="w-4 h-4" /></Button>
                          <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#ef4444] h-8 w-8 p-0" onClick={() => toast('Delete user coming soon')}><Trash2 className="w-4 h-4" /></Button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </Card>
          </TabsContent>

          <TabsContent value="roles" className="mt-4 space-y-4">
            <div className="flex items-center justify-between">
              <p className="text-sm text-[#64748b]">Manage roles and their associated permissions</p>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add role coming soon')}><Plus className="w-4 h-4 mr-1.5" /> Add Role</Button>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {mockRoles.map((role) => (
                <Card key={role.id} className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow">
                  <div className="flex items-start justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <Shield className="w-4 h-4 text-[#3b82f6]" />
                      <h3 className="font-heading font-semibold text-[#1e293b]">{role.name}</h3>
                    </div>
                    <Badge variant="outline" className="text-xs bg-[#f1f5f9] text-[#64748b] border-[#e2e8f0]">{role.users} users</Badge>
                  </div>
                  <p className="text-sm text-[#64748b]">{role.permissions}</p>
                </Card>
              ))}
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </DashboardLayout>
  );
}
