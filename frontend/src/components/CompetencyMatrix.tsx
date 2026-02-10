/**
 * Competency Matrix — Grid view of student competencies across course units.
 * Rows = students, Columns = lessons (units), Cells = competent/not-yet.
 */
import { useState, useEffect } from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
  fetchAvailableCourses, fetchCompetencyMatrix,
  type CompetencyMatrixData,
} from '@/lib/api';
import { exportToCSV } from '@/lib/utils';
import {
  Loader2, CheckCircle2, Circle, Search, Download, Grid3X3,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

export function CompetencyMatrix() {
  const [courses, setCourses] = useState<{ id: number; title: string }[]>([]);
  const [selectedCourseId, setSelectedCourseId] = useState<string>('');
  const [matrixData, setMatrixData] = useState<CompetencyMatrixData | null>(null);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');

  useEffect(() => {
    fetchAvailableCourses().then(c => setCourses((c ?? []).map(x => ({ id: x.id, title: x.title }))));
  }, []);

  useEffect(() => {
    if (!selectedCourseId) {
      setMatrixData(null);
      return;
    }
    setLoading(true);
    fetchCompetencyMatrix(parseInt(selectedCourseId, 10))
      .then(data => setMatrixData(data))
      .catch(() => setMatrixData(null))
      .finally(() => setLoading(false));
  }, [selectedCourseId]);

  const filteredStudents = matrixData?.students.filter(s =>
    !search || s.student_name.toLowerCase().includes(search.toLowerCase())
  ) ?? [];

  const handleExport = () => {
    if (!matrixData || filteredStudents.length === 0) {
      toast.error('No data to export');
      return;
    }
    const rows = filteredStudents.map(s => {
      const row: Record<string, unknown> = { student_name: s.student_name };
      matrixData.lessons.forEach((l, i) => {
        row[`unit_${i + 1}`] = s.lessons[i]?.is_competent ? 'Competent' : 'Not Yet';
      });
      const competentCount = s.lessons.filter(l => l.is_competent).length;
      row.competent_count = `${competentCount}/${matrixData.lessons.length}`;
      return row;
    });
    const headers = [
      { key: 'student_name', label: 'Student' },
      ...matrixData.lessons.map((l, i) => ({ key: `unit_${i + 1}`, label: `Unit ${l.order}: ${l.title}` })),
      { key: 'competent_count', label: 'Total' },
    ];
    exportToCSV(rows, `competency-matrix-${new Date().toISOString().split('T')[0]}.csv`, headers);
    toast.success('Exported to CSV');
  };

  return (
    <Card className="border-[#e2e8f0]/50">
      <CardHeader>
        <div className="flex items-center justify-between gap-3 flex-wrap">
          <CardTitle className="text-base text-[#3b82f6] flex items-center gap-2">
            <Grid3X3 className="w-4 h-4" />
            Competency Matrix
          </CardTitle>
          <div className="flex items-center gap-2 flex-wrap">
            <Select value={selectedCourseId} onValueChange={setSelectedCourseId}>
              <SelectTrigger className="w-60 h-9 border-[#e2e8f0]">
                <SelectValue placeholder="Select a course..." />
              </SelectTrigger>
              <SelectContent>
                {courses.map(c => (
                  <SelectItem key={c.id} value={String(c.id)}>{c.title}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            {matrixData && matrixData.students.length > 0 && (
              <>
                <div className="relative">
                  <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#94a3b8]" />
                  <Input
                    placeholder="Filter students..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-8 h-9 w-48 border-[#e2e8f0]"
                  />
                </div>
                <Button variant="outline" size="sm" className="h-9 border-[#e2e8f0] text-[#64748b]" onClick={handleExport}>
                  <Download className="w-4 h-4 mr-1" /> Export
                </Button>
              </>
            )}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        {!selectedCourseId ? (
          <div className="py-12 text-center">
            <Grid3X3 className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
            <p className="text-sm text-[#64748b]">Select a course to view the competency matrix.</p>
          </div>
        ) : loading ? (
          <div className="py-12 text-center">
            <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
            <p className="mt-2 text-sm text-[#64748b]">Loading matrix...</p>
          </div>
        ) : !matrixData || matrixData.students.length === 0 ? (
          <div className="py-12 text-center">
            <p className="text-sm text-[#94a3b8]">No students enrolled in this course.</p>
          </div>
        ) : (
          <div className="overflow-x-auto -mx-6">
            <table className="w-full text-xs border-collapse min-w-[600px]">
              <thead>
                <tr className="bg-[#f8fafc]">
                  <th className="sticky left-0 bg-[#f8fafc] z-10 px-4 py-2.5 text-left font-semibold text-[#64748b] border-b border-r border-[#e2e8f0] min-w-[180px]">
                    Student
                  </th>
                  {matrixData.lessons.map(l => (
                    <th
                      key={l.id}
                      className="px-2 py-2.5 text-center font-semibold text-[#64748b] border-b border-[#e2e8f0] min-w-[60px]"
                      title={l.title}
                    >
                      <span className="block truncate max-w-[80px]">U{l.order}</span>
                    </th>
                  ))}
                  <th className="px-3 py-2.5 text-center font-semibold text-[#64748b] border-b border-l border-[#e2e8f0] min-w-[60px]">
                    Total
                  </th>
                </tr>
              </thead>
              <tbody>
                {filteredStudents.map(student => {
                  const competentCount = student.lessons.filter(l => l.is_competent).length;
                  const total = matrixData.lessons.length;
                  const pct = total > 0 ? Math.round((competentCount / total) * 100) : 0;
                  return (
                    <tr key={student.student_id} className="hover:bg-blue-50/30 transition-colors">
                      <td className="sticky left-0 bg-white z-10 px-4 py-2 font-medium text-[#1e293b] border-b border-r border-[#f1f5f9] whitespace-nowrap">
                        {student.student_name}
                      </td>
                      {student.lessons.map((lc, i) => (
                        <td
                          key={matrixData.lessons[i].id}
                          className={cn(
                            "px-2 py-2 text-center border-b border-[#f1f5f9]",
                            lc.is_competent ? 'bg-emerald-50/50' : ''
                          )}
                          title={lc.is_competent
                            ? `Competent${lc.competent_on ? ` — ${new Date(lc.competent_on).toLocaleDateString('en-AU')}` : ''}`
                            : 'Not Yet Competent'
                          }
                        >
                          {lc.is_competent
                            ? <CheckCircle2 className="w-4 h-4 text-emerald-500 mx-auto" />
                            : <Circle className="w-4 h-4 text-[#e2e8f0] mx-auto" />
                          }
                        </td>
                      ))}
                      <td className="px-3 py-2 text-center border-b border-l border-[#f1f5f9] font-semibold">
                        <span className={cn(
                          "text-xs",
                          pct === 100 ? 'text-emerald-600' : pct >= 50 ? 'text-blue-600' : 'text-[#64748b]'
                        )}>
                          {competentCount}/{total}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>

            {/* Legend */}
            <div className="flex items-center gap-4 px-4 pt-3 pb-1 text-xs text-[#94a3b8]">
              <span className="flex items-center gap-1">
                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> Competent
              </span>
              <span className="flex items-center gap-1">
                <Circle className="w-3.5 h-3.5 text-[#e2e8f0]" /> Not Yet
              </span>
              <span className="ml-auto">
                {filteredStudents.length} student{filteredStudents.length !== 1 ? 's' : ''} · {matrixData.lessons.length} unit{matrixData.lessons.length !== 1 ? 's' : ''}
              </span>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
