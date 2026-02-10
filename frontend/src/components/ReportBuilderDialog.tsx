/**
 * ReportBuilderDialog — Generic report generation with filters
 */
import { useState } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { buildReport, type GeneratedReport, type ReportFilters } from '@/lib/api';
import { exportToCSV, exportToPDF } from '@/lib/utils';
import { Loader2, FileText, Download, FileDown, Calendar, Users, GraduationCap, ClipboardCheck, Building2 } from 'lucide-react';
import { toast } from 'sonner';

interface ReportBuilderDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  preSelectedType?: string;
}

interface ReportType {
  id: string;
  name: string;
  description: string;
  category: 'enrolment' | 'student' | 'assessment' | 'company';
  icon: React.ElementType;
  supportsDateRange: boolean;
  supportsStatus: boolean;
}

const reportTypes: ReportType[] = [
  {
    id: 'daily-enrolment',
    name: 'Daily Enrolment Report',
    description: 'New enrolments for a specific date range',
    category: 'enrolment',
    icon: Calendar,
    supportsDateRange: true,
    supportsStatus: false,
  },
  {
    id: 'enrolment-summary',
    name: 'Enrolment Summary',
    description: 'Overview of all enrolments by status',
    category: 'enrolment',
    icon: GraduationCap,
    supportsDateRange: true,
    supportsStatus: true,
  },
  {
    id: 'active-students',
    name: 'Active Students',
    description: 'List of currently active students',
    category: 'student',
    icon: Users,
    supportsDateRange: false,
    supportsStatus: false,
  },
  {
    id: 'disengaged-students',
    name: 'Disengaged Students',
    description: 'Students with no recent login activity',
    category: 'student',
    icon: Users,
    supportsDateRange: false,
    supportsStatus: false,
  },
  {
    id: 'student-progress',
    name: 'Student Progress',
    description: 'Progress tracking across active courses',
    category: 'student',
    icon: GraduationCap,
    supportsDateRange: false,
    supportsStatus: false,
  },
  {
    id: 'pending-assessments',
    name: 'Pending Assessments',
    description: 'Assessments awaiting review',
    category: 'assessment',
    icon: ClipboardCheck,
    supportsDateRange: false,
    supportsStatus: false,
  },
  {
    id: 'competency-report',
    name: 'Competency Report',
    description: 'Pass/fail outcomes by student and course',
    category: 'assessment',
    icon: ClipboardCheck,
    supportsDateRange: false,
    supportsStatus: false,
  },
  {
    id: 'assessment-analytics',
    name: 'Assessment Analytics',
    description: 'Daily submission trends and outcomes',
    category: 'assessment',
    icon: ClipboardCheck,
    supportsDateRange: true,
    supportsStatus: false,
  },
  {
    id: 'company-summary',
    name: 'Company Summary',
    description: 'Overview of company partnerships',
    category: 'company',
    icon: Building2,
    supportsDateRange: false,
    supportsStatus: false,
  },
  {
    id: 'work-placements',
    name: 'Work Placements',
    description: 'Active work placement tracking',
    category: 'company',
    icon: Building2,
    supportsDateRange: false,
    supportsStatus: false,
  },
];

const categoryColors: Record<string, string> = {
  enrolment: 'bg-[#eff6ff] text-[#3b82f6] border-[#3b82f6]/20',
  student: 'bg-[#f0fdfa] text-[#14b8a6] border-[#14b8a6]/20',
  assessment: 'bg-[#fffbeb] text-[#f59e0b] border-[#f59e0b]/20',
  company: 'bg-[#f5f3ff] text-[#8b5cf6] border-[#8b5cf6]/20',
};

