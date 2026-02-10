/**
 * All Records / Activity Timeline Page
 * Global activity feed with search, event type filter, and date range filter.
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchActivityFeed } from '@/lib/api';
import {
  Search, Activity, Loader2, AlertCircle, RefreshCw,
  ChevronLeft, ChevronRight, User, Calendar,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const EVENT_TYPES = [
  { value: 'all', label: 'All Events' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
  { value: 'logged in', label: 'Logged In' },
  { value: 'activated', label: 'Activated' },
  { value: 'deactivated', label: 'Deactivated' },
];

function eventColor(desc: string | null): string {
  if (!desc) return 'bg-slate-100 text-slate-600';
  const d = desc.toLowerCase();
  if (d.includes('created') || d.includes('added')) return 'bg-emerald-100 text-emerald-700';
  if (d.includes('updated') || d.includes('changed')) return 'bg-blue-100 text-blue-700';
  if (d.includes('deleted') || d.includes('removed')) return 'bg-red-100 text-red-700';
  if (d.includes('logged')) return 'bg-violet-100 text-violet-700';
  if (d.includes('activated')) return 'bg-amber-100 text-amber-700';
  if (d.includes('completed') || d.includes('passed')) return 'bg-emerald-100 text-emerald-700';
  return 'bg-slate-100 text-slate-600';
}

function formatSubjectType(type: string | null): string {
  if (!type) return '';
  return type.replace(/^App\\\\Models\\\\/, '').replace(/^App\\Models\\/, '');
}

const LIMIT = 50;

export default function AllRecords() {
  const [search, setSearch] = useState('');
  const [eventType, setEventType] = useState('all');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [page, setPage] = useState(0);

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchActivityFeed({ search, eventType, dateFrom: dateFrom || undefined, dateTo: dateTo || undefined, limit: LIMIT, offset: page * LIMIT }),
    [search, eventType, dateFrom, dateTo, page]
  );

  const activities = data?.data ?? [];
  const total = data?.total ?? 0;
  const totalPages = Math.ceil(total / LIMIT);

  return (
    <DashboardLayout title="Activity Timeline" subtitle="Global activity feed across all records">
      <div className="space-y-4 animate-fade-in-up">
        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="flex items-center gap-3 w-full sm:w-auto flex-wrap">
            <div className="relative w-full sm:w-72">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
              <Input
                placeholder="Search activities..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(0); }}
                className="pl-9 border-[#e2e8f0] h-9"
              />
            </div>
            <Select value={eventType} onValueChange={(v) => { setEventType(v); setPage(0); }}>
              <SelectTrigger className="w-36 h-9 border-[#e2e8f0]">
                <SelectValue placeholder="All Events" />
              </SelectTrigger>
              <SelectContent>
                {EVENT_TYPES.map(t => (
                  <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input
              type="date"
              value={dateFrom}
              onChange={(e) => { setDateFrom(e.target.value); setPage(0); }}
              className="w-36 h-9 border-[#e2e8f0] text-sm"
              placeholder="From"
            />
            <Input
              type="date"
              value={dateTo}
              onChange={(e) => { setDateTo(e.target.value); setPage(0); }}
              className="w-36 h-9 border-[#e2e8f0] text-sm"
              placeholder="To"
            />
          </div>
          <Button variant="outline" size="sm" className="border-[#e2e8f0] text-[#64748b]" onClick={refetch}>
            <RefreshCw className="w-4 h-4 mr-1" /> Refresh
          </Button>
        </div>

        {/* Error */}
        {error && (
          <Card className="p-6 border-red-200 bg-red-50">
            <div className="flex items-center gap-3 text-red-700">
              <AlertCircle className="w-5 h-5" />
              <p className="text-sm">{error}</p>
              <Button variant="outline" size="sm" onClick={refetch}>Retry</Button>
            </div>
          </Card>
        )}

        {/* Loading */}
        {loading && (
          <Card className="p-12 flex items-center justify-center border-[#e2e8f0]/50">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
            <span className="ml-3 text-sm text-[#64748b]">Loading activity...</span>
          </Card>
        )}

        {/* Timeline */}
        {!loading && !error && (
          <>
            {activities.length === 0 ? (
              <Card className="p-8 text-center border-[#e2e8f0]/50">
                <Activity className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
                <h3 className="text-lg font-semibold text-[#1e293b]">No activity found</h3>
                <p className="mt-1 text-sm text-[#64748b]">Try adjusting your filters or date range.</p>
              </Card>
            ) : (
              <div className="relative">
                {/* Timeline line */}
                <div className="absolute left-[23px] top-2 bottom-2 w-px bg-[#e2e8f0]" />

                <div className="space-y-1">
                  {activities.map((activity) => {
                    const color = eventColor(activity.description);
                    const model = formatSubjectType(activity.subject_type);
                    return (
                      <div key={activity.id} className="flex gap-4 group relative">
                        {/* Timeline dot */}
                        <div className="flex-shrink-0 w-[47px] flex justify-center pt-3 z-10">
                          <div className={cn(
                            "w-3 h-3 rounded-full border-2 border-white shadow-sm",
                            color.split(' ')[0]
                          )} />
                        </div>

                        {/* Card */}
                        <Card className="flex-1 px-4 py-3 border-[#e2e8f0]/50 hover:shadow-sm transition-shadow my-0.5">
                          <div className="flex items-start justify-between gap-3">
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2 flex-wrap">
                                <span className={cn("text-[10px] font-semibold px-1.5 py-0.5 rounded", color)}>
                                  {activity.description ?? activity.event ?? 'Activity'}
                                </span>
                                {model && (
                                  <span className="text-[10px] text-[#94a3b8] font-medium">
                                    {model}{activity.subject_id ? ` #${activity.subject_id}` : ''}
                                  </span>
                                )}
                              </div>
                              {activity.subject_name && (
                                <p className="text-sm text-[#1e293b] mt-1 font-medium">
                                  <User className="w-3 h-3 inline mr-1 text-[#94a3b8]" />
                                  {activity.subject_name}
                                </p>
                              )}
                              {activity.event && activity.event !== activity.description && (
                                <p className="text-xs text-[#64748b] mt-0.5">{activity.event}</p>
                              )}
                            </div>
                            <div className="text-right flex-shrink-0">
                              <p className="text-[10px] text-[#94a3b8] whitespace-nowrap">
                                <Calendar className="w-3 h-3 inline mr-0.5" />
                                {activity.created_at
                                  ? new Date(activity.created_at).toLocaleString('en-AU', {
                                      day: 'numeric', month: 'short', year: 'numeric',
                                      hour: '2-digit', minute: '2-digit',
                                    })
                                  : '—'}
                              </p>
                              {activity.causer_name && (
                                <p className="text-[10px] text-[#94a3b8] mt-0.5">
                                  by {activity.causer_name}
                                </p>
                              )}
                            </div>
                          </div>
                        </Card>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="flex items-center justify-between px-1 py-2">
                <p className="text-xs text-[#94a3b8]">
                  Showing {page * LIMIT + 1}–{Math.min((page + 1) * LIMIT, total)} of {total}
                </p>
                <div className="flex items-center gap-1">
                  <Button variant="ghost" size="sm" disabled={page === 0} onClick={() => setPage(p => p - 1)}>
                    <ChevronLeft className="h-4 w-4" />
                  </Button>
                  <span className="px-2 text-xs text-[#94a3b8]">Page {page + 1} of {totalPages}</span>
                  <Button variant="ghost" size="sm" disabled={page >= totalPages - 1} onClick={() => setPage(p => p + 1)}>
                    <ChevronRight className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </DashboardLayout>
  );
}
