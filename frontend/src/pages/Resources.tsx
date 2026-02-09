/**
 * Resources Page — Document library and media assets
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FolderOpen, Upload } from 'lucide-react';

export default function Resources() {
  return (
    <DashboardLayout title="Resources" subtitle="Document library and media assets">
      <div className="space-y-4 animate-fade-in-up">
        <div className="flex items-center justify-between">
          <div />
          <Button className="bg-[#3b82f6] hover:bg-[#2563eb] text-white gap-2">
            <Upload className="w-4 h-4" />
            Upload Resource
          </Button>
        </div>

        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <FolderOpen className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">No resources yet</h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            Upload documents, templates, and media assets to share across courses and learners.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
