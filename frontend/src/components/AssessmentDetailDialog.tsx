/**
 * AssessmentDetailDialog — Shows full quiz attempt details in a dialog
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { StatusBadge } from './StatusBadge';
import { fetchQuizAttemptDetail } from '@/lib/api';
import { Loader2 } from 'lucide-react';

interface AssessmentDetailDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  attemptId: number;
}

export function AssessmentDetailDialog({ open, onOpenChange, attemptId }: AssessmentDetailDialogProps) {
  const [data, setData] = useState<Awaited<ReturnType<typeof fetchQuizAttemptDetail>>>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!open || !attemptId) return;
    setLoading(true);
    fetchQuizAttemptDetail(attemptId)
      .then(setData)
      .catch(() => setData(null))
      .finally(() => setLoading(false));
  }, [open, attemptId]);

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg">
        <DialogHeader>
          <DialogTitle>Assessment Details</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : !data ? (
          <p className="text-sm text-red-500 text-center py-8">Assessment not found</p>
        ) : (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="font-semibold text-[#1e293b]">{data.student_name}</h3>
                <p className="text-sm text-[#64748b]">{data.quiz_title}</p>
              </div>
              <StatusBadge status={data.status} />
            </div>

            <div className="border-t border-[#e2e8f0] pt-3">
              <div className="grid grid-cols-2 gap-3 text-sm">
                <DetailRow label="Course">{data.course_title}</DetailRow>
                <DetailRow label="Lesson">{data.lesson_title}</DetailRow>
                <DetailRow label="Topic">{data.topic_title}</DetailRow>
                <DetailRow label="Quiz">{data.quiz_title}</DetailRow>
                <DetailRow label="Attempt #">{data.attempt}</DetailRow>
                <DetailRow label="Status"><StatusBadge status={data.status} /></DetailRow>
                {data.system_result && (
                  <DetailRow label="System Result">{data.system_result}</DetailRow>
                )}
                <DetailRow label="Assisted">{data.assisted === 1 ? 'Yes' : 'No'}</DetailRow>
                {data.submitted_at && (
                  <DetailRow label="Submitted">
                    {new Date(data.submitted_at).toLocaleString('en-AU', {
                      day: 'numeric', month: 'short', year: 'numeric',
                      hour: '2-digit', minute: '2-digit',
                    })}
                  </DetailRow>
                )}
                {data.accessed_at && (
                  <DetailRow label="Accessed">
                    {new Date(data.accessed_at).toLocaleString('en-AU', {
                      day: 'numeric', month: 'short', year: 'numeric',
                      hour: '2-digit', minute: '2-digit',
                    })}
                  </DetailRow>
                )}
                <DetailRow label="Created">
                  {data.created_at
                    ? new Date(data.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })
                    : '—'}
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
