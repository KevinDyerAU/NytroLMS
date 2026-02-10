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

/**
 * Generate and print a course completion certificate as a styled HTML page.
 */
export function generateCertificate(params: {
  studentName: string;
  courseName: string;
  courseCode?: string | null;
  completionDate: string;
  certificateId?: string;
}): void {
  const { studentName, courseName, courseCode, completionDate } = params;
  const certId = params.certificateId ?? `CERT-${Date.now().toString(36).toUpperCase()}`;
  const fmtDate = new Date(completionDate).toLocaleDateString('en-AU', {
    day: 'numeric', month: 'long', year: 'numeric',
  });

  const html = `<!DOCTYPE html>
<html><head><title>Certificate of Completion</title>
<style>
  @page { size: landscape; margin: 0; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Georgia', 'Times New Roman', serif; background: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .cert { width: 297mm; height: 210mm; position: relative; padding: 20mm 30mm; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
  .border-outer { position: absolute; inset: 8mm; border: 3px solid #1e40af; border-radius: 4px; }
  .border-inner { position: absolute; inset: 11mm; border: 1px solid #93c5fd; border-radius: 2px; }
  .corner { position: absolute; width: 40px; height: 40px; }
  .corner svg { width: 100%; height: 100%; }
  .corner-tl { top: 14mm; left: 14mm; }
  .corner-tr { top: 14mm; right: 14mm; transform: rotate(90deg); }
  .corner-bl { bottom: 14mm; left: 14mm; transform: rotate(-90deg); }
  .corner-br { bottom: 14mm; right: 14mm; transform: rotate(180deg); }
  .logo-area { margin-bottom: 8mm; }
  .logo-text { font-size: 14px; font-weight: 700; letter-spacing: 6px; text-transform: uppercase; color: #1e40af; }
  .title { font-size: 38px; font-weight: 400; color: #1e293b; margin-bottom: 3mm; letter-spacing: 2px; }
  .subtitle { font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 10mm; }
  .presented { font-size: 13px; color: #94a3b8; margin-bottom: 4mm; }
  .student-name { font-size: 32px; font-weight: 400; color: #1e293b; border-bottom: 2px solid #3b82f6; padding-bottom: 3mm; margin-bottom: 6mm; min-width: 200px; display: inline-block; font-style: italic; }
  .completion-text { font-size: 13px; color: #64748b; line-height: 1.8; max-width: 500px; margin-bottom: 8mm; }
  .course-name { font-size: 20px; font-weight: 700; color: #1e40af; margin-bottom: 2mm; }
  .course-code { font-size: 12px; color: #94a3b8; margin-bottom: 6mm; }
  .details-row { display: flex; justify-content: space-between; width: 100%; max-width: 600px; margin-top: 10mm; padding-top: 6mm; }
  .detail { text-align: center; }
  .detail-line { width: 150px; border-top: 1px solid #cbd5e1; margin: 0 auto 3mm; }
  .detail-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
  .detail-value { font-size: 12px; color: #1e293b; margin-bottom: 2mm; }
  .cert-id { position: absolute; bottom: 15mm; right: 35mm; font-size: 8px; color: #cbd5e1; font-family: monospace; }
  @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head><body>
<div class="cert">
  <div class="border-outer"></div>
  <div class="border-inner"></div>
  <div class="corner corner-tl"><svg viewBox="0 0 40 40"><path d="M0 40 L0 8 Q0 0 8 0 L40 0" fill="none" stroke="#1e40af" stroke-width="2"/></svg></div>
  <div class="corner corner-tr"><svg viewBox="0 0 40 40"><path d="M0 40 L0 8 Q0 0 8 0 L40 0" fill="none" stroke="#1e40af" stroke-width="2"/></svg></div>
  <div class="corner corner-bl"><svg viewBox="0 0 40 40"><path d="M0 40 L0 8 Q0 0 8 0 L40 0" fill="none" stroke="#1e40af" stroke-width="2"/></svg></div>
  <div class="corner corner-br"><svg viewBox="0 0 40 40"><path d="M0 40 L0 8 Q0 0 8 0 L40 0" fill="none" stroke="#1e40af" stroke-width="2"/></svg></div>

  <div class="logo-area"><span class="logo-text">NytroLMS</span></div>
  <div class="title">Certificate</div>
  <div class="subtitle">of Completion</div>
  <div class="presented">This is to certify that</div>
  <div class="student-name">${esc(studentName)}</div>
  <div class="completion-text">has successfully completed all requirements for the following course:</div>
  <div class="course-name">${esc(courseName)}</div>
  ${courseCode ? `<div class="course-code">${esc(courseCode)}</div>` : ''}
  <div class="details-row">
    <div class="detail">
      <div class="detail-value">${fmtDate}</div>
      <div class="detail-line"></div>
      <div class="detail-label">Date of Completion</div>
    </div>
    <div class="detail">
      <div class="detail-value">${esc(certId)}</div>
      <div class="detail-line"></div>
      <div class="detail-label">Certificate Number</div>
    </div>
  </div>
  <div class="cert-id">${esc(certId)}</div>
</div>
<script>window.onload=function(){window.print();}</script>
</body></html>`;

  const win = window.open('', '_blank');
  if (win) {
    win.document.write(html);
    win.document.close();
  }
}
