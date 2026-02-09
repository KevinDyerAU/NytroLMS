import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Export data to CSV and trigger download
 */
export function exportToCSV(
  data: Record<string, unknown>[],
  filename: string,
  headers?: { key: string; label: string }[]
): void {
  if (data.length === 0) {
    return;
  }

  // Determine columns from headers or data keys
  const columns = headers
    ? headers.map(h => ({ key: h.key, label: h.label }))
    : Object.keys(data[0]).map(key => ({ key, label: key }));

  // Build CSV content
  const csvRows: string[] = [];

  // Header row
  csvRows.push(columns.map(c => `"${c.label}"`).join(','));

  // Data rows
  for (const row of data) {
    const values = columns.map(col => {
      const value = row[col.key];
      if (value === null || value === undefined) {
        return '""';
      }
      // Escape quotes and wrap in quotes
      const stringValue = String(value).replace(/"/g, '""');
      return `"${stringValue}"`;
    });
    csvRows.push(values.join(','));
  }

  // Create blob and download
  const csvContent = csvRows.join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

/**
 * Export data to a styled PDF via browser print dialog.
 * Opens a new window with an HTML table and triggers print.
 */
export function exportToPDF(
  data: Record<string, unknown>[],
  title: string,
  headers?: { key: string; label: string }[]
): void {
  if (data.length === 0) return;

  const columns = headers
    ? headers.map(h => ({ key: h.key, label: h.label }))
    : Object.keys(data[0]).map(key => ({ key, label: key.replace(/_/g, ' ') }));

  const headerRow = columns.map(c => `<th>${esc(c.label)}</th>`).join('');
  const bodyRows = data.map(row => {
    const cells = columns.map(col => {
      const v = row[col.key];
      return `<td>${v === null || v === undefined ? '' : esc(String(v))}</td>`;
    }).join('');
    return `<tr>${cells}</tr>`;
  }).join('');

  const html = `<!DOCTYPE html>
<html><head><title>${esc(title)}</title>
<style>
  @page { size: landscape; margin: 12mm; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 10px; color: #1e293b; padding: 0; margin: 0; }
  h1 { font-size: 16px; margin: 0 0 4px 0; color: #1e293b; }
  .meta { font-size: 9px; color: #64748b; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; }
  th { background: #f1f5f9; padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
  td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 10px; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
  tr:nth-child(even) { background: #f8fafc; }
  .footer { margin-top: 12px; font-size: 8px; color: #94a3b8; text-align: right; }
</style>
</head><body>
<h1>${esc(title)}</h1>
<div class="meta">Generated ${new Date().toLocaleString()} &bull; ${data.length} records</div>
<table><thead><tr>${headerRow}</tr></thead><tbody>${bodyRows}</tbody></table>
<div class="footer">NytroLMS Report</div>
<script>window.onload=function(){window.print();}</script>
</body></html>`;

  const win = window.open('', '_blank');
  if (win) {
    win.document.write(html);
    win.document.close();
  }
}

function esc(s: string): string {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
