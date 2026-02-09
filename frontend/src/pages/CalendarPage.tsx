/**
 * Calendar Page — Timeline view of activities, due dates, and scheduled events
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Calendar, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

export default function CalendarPage() {
  const [currentDate, setCurrentDate] = useState(new Date());
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();

  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDayOfWeek = (firstDay.getDay() + 6) % 7; // Monday-based
  const daysInMonth = lastDay.getDate();

  const prevMonth = () => setCurrentDate(new Date(year, month - 1, 1));
  const nextMonth = () => setCurrentDate(new Date(year, month + 1, 1));
  const today = new Date();

  const cells: (number | null)[] = [];
  for (let i = 0; i < startDayOfWeek; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);
  while (cells.length % 7 !== 0) cells.push(null);

  const isToday = (day: number | null) =>
    day !== null && today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;

  return (
    <DashboardLayout title="Calendar" subtitle="Activities, due dates, and events">
      <div className="space-y-6 animate-fade-in-up">
        <Card className="p-6 border-[#e2e8f0]/50 shadow-card">
          {/* Calendar header */}
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-lg font-semibold text-[#1e293b]">
              {MONTHS[month]} {year}
            </h2>
            <div className="flex items-center gap-2">
              <Button variant="outline" size="icon" onClick={prevMonth} className="h-8 w-8">
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <Button variant="outline" size="sm" onClick={() => setCurrentDate(new Date())} className="h-8 text-xs">
                Today
              </Button>
              <Button variant="outline" size="icon" onClick={nextMonth} className="h-8 w-8">
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>

          {/* Day headers */}
          <div className="grid grid-cols-7 gap-px mb-1">
            {DAYS.map(day => (
              <div key={day} className="text-center text-xs font-semibold text-[#94a3b8] py-2">
                {day}
              </div>
            ))}
          </div>

          {/* Calendar grid */}
          <div className="grid grid-cols-7 gap-px bg-[#e2e8f0] rounded-lg overflow-hidden">
            {cells.map((day, i) => (
              <div
                key={i}
                className={`bg-white min-h-[80px] p-2 ${day === null ? 'bg-[#f8fafc]' : 'hover:bg-[#f8fafc] cursor-pointer transition-colors'}`}
              >
                {day !== null && (
                  <span className={`text-sm font-medium inline-flex items-center justify-center w-7 h-7 rounded-full ${
                    isToday(day) ? 'bg-[#3b82f6] text-white' : 'text-[#64748b]'
                  }`}>
                    {day}
                  </span>
                )}
              </div>
            ))}
          </div>
        </Card>

        {/* Upcoming events placeholder */}
        <Card className="p-6 border-[#e2e8f0]/50 shadow-card">
          <div className="flex items-center gap-2 mb-4">
            <Calendar className="w-4 h-4 text-[#3b82f6]" />
            <h3 className="font-semibold text-[#1e293b]">Upcoming Events</h3>
          </div>
          <p className="text-sm text-[#64748b]">
            No upcoming events. Activities, assessment due dates, and intake start dates will appear here.
          </p>
        </Card>
      </div>
    </DashboardLayout>
  );
}
