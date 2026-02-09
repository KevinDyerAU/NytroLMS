/**
 * Communications Page — Email templates, notifications, Slack integration
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Mail } from 'lucide-react';

export default function Communications() {
  return (
    <DashboardLayout title="Communications" subtitle="Email templates and notification settings">
      <div className="space-y-6 animate-fade-in-up">
        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <Mail className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">Communications Settings</h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            Configure email templates, notification preferences, and Slack integration settings.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
