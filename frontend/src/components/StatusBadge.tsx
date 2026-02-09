/**
 * StatusBadge — Consistent status display across all NytroLMS pages.
 */

import { cn } from '@/lib/utils';

const statusStyles: Record<string, string> = {
  // Enrolment / Course statuses
  ACTIVE: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  COMPLETED: 'bg-blue-50 text-blue-700 border-blue-200',
  DRAFT: 'bg-slate-100 text-slate-600 border-slate-200',
  PUBLISHED: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  ARCHIVED: 'bg-amber-50 text-amber-700 border-amber-200',
  DEFERRED: 'bg-orange-50 text-orange-700 border-orange-200',
  EXPIRED: 'bg-red-50 text-red-600 border-red-200',
  LOCKED: 'bg-slate-100 text-slate-500 border-slate-200',
  WITHDRAWN: 'bg-red-50 text-red-600 border-red-200',

  // Quiz attempt statuses
  SUBMITTED: 'bg-amber-50 text-amber-700 border-amber-200',
  PASSED: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  FAILED: 'bg-red-50 text-red-600 border-red-200',
  PENDING: 'bg-amber-50 text-amber-700 border-amber-200',
  IN_PROGRESS: 'bg-blue-50 text-blue-700 border-blue-200',

  // User statuses
  CREATED: 'bg-slate-100 text-slate-600 border-slate-200',
  ONBOARDED: 'bg-blue-50 text-blue-700 border-blue-200',

  // Visibility
  PUBLIC: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  PRIVATE: 'bg-slate-100 text-slate-600 border-slate-200',

  // Course types
  PAID: 'bg-purple-50 text-purple-700 border-purple-200',
  FREE: 'bg-teal-50 text-teal-700 border-teal-200',
};

interface StatusBadgeProps {
  status: string | null | undefined;
  className?: string;
}

export function StatusBadge({ status, className }: StatusBadgeProps) {
  if (!status) return <span className="text-xs text-muted-foreground">—</span>;

  const normalized = status.toUpperCase().replace(/\s+/g, '_');
  const style = statusStyles[normalized] ?? 'bg-slate-100 text-slate-600 border-slate-200';

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize',
        style,
        className
      )}
    >
      {status.toLowerCase().replace(/_/g, ' ')}
    </span>
  );
}
