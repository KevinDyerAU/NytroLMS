/**
 * NytroLMS DataTable — Reusable data table with search, pagination, and loading states.
 * Design: NytroAI-inspired with glass-morphism cards and smooth transitions.
 */

import { useState, useMemo } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search, ChevronLeft, ChevronRight, Loader2, AlertCircle, RefreshCw } from 'lucide-react';

interface Column<T> {
  key: string;
  label: string;
  render?: (row: T) => React.ReactNode;
  className?: string;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  data: T[];
  total?: number;
  loading?: boolean;
  error?: string | null;
  searchPlaceholder?: string;
  onSearch?: (query: string) => void;
  onRefetch?: () => void;
  pageSize?: number;
  emptyMessage?: string;
  actions?: (row: T) => React.ReactNode;
  headerActions?: React.ReactNode;
  filterSlot?: React.ReactNode;
}

export function DataTable<T extends { id?: number | string }>({
  columns,
  data,
  total,
  loading,
  error,
  searchPlaceholder = 'Search...',
  onSearch,
  onRefetch,
  pageSize = 25,
  emptyMessage = 'No data found',
  actions,
  headerActions,
  filterSlot,
}: DataTableProps<T>) {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(0);

  const filteredData = useMemo(() => {
    if (!search || onSearch) return data;
    const s = search.toLowerCase();
    return data.filter(row =>
      columns.some(col => {
        const val = (row as Record<string, unknown>)[col.key];
        return val != null && String(val).toLowerCase().includes(s);
      })
    );
  }, [data, search, columns, onSearch]);

  const pagedData = useMemo(() => {
    const start = page * pageSize;
    return filteredData.slice(start, start + pageSize);
  }, [filteredData, page, pageSize]);

  const totalPages = Math.ceil((total ?? filteredData.length) / pageSize);

  const handleSearch = (value: string) => {
    setSearch(value);
    setPage(0);
    if (onSearch) onSearch(value);
  };

  if (error) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center">
        <AlertCircle className="mx-auto mb-3 h-8 w-8 text-red-400" />
        <p className="text-sm font-medium text-red-700">Failed to load data</p>
        <p className="mt-1 text-xs text-red-500">{error}</p>
        {onRefetch && (
          <Button variant="outline" size="sm" className="mt-4" onClick={onRefetch}>
            <RefreshCw className="mr-2 h-3.5 w-3.5" /> Retry
          </Button>
        )}
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Header bar */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder={searchPlaceholder}
            value={search}
            onChange={e => handleSearch(e.target.value)}
            className="pl-9 bg-white/60 backdrop-blur-sm border-slate-200"
          />
        </div>
        <div className="flex items-center gap-2">
          {filterSlot}
          {headerActions}
        </div>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-slate-200/60 bg-white/70 backdrop-blur-sm shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/80">
                {columns.map(col => (
                  <th
                    key={col.key}
                    className={`px-4 py-3 text-left font-semibold text-slate-600 whitespace-nowrap ${col.className ?? ''}`}
                  >
                    {col.label}
                  </th>
                ))}
                {actions && <th className="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>}
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={columns.length + (actions ? 1 : 0)} className="py-16 text-center">
                    <Loader2 className="mx-auto h-6 w-6 animate-spin text-blue-500" />
                    <p className="mt-2 text-sm text-muted-foreground">Loading data...</p>
                  </td>
                </tr>
              ) : pagedData.length === 0 ? (
                <tr>
                  <td colSpan={columns.length + (actions ? 1 : 0)} className="py-16 text-center">
                    <p className="text-sm text-muted-foreground">{emptyMessage}</p>
                  </td>
                </tr>
              ) : (
                pagedData.map((row, idx) => (
                  <tr
                    key={(row as Record<string, unknown>).id as string ?? idx}
                    className="border-b border-slate-50 transition-colors hover:bg-blue-50/30"
                  >
                    {columns.map(col => (
                      <td key={col.key} className={`px-4 py-3 text-slate-700 ${col.className ?? ''}`}>
                        {col.render
                          ? col.render(row)
                          : String((row as Record<string, unknown>)[col.key] ?? '—')}
                      </td>
                    ))}
                    {actions && (
                      <td className="px-4 py-3 text-right">{actions(row)}</td>
                    )}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between border-t border-slate-100 px-4 py-3 bg-slate-50/50">
            <p className="text-xs text-muted-foreground">
              Showing {page * pageSize + 1}–{Math.min((page + 1) * pageSize, total ?? filteredData.length)} of{' '}
              {total ?? filteredData.length}
            </p>
            <div className="flex items-center gap-1">
              <Button
                variant="ghost"
                size="sm"
                disabled={page === 0}
                onClick={() => setPage(p => p - 1)}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <span className="px-2 text-xs text-muted-foreground">
                Page {page + 1} of {totalPages}
              </span>
              <Button
                variant="ghost"
                size="sm"
                disabled={page >= totalPages - 1}
                onClick={() => setPage(p => p + 1)}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
