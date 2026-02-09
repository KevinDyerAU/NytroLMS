/**
 * Automation Page — Workflows, quality checks, scheduled tasks
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Zap } from 'lucide-react';

export default function Automation() {
  return (
    <DashboardLayout title="Automation" subtitle="Workflows and scheduled tasks">
      <div className="space-y-6 animate-fade-in-up">
        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <Zap className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">Automation Settings</h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            Configure automated workflows, quality check rules, and scheduled background tasks.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
