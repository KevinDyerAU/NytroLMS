/**
 * Dashboard Page - NytroAI-style dashboard with KPI widgets and activity feed
 * Matches the NytroAI dashboard layout with cards-based design
 */
import React from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { KPIWidget } from '../components/KPIWidget';
import { useAuth } from '../contexts/AuthContext';
import { Card } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import {
  Users,
  GraduationCap,
  BookOpen,
  ClipboardCheck,
  Activity,
  TrendingUp,
  Clock,
  AlertTriangle,
  CheckCircle2,
  ArrowRight,
  FileText,
  UserPlus,
  Zap,
} from 'lucide-react';
import { Link } from 'react-router-dom';

// Mock data - will be replaced with Laravel API calls
const recentActivity = [
  { id: 1, type: 'enrolment', message: 'Alex Johnson enrolled in Certificate III in Business', time: '2 min ago', icon: UserPlus, color: 'text-[#3b82f6]' },
  { id: 2, type: 'assessment', message: 'Sarah Mitchell submitted Assessment BSBWHS411', time: '15 min ago', icon: ClipboardCheck, color: 'text-[#14b8a6]' },
  { id: 3, type: 'completion', message: 'Mark Davis completed Diploma of Leadership', time: '1 hr ago', icon: CheckCircle2, color: 'text-[#22c55e]' },
  { id: 4, type: 'alert', message: '3 students at risk of disengagement in BSB40120', time: '2 hrs ago', icon: AlertTriangle, color: 'text-[#f59e0b]' },
  { id: 5, type: 'assessment', message: 'New assessment uploaded for BSBCRT411', time: '3 hrs ago', icon: FileText, color: 'text-[#3b82f6]' },
];

const topCourses = [
  { name: 'Certificate III in Business', enrolled: 145, completion: 72 },
  { name: 'Diploma of Leadership & Management', enrolled: 98, completion: 65 },
  { name: 'Certificate IV in Training & Assessment', enrolled: 87, completion: 81 },
  { name: 'Certificate III in Individual Support', enrolled: 76, completion: 58 },
];

export default function Dashboard() {
  const { user } = useAuth();

  return (
    <DashboardLayout title="Dashboard" subtitle={`Welcome back, ${user?.name || 'User'}`}>
      <div className="space-y-6 animate-fade-in-up">
        {/* KPI Widgets */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <KPIWidget
            label="Total Students"
            value="2,847"
            icon={Users}
            trend={{ value: 12, label: 'vs last month' }}
            color="blue"
          />
          <KPIWidget
            label="Active Enrolments"
            value="1,234"
            icon={GraduationCap}
            trend={{ value: 8, label: 'vs last month' }}
            color="teal"
          />
          <KPIWidget
            label="Courses"
            value="48"
            icon={BookOpen}
            trend={{ value: 3, label: 'new this month' }}
            color="amber"
          />
          <KPIWidget
            label="Pending Assessments"
            value="156"
            icon={ClipboardCheck}
            trend={{ value: -5, label: 'vs last week' }}
            color="red"
          />
        </div>

        {/* Main content grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Recent Activity */}
          <Card className="lg:col-span-2 p-0 overflow-hidden border-[#e2e8f0]/50 shadow-card">
            <div className="flex items-center justify-between px-5 py-4 border-b border-[#e2e8f0]">
              <div className="flex items-center gap-2">
                <Activity className="w-4 h-4 text-[#3b82f6]" />
                <h3 className="font-heading font-semibold text-[#1e293b]">Recent Activity</h3>
              </div>
              <Link to="/reports" className="text-sm text-[#3b82f6] hover:underline flex items-center gap-1">
                View all <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            </div>
            <div className="divide-y divide-[#f1f5f9]">
              {recentActivity.map((item) => {
                const Icon = item.icon;
                return (
                  <div key={item.id} className="flex items-start gap-3 px-5 py-3.5 hover:bg-[#f8fafc] transition-colors">
                    <div className="mt-0.5">
                      <Icon className={`w-4 h-4 ${item.color}`} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm text-[#1e293b]">{item.message}</p>
                      <p className="text-xs text-[#94a3b8] mt-0.5 flex items-center gap-1">
                        <Clock className="w-3 h-3" /> {item.time}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </Card>

          {/* Top Courses */}
          <Card className="p-0 overflow-hidden border-[#e2e8f0]/50 shadow-card">
            <div className="flex items-center justify-between px-5 py-4 border-b border-[#e2e8f0]">
              <div className="flex items-center gap-2">
                <TrendingUp className="w-4 h-4 text-[#14b8a6]" />
                <h3 className="font-heading font-semibold text-[#1e293b]">Top Courses</h3>
              </div>
              <Link to="/courses" className="text-sm text-[#3b82f6] hover:underline flex items-center gap-1">
                View all <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            </div>
            <div className="divide-y divide-[#f1f5f9]">
              {topCourses.map((course, i) => (
                <div key={i} className="px-5 py-3.5 hover:bg-[#f8fafc] transition-colors">
                  <div className="flex items-center justify-between mb-1.5">
                    <p className="text-sm font-medium text-[#1e293b] truncate pr-2">{course.name}</p>
                    <span className="text-xs text-[#94a3b8] flex-shrink-0">{course.enrolled} students</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Progress value={course.completion} className="h-1.5 flex-1" />
                    <span className="text-xs font-medium text-[#64748b] w-8 text-right">{course.completion}%</span>
                  </div>
                </div>
              ))}
            </div>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card className="p-5 border-[#e2e8f0]/50 shadow-card">
          <div className="flex items-center gap-2 mb-4">
            <Zap className="w-4 h-4 text-[#f59e0b]" />
            <h3 className="font-heading font-semibold text-[#1e293b]">Quick Actions</h3>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            <Link to="/students">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#3b82f6] hover:bg-[#eff6ff] transition-all">
                <UserPlus className="w-5 h-5 text-[#3b82f6]" />
                <span className="text-xs font-medium">Add Student</span>
              </Button>
            </Link>
            <Link to="/courses">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#14b8a6] hover:bg-[#f0fdfa] transition-all">
                <BookOpen className="w-5 h-5 text-[#14b8a6]" />
                <span className="text-xs font-medium">New Course</span>
              </Button>
            </Link>
            <Link to="/assessments">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#f59e0b] hover:bg-[#fffbeb] transition-all">
                <ClipboardCheck className="w-5 h-5 text-[#f59e0b]" />
                <span className="text-xs font-medium">Review Assessment</span>
              </Button>
            </Link>
            <Link to="/reports">
              <Button variant="outline" className="w-full h-auto py-4 flex flex-col gap-2 border-[#e2e8f0] hover:border-[#8b5cf6] hover:bg-[#f5f3ff] transition-all">
                <FileText className="w-5 h-5 text-[#8b5cf6]" />
                <span className="text-xs font-medium">Generate Report</span>
              </Button>
            </Link>
          </div>
        </Card>
      </div>
    </DashboardLayout>
  );
}
