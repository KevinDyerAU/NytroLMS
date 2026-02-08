/**
 * Courses Page - Course management with card grid
 * NytroAI design: clean cards with progress indicators
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import {
  Search, Plus, BookOpen, Users, Clock, MoreHorizontal,
  Grid3X3, List, ChevronRight,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const mockCourses = [
  { id: 1, code: 'BSB30120', name: 'Certificate III in Business', students: 145, units: 13, completion: 72, status: 'active', duration: '12 months' },
  { id: 2, code: 'BSB50420', name: 'Diploma of Leadership and Management', students: 98, units: 12, completion: 65, status: 'active', duration: '18 months' },
  { id: 3, code: 'TAE40122', name: 'Certificate IV in Training and Assessment', students: 87, units: 10, completion: 81, status: 'active', duration: '12 months' },
  { id: 4, code: 'CHC33021', name: 'Certificate III in Individual Support', students: 76, units: 14, completion: 58, status: 'active', duration: '12 months' },
  { id: 5, code: 'BSB40120', name: 'Certificate IV in Business', students: 54, units: 10, completion: 45, status: 'active', duration: '12 months' },
  { id: 6, code: 'BSB60420', name: 'Advanced Diploma of Leadership and Management', students: 32, units: 8, completion: 90, status: 'active', duration: '24 months' },
];

export default function Courses() {
  const [search, setSearch] = useState('');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

  const filtered = mockCourses.filter(c =>
    c.name.toLowerCase().includes(search.toLowerCase()) ||
    c.code.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <DashboardLayout title="Courses" subtitle="Manage training courses and qualifications">
      <div className="space-y-4 animate-fade-in-up">
        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input
              placeholder="Search courses..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9 border-[#e2e8f0] h-9"
            />
          </div>
          <div className="flex items-center gap-2">
            <div className="flex border border-[#e2e8f0] rounded-lg overflow-hidden">
              <button
                onClick={() => setViewMode('grid')}
                className={cn("p-1.5", viewMode === 'grid' ? 'bg-[#eff6ff] text-[#3b82f6]' : 'text-[#94a3b8] hover:bg-[#f8fafc]')}
              >
                <Grid3X3 className="w-4 h-4" />
              </button>
              <button
                onClick={() => setViewMode('list')}
                className={cn("p-1.5", viewMode === 'list' ? 'bg-[#eff6ff] text-[#3b82f6]' : 'text-[#94a3b8] hover:bg-[#f8fafc]')}
              >
                <List className="w-4 h-4" />
              </button>
            </div>
            <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add course coming soon')}>
              <Plus className="w-4 h-4 mr-1.5" /> Add Course
            </Button>
          </div>
        </div>

        {/* Course Grid */}
        {viewMode === 'grid' ? (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {filtered.map((course) => (
              <Card key={course.id} className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow group cursor-pointer" onClick={() => toast('Course details coming soon')}>
                <div className="flex items-start justify-between mb-3">
                  <div className="p-2 rounded-lg bg-[#eff6ff]">
                    <BookOpen className="w-5 h-5 text-[#3b82f6]" />
                  </div>
                  <Badge variant="outline" className="bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20 text-xs capitalize">
                    {course.status}
                  </Badge>
                </div>
                <h3 className="font-heading font-semibold text-[#1e293b] text-sm mb-1 group-hover:text-[#3b82f6] transition-colors">
                  {course.name}
                </h3>
                <p className="text-xs text-[#94a3b8] mb-4">{course.code} &middot; {course.units} units &middot; {course.duration}</p>
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-[#64748b]">Completion</span>
                    <span className="font-medium text-[#1e293b]">{course.completion}%</span>
                  </div>
                  <Progress value={course.completion} className="h-1.5" />
                </div>
                <div className="flex items-center justify-between mt-4 pt-3 border-t border-[#f1f5f9]">
                  <div className="flex items-center gap-1 text-xs text-[#94a3b8]">
                    <Users className="w-3.5 h-3.5" /> {course.students} students
                  </div>
                  <ChevronRight className="w-4 h-4 text-[#94a3b8] group-hover:text-[#3b82f6] transition-colors" />
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <Card className="overflow-hidden border-[#e2e8f0]/50 shadow-card">
            <table className="w-full">
              <thead>
                <tr className="border-b border-[#e2e8f0] bg-[#f8fafc]">
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Course</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden md:table-cell">Code</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Students</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider hidden lg:table-cell">Progress</th>
                  <th className="text-left px-4 py-3 text-xs font-semibold text-[#64748b] uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[#f1f5f9]">
                {filtered.map((course) => (
                  <tr key={course.id} className="hover:bg-[#f8fafc] transition-colors cursor-pointer" onClick={() => toast('Course details coming soon')}>
                    <td className="px-4 py-3">
                      <p className="text-sm font-medium text-[#1e293b]">{course.name}</p>
                      <p className="text-xs text-[#94a3b8] md:hidden">{course.code}</p>
                    </td>
                    <td className="px-4 py-3 hidden md:table-cell text-sm text-[#64748b]">{course.code}</td>
                    <td className="px-4 py-3 text-sm text-[#64748b]">{course.students}</td>
                    <td className="px-4 py-3 hidden lg:table-cell">
                      <div className="flex items-center gap-2 w-24">
                        <Progress value={course.completion} className="h-1.5 flex-1" />
                        <span className="text-xs text-[#64748b]">{course.completion}%</span>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <Badge variant="outline" className="bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20 text-xs capitalize">
                        {course.status}
                      </Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Card>
        )}
      </div>
    </DashboardLayout>
  );
}
