/**
 * Reports Page - Report generation and analytics
 */
import React from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  BarChart3, FileText, Users, GraduationCap, TrendingUp,
  Download, Calendar, ArrowRight, ClipboardCheck, Building2,
} from 'lucide-react';
import { toast } from 'sonner';

const reportCategories = [
  {
    title: 'Enrolment Reports',
    icon: GraduationCap,
    color: 'text-[#3b82f6]',
    bg: 'bg-[#eff6ff]',
    reports: [
      { name: 'Daily Enrolment Report', description: 'Daily summary of new enrolments' },
      { name: 'Enrolment Summary', description: 'Overview of all active enrolments' },
      { name: 'Commenced Units Report', description: 'Units commenced by students' },
    ],
  },
  {
    title: 'Student Reports',
    icon: Users,
    color: 'text-[#14b8a6]',
    bg: 'bg-[#f0fdfa]',
    reports: [
      { name: 'Active Students', description: 'Currently active student list' },
      { name: 'Disengaged Students', description: 'Students at risk of disengagement' },
      { name: 'Student Progress', description: 'Progress tracking across all courses' },
    ],
  },
  {
    title: 'Assessment Reports',
    icon: ClipboardCheck,
    color: 'text-[#f59e0b]',
    bg: 'bg-[#fffbeb]',
    reports: [
      { name: 'Pending Assessments', description: 'Assessments awaiting review' },
      { name: 'Competency Report', description: 'Competency outcomes by unit' },
      { name: 'Assessment Analytics', description: 'Trends and patterns in assessments' },
    ],
  },
  {
    title: 'Company Reports',
    icon: Building2,
    color: 'text-[#8b5cf6]',
    bg: 'bg-[#f5f3ff]',
    reports: [
      { name: 'Company Summary', description: 'Overview of company partnerships' },
      { name: 'Work Placements', description: 'Active work placement tracking' },
    ],
  },
];

export default function Reports() {
  return (
    <DashboardLayout title="Reports" subtitle="Generate and download reports">
      <div className="space-y-6 animate-fade-in-up">
        {reportCategories.map((category) => {
          const Icon = category.icon;
          return (
            <Card key={category.title} className="p-5 border-[#e2e8f0]/50 shadow-card">
              <div className="flex items-center gap-3 mb-4">
                <div className={`p-2 rounded-lg ${category.bg}`}>
                  <Icon className={`w-5 h-5 ${category.color}`} />
                </div>
                <h3 className="font-heading font-semibold text-[#1e293b]">{category.title}</h3>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                {category.reports.map((report) => (
                  <div
                    key={report.name}
                    className="flex items-center justify-between p-3 rounded-lg border border-[#e2e8f0] hover:border-[#3b82f6] hover:bg-[#f8fafc] transition-all cursor-pointer group"
                    onClick={() => toast('Report generation coming soon')}
                  >
                    <div>
                      <p className="text-sm font-medium text-[#1e293b] group-hover:text-[#3b82f6] transition-colors">{report.name}</p>
                      <p className="text-xs text-[#94a3b8] mt-0.5">{report.description}</p>
                    </div>
                    <ArrowRight className="w-4 h-4 text-[#94a3b8] group-hover:text-[#3b82f6] transition-colors flex-shrink-0" />
                  </div>
                ))}
              </div>
            </Card>
          );
        })}
      </div>
    </DashboardLayout>
  );
}
