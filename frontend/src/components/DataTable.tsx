/**
 * NytroLMS DataTable — Reusable data table with search, pagination, and loading states.
 * Design: NytroAI-inspired with glass-morphism cards and smooth transitions.
 */

import { useState, useMemo, useCallback } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Search, ChevronLeft, ChevronRight, Loader2, AlertCircle, RefreshCw, X } from 'lucide-react';

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
  selectable?: boolean;
  bulkActions?: (selectedIds: (number | string)[], clearSelection: () => void) => React.ReactNode;
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
  selectable,
  bulkActions,
}: DataTableProps<T>) {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(0);
  const [selectedIds, setSelectedIds] = useState<Set<number | string>>(new Set());

  const clearSelection = useCallback(() => setSelectedIds(new Set()), []);

  const toggleRow = useCallback((id: number | string) => {
    setSelectedIds(prev => {
      const next = new Set(prev);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  }, []);

  const toggleAll = useCallback((checked: boolean) => {
    if (checked) {
      const ids = pagedDataRef.current.map(r => (r as Record<string, unknown>).id as number | string).filter(Boolean);
      setSelectedIds(new Set(ids));
    } else {
      setSelectedIds(new Set());
    }
  }, []);

  // Ref to avoid circular dependency with toggleAll
  const pagedDataRef = { current: [] as T[] };

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
  pagedDataRef.current = pagedData;

  const allPageSelected = pagedData.length > 0 && pagedData.every(r => {
    const id = (r as Record<string, unknown>).id as number | string;
    return id != null && selectedIds.has(id);
  });
  const somePageSelected = pagedData.some(r => {
    const id = (r as Record<string, unknown>).id as number | string;
    return id != null && selectedIds.has(id);
  });

  const colCount = columns.length + (actions ? 1 : 0) + (selectable ? 1 : 0);

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
      {/* Bulk action bar */}
      {selectable && selectedIds.size > 0 && bulkActions && (
        <div className="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50/80 px-4 py-2.5 animate-fade-in-up">
          <span className="text-sm font-medium text-blue-700">
            {selectedIds.size} selected
          </span>
          <div className="flex items-center gap-2">
            {bulkActions(Array.from(selectedIds), clearSelection)}
          </div>
          <Button variant="ghost" size="sm" className="ml-auto h-7 text-xs text-blue-600" onClick={clearSelection}>
            <X className="w-3 h-3 mr-1" /> Clear
          </Button>
        </div>
      )}

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
                {selectable && (
                  <th className="px-3 py-3 w-10">
                    <Checkbox
                      checked={allPageSelected ? true : somePageSelected ? 'indeterminate' : false}
                      onCheckedChange={(c) => toggleAll(c === true)}
                      aria-label="Select all"
                    />
                  </th>
                )}
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
                  <td colSpan={colCount} className="py-16 text-center">
                    <Loader2 className="mx-auto h-6 w-6 animate-spin text-blue-500" />
                    <p className="mt-2 text-sm text-muted-foreground">Loading data...</p>
                  </td>
                </tr>
              ) : pagedData.length === 0 ? (
                <tr>
                  <td colSpan={colCount} className="py-16 text-center">
                    <p className="text-sm text-muted-foreground">{emptyMessage}</p>
                  </td>
                </tr>
              ) : (
                pagedData.map((row, idx) => {
                  const rowId = (row as Record<string, unknown>).id as number | string;
                  const isSelected = rowId != null && selectedIds.has(rowId);
                  return (
                    <tr
                      key={rowId ?? idx}
                      className={`border-b border-slate-50 transition-colors ${isSelected ? 'bg-blue-50/50' : 'hover:bg-blue-50/30'}`}
                    >
                      {selectable && (
                        <td className="px-3 py-3 w-10">
                          <Checkbox
                            checked={isSelected}
                            onCheckedChange={() => rowId != null && toggleRow(rowId)}
                            aria-label="Select row"
                          />
                        </td>
                      )}
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
                  );
                })
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
