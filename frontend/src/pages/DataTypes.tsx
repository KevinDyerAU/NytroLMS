/**
 * Data Types Page — Custom categories, tags, metadata
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Database } from 'lucide-react';

export default function DataTypes() {
  return (
    <DashboardLayout title="Data Types" subtitle="Custom categories, tags, and metadata">
      <div className="space-y-6 animate-fade-in-up">
        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <Database className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">Data Types</h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            Manage custom categories, tags, and metadata types used across the system.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
