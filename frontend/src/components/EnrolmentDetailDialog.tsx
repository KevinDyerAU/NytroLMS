/**
 * EnrolmentDetailDialog — Shows full enrolment details in a dialog
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { StatusBadge } from './StatusBadge';
import { supabase } from '@/lib/supabase';
import { Loader2 } from 'lucide-react';
import type { DbStudentCourseEnrolment } from '@/lib/types';

interface EnrolmentDetailDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  enrolmentId: number;
}

interface EnrolmentDetail extends DbStudentCourseEnrolment {
  student_name: string;
  student_email: string;
  course_title: string;
  progress_percentage: number | null;
}

export function EnrolmentDetailDialog({ open, onOpenChange, enrolmentId }: EnrolmentDetailDialogProps) {
  const [data, setData] = useState<EnrolmentDetail | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!open || !enrolmentId) return;
    setLoading(true);
    (async () => {
      try {
        const { data: enrolment, error } = await supabase
          .from('student_course_enrolments')
          .select('*')
          .eq('id', enrolmentId)
          .single();
        if (error || !enrolment) { setData(null); return; }

        const [studentResult, courseResult, progressResult] = await Promise.all([
          supabase.from('users').select('first_name, last_name, email').eq('id', enrolment.user_id).single(),
          supabase.from('courses').select('title').eq('id', enrolment.course_id).single(),
          supabase.from('course_progress').select('percentage').eq('user_id', enrolment.user_id).eq('course_id', enrolment.course_id).maybeSingle(),
        ]);

        setData({
          ...enrolment,
          student_name: studentResult.data ? `${studentResult.data.first_name} ${studentResult.data.last_name}` : 'Unknown',
          student_email: studentResult.data?.email ?? '',
          course_title: courseResult.data?.title ?? 'Unknown',
          progress_percentage: progressResult.data ? parseFloat(progressResult.data.percentage) : null,
        });
      } catch {
        setData(null);
      } finally {
        setLoading(false);
      }
    })();
  }, [open, enrolmentId]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Enrolment Details</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : !data ? (
          <p className="text-sm text-red-500 text-center py-8">Enrolment not found</p>
        ) : (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="font-semibold text-[#1e293b]">{data.student_name}</h3>
                <p className="text-sm text-[#64748b]">{data.student_email}</p>
              </div>
              <StatusBadge status={data.status} />
            </div>

            <div className="border-t border-[#e2e8f0] pt-3">
              <h4 className="font-medium text-[#1e293b] mb-2">{data.course_title}</h4>

              {data.progress_percentage !== null && (
                <div className="mb-3">
                  <div className="flex justify-between text-xs text-[#64748b] mb-1">
                    <span>Progress</span>
                    <span className="font-semibold text-[#3b82f6]">{Math.round(data.progress_percentage)}%</span>
                  </div>
                  <div className="w-full bg-[#e2e8f0] rounded-full h-2">
                    <div
                      className="bg-[#3b82f6] h-2 rounded-full transition-all"
                      style={{ width: `${Math.min(100, data.progress_percentage)}%` }}
                    />
                  </div>
                </div>
              )}

              <div className="grid grid-cols-2 gap-3 text-sm">
                <DetailRow label="Start Date">
                  {data.course_start_at ? new Date(data.course_start_at).toLocaleDateString('en-AU') : '—'}
                </DetailRow>
                <DetailRow label="End Date">
                  {data.course_ends_at ? new Date(data.course_ends_at).toLocaleDateString('en-AU') : '—'}
                </DetailRow>
                {data.course_expiry && (
                  <DetailRow label="Expiry">
                    {new Date(data.course_expiry).toLocaleDateString('en-AU')}
                  </DetailRow>
                )}
                {data.course_completed_at && (
                  <DetailRow label="Completed">
                    {new Date(data.course_completed_at).toLocaleDateString('en-AU')}
                  </DetailRow>
                )}
                <DetailRow label="Version">{data.version}</DetailRow>
                <DetailRow label="Locked">{data.is_locked === 1 ? 'Yes' : 'No'}</DetailRow>
                <DetailRow label="Deferred">{data.deferred === 1 ? 'Yes' : 'No'}</DetailRow>
                <DetailRow label="Chargeable">{data.is_chargeable === 1 ? 'Yes' : 'No'}</DetailRow>
                {data.cert_issued === 1 && (
                  <DetailRow label="Certificate">
                    Issued {data.cert_issued_on ? new Date(data.cert_issued_on).toLocaleDateString('en-AU') : ''}
                  </DetailRow>
                )}
                <DetailRow label="Created">
                  {data.created_at ? new Date(data.created_at).toLocaleDateString('en-AU') : '—'}
                </DetailRow>
              </div>
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
