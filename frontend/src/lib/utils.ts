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
