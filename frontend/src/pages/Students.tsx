/**
 * Students Page - Student management with data table
 * NytroAI design: clean white cards, blue accents, sortable table
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Search, Plus, Filter, Download, MoreHorizontal,
  ChevronLeft, ChevronRight, Mail, Phone, Eye,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const mockStudents = [
  { id: 1, name: 'Alex Johnson', email: 'alex.j@email.com', phone: '0412 345 678', course: 'Cert III Business', status: 'active', progress: 72, enrolled: '2025-08-15' },
  { id: 2, name: 'Emily Chen', email: 'emily.c@email.com', phone: '0423 456 789', course: 'Diploma Leadership', status: 'active', progress: 45, enrolled: '2025-09-01' },
  { id: 3, name: 'Mark Davis', email: 'mark.d@email.com', phone: '0434 567 890', course: 'Cert IV TAE', status: 'completed', progress: 100, enrolled: '2025-06-10' },
  { id: 4, name: 'Sarah Wilson', email: 'sarah.w@email.com', phone: '0445 678 901', course: 'Cert III Individual Support', status: 'at-risk', progress: 28, enrolled: '2025-10-01' },
  { id: 5, name: 'James Brown', email: 'james.b@email.com', phone: '0456 789 012', course: 'Cert III Business', status: 'inactive', progress: 15, enrolled: '2025-07-20' },
  { id: 6, name: 'Lisa Taylor', email: 'lisa.t@email.com', phone: '0467 890 123', course: 'Diploma Leadership', status: 'active', progress: 89, enrolled: '2025-05-12' },
  { id: 7, name: 'David Lee', email: 'david.l@email.com', phone: '0478 901 234', course: 'Cert IV TAE', status: 'active', progress: 56, enrolled: '2025-08-28' },
  { id: 8, name: 'Rachel Green', email: 'rachel.g@email.com', phone: '0489 012 345', course: 'Cert III Business', status: 'at-risk', progress: 32, enrolled: '2025-09-15' },
];

const statusColors: Record<string, string> = {
  active: 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20',
  'at-risk': 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20',
  inactive: 'bg-[#f1f5f9] text-[#94a3b8] border-[#94a3b8]/20',
  completed: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20',
};

export default function Students() {
  const [search, setSearch] = useState('');

  const filtered = mockStudents.filter(s =>
    s.name.toLowerCase().includes(search.toLowerCase()) ||
    s.email.toLowerCase().includes(search.toLowerCase()) ||
    s.course.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <DashboardLayout title="Students" subtitle="Manage student records and enrolments">
      <div className="space-y-4 animate-fade-in-up">
        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="flex items-center gap-2 w-full sm:w-auto">
            <div className="relative flex-1 sm:w-72">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
              <Input
                placeholder="Search students..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="pl-9 border-[#e2e8f0] h-9"
              />
            </div>
            <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={() => toast('Filter options coming soon')}>
              <Filter className="w-4 h-4 mr-1.5" /> Filter
            </Button>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={() => toast('Export coming soon')}>
              <Download className="w-4 h-4 mr-1.5" /> Export
            </Button>
            <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add student coming soon')}>
              <Plus className="w-4 h-4 mr-1.5" /> Add Student
            </Button>
          </div>
        </div>

        {/* Table */}
        <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Student</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Course</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Progress</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Enrolled</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#f1f5f9]">
                {filtered.map((student) => (
                  <tr key={student.id} className="hover:bg-[#f8fafc] transition-colors">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-gradient-nytro flex items-center justify-center flex-shrink-0">
                          <span className="text-white text-xs font-semibold">
                            {student.name.split(' ').map(n => n[0]).join('')}
                          </span>
                        </div>
                        <div>
                          <p className="text-sm font-medium text-[#1e293b]">{student.name}</p>
                          <p className="text-xs text-[#94a3b8]">{student.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3 hidden md:table-cell">
                      <p className="text-sm text-[#64748b]">{student.course}</p>
                    </td>
                    <td className="px-4 py-3">
                      <Badge variant="outline" className={cn("text-xs capitalize font-medium", statusColors[student.status])}>
                        {student.status}
                      </Badge>
                    </td>
                    <td className="px-4 py-3 hidden lg:table-cell">
                      <div className="flex items-center gap-2 w-24">
                        <div className="flex-1 h-1.5 bg-[#f1f5f9] rounded-full overflow-hidden">
                          <div
                            className="h-full bg-[#3b82f6] rounded-full transition-all"
                            style={{ width: `${student.progress}%` }}
                          />
                        </div>
                        <span className="text-xs text-[#64748b] w-8 text-right">{student.progress}%</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 hidden lg:table-cell">
                      <p className="text-sm text-[#64748b]">{new Date(student.enrolled).toLocaleDateString('en-AU')}</p>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => toast('Student details coming soon')}>
                        <Eye className="w-4 h-4" />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {/* Pagination */}
          <div className="flex items-center justify-between px-4 py-3 border-t border-[#e2e8f0]">
            <p className="text-sm text-[#94a3b8]">Showing {filtered.length} of {mockStudents.length} students</p>
            <div className="flex items-center gap-1">
              <Button variant="outline" size="sm" className="h-8 w-8 p-0 border-[#e2e8f0]" disabled>
                <ChevronLeft className="w-4 h-4" />
              </Button>
              <Button size="sm" className="h-8 w-8 p-0 bg-[#3b82f6] text-white">1</Button>
              <Button variant="outline" size="sm" className="h-8 w-8 p-0 border-[#e2e8f0]" disabled>
                <ChevronRight className="w-4 h-4" />
              </Button>
            </div>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
