/**
 * All Records Page — Comprehensive searchable/filterable view with tabs
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { FileText, Filter, Search, ClipboardCheck, BookOpen, FileCheck, Download } from 'lucide-react';
import { cn } from '@/lib/utils';

const tabs = [
  { id: 'activities', label: 'Activities', icon: ClipboardCheck },
  { id: 'units', label: 'Units', icon: BookOpen },
  { id: 'forms', label: 'Forms', icon: FileCheck },
  { id: 'exports', label: 'Exports', icon: Download },
] as const;

type TabId = typeof tabs[number]['id'];

export default function AllRecords() {
  const [activeTab, setActiveTab] = useState<TabId>('activities');
  const [search, setSearch] = useState('');

  return (
    <DashboardLayout title="All Records" subtitle="Search and filter across all data">
      <div className="space-y-4 animate-fade-in-up">
        {/* Search and filter bar */}
        <div className="flex items-center gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input
              placeholder="Search records..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-10 h-10 border-[#e2e8f0]"
            />
          </div>
          <Button variant="outline" className="h-10 gap-2 border-[#e2e8f0]">
            <Filter className="w-4 h-4" />
            Filter
          </Button>
        </div>

        {/* Tabs */}
        <div className="flex border-b border-[#e2e8f0]">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={cn(
                  "flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px",
                  activeTab === tab.id
                    ? "border-[#3b82f6] text-[#3b82f6]"
                    : "border-transparent text-[#64748b] hover:text-[#1e293b]"
                )}
              >
                <Icon className="w-4 h-4" />
                {tab.label}
              </button>
            );
          })}
        </div>

        {/* Tab content */}
        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <FileText className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">
            {activeTab === 'activities' && 'Activity Records'}
            {activeTab === 'units' && 'Unit Records'}
            {activeTab === 'forms' && 'Form Records'}
            {activeTab === 'exports' && 'Export History'}
          </h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            {activeTab === 'activities' && 'Assessment tasks, quiz attempts, and submissions will appear here.'}
            {activeTab === 'units' && 'Competency units and progress tracking records will appear here.'}
            {activeTab === 'forms' && 'Enrolment forms, compliance documents, and consent forms will appear here.'}
            {activeTab === 'exports' && 'Generated reports and data exports will appear here.'}
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