export function ReportBuilderDialog({ open, onOpenChange, preSelectedType }: ReportBuilderDialogProps) {
  const [selectedReport, setSelectedReport] = useState<string>('');
  const [generating, setGenerating] = useState(false);
  const [generatedReport, setGeneratedReport] = useState<GeneratedReport | null>(null);

  // Filters
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [status, setStatus] = useState('');

  // Auto-select when preSelectedType is provided
  const effectiveSelection = preSelectedType || selectedReport;
  const isPreSelected = !!preSelectedType;

  const resetForm = () => {
    setSelectedReport('');
    setStartDate('');
    setEndDate('');
    setStatus('');
    setGeneratedReport(null);
  };

  const handleClose = () => {
    resetForm();
    onOpenChange(false);
  };

  const handleGenerate = async () => {
    if (!effectiveSelection) {
      toast.error('Please select a report type');
      return;
    }

    setGenerating(true);
    try {
      const filters: ReportFilters = {};
      if (startDate) filters.startDate = `${startDate}T00:00:00`;
      if (endDate) filters.endDate = `${endDate}T23:59:59`;
      if (status) filters.status = status;

      const report = await buildReport(effectiveSelection, filters);
      setGeneratedReport(report);
      toast.success(`Generated report with ${report.recordCount} records`);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to generate report');
    } finally {
      setGenerating(false);
    }
  };

  const handleExportCSV = () => {
    if (!generatedReport || generatedReport.data.length === 0) {
      toast.error('No data to export');
      return;
    }

    exportToCSV(
      generatedReport.data,
      `${generatedReport.reportType}-${new Date().toISOString().split('T')[0]}.csv`
    );
    toast.success('Report exported to CSV');
  };

  const handleExportPDF = () => {
    if (!generatedReport || generatedReport.data.length === 0) {
      toast.error('No data to export');
      return;
    }

    const reportName = reportTypes.find(r => r.id === generatedReport.reportType)?.name || generatedReport.reportType;
    exportToPDF(
      generatedReport.data,
      reportName
    );
    toast.success('PDF print dialog opened');
  };

  const selectedReportType = reportTypes.find(r => r.id === effectiveSelection);

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) resetForm(); onOpenChange(v); }}>
      <DialogContent className={`${isPreSelected ? 'max-w-xl' : 'max-w-4xl'} max-h-[90vh] overflow-y-auto`}>
        <DialogHeader>
          <DialogTitle>
            {isPreSelected && selectedReportType
              ? selectedReportType.name
              : 'Report Builder'}
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-6">
          {/* Report Type Selection — hidden when pre-selected */}
          {!generatedReport && (
            <>
              {!isPreSelected && (
                <div className="space-y-3">
                  <Label>Select Report Type</Label>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {reportTypes.map((report) => {
                      const Icon = report.icon;
                      return (
                        <Card
                          key={report.id}
                          className={`p-4 cursor-pointer transition-all border-2 ${
                            effectiveSelection === report.id
                              ? 'border-[#3b82f6] bg-[#f8fafc]'
                              : 'border-transparent hover:border-[#3b82f6]/20'
                          }`}
                          onClick={() => setSelectedReport(report.id)}
                        >
                          <div className="flex items-start gap-3">
                            <div className={`p-2 rounded-lg ${categoryColors[report.category]}`}>
                              <Icon className="w-4 h-4" />
                            </div>
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2">
                                <h4 className="font-medium text-[#1e293b] text-sm">{report.name}</h4>
                                <Badge variant="outline" className={`text-[10px] ${categoryColors[report.category]}`}>
                                  {report.category}
                                </Badge>
                              </div>
                              <p className="text-xs text-[#94a3b8] mt-1">{report.description}</p>
                            </div>
                          </div>
                        </Card>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Filters */}
              {selectedReportType && (
                <div className={`space-y-4 ${!isPreSelected ? 'border-t border-[#3b82f6]/10 pt-4' : ''}`}>
                  {selectedReportType.supportsDateRange || selectedReportType.supportsStatus ? (
                    <>
                      <Label>Filters</Label>
                      <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                        {selectedReportType.supportsDateRange && (
                          <>
                            <div>
                              <Label htmlFor="startDate" className="text-xs">Start Date</Label>
                              <Input
                                id="startDate"
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                              />
                            </div>
                            <div>
                              <Label htmlFor="endDate" className="text-xs">End Date</Label>
                              <Input
                                id="endDate"
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                              />
                            </div>
                          </>
                        )}
                        {selectedReportType.supportsStatus && (
                          <div>
                            <Label htmlFor="status" className="text-xs">Status</Label>
                            <Select value={status} onValueChange={setStatus}>
                              <SelectTrigger id="status">
                                <SelectValue placeholder="All statuses" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="ACTIVE">Active</SelectItem>
                                <SelectItem value="COMPLETED">Completed</SelectItem>
                                <SelectItem value="WITHDRAWN">Withdrawn</SelectItem>
                                <SelectItem value="DEFERRED">Deferred</SelectItem>
                                <SelectItem value="CANCELLED">Cancelled</SelectItem>
                              </SelectContent>
                            </Select>
                          </div>
                        )}
                      </div>
                    </>
                  ) : isPreSelected ? (
                    <p className="text-sm text-[#64748b]">
                      {selectedReportType.description}. Click <strong>Generate Report</strong> to continue.
                    </p>
                  ) : null}
                </div>
              )}
            </>
          )}

          {/* Generated Report Preview */}
          {generatedReport && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="font-medium text-[#1e293b]">
                    {reportTypes.find(r => r.id === generatedReport.reportType)?.name || generatedReport.reportType}
                  </h3>
                  <p className="text-xs text-[#94a3b8]">
                    Generated {new Date(generatedReport.generatedAt).toLocaleString()} • {generatedReport.recordCount} records
                  </p>
                </div>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={handleExportCSV}>
                    <Download className="w-4 h-4 mr-1.5" /> Export CSV
                  </Button>
                  <Button variant="outline" size="sm" onClick={handleExportPDF}>
                    <FileDown className="w-4 h-4 mr-1.5" /> Export PDF
                  </Button>
                  <Button variant="outline" size="sm" onClick={() => setGeneratedReport(null)}>
                    New Report
                  </Button>
                </div>
              </div>

              {generatedReport.data.length > 0 ? (
                <div className="border border-[#3b82f6]/20 rounded-md overflow-hidden max-h-96 overflow-y-auto">
                  <table className="w-full text-sm">
                    <thead className="bg-[#f8fafc] sticky top-0">
                      <tr>
                        {Object.keys(generatedReport.data[0]).map((key) => (
                          <th key={key} className="px-3 py-2 text-left text-xs font-medium text-[#64748b] uppercase">
                            {key.replace(/_/g, ' ')}
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#f1f5f9]">
                      {generatedReport.data.slice(0, 100).map((row, idx) => (
                        <tr key={idx} className="hover:bg-[#f8fafc]">
                          {Object.values(row).map((value, vIdx) => (
                            <td key={vIdx} className="px-3 py-2 text-[#1e293b]">
                              {value === null || value === undefined ? '—' : String(value)}
                            </td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                  {generatedReport.data.length > 100 && (
                    <div className="px-3 py-2 text-xs text-[#94a3b8] text-center border-t border-[#3b82f6]/10">
                      Showing first 100 of {generatedReport.data.length} records. Export to see all.
                    </div>
                  )}
                </div>
              ) : (
                <Card className="p-8 text-center">
                  <FileText className="w-10 h-10 text-[#94a3b8] mx-auto mb-2" />
                  <p className="text-sm text-[#94a3b8]">No data found for the selected filters</p>
                </Card>
              )}
            </div>
          )}
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={handleClose} disabled={generating}>
            {generatedReport ? 'Close' : 'Cancel'}
          </Button>
          {!generatedReport && (
            <Button
              className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
              disabled={!effectiveSelection || generating}
              onClick={handleGenerate}
            >
              {generating && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
              Generate Report
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
