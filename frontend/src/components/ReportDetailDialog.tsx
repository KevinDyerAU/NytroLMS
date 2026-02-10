/**
 * ReportDetailDialog — Shows full admin report details in a dialog
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { StatusBadge } from './StatusBadge';
import { supabase } from '@/lib/supabase';
import { Loader2 } from 'lucide-react';
import type { DbAdminReport } from '@/lib/types';

interface ReportDetailDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  reportId: number;
}

function safeParseJson(val: any): Record<string, unknown> | null {
  if (!val) return null;
  if (typeof val === 'object') return val as Record<string, unknown>;
  try { return JSON.parse(val); } catch { return null; }
}

function extractName(val: any): string {
  const obj = safeParseJson(val);
  if (!obj) return '—';
  return (obj.name as string) ?? (obj.first_name ? `${obj.first_name} ${obj.last_name ?? ''}`.trim() : '—');
}

function extractEmail(val: any): string {
  const obj = safeParseJson(val);
  return (obj?.email as string) ?? '';
}

export function ReportDetailDialog({ open, onOpenChange, reportId }: ReportDetailDialogProps) {
  const [data, setData] = useState<DbAdminReport | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!open || !reportId) return;
    setLoading(true);
    (async () => {
      try {
        const { data: report, error } = await supabase
          .from('admin_reports')
          .select('*')
          .eq('id', reportId)
          .single();
        if (error) throw error;
        setData(report);
      } catch {
        setData(null);
      } finally {
        setLoading(false);
      }
    })();
  }, [open, reportId]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Admin Report Detail</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : !data ? (
          <p className="text-sm text-red-500 text-center py-8">Report not found</p>
        ) : (
          <div className="space-y-4">
            {/* Student Info */}
            <div>
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider mb-2">Student</h3>
              <div className="bg-[#f8fafc] rounded-lg p-3 space-y-1">
                <p className="font-medium text-[#1e293b]">{extractName(data.student_details)}</p>
                {extractEmail(data.student_details) && (
                  <p className="text-sm text-[#64748b]">{extractEmail(data.student_details)}</p>
                )}
                <div className="flex items-center gap-2 mt-1">
                  <StatusBadge status={data.student_status} />
                  {data.student_last_active && (
                    <span className="text-xs text-[#94a3b8]">
                      Last active: {new Date(data.student_last_active).toLocaleDateString('en-AU')}
                    </span>
                  )}
                </div>
              </div>
            </div>

            {/* Course Info */}
            <div>
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider mb-2">Course</h3>
              <div className="bg-[#f8fafc] rounded-lg p-3 space-y-1">
                <p className="font-medium text-[#1e293b]">
                  {data.course_details ? (() => {
                    const obj = safeParseJson(data.course_details);
                    return obj?.title as string ?? data.course_details;
                  })() : '—'}
                </p>
                {data.course_status && <StatusBadge status={data.course_status} />}
                <div className="grid grid-cols-2 gap-2 text-sm mt-2">
                  <DetailRow label="Start Date">
                    {data.student_course_start_date
                      ? new Date(data.student_course_start_date).toLocaleDateString('en-AU')
                      : '—'}
                  </DetailRow>
                  <DetailRow label="End Date">
                    {data.student_course_end_date
                      ? new Date(data.student_course_end_date).toLocaleDateString('en-AU')
                      : '—'}
                  </DetailRow>
                  {data.course_completed_at && (
                    <DetailRow label="Completed">
                      {new Date(data.course_completed_at).toLocaleDateString('en-AU')}
                    </DetailRow>
                  )}
                  {data.course_expiry && (
                    <DetailRow label="Expiry">
                      {new Date(data.course_expiry).toLocaleDateString('en-AU')}
                    </DetailRow>
                  )}
                </div>
                {data.student_course_progress && (
                  <div className="mt-2">
                    <span className="text-xs text-[#94a3b8]">Progress</span>
                    <p className="text-sm text-[#1e293b]">{data.student_course_progress}</p>
                  </div>
                )}
              </div>
            </div>

            {/* Trainer Info */}
            <div>
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider mb-2">Trainer</h3>
              <div className="bg-[#f8fafc] rounded-lg p-3">
                <p className="text-sm text-[#1e293b]">{extractName(data.trainer_details)}</p>
              </div>
            </div>

            {/* Leader Info */}
            <div>
              <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider mb-2">Leader</h3>
              <div className="bg-[#f8fafc] rounded-lg p-3 space-y-1">
                <p className="text-sm text-[#1e293b]">{extractName(data.leader_details)}</p>
                {data.leader_last_active && (
                  <p className="text-xs text-[#94a3b8]">
                    Last active: {new Date(data.leader_last_active).toLocaleDateString('en-AU')}
                  </p>
                )}
              </div>
            </div>

            {/* Company Info */}
            {data.company_details && (
              <div>
                <h3 className="text-sm font-semibold text-[#64748b] uppercase tracking-wider mb-2">Company</h3>
                <div className="bg-[#f8fafc] rounded-lg p-3">
                  <p className="text-sm text-[#1e293b]">{extractName(data.company_details)}</p>
                </div>
              </div>
            )}

            {/* Meta */}
            <div className="border-t border-[#3b82f6]/10 pt-3 grid grid-cols-2 gap-2 text-sm">
              <DetailRow label="Main Course">{data.is_main_course === 1 ? 'Yes' : 'No'}</DetailRow>
              <DetailRow label="Next Course Allowed">{data.allowed_to_next_course}</DetailRow>
              <DetailRow label="Updated">
                {data.updated_at ? new Date(data.updated_at).toLocaleDateString('en-AU') : '—'}
              </DetailRow>
              <DetailRow label="Created">
                {data.created_at ? new Date(data.created_at).toLocaleDateString('en-AU') : '—'}
              </DetailRow>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

function DetailRow({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <span className="text-xs text-[#94a3b8] block">{label}</span>
      <span className="text-[#1e293b]">{children}</span>
    </div>
  );
}
