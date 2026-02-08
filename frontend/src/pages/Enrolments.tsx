/**
 * Enrolments Page - Manage student enrolments
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, Plus, Download, GraduationCap, Eye } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const mockEnrolments = [
  { id: 1, student: 'Alex Johnson', course: 'Certificate III in Business', startDate: '2025-08-15', endDate: '2026-08-15', status: 'active', funding: 'VET Student Loans' },
  { id: 2, student: 'Emily Chen', course: 'Diploma of Leadership', startDate: '2025-09-01', endDate: '2027-03-01', status: 'active', funding: 'Fee for Service' },
  { id: 3, student: 'Mark Davis', course: 'Certificate IV in TAE', startDate: '2025-06-10', endDate: '2026-06-10', status: 'completed', funding: 'Smart & Skilled' },
  { id: 4, student: 'Sarah Wilson', course: 'Certificate III Individual Support', startDate: '2025-10-01', endDate: '2026-10-01', status: 'active', funding: 'VET Student Loans' },
  { id: 5, student: 'James Brown', course: 'Certificate III in Business', startDate: '2025-07-20', endDate: '2026-07-20', status: 'withdrawn', funding: 'Fee for Service' },
  { id: 6, student: 'Lisa Taylor', course: 'Diploma of Leadership', startDate: '2025-05-12', endDate: '2026-11-12', status: 'active', funding: 'Smart & Skilled' },
];

const statusColors: Record<string, string> = {
  active: 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20',
  completed: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20',
  withdrawn: 'bg-[#fef2f2] text-[#ef4444] border-[#ef4444]/20',
  deferred: 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20',
};

export default function Enrolments() {
  const [search, setSearch] = useState('');
  const filtered = mockEnrolments.filter(e =>
    e.student.toLowerCase().includes(search.toLowerCase()) ||
    e.course.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <DashboardLayout title="Enrolments" subtitle="Manage student course enrolments">
      <div className="space-y-4 animate-fade-in-up">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input placeholder="Search enrolments..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9 border-[#e2e8f0] h-9" />
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={() => toast('Export coming soon')}><Download className="w-4 h-4 mr-1.5" /> Export</Button>
            <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('New enrolment coming soon')}><Plus className="w-4 h-4 mr-1.5" /> New Enrolment</Button>
          </div>
        </div>

        <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Student</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Course</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Start Date</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">End Date</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden xl:table-cell">Funding</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                  <th className="text-right px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#f1f5f9]">
                {filtered.map((enrolment) => (
                  <tr key={enrolment.id} className="hover:bg-[#f8fafc] transition-colors">
                    <td className="px-4 py-3"><p className="text-sm font-medium text-[#1e293b]">{enrolment.student}</p></td>
                    <td className="px-4 py-3 hidden md:table-cell text-sm text-[#64748b]">{enrolment.course}</td>
                    <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">{new Date(enrolment.startDate).toLocaleDateString('en-AU')}</td>
                    <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">{new Date(enrolment.endDate).toLocaleDateString('en-AU')}</td>
                    <td className="px-4 py-3 hidden xl:table-cell text-sm text-[#64748b]">{enrolment.funding}</td>
                    <td className="px-4 py-3">
                      <Badge variant="outline" className={cn("text-xs capitalize font-medium", statusColors[enrolment.status])}>{enrolment.status}</Badge>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => toast('Enrolment details coming soon')}><Eye className="w-4 h-4" /></Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
