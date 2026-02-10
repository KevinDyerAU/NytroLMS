/**
 * Calendar Page — Full interactive calendar with FullCalendar
 * Shows course start/end dates, assessment submissions, completions, expiry, certificates, and overdue items.
 * Supports student filtering, set/clear due dates, and overdue detection.
 */
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Calendar as CalendarIcon, Loader2, AlertCircle, Filter,
  GraduationCap, ClipboardCheck, Award, Clock, CheckCircle2, ShieldAlert,
  ChevronRight, X, Search, User, AlertTriangle, CalendarDays, Trash2,
} from 'lucide-react';
import { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import type { EventClickArg, DatesSetArg, EventContentArg } from '@fullcalendar/core';
import {
  fetchCalendarEvents, fetchStudentListForCalendar,
  updateEnrolmentDueDate, clearEnrolmentDueDate,
  type CalendarEvent, type CalendarEventType,
} from '@/lib/api';
import { toast } from 'sonner';

// ─── Event type config ──────────────────────────────────────────────────────

interface EventTypeConfig {
  label: string;
  color: string;
  bg: string;
  border: string;
  icon: React.ElementType;
}

const EVENT_TYPE_CONFIG: Record<CalendarEventType, EventTypeConfig> = {
  course_start:           { label: 'Course Start',          color: '#3b82f6', bg: '#eff6ff', border: '#bfdbfe', icon: GraduationCap },
  course_end:             { label: 'Course Due',            color: '#f59e0b', bg: '#fffbeb', border: '#fde68a', icon: Clock },
  overdue:                { label: 'Overdue',               color: '#dc2626', bg: '#fef2f2', border: '#fecaca', icon: AlertTriangle },
  course_expiry:          { label: 'Course Expiry',         color: '#ef4444', bg: '#fef2f2', border: '#fecaca', icon: ShieldAlert },
  course_completed:       { label: 'Completed',             color: '#10b981', bg: '#ecfdf5', border: '#a7f3d0', icon: CheckCircle2 },
  assessment_submitted:   { label: 'Assessment Submitted',  color: '#8b5cf6', bg: '#f5f3ff', border: '#ddd6fe', icon: ClipboardCheck },
  cert_issued:            { label: 'Certificate Issued',    color: '#0ea5e9', bg: '#f0f9ff', border: '#bae6fd', icon: Award },
};

const ALL_EVENT_TYPES = Object.keys(EVENT_TYPE_CONFIG) as CalendarEventType[];

// ─── Component ──────────────────────────────────────────────────────────────

export default function CalendarPage() {
  const calendarRef = useRef<FullCalendar>(null);
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null);
  const [popoverAnchor, setPopoverAnchor] = useState<{ x: number; y: number } | null>(null);
  const [activeFilters, setActiveFilters] = useState<Set<CalendarEventType>>(new Set(ALL_EVENT_TYPES));
  const [currentRange, setCurrentRange] = useState<{ from: string; to: string } | null>(null);

  // Student filter state
  const [studentSearch, setStudentSearch] = useState('');
  const [studentResults, setStudentResults] = useState<{ id: number; name: string }[]>([]);
  const [selectedStudent, setSelectedStudent] = useState<{ id: number; name: string } | null>(null);
  const [showStudentDropdown, setShowStudentDropdown] = useState(false);
  const [searchingStudents, setSearchingStudents] = useState(false);
  const studentDropdownRef = useRef<HTMLDivElement>(null);

  // Due date dialog state
  const [dueDateDialog, setDueDateDialog] = useState<{ event: CalendarEvent; mode: 'set' } | null>(null);
  const [newDueDate, setNewDueDate] = useState('');
  const [savingDueDate, setSavingDueDate] = useState(false);

  // ─── Student search with debounce ───────────────────────────────────────
  useEffect(() => {
    if (!studentSearch.trim()) {
      setStudentResults([]);
      return;
    }
    const timer = setTimeout(async () => {
      setSearchingStudents(true);
      try {
        const results = await fetchStudentListForCalendar(studentSearch);
        setStudentResults(results);
      } catch {
        setStudentResults([]);
      } finally {
        setSearchingStudents(false);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [studentSearch]);

  // Close student dropdown on outside click
  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      if (studentDropdownRef.current && !studentDropdownRef.current.contains(e.target as Node)) {
        setShowStudentDropdown(false);
      }
    };
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, []);

  // ─── Fetch events when date range or student filter changes ─────────────
  const loadEvents = useCallback(async (from: string, to: string, studentId?: number) => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchCalendarEvents({ from, to, studentId });
      setEvents(data);
    } catch (err) {
      const msg = err instanceof Error ? err.message : 'Failed to load events';
      setError(msg);
      toast.error('Calendar error', { description: msg });
    } finally {
      setLoading(false);
    }
  }, []);

  const handleDatesSet = useCallback((arg: DatesSetArg) => {
    const from = arg.startStr.slice(0, 10);
    const to = arg.endStr.slice(0, 10);
    setCurrentRange({ from, to });
    loadEvents(from, to, selectedStudent?.id);
  }, [loadEvents, selectedStudent]);

  // Reload when student filter changes (not when currentRange changes — that's handled by handleDatesSet)
  const prevStudentRef = useRef(selectedStudent?.id);
  useEffect(() => {
    if (prevStudentRef.current !== selectedStudent?.id && currentRange) {
      prevStudentRef.current = selectedStudent?.id;
      loadEvents(currentRange.from, currentRange.to, selectedStudent?.id);
    }
  }, [selectedStudent, currentRange, loadEvents]);

  const refreshEvents = useCallback(() => {
    if (currentRange) loadEvents(currentRange.from, currentRange.to, selectedStudent?.id);
  }, [currentRange, selectedStudent, loadEvents]);

  // ─── Student selection handlers ─────────────────────────────────────────
  const selectStudent = (student: { id: number; name: string }) => {
    setSelectedStudent(student);
    setStudentSearch('');
    setShowStudentDropdown(false);
    setStudentResults([]);
  };

  const clearStudentFilter = () => {
    setSelectedStudent(null);
    setStudentSearch('');
    setStudentResults([]);
  };

  // ─── Filter logic ───────────────────────────────────────────────────────
  const toggleFilter = (type: CalendarEventType) => {
    setActiveFilters(prev => {
      const next = new Set(prev);
      if (next.has(type)) next.delete(type);
      else next.add(type);
      return next;
    });
  };

  const toggleAllFilters = () => {
    if (activeFilters.size === ALL_EVENT_TYPES.length) {
      setActiveFilters(new Set());
    } else {
      setActiveFilters(new Set(ALL_EVENT_TYPES));
    }
  };

  const filteredEvents = useMemo(() =>
    events.filter(e => activeFilters.has(e.type)),
    [events, activeFilters]
  );

  // ─── Map to FullCalendar event objects ──────────────────────────────────
  const fcEvents = useMemo(() =>
    filteredEvents.map(e => {
      const cfg = EVENT_TYPE_CONFIG[e.type];
      return {
        id: e.id,
        title: e.title,
        date: e.date,
        backgroundColor: cfg.bg,
        borderColor: cfg.border,
        textColor: cfg.color,
        extendedProps: { calEvent: e },
      };
    }),
    [filteredEvents]
  );

  // ─── Event click → show popover ─────────────────────────────────────────
  const handleEventClick = useCallback((info: EventClickArg) => {
    const calEvent = info.event.extendedProps.calEvent as CalendarEvent;
    const rect = info.el.getBoundingClientRect();
    setSelectedEvent(calEvent);
    setPopoverAnchor({ x: rect.left + rect.width / 2, y: rect.top });
  }, []);

  // ─── Due date actions ───────────────────────────────────────────────────
  const handleSetDueDate = (event: CalendarEvent) => {
    setDueDateDialog({ event, mode: 'set' });
    setNewDueDate('');
    setSelectedEvent(null);
    setPopoverAnchor(null);
  };

  const handleClearDueDate = async (event: CalendarEvent) => {
    if (!event.meta?.enrolmentId) return;
    try {
      await clearEnrolmentDueDate(event.meta.enrolmentId);
      toast.success('Due date cleared', { description: `Cleared due date for ${event.meta.studentName}` });
      setSelectedEvent(null);
      setPopoverAnchor(null);
      refreshEvents();
    } catch {
      toast.error('Failed to clear due date');
    }
  };

  const handleSaveDueDate = async () => {
    if (!dueDateDialog || !newDueDate || !dueDateDialog.event.meta?.enrolmentId) return;
    setSavingDueDate(true);
    try {
      await updateEnrolmentDueDate(dueDateDialog.event.meta.enrolmentId, newDueDate);
      toast.success('Due date updated', {
        description: `Set due date to ${new Date(newDueDate).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })} for ${dueDateDialog.event.meta.studentName}`,
      });
      setDueDateDialog(null);
      refreshEvents();
    } catch {
      toast.error('Failed to update due date');
    } finally {
      setSavingDueDate(false);
    }
  };

  // ─── Upcoming events (next 14 days) + overdue ───────────────────────────
  const upcomingEvents = useMemo(() => {
    const today = new Date().toISOString().slice(0, 10);
    const twoWeeks = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);
    // Include overdue events (date in the past) and upcoming events
    return filteredEvents
      .filter(e => e.type === 'overdue' || (e.date >= today && e.date <= twoWeeks))
      .sort((a, b) => {
        // Overdue first, then by date
        if (a.type === 'overdue' && b.type !== 'overdue') return -1;
        if (a.type !== 'overdue' && b.type === 'overdue') return 1;
        return a.date.localeCompare(b.date);
      })
      .slice(0, 25);
  }, [filteredEvents]);

  // ─── Event counts by type ───────────────────────────────────────────────
  const eventCounts = useMemo(() => {
    const counts = new Map<CalendarEventType, number>();
    events.forEach(e => counts.set(e.type, (counts.get(e.type) ?? 0) + 1));
    return counts;
  }, [events]);

  // ─── Custom event content renderer ──────────────────────────────────────
  function renderEventContent(arg: EventContentArg) {
    const calEvent = arg.event.extendedProps.calEvent as CalendarEvent;
    const cfg = EVENT_TYPE_CONFIG[calEvent.type];
    const Icon = cfg.icon;
    return (
      <div
        className="flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium truncate w-full cursor-pointer"
        style={{ color: cfg.color, backgroundColor: cfg.bg, borderLeft: `3px solid ${cfg.color}` }}
        title={arg.event.title}
      >
        <Icon className="w-3 h-3 flex-shrink-0" />
        <span className="truncate">{calEvent.meta?.studentName ?? arg.event.title}</span>
      </div>
    );
  }

  return (
    <DashboardLayout title="Calendar" subtitle="Activities, due dates, and events">
      <div className="space-y-4 animate-fade-in-up">
        {/* ── Top bar: student filter + event type filters ── */}
        <Card className="p-4 border-[#3b82f6]/20 shadow-card">
          <div className="space-y-3">
            {/* Row 1: Student filter */}
            <div className="flex flex-wrap items-center gap-3">
              <div className="flex items-center gap-2 mr-1">
                <User className="w-4 h-4 text-[#64748b]" />
                <span className="text-sm font-medium text-[#1e293b]">Student</span>
              </div>

              {selectedStudent ? (
                <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-[#eff6ff] border border-[#bfdbfe] text-[#3b82f6]">
                  <User className="w-3 h-3" />
                  {selectedStudent.name}
                  <button
                    onClick={clearStudentFilter}
                    className="ml-1 p-0.5 rounded-full hover:bg-[#dbeafe] transition-colors"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ) : (
                <div ref={studentDropdownRef} className="relative">
                  <div className="relative">
                    <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#94a3b8]" />
                    <Input
                      placeholder="Search students..."
                      value={studentSearch}
                      onChange={(e) => {
                        setStudentSearch(e.target.value);
                        setShowStudentDropdown(true);
                      }}
                      onFocus={() => { if (studentSearch.trim()) setShowStudentDropdown(true); }}
                      className="h-8 w-56 pl-8 text-xs border-[#3b82f6]/20 focus:border-[#3b82f6] focus:ring-[#3b82f6]/20"
                    />
                    {searchingStudents && (
                      <Loader2 className="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 animate-spin text-[#3b82f6]" />
                    )}
                  </div>
                  {showStudentDropdown && studentResults.length > 0 && (
                    <div className="absolute top-full left-0 mt-1 w-64 bg-white rounded-lg border border-[#3b82f6]/20 shadow-xl shadow-[#3b82f6]/5 z-50 max-h-48 overflow-y-auto">
                      {studentResults.map(s => (
                        <button
                          key={s.id}
                          className="w-full text-left px-3 py-2 text-xs hover:bg-[#f8fafc] transition-colors flex items-center gap-2"
                          onClick={() => selectStudent(s)}
                        >
                          <User className="w-3 h-3 text-[#94a3b8]" />
                          <span className="font-medium text-[#1e293b]">{s.name}</span>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}

              {selectedStudent && (
                <span className="text-[11px] text-[#94a3b8]">
                  Showing events for this student only
                </span>
              )}

              {loading && <Loader2 className="w-4 h-4 animate-spin text-[#3b82f6] ml-auto" />}
            </div>

            {/* Row 2: Event type filters */}
            <div className="flex flex-wrap items-center gap-3">
              <div className="flex items-center gap-2 mr-1">
                <Filter className="w-4 h-4 text-[#64748b]" />
                <span className="text-sm font-medium text-[#1e293b]">Types</span>
              </div>
              <Button
                variant="outline"
                size="sm"
                className="h-7 text-xs"
                onClick={toggleAllFilters}
              >
                {activeFilters.size === ALL_EVENT_TYPES.length ? 'Clear All' : 'Select All'}
              </Button>
              <div className="flex flex-wrap gap-1.5">
                {ALL_EVENT_TYPES.map(type => {
                  const cfg = EVENT_TYPE_CONFIG[type];
                  const active = activeFilters.has(type);
                  const count = eventCounts.get(type) ?? 0;
                  return (
                    <button
                      key={type}
                      onClick={() => toggleFilter(type)}
                      className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-all border"
                      style={{
                        backgroundColor: active ? cfg.bg : 'transparent',
                        borderColor: active ? cfg.border : '#e2e8f0',
                        color: active ? cfg.color : '#94a3b8',
                        opacity: active ? 1 : 0.6,
                      }}
                    >
                      <cfg.icon className="w-3 h-3" />
                      {cfg.label}
                      {count > 0 && (
                        <span
                          className="ml-0.5 px-1.5 py-0 rounded-full text-[10px] font-bold"
                          style={{ backgroundColor: active ? cfg.color : '#94a3b8', color: '#fff' }}
                        >
                          {count}
                        </span>
                      )}
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        </Card>

        {/* ── Error state ── */}
        {error && (
          <Card className="p-4 border-red-200 bg-red-50">
            <div className="flex items-center gap-2 text-red-700">
              <AlertCircle className="w-4 h-4" />
              <span className="text-sm font-medium">{error}</span>
              <Button
                variant="outline"
                size="sm"
                className="ml-auto h-7 text-xs border-red-200 text-red-700 hover:bg-red-100"
                onClick={refreshEvents}
              >
                Retry
              </Button>
            </div>
          </Card>
        )}

        {/* ── Main layout: calendar + sidebar ── */}
        <div className="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-4">
          {/* Calendar Card */}
          <Card className="p-4 border-[#3b82f6]/20 shadow-card overflow-hidden nytro-calendar">
            <FullCalendar
              ref={calendarRef}
              plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin]}
              initialView="dayGridMonth"
              headerToolbar={{
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek',
              }}
              buttonText={{
                today: 'Today',
                month: 'Month',
                week: 'Week',
                list: 'List',
              }}
              events={fcEvents}
              eventContent={renderEventContent}
              eventClick={handleEventClick}
              datesSet={handleDatesSet}
              height="auto"
              dayMaxEvents={4}
              moreLinkContent={(args) => `+${args.num} more`}
              firstDay={1}
              fixedWeekCount={false}
              nowIndicator
              eventDisplay="block"
            />
          </Card>

          {/* Sidebar: upcoming events + overdue */}
          <div className="space-y-4">
            <Card className="p-4 border-[#3b82f6]/20 shadow-card">
              <div className="flex items-center gap-2 mb-3">
                <CalendarIcon className="w-4 h-4 text-[#3b82f6]" />
                <h3 className="font-semibold text-[#1e293b] text-sm">Upcoming & Overdue</h3>
                <Badge variant="outline" className="ml-auto text-[10px]">{upcomingEvents.length}</Badge>
              </div>
              {upcomingEvents.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-6">No upcoming events</p>
              ) : (
                <div className="space-y-1.5 max-h-[600px] overflow-y-auto pr-1">
                  {upcomingEvents.map(evt => {
                    const cfg = EVENT_TYPE_CONFIG[evt.type];
                    const Icon = cfg.icon;
                    return (
                      <button
                        key={evt.id}
                        className="w-full text-left flex items-start gap-2 p-2 rounded-lg hover:bg-[#f8fafc] transition-colors group"
                        onClick={() => {
                          setSelectedEvent(evt);
                          setPopoverAnchor(null);
                        }}
                      >
                        <div
                          className="p-1.5 rounded-md mt-0.5 flex-shrink-0"
                          style={{ backgroundColor: cfg.bg, color: cfg.color }}
                        >
                          <Icon className="w-3 h-3" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-xs font-medium text-[#1e293b] truncate">
                            {evt.meta?.studentName ?? 'Unknown'}
                          </p>
                          <p className="text-[11px] text-[#64748b] truncate">
                            {cfg.label}: {evt.meta?.courseTitle ?? ''}
                          </p>
                          <p className="text-[10px] text-[#94a3b8]">
                            {new Date(evt.date).toLocaleDateString('en-AU', { weekday: 'short', day: 'numeric', month: 'short' })}
                          </p>
                        </div>
                        <ChevronRight className="w-3.5 h-3.5 text-[#94a3b8] opacity-0 group-hover:opacity-100 transition-opacity mt-1.5" />
                      </button>
                    );
                  })}
                </div>
              )}
            </Card>

            {/* Event summary stats */}
            <Card className="p-4 border-[#3b82f6]/20 shadow-card">
              <h3 className="font-semibold text-[#1e293b] text-sm mb-3">Event Summary</h3>
              <div className="space-y-2">
                {ALL_EVENT_TYPES.map(type => {
                  const cfg = EVENT_TYPE_CONFIG[type];
                  const count = eventCounts.get(type) ?? 0;
                  const Icon = cfg.icon;
                  return (
                    <div key={type} className="flex items-center justify-between text-xs">
                      <div className="flex items-center gap-1.5" style={{ color: cfg.color }}>
                        <Icon className="w-3.5 h-3.5" />
                        <span className="font-medium">{cfg.label}</span>
                      </div>
                      <span className="font-semibold text-[#1e293b]">{count}</span>
                    </div>
                  );
                })}
                <div className="border-t border-[#3b82f6]/10 pt-2 mt-2 flex items-center justify-between text-xs">
                  <span className="font-semibold text-[#64748b]">Total Events</span>
                  <span className="font-bold text-[#1e293b]">{events.length}</span>
                </div>
              </div>
            </Card>
          </div>
        </div>

        {/* ── Event detail popover (floating) ── */}
        {selectedEvent && popoverAnchor && (
          <EventDetailPopover
            event={selectedEvent}
            anchor={popoverAnchor}
            onClose={() => { setSelectedEvent(null); setPopoverAnchor(null); }}
            onSetDueDate={handleSetDueDate}
            onClearDueDate={handleClearDueDate}
          />
        )}

        {/* ── Event detail inline (sidebar click) ── */}
        {selectedEvent && !popoverAnchor && (
          <EventDetailDialog
            event={selectedEvent}
            onClose={() => setSelectedEvent(null)}
            onSetDueDate={handleSetDueDate}
            onClearDueDate={handleClearDueDate}
          />
        )}

        {/* ── Set Due Date Dialog ── */}
        {dueDateDialog && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/20" onClick={() => setDueDateDialog(null)}>
            <div
              className="bg-white rounded-xl border border-[#3b82f6]/20 shadow-xl shadow-[#3b82f6]/5 p-5 w-96 space-y-4"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="flex items-center gap-2">
                <CalendarDays className="w-5 h-5 text-[#3b82f6]" />
                <h3 className="font-semibold text-[#1e293b]">Set Due Date</h3>
              </div>
              <div className="space-y-2">
                <p className="text-xs text-[#64748b]">
                  <span className="font-medium text-[#1e293b]">{dueDateDialog.event.meta?.studentName}</span>
                  {' — '}
                  {dueDateDialog.event.meta?.courseTitle}
                </p>
                <Input
                  type="date"
                  value={newDueDate}
                  onChange={(e) => setNewDueDate(e.target.value)}
                  className="border-[#3b82f6]/20 focus:border-[#3b82f6] focus:ring-[#3b82f6]/20"
                />
              </div>
              <div className="flex justify-end gap-2">
                <Button variant="outline" size="sm" onClick={() => setDueDateDialog(null)}>
                  Cancel
                </Button>
                <Button
                  size="sm"
                  disabled={!newDueDate || savingDueDate}
                  onClick={handleSaveDueDate}
                  className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
                >
                  {savingDueDate ? <Loader2 className="w-4 h-4 animate-spin mr-1" /> : null}
                  Apply
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </DashboardLayout>
  );
}

// ─── Event Detail Floating Popover ──────────────────────────────────────────

function EventDetailPopover({
  event,
  anchor,
  onClose,
  onSetDueDate,
  onClearDueDate,
}: {
  event: CalendarEvent;
  anchor: { x: number; y: number };
  onClose: () => void;
  onSetDueDate: (event: CalendarEvent) => void;
  onClearDueDate: (event: CalendarEvent) => void;
}) {
  const cfg = EVENT_TYPE_CONFIG[event.type];
  const Icon = cfg.icon;

  useEffect(() => {
    const handleClick = (e: MouseEvent) => {
      const target = e.target as HTMLElement;
      if (!target.closest('[data-event-popover]')) onClose();
    };
    const handleEsc = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    document.addEventListener('mousedown', handleClick);
    document.addEventListener('keydown', handleEsc);
    return () => {
      document.removeEventListener('mousedown', handleClick);
      document.removeEventListener('keydown', handleEsc);
    };
  }, [onClose]);

  return (
    <div
      data-event-popover
      className="fixed z-50 w-80 bg-white rounded-xl border border-[#3b82f6]/20 shadow-xl shadow-[#3b82f6]/5 p-4 animate-in fade-in-0 zoom-in-95"
      style={{
        top: Math.min(anchor.y - 10, window.innerHeight - 350),
        left: Math.min(anchor.x - 160, window.innerWidth - 340),
      }}
    >
      <button
        className="absolute top-3 right-3 p-1 rounded-md text-[#94a3b8] hover:text-[#1e293b] hover:bg-[#f1f5f9] transition-all"
        onClick={onClose}
      >
        <X className="w-3.5 h-3.5" />
      </button>
      <EventDetailContent
        event={event} cfg={cfg} Icon={Icon}
        onSetDueDate={onSetDueDate} onClearDueDate={onClearDueDate}
      />
    </div>
  );
}

// ─── Event Detail Card (sidebar click) ──────────────────────────────────────

function EventDetailDialog({
  event,
  onClose,
  onSetDueDate,
  onClearDueDate,
}: {
  event: CalendarEvent;
  onClose: () => void;
  onSetDueDate: (event: CalendarEvent) => void;
  onClearDueDate: (event: CalendarEvent) => void;
}) {
  const cfg = EVENT_TYPE_CONFIG[event.type];
  const Icon = cfg.icon;

  return (
    <Card className="p-4 border-[#3b82f6]/20 shadow-card relative">
      <button
        className="absolute top-3 right-3 p-1 rounded-md text-[#94a3b8] hover:text-[#1e293b] hover:bg-[#f1f5f9] transition-all"
        onClick={onClose}
      >
        <X className="w-4 h-4" />
      </button>
      <EventDetailContent
        event={event} cfg={cfg} Icon={Icon}
        onSetDueDate={onSetDueDate} onClearDueDate={onClearDueDate}
      />
    </Card>
  );
}

// ─── Shared event detail content ────────────────────────────────────────────

function EventDetailContent({
  event,
  cfg,
  Icon,
  onSetDueDate,
  onClearDueDate,
}: {
  event: CalendarEvent;
  cfg: EventTypeConfig;
  Icon: React.ElementType;
  onSetDueDate?: (event: CalendarEvent) => void;
  onClearDueDate?: (event: CalendarEvent) => void;
}) {
  const canManageDueDate = event.meta?.enrolmentId &&
    (event.type === 'course_end' || event.type === 'overdue');

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2">
        <div className="p-2 rounded-lg" style={{ backgroundColor: cfg.bg, color: cfg.color }}>
          <Icon className="w-4 h-4" />
        </div>
        <div>
          <Badge
            className="text-[10px] font-semibold px-2 py-0.5"
            style={{ backgroundColor: cfg.bg, color: cfg.color, borderColor: cfg.border }}
          >
            {cfg.label}
          </Badge>
        </div>
      </div>

      <div className="space-y-1.5">
        {event.meta?.studentName && (
          <div className="flex items-center gap-2">
            <span className="text-xs text-[#94a3b8] w-16 flex-shrink-0">Student</span>
            <span className="text-sm font-medium text-[#1e293b]">{event.meta.studentName}</span>
          </div>
        )}
        {event.meta?.courseTitle && (
          <div className="flex items-center gap-2">
            <span className="text-xs text-[#94a3b8] w-16 flex-shrink-0">Course</span>
            <span className="text-sm font-medium text-[#1e293b]">{event.meta.courseTitle}</span>
          </div>
        )}
        {event.meta?.quizTitle && (
          <div className="flex items-center gap-2">
            <span className="text-xs text-[#94a3b8] w-16 flex-shrink-0">Quiz</span>
            <span className="text-sm font-medium text-[#1e293b]">{event.meta.quizTitle}</span>
          </div>
        )}
        {event.meta?.status && (
          <div className="flex items-center gap-2">
            <span className="text-xs text-[#94a3b8] w-16 flex-shrink-0">Status</span>
            <Badge variant="outline" className="text-[10px]">{event.meta.status}</Badge>
          </div>
        )}
        <div className="flex items-center gap-2">
          <span className="text-xs text-[#94a3b8] w-16 flex-shrink-0">Date</span>
          <span className="text-sm text-[#1e293b]">
            {new Date(event.date).toLocaleDateString('en-AU', {
              weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
            })}
          </span>
        </div>
      </div>

      {/* ── Due date actions ── */}
      {canManageDueDate && (
        <div className="border-t border-[#3b82f6]/10 pt-3 flex gap-2">
          <Button
            size="sm"
            variant="outline"
            className="h-7 text-xs flex-1 border-[#3b82f6]/20 text-[#3b82f6] hover:bg-[#eff6ff]"
            onClick={() => onSetDueDate?.(event)}
          >
            <CalendarDays className="w-3 h-3 mr-1" />
            Set New Due Date
          </Button>
          <Button
            size="sm"
            variant="outline"
            className="h-7 text-xs border-red-200 text-red-600 hover:bg-red-50"
            onClick={() => onClearDueDate?.(event)}
          >
            <Trash2 className="w-3 h-3 mr-1" />
            Clear
          </Button>
        </div>
      )}

      {/* Overdue warning banner */}
      {event.type === 'overdue' && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-2.5 flex items-start gap-2">
          <AlertTriangle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-xs font-semibold text-red-700">This item is overdue</p>
            <p className="text-[11px] text-red-600 mt-0.5">
              Set a new due date to unlock access for the student.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
