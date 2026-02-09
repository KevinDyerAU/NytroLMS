/**
 * Audit logging helper for Edge Functions.
 * Inserts records into the activity_log table.
 */

import { getAdminClient } from './db.ts';

export interface AuditEntry {
  logName: string;
  description: string;
  subjectType: string;
  subjectId: number;
  causerId: number | null;
  event?: string;
  properties?: Record<string, unknown>;
}

/**
 * Writes an audit log entry to the activity_log table.
 */
export async function writeAuditLog(entry: AuditEntry): Promise<void> {
  const adminClient = getAdminClient();

  const { error } = await adminClient.from('activity_log').insert({
    log_name: entry.logName,
    description: entry.description,
    subject_type: entry.subjectType,
    subject_id: entry.subjectId,
    event: entry.event ?? entry.description,
    causer_type: entry.causerId ? 'user' : null,
    causer_id: entry.causerId,
    properties: entry.properties ? JSON.stringify(entry.properties) : null,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  });

  if (error) {
    console.error('Failed to write audit log:', error.message);
  }
}
