/**
 * To Do Page — Aggregated task list with priority indicators
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { CheckSquare, Clock, AlertTriangle, ArrowRight } from 'lucide-react';

export default function ToDo() {
  return (
    <DashboardLayout title="To Do" subtitle="Your tasks and activities">
      <div className="space-y-6 animate-fade-in-up">
        {/* Placeholder summary cards */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <Card className="p-5 border-red-100 bg-red-50/50">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <AlertTriangle className="w-5 h-5 text-red-500" />
              </div>
              <div>
                <p className="text-2xl font-bold text-red-600">0</p>
                <p className="text-xs text-red-500 font-medium">Overdue</p>
              </div>
            </div>
          </Card>
          <Card className="p-5 border-orange-100 bg-orange-50/50">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                <Clock className="w-5 h-5 text-orange-500" />
              </div>
              <div>
                <p className="text-2xl font-bold text-orange-600">0</p>
                <p className="text-xs text-orange-500 font-medium">Due This Week</p>
              </div>
            </div>
          </Card>
          <Card className="p-5 border-blue-100 bg-blue-50/50">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <CheckSquare className="w-5 h-5 text-blue-500" />
              </div>
              <div>
                <p className="text-2xl font-bold text-blue-600">0</p>
                <p className="text-xs text-blue-500 font-medium">Completed</p>
              </div>
            </div>
          </Card>
        </div>

        {/* Task list placeholder */}
        <Card className="p-8 text-center border-[#e2e8f0]/50">
          <CheckSquare className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
          <h3 className="text-lg font-semibold text-[#1e293b]">No tasks yet</h3>
          <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
            Tasks from assessments, activities, and forms will appear here once the system is fully configured.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
