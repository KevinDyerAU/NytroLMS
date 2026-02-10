/**
 * Reports Page — Report generation, analytics, and admin_reports data
 * Connected to Supabase: admin_reports, course_progress, student_course_enrolments
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { ReportDetailDialog } from '../components/ReportDetailDialog';
import { ReportBuilderDialog } from '../components/ReportBuilderDialog';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  BarChart3, FileText, Users, GraduationCap,
  Download, FileDown, ArrowRight, ClipboardCheck, Building2,
  Search, Loader2, AlertCircle, Eye,
} from 'lucide-react';
import { toast } from 'sonner';
import { exportToCSV, exportToPDF } from '@/lib/utils';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchAdminReports, fetchAdminReportsForExport, fetchCourseProgressSummary, CourseProgressSummary } from '@/lib/api';
import type { DbAdminReport } from '@/lib/types';
import { CompetencyMatrix } from '@/components/CompetencyMatrix';
import { ProgressComparison } from '@/components/ProgressComparison';

const reportCategories = [
  {
    title: 'Enrolment Reports',
    icon: GraduationCap,
    color: 'text-[#3b82f6]',
    bg: 'bg-[#eff6ff]',
    reports: [
      { name: 'Daily Enrolment Report', description: 'Daily summary of new enrolments' },
      { name: 'Enrolment Summary', description: 'Overview of all active enrolments' },
      { name: 'Commenced Units Report', description: 'Units commenced by students' },
    ],
  },
  {
    title: 'Student Reports',
    icon: Users,
    color: 'text-[#14b8a6]',
    bg: 'bg-[#f0fdfa]',
    reports: [
      { name: 'Active Students', description: 'Currently active student list' },
      { name: 'Disengaged Students', description: 'Students at risk of disengagement' },
      { name: 'Student Progress', description: 'Progress tracking across all courses' },
    ],
  },
  {
    title: 'Assessment Reports',
    icon: ClipboardCheck,
    color: 'text-[#f59e0b]',
    bg: 'bg-[#fffbeb]',
    reports: [
      { name: 'Pending Assessments', description: 'Assessments awaiting review' },
      { name: 'Competency Report', description: 'Competency outcomes by unit' },
      { name: 'Assessment Analytics', description: 'Trends and patterns in assessments' },
    ],
  },
  {
    title: 'Company Reports',
    icon: Building2,
    color: 'text-[#8b5cf6]',
    bg: 'bg-[#f5f3ff]',
    reports: [
      { name: 'Company Summary', description: 'Overview of company partnerships' },
      { name: 'Work Placements', description: 'Active work placement tracking' },
    ],
  },
];

const statusColors: Record<string, string> = {
  Active: 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20',
  Completed: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20',
  Withdrawn: 'bg-[#fef2f2] text-[#ef4444] border-[#ef4444]/20',
  Deferred: 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20',
};

export default function Reports() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [page, setPage] = useState(0);
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [reportBuilderOpen, setReportBuilderOpen] = useState(false);
  const [exporting, setExporting] = useState(false);
  const limit = 25;

  const handleExport = async (format: 'csv' | 'pdf') => {
    setExporting(true);
    try {
      const data = await fetchAdminReportsForExport({ status: statusFilter });
      if (!data || data.length === 0) { toast.error('No data to export'); return; }
      const filename = `admin-reports-${new Date().toISOString().split('T')[0]}`;
      if (format === 'csv') {
        exportToCSV(data, `${filename}.csv`);
        toast.success(`Exported ${data.length} records to CSV`);
      } else {
        exportToPDF(data, 'Admin Reports');
        toast.success('PDF print dialog opened');
      }
    } catch (err) {
      toast.error('Export failed');
    } finally {
      setExporting(false);
    }
  };

  const { data: reportData, loading: reportsLoading, error: reportsError, refetch: refetchReports } = useSupabaseQuery(
    () => fetchAdminReports({ search, status: statusFilter, limit, offset: page * limit }),
    [search, statusFilter, page]
  );

  const { data: progressData, loading: progressLoading } = useSupabaseQuery(
    () => fetchCourseProgressSummary(),
    []
  );

  const reports = reportData?.data ?? [];
  const total = reportData?.total ?? 0;
  const courseProgress = progressData ?? [];

  return (
    <DashboardLayout title="Reports" subtitle="Generate reports and view analytics">
      <div className="space-y-6 animate-fade-in-up">
        <Tabs defaultValue="templates" className="w-full">
          <div className="overflow-x-auto -mx-1 px-1">
            <TabsList className="bg-[#f1f5f9] border border-[#e2e8f0] w-full sm:w-auto inline-flex">
              <TabsTrigger value="templates" className="text-xs sm:text-sm">Report Templates</TabsTrigger>
              <TabsTrigger value="admin" className="text-xs sm:text-sm">Admin Reports</TabsTrigger>
              <TabsTrigger value="progress" className="text-xs sm:text-sm">Course Progress</TabsTrigger>
              <TabsTrigger value="competency" className="text-xs sm:text-sm">Competency Matrix</TabsTrigger>
              <TabsTrigger value="comparison" className="text-xs sm:text-sm">Progress Comparison</TabsTrigger>
            </TabsList>
          </div>

          {/* Report Templates Tab */}
          <TabsContent value="templates" className="space-y-6 mt-4">
            {reportCategories.map((category) => {
              const Icon = category.icon;
              return (
                <Card key={category.title} className="p-5 border-[#3b82f6]/20 shadow-card">
                  <div className="flex items-center gap-3 mb-4">
                    <div className={`p-2 rounded-lg ${category.bg}`}>
                      <Icon className={`w-5 h-5 ${category.color}`} />
                    </div>
                    <h3 className="font-heading font-semibold text-[#1e293b]">{category.title}</h3>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    {category.reports.map((report) => (
                      <div
                        key={report.name}
                        className="flex items-center justify-between p-3 rounded-lg border border-[#e2e8f0] hover:border-[#3b82f6] hover:bg-[#f8fafc] transition-all cursor-pointer group"
                        onClick={() => setReportBuilderOpen(true)}
                      >
                        <div>
                          <p className="text-sm font-medium text-[#1e293b] group-hover:text-[#3b82f6] transition-colors">{report.name}</p>
                          <p className="text-xs text-[#94a3b8] mt-0.5">{report.description}</p>
                        </div>
                        <ArrowRight className="w-4 h-4 text-[#94a3b8] group-hover:text-[#3b82f6] transition-colors flex-shrink-0" />
                      </div>
                    ))}
                  </div>
                </Card>
              );
            })}
          </TabsContent>

          {/* Admin Reports Tab */}
          <TabsContent value="admin" className="space-y-4 mt-4">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
              <div className="flex items-center gap-3 w-full sm:w-auto">
                <div className="relative w-full sm:w-72">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
                  <Input
                    placeholder="Search reports..."
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
                    <SelectItem value="Active">Active</SelectItem>
                    <SelectItem value="Completed">Completed</SelectItem>
                    <SelectItem value="Withdrawn">Withdrawn</SelectItem>
                    <SelectItem value="Deferred">Deferred</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="flex gap-2">
                <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" disabled={exporting} onClick={() => handleExport('csv')}>
                  {exporting ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <Download className="w-4 h-4 mr-1.5" />} Export CSV
                </Button>
                <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" disabled={exporting} onClick={() => handleExport('pdf')}>
                  {exporting ? <Loader2 className="w-4 h-4 mr-1.5 animate-spin" /> : <FileDown className="w-4 h-4 mr-1.5" />} Export PDF
                </Button>
              </div>
            </div>

            {reportsError && (
              <Card className="p-6 border-red-200 bg-red-50">
                <div className="flex items-center gap-3 text-red-700">
                  <AlertCircle className="w-5 h-5" />
                  <p className="text-sm">{reportsError}</p>
                  <Button variant="outline" size="sm" onClick={refetchReports}>Retry</Button>
                </div>
              </Card>
            )}

            {reportsLoading ? (
              <Card className="p-12 flex items-center justify-center border-[#3b82f6]/20 shadow-card">
                <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                <span className="ml-3 text-sm text-[#64748b]">Loading admin reports...</span>
              </Card>
            ) : (
              <Card className="overflow-hidden border-[#3b82f6]/20 shadow-card">
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Student</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Company</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Course</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                        <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden xl:table-cell">Updated</th>
                        <th className="text-right px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#f1f5f9]">
                      {reports.length === 0 ? (
                        <tr>
                          <td colSpan={6} className="px-4 py-12 text-center text-sm text-[#94a3b8]">No admin reports found.</td>
                        </tr>
                      ) : (
                        reports.map((report) => (
                          <tr key={report.id} className="hover:bg-[#f8fafc] transition-colors">
                            <td className="px-4 py-3">
                              <p className="text-sm font-medium text-[#1e293b]">{report.student_details ? (() => { try { const d = JSON.parse(report.student_details); return d?.name ?? d?.first_name ?? '—'; } catch { return report.student_details; } })() : '—'}</p>
                              <p className="text-xs text-[#94a3b8]">{report.student_details ? (() => { try { const d = JSON.parse(report.student_details); return d?.email ?? ''; } catch { return ''; } })() : ''}</p>
                            </td>
                            <td className="px-4 py-3 hidden md:table-cell text-sm text-[#64748b]">{(report as any).company_name ?? (report.company_details ? (() => { try { return JSON.parse(report.company_details)?.name; } catch { return report.company_details; } })() : '—')}</td>
                            <td className="px-4 py-3 hidden lg:table-cell text-sm text-[#64748b]">{(report as any).course_name ?? (report.course_details ? (() => { try { return JSON.parse(report.course_details)?.title; } catch { return report.course_details; } })() : '—')}</td>
                            <td className="px-4 py-3">
                              <Badge variant="outline" className={`text-xs font-medium ${statusColors[report.student_status] ?? 'bg-[#f1f5f9] text-[#64748b] border-[#64748b]/20'}`}>
                                {report.student_status ?? 'Unknown'}
                              </Badge>
                            </td>
                            <td className="px-4 py-3 hidden xl:table-cell text-sm text-[#64748b]">
                              {report.updated_at ? new Date(report.updated_at).toLocaleDateString('en-AU') : '—'}
                            </td>
                            <td className="px-4 py-3 text-right">
                              <Button variant="ghost" size="sm" className="text-[#64748b] hover:text-[#3b82f6]" onClick={() => setSelectedReportId(report.id)}>
                                <Eye className="w-4 h-4" />
                              </Button>
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

          {/* Course Progress Tab */}
          <TabsContent value="progress" className="space-y-4 mt-4">
            {progressLoading ? (
              <Card className="p-12 flex items-center justify-center border-[#3b82f6]/20 shadow-card">
                <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                <span className="ml-3 text-sm text-[#64748b]">Loading course progress...</span>
              </Card>
            ) : courseProgress.length === 0 ? (
              <Card className="p-12 text-center border-[#3b82f6]/20">
                <BarChart3 className="w-10 h-10 text-[#94a3b8] mx-auto mb-3" />
                <p className="text-sm text-[#94a3b8]">No course progress data available.</p>
              </Card>
            ) : (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {courseProgress.map((cp) => (
                  <Card key={cp.course_id} className="p-5 border-[#3b82f6]/20 shadow-card">
                    <h4 className="font-heading font-semibold text-[#1e293b] mb-3 text-sm">{cp.course_title}</h4>
                    <div className="grid grid-cols-4 gap-3 mb-4">
                      <div className="text-center">
                        <p className="text-lg font-bold text-[#1e293b]">{cp.total_enrolled}</p>
                        <p className="text-[10px] text-[#94a3b8] uppercase">Enrolled</p>
                      </div>
                      <div className="text-center">
                        <p className="text-lg font-bold text-[#22c55e]">{cp.completed}</p>
                        <p className="text-[10px] text-[#94a3b8] uppercase">Completed</p>
                      </div>
                      <div className="text-center">
                        <p className="text-lg font-bold text-[#3b82f6]">{cp.in_progress}</p>
                        <p className="text-[10px] text-[#94a3b8] uppercase">In Progress</p>
                      </div>
                      <div className="text-center">
                        <p className="text-lg font-bold text-[#94a3b8]">{cp.not_started}</p>
                        <p className="text-[10px] text-[#94a3b8] uppercase">Not Started</p>
                      </div>
                    </div>
                    <div className="space-y-1.5">
                      <div className="flex items-center justify-between text-xs">
                        <span className="text-[#64748b]">Avg. Progress</span>
                        <span className="font-semibold text-[#1e293b]">{cp.avg_progress}%</span>
                      </div>
                      <div className="w-full h-2 bg-[#e2e8f0] rounded-full overflow-hidden">
                        <div
                          className="h-full bg-gradient-to-r from-[#3b82f6] to-[#14b8a6] rounded-full transition-all duration-500"
                          style={{ width: `${cp.avg_progress}%` }}
                        />
                      </div>
                    </div>
                  </Card>
                ))}
              </div>
            )}
          </TabsContent>

          {/* Competency Matrix Tab */}
          <TabsContent value="competency" className="mt-4">
            <CompetencyMatrix />
          </TabsContent>

          {/* Progress Comparison Tab */}
          <TabsContent value="comparison" className="mt-4">
            <ProgressComparison />
          </TabsContent>
        </Tabs>
      </div>

      <ReportBuilderDialog
        open={reportBuilderOpen}
        onOpenChange={setReportBuilderOpen}
      />

      {selectedReportId !== null && (
        <ReportDetailDialog
          open={true}
          onOpenChange={(open) => { if (!open) setSelectedReportId(null); }}
          reportId={selectedReportId}
        />
      )}
    </DashboardLayout>
  );
}
