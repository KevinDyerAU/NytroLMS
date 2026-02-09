/**
 * Organisation Page — AVETMISS settings and reporting configuration
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Building } from 'lucide-react';

export default function Organisation() {
  return (
    <DashboardLayout title="Organisation" subtitle="AVETMISS and reporting configuration">
      <div className="space-y-6 animate-fade-in-up">
        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <Building className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">Organisation Settings</h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            Configure your RTO details, AVETMISS reporting settings, and national/state reporting preferences.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
