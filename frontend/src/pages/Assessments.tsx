/**
 * Assessments Page - Assessment management and review
 * NytroAI design: clean table with status badges
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Search, Filter, Download, ClipboardCheck, Clock, CheckCircle2,
  XCircle, AlertTriangle, Eye, ChevronLeft, ChevronRight,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const mockAssessments = [
  { id: 1, student: 'Alex Johnson', unit: 'BSBWHS411', title: 'Implement and monitor WHS policies', status: 'pending', submitted: '2026-02-07', type: 'Written' },
  { id: 2, student: 'Emily Chen', unit: 'BSBLDR523', title: 'Lead and manage effective workplace relationships', status: 'reviewed', submitted: '2026-02-06', type: 'Portfolio' },
  { id: 3, student: 'Mark Davis', unit: 'BSBCRT411', title: 'Apply critical thinking to work practices', status: 'competent', submitted: '2026-02-05', type: 'Written' },
  { id: 4, student: 'Sarah Wilson', unit: 'CHCCOM005', title: 'Communicate and work in health or community services', status: 'not-competent', submitted: '2026-02-04', type: 'Practical' },
  { id: 5, student: 'James Brown', unit: 'BSBXCM401', title: 'Apply communication strategies in the workplace', status: 'pending', submitted: '2026-02-03', type: 'Written' },
  { id: 6, student: 'Lisa Taylor', unit: 'BSBOPS502', title: 'Manage business operational plans', status: 'pending', submitted: '2026-02-02', type: 'Project' },
  { id: 7, student: 'David Lee', unit: 'TAEDEL411', title: 'Facilitate vocational training', status: 'reviewed', submitted: '2026-02-01', type: 'Practical' },
];

const statusConfig: Record<string, { label: string; color: string; icon: React.ElementType }> = {
  pending: { label: 'Pending Review', color: 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20', icon: Clock },
  reviewed: { label: 'Under Review', color: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20', icon: Eye },
  competent: { label: 'Competent', color: 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20', icon: CheckCircle2 },
  'not-competent': { label: 'Not Yet Competent', color: 'bg-[#fef2f2] text-[#ef4444] border-[#ef4444]/20', icon: XCircle },
};

export default function Assessments() {
  const [search, setSearch] = useState('');
  const [tab, setTab] = useState('all');

  const filtered = mockAssessments.filter(a => {
    const matchesSearch = a.student.toLowerCase().includes(search.toLowerCase()) ||
      a.unit.toLowerCase().includes(search.toLowerCase()) ||
      a.title.toLowerCase().includes(search.toLowerCase());
    const matchesTab = tab === 'all' || a.status === tab;
    return matchesSearch && matchesTab;
  });

  return (
    <DashboardLayout title="Assessments" subtitle="Review and manage student assessments">
      <div className="space-y-4 animate-fade-in-up">
        {/* Stats row */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          {[
            { label: 'Pending', count: 3, icon: Clock, color: 'text-[#f59e0b]', bg: 'bg-[#fffbeb]' },
            { label: 'Under Review', count: 2, icon: Eye, color: 'text-[#3b82f6]', bg: 'bg-[#eff6ff]' },
            { label: 'Competent', count: 1, icon: CheckCircle2, color: 'text-[#22c55e]', bg: 'bg-[#f0fdf4]' },
            { label: 'Not Yet Competent', count: 1, icon: XCircle, color: 'text-[#ef4444]', bg: 'bg-[#fef2f2]' },
          ].map((stat) => {
            const Icon = stat.icon;
            return (
              <Card key={stat.label} className="p-4 border-[#e2e8f0]/50 shadow-card">
                <div className="flex items-center gap-3">
                  <div className={cn("p-2 rounded-lg", stat.bg)}>
                    <Icon className={cn("w-4 h-4", stat.color)} />
                  </div>
                  <div>
                    <p className="text-xl font-bold text-[#1e293b] font-heading">{stat.count}</p>
                    <p className="text-xs text-[#94a3b8]">{stat.label}</p>
                  </div>
                </div>
              </Card>
            );
          })}
        </div>

        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input
              placeholder="Search assessments..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9 border-[#e2e8f0] h-9"
            />
          </div>
          <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={() => toast('Export coming soon')}>
            <Download className="w-4 h-4 mr-1.5" /> Export
          </Button>
        </div>

        {/* Tabs + Table */}
        <Tabs value={tab} onValueChange={setTab}>
          <TabsList className="bg-[#f1f5f9] p-1">
            <TabsTrigger value="all" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">All</TabsTrigger>
            <TabsTrigger value="pending" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">Pending</TabsTrigger>
            <TabsTrigger value="reviewed" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">Under Review</TabsTrigger>
            <TabsTrigger value="competent" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">Competent</TabsTrigger>
            <TabsTrigger value="not-competent" className="text-xs data-[state=active]:bg-white data-[state=active]:text-[#1e293b]">NYC</TabsTrigger>
          </TabsList>

          <TabsContent value={tab} className="mt-3">
            <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                      <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Student</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Unit</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Type</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                      <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Submitted</th>
                      <th className="text-right px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#f1f5f9]">
                    {filtered.map((assessment) => {
                      const status = statusConfig[assessment.status];
                      const StatusIcon = status.icon;
                      return (
                        <tr key={assessment.id} className="hover:bg-[#f8fafc] transition-colors">
                          <td className="px-4 py-3">
                            <p className="text-sm font-medium text-[#1e293b]">{assessment.student}</p>
                            <p className="text-xs text-[#94a3b8] md:hidden">{assessment.unit}</p>
                          </td>
                          <td className="px-4 py-3 hidden md:table-cell">
                            <p className="text-sm text-[#1e293b]">{assessment.unit}</p>
                            <p className="text-xs text-[#94a3b8] truncate max-w-[200px]">{assessment.title}</p>
                          </td>
                          <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">{assessment.type}</td>
                          <td className="px-4 py-3">
                            <Badge variant="outline" className={cn("text-xs font-medium", status.color)}>
                              <StatusIcon className="w-3 h-3 mr-1" />
                              {status.label}
                            </Badge>
                          </td>
                          <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">
                            {new Date(assessment.submitted).toLocaleDateString('en-AU')}
                          </td>
                          <td className="px-4 py-3 text-right">
                            <Button variant="outline" size="sm" className="text-xs border-[#e2e8f0] text-[#3b82f6] hover:bg-[#eff6ff]" onClick={() => toast('Assessment review coming soon')}>
                              Review
                            </Button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </DashboardLayout>
  );
}
