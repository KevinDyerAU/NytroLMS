/**
 * CompanyDetail — Full company detail view matching Laravel's companies/show.blade.php
 * Shows company info, leaders, students, and signup links
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { StatusBadge } from './StatusBadge';
import { useAuth } from '@/contexts/AuthContext';
import {
  fetchCompanyFullDetail,
  createSignupLink,
  deleteSignupLink,
  toggleSignupLinkActive,
  fetchAvailableCourses,
  fetchCompanyNotes,
  createCompanyNote,
  updateStudentNote,
  deleteStudentNote,
  toggleNotePin,
  type CompanyFullDetail as CompanyFullDetailType,
  type StudentNote,
} from '@/lib/api';
import { toast } from 'sonner';
import {
  ArrowLeft, Edit, Building2, Users, Mail, Phone, MapPin,
  Link2, UserCircle, Loader2, Hash, Plus, Trash2, Copy,
  ExternalLink, ToggleLeft, ToggleRight, StickyNote, Pin,
  Send, Pencil, X, Check,
} from 'lucide-react';

interface CompanyDetailProps {
  companyId: number;
  onBack: () => void;
  onEdit: (companyId: number) => void;
}

export function CompanyDetail({ companyId, onBack, onEdit }: CompanyDetailProps) {
  const { user } = useAuth();
  const [company, setCompany] = useState<CompanyFullDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [addLinkOpen, setAddLinkOpen] = useState(false);
  const [deletingLinkId, setDeletingLinkId] = useState<number | null>(null);
  const [togglingLinkId, setTogglingLinkId] = useState<number | null>(null);
  const [notes, setNotes] = useState<StudentNote[]>([]);
  const [notesLoading, setNotesLoading] = useState(false);
  const [newNote, setNewNote] = useState('');
  const [editingNoteId, setEditingNoteId] = useState<number | null>(null);
  const [editingNoteText, setEditingNoteText] = useState('');

  const loadCompany = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await fetchCompanyFullDetail(companyId);
      if (!data) { setError('Company not found'); return; }
      setCompany(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load company');
    } finally {
      setLoading(false);
    }
  }, [companyId]);

  useEffect(() => { loadCompany(); }, [loadCompany]);

  const loadNotes = useCallback(async () => {
    setNotesLoading(true);
    try {
      const data = await fetchCompanyNotes(companyId);
      setNotes(data);
    } catch { /* silent */ }
    finally { setNotesLoading(false); }
  }, [companyId]);

  const handleAddNote = async () => {
    if (!newNote.trim() || !user) return;
    try {
      await createCompanyNote(companyId, newNote.trim(), user.id);
      setNewNote('');
      toast.success('Note added');
      loadNotes();
    } catch { toast.error('Failed to add note'); }
  };

  const handleDeleteNote = async (noteId: number) => {
    try {
      await deleteStudentNote(noteId);
      setNotes(prev => prev.filter(n => n.id !== noteId));
      toast.success('Note deleted');
    } catch { toast.error('Failed to delete note'); }
  };

  const handleEditNote = async (noteId: number) => {
    if (!editingNoteText.trim()) return;
    try {
      await updateStudentNote(noteId, editingNoteText.trim());
      setNotes(prev => prev.map(n => n.id === noteId ? { ...n, note_body: editingNoteText.trim() } : n));
      setEditingNoteId(null);
      setEditingNoteText('');
      toast.success('Note updated');
    } catch { toast.error('Failed to update note'); }
  };

  const handleTogglePin = async (noteId: number, currentlyPinned: boolean) => {
    try {
      await toggleNotePin(noteId, !currentlyPinned);
      setNotes(prev => prev.map(n => n.id === noteId ? { ...n, is_pinned: currentlyPinned ? 0 : 1 } : n));
    } catch { toast.error('Failed to update note'); }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 className="w-8 h-8 animate-spin text-[#3b82f6]" />
      </div>
    );
  }

  if (error || !company) {
    return (
      <div className="space-y-4">
        <Button variant="ghost" size="sm" onClick={onBack} className="text-[#64748b]">
          <ArrowLeft className="w-4 h-4 mr-1.5" /> Back to Companies
        </Button>
        <Card className="border-red-200 bg-red-50">
          <CardContent className="py-8 text-center">
            <p className="text-red-600">{error || 'Company not found'}</p>
            <Button variant="outline" size="sm" className="mt-4" onClick={loadCompany}>Retry</Button>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-4 animate-fade-in-up">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="sm" onClick={onBack} className="text-[#64748b]">
            <ArrowLeft className="w-4 h-4 mr-1.5" /> Back
          </Button>
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-lg bg-[#eff6ff]">
              <Building2 className="w-5 h-5 text-[#3b82f6]" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-[#1e293b]">{company.name}</h2>
              <p className="text-sm text-[#64748b]">{company.email}</p>
            </div>
          </div>
        </div>
        <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => onEdit(company.id)}>
          <Edit className="w-4 h-4 mr-1.5" /> Edit
        </Button>
      </div>

      <Tabs defaultValue="overview">
        <TabsList className="bg-white border border-[#e2e8f0] w-full justify-start">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="students">Students ({company.students.length})</TabsTrigger>
          <TabsTrigger value="leaders">Leaders ({company.leaders.length})</TabsTrigger>
          <TabsTrigger value="links">Signup Links ({company.signup_links.length})</TabsTrigger>
          <TabsTrigger value="notes" onClick={() => { if (notes.length === 0 && !notesLoading) loadNotes(); }}>Notes</TabsTrigger>
        </TabsList>

        {/* ── Overview Tab ── */}
        <TabsContent value="overview" className="mt-4">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div className="lg:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-3">
              <StatCard icon={<Users className="w-4 h-4" />} label="Students" value={company.students.length} />
              <StatCard icon={<UserCircle className="w-4 h-4" />} label="Leaders" value={company.leaders.length} />
              <StatCard icon={<Link2 className="w-4 h-4" />} label="Signup Links" value={company.signup_links.length} />
              <StatCard icon={<Hash className="w-4 h-4" />} label="ID" value={company.id} />
            </div>

            <div className="lg:col-span-2">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base text-[#3b82f6]">Company Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                  <InfoRow icon={<Building2 className="w-4 h-4" />} label="Name">{company.name}</InfoRow>
                  <InfoRow icon={<Mail className="w-4 h-4" />} label="Email">{company.email}</InfoRow>
                  {company.address && (
                    <InfoRow icon={<MapPin className="w-4 h-4" />} label="Address">{company.address}</InfoRow>
                  )}
                  {company.number && (
                    <InfoRow icon={<Phone className="w-4 h-4" />} label="Number">{company.number}</InfoRow>
                  )}
                  {company.poc_user_name && (
                    <InfoRow icon={<UserCircle className="w-4 h-4" />} label="Point of Contact">{company.poc_user_name}</InfoRow>
                  )}
                  {company.bm_user_name && (
                    <InfoRow icon={<UserCircle className="w-4 h-4" />} label="Business Manager">{company.bm_user_name}</InfoRow>
                  )}
                  <InfoRow label="Created">
                    {company.created_at
                      ? new Date(company.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })
                      : '—'}
                  </InfoRow>
                </CardContent>
              </Card>
            </div>

            <div>
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base text-[#3b82f6]">Active Signup Links</CardTitle>
                </CardHeader>
                <CardContent>
                  {company.signup_links.filter(l => l.is_active === 1).length === 0 ? (
                    <p className="text-sm text-[#94a3b8]">No active signup links</p>
                  ) : (
                    <div className="space-y-2">
                      {company.signup_links.filter(l => l.is_active === 1).map(link => (
                        <div key={link.id} className="flex items-center justify-between py-1.5 border-b border-[#f1f5f9] last:border-0">
                          <div>
                            <p className="text-sm text-[#1e293b]">{link.course_title}</p>
                            <p className="text-xs text-[#94a3b8] font-mono">{link.key}</p>
                          </div>
                          <Badge variant="outline" className="text-xs bg-green-50 text-green-600 border-green-200">Active</Badge>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* ── Students Tab ── */}
        <TabsContent value="students" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">Students ({company.students.length})</CardTitle>
            </CardHeader>
            <CardContent>
              {company.students.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-8">No students linked to this company</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-[#e2e8f0]">
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase">Name</th>
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase hidden md:table-cell">Email</th>
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#f1f5f9]">
                      {company.students.map(s => (
                        <tr key={s.id} className="hover:bg-[#f8fafc]">
                          <td className="px-3 py-2 text-sm text-[#1e293b]">{s.first_name} {s.last_name}</td>
                          <td className="px-3 py-2 text-sm text-[#64748b] hidden md:table-cell">{s.email}</td>
                          <td className="px-3 py-2">
                            <StatusBadge status={s.is_active === 1 ? 'Active' : 'Inactive'} />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Leaders Tab ── */}
        <TabsContent value="leaders" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">Leaders ({company.leaders.length})</CardTitle>
            </CardHeader>
            <CardContent>
              {company.leaders.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-8">No leaders linked to this company</p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-[#e2e8f0]">
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase">Name</th>
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase hidden md:table-cell">Email</th>
                        <th className="text-left px-3 py-2 text-xs font-semibold text-[#64748b] uppercase">Status</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-[#f1f5f9]">
                      {company.leaders.map(l => (
                        <tr key={l.id} className="hover:bg-[#f8fafc]">
                          <td className="px-3 py-2 text-sm text-[#1e293b]">{l.first_name} {l.last_name}</td>
                          <td className="px-3 py-2 text-sm text-[#64748b] hidden md:table-cell">{l.email}</td>
                          <td className="px-3 py-2">
                            <StatusBadge status={l.is_active === 1 ? 'Active' : 'Inactive'} />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* ── Signup Links Tab ── */}
        <TabsContent value="links" className="mt-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle className="text-base text-[#3b82f6]">Signup Links ({company.signup_links.length})</CardTitle>
              <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddLinkOpen(true)}>
                <Plus className="w-4 h-4 mr-1.5" /> Create Link
              </Button>
            </CardHeader>
            <CardContent>
              {company.signup_links.length === 0 ? (
                <div className="text-center py-8">
                  <Link2 className="w-10 h-10 text-[#94a3b8] mx-auto mb-2" />
                  <p className="text-sm text-[#94a3b8]">No signup links</p>
                  <Button size="sm" variant="outline" className="mt-3" onClick={() => setAddLinkOpen(true)}>
                    <Plus className="w-4 h-4 mr-1.5" /> Create First Link
                  </Button>
                </div>
              ) : (
                <div className="space-y-3">
                  {company.signup_links.map(link => {
                    const signupUrl = `${window.location.origin}/signup/${link.key}`;
                    return (
                      <div key={link.id} className="border border-[#e2e8f0] rounded-lg p-4 hover:border-[#3b82f6]/30 transition-colors">
                        <div className="flex items-start justify-between">
                          <div className="flex-1 min-w-0">
                            <h4 className="font-medium text-[#1e293b]">{link.course_title}</h4>
                            <div className="flex items-center gap-2 mt-1">
                              <p className="text-xs text-[#94a3b8] font-mono truncate">{signupUrl}</p>
                              <Button
                                variant="ghost" size="sm" className="h-6 w-6 p-0 text-[#94a3b8] hover:text-[#3b82f6] flex-shrink-0"
                                onClick={() => { navigator.clipboard.writeText(signupUrl); toast.success('Link copied to clipboard'); }}
                                title="Copy signup URL"
                              >
                                <Copy className="w-3 h-3" />
                              </Button>
                              <a href={signupUrl} target="_blank" rel="noopener noreferrer" className="flex-shrink-0">
                                <Button variant="ghost" size="sm" className="h-6 w-6 p-0 text-[#94a3b8] hover:text-[#3b82f6]" title="Open signup page">
                                  <ExternalLink className="w-3 h-3" />
                                </Button>
                              </a>
                            </div>
                          </div>
                          <div className="flex items-center gap-1 ml-2">
                            <StatusBadge status={link.is_active === 1 ? 'Active' : 'Inactive'} />
                            <Button
                              variant="ghost" size="sm" className="h-7 w-7 p-0 text-[#64748b]"
                              disabled={togglingLinkId === link.id}
                              title={link.is_active === 1 ? 'Deactivate link' : 'Activate link'}
                              onClick={async () => {
                                setTogglingLinkId(link.id);
                                try {
                                  await toggleSignupLinkActive(link.id, link.is_active !== 1);
                                  toast.success(link.is_active === 1 ? 'Link deactivated' : 'Link activated');
                                  loadCompany();
                                } catch { toast.error('Failed to update'); }
                                finally { setTogglingLinkId(null); }
                              }}
                            >
                              {togglingLinkId === link.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : (link.is_active === 1 ? <ToggleRight className="w-3.5 h-3.5 text-green-500" /> : <ToggleLeft className="w-3.5 h-3.5" />)}
                            </Button>
                            <Button
                              variant="ghost" size="sm" className="h-7 w-7 p-0 text-[#64748b] hover:text-red-500"
                              disabled={deletingLinkId === link.id}
                              title="Delete link"
                              onClick={async () => {
                                if (!confirm(`Delete signup link for "${link.course_title}"?`)) return;
                                setDeletingLinkId(link.id);
                                try {
                                  await deleteSignupLink(link.id);
                                  toast.success('Signup link deleted');
                                  loadCompany();
                                } catch { toast.error('Failed to delete'); }
                                finally { setDeletingLinkId(null); }
                              }}
                            >
                              {deletingLinkId === link.id ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
                            </Button>
                          </div>
                        </div>
                        {link.created_at && (
                          <p className="text-xs text-[#94a3b8] mt-2">
                            Created: {new Date(link.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })}
                          </p>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Add Signup Link Dialog */}
          <AddSignupLinkDialog
            open={addLinkOpen}
            onOpenChange={setAddLinkOpen}
            companyId={companyId}
            leaders={company.leaders}
            creatorId={user?.id ?? 0}
            onSaved={loadCompany}
          />
        </TabsContent>
        {/* ── Notes Tab ── */}
        <TabsContent value="notes" className="mt-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">
                <StickyNote className="w-4 h-4 inline mr-2" />
                Company Notes
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {/* Add Note Form */}
              <div className="flex gap-2">
                <textarea
                  className="flex-1 min-h-[60px] rounded-md border border-[#e2e8f0] px-3 py-2 text-sm placeholder:text-[#94a3b8] focus:outline-none focus:ring-2 focus:ring-[#3b82f6] focus:border-transparent resize-none"
                  placeholder="Add a note about this company..."
                  value={newNote}
                  onChange={(e) => setNewNote(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) handleAddNote();
                  }}
                />
                <Button
                  size="sm"
                  className="bg-[#3b82f6] hover:bg-[#2563eb] text-white self-end"
                  disabled={!newNote.trim()}
                  onClick={handleAddNote}
                >
                  <Send className="w-4 h-4" />
                </Button>
              </div>

              {notesLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
                </div>
              ) : notes.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-6">No notes yet. Add one above.</p>
              ) : (
                <div className="space-y-3">
                  {notes.map((note) => (
                    <div
                      key={note.id}
                      className={`border rounded-lg p-4 ${note.is_pinned ? 'border-amber-300 bg-amber-50/50' : 'border-[#e2e8f0]'}`}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex-1 min-w-0">
                          {note.is_pinned === 1 && (
                            <span className="text-xs text-amber-600 font-medium flex items-center gap-1 mb-1">
                              <Pin className="w-3 h-3" /> Pinned
                            </span>
                          )}
                          {editingNoteId === note.id ? (
                            <div className="space-y-2">
                              <textarea
                                className="w-full min-h-[60px] rounded-md border border-[#e2e8f0] px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#3b82f6] resize-none"
                                value={editingNoteText}
                                onChange={(e) => setEditingNoteText(e.target.value)}
                                autoFocus
                              />
                              <div className="flex gap-1">
                                <Button size="sm" className="h-7 bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => handleEditNote(note.id)}>
                                  <Check className="w-3.5 h-3.5 mr-1" /> Save
                                </Button>
                                <Button size="sm" variant="ghost" className="h-7" onClick={() => { setEditingNoteId(null); setEditingNoteText(''); }}>
                                  <X className="w-3.5 h-3.5 mr-1" /> Cancel
                                </Button>
                              </div>
                            </div>
                          ) : (
                            <p className="text-sm text-[#1e293b] whitespace-pre-wrap">{note.note_body}</p>
                          )}
                          <p className="text-xs text-[#94a3b8] mt-2">
                            {note.author_name}
                            {note.created_at && ` · ${new Date(note.created_at).toLocaleString('en-AU', {
                              day: 'numeric', month: 'short', year: 'numeric',
                              hour: '2-digit', minute: '2-digit',
                            })}`}
                          </p>
                        </div>
                        <div className="flex items-center gap-1 flex-shrink-0">
                          <Button
                            variant="ghost" size="sm"
                            className="text-[#94a3b8] hover:text-[#3b82f6] h-7 w-7 p-0"
                            onClick={() => { setEditingNoteId(note.id); setEditingNoteText(note.note_body); }}
                            title="Edit"
                          >
                            <Pencil className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            variant="ghost" size="sm"
                            className={`h-7 w-7 p-0 ${note.is_pinned ? 'text-amber-500' : 'text-[#94a3b8] hover:text-amber-500'}`}
                            onClick={() => handleTogglePin(note.id, note.is_pinned === 1)}
                            title={note.is_pinned ? 'Unpin' : 'Pin'}
                          >
                            <Pin className="w-3.5 h-3.5" />
                          </Button>
                          <Button
                            variant="ghost" size="sm"
                            className="text-[#94a3b8] hover:text-[#ef4444] h-7 w-7 p-0"
                            onClick={() => handleDeleteNote(note.id)}
                            title="Delete"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}

function StatCard({ icon, label, value }: { icon: React.ReactNode; label: string; value: number }) {
  return (
    <Card className="p-4">
      <div className="flex items-center gap-2 mb-1">
        <span className="text-[#3b82f6]">{icon}</span>
        <span className="text-xs text-[#94a3b8]">{label}</span>
      </div>
      <p className="text-2xl font-bold text-[#1e293b]">{value}</p>
    </Card>
  );
}

function InfoRow({ icon, label, children }: { icon?: React.ReactNode; label: string; children: React.ReactNode }) {
  return (
    <div className="flex items-start gap-2">
      {icon && <span className="text-[#94a3b8] mt-0.5 flex-shrink-0">{icon}</span>}
      <div className="flex-1 min-w-0">
        <span className="text-[#94a3b8] text-xs block">{label}</span>
        <span className="text-[#1e293b]">{children}</span>
      </div>
    </div>
  );
}

// ─── Add Signup Link Dialog ────────────────────────────────────────────────

function AddSignupLinkDialog({ open, onOpenChange, companyId, leaders, creatorId, onSaved }: {
  open: boolean;
  onOpenChange: (v: boolean) => void;
  companyId: number;
  leaders: { id: number; first_name: string; last_name: string }[];
  creatorId: number;
  onSaved: () => void;
}) {
  const [saving, setSaving] = useState(false);
  const [courses, setCourses] = useState<{ id: number; title: string }[]>([]);
  const [loadingCourses, setLoadingCourses] = useState(false);
  const [selectedCourseId, setSelectedCourseId] = useState('');
  const [selectedLeaderId, setSelectedLeaderId] = useState('');
  const [isChargeable, setIsChargeable] = useState(false);

  useEffect(() => {
    if (open && courses.length === 0) {
      setLoadingCourses(true);
      fetchAvailableCourses()
        .then(c => setCourses(c ?? []))
        .finally(() => setLoadingCourses(false));
    }
  }, [open]);

  const reset = () => {
    setSelectedCourseId('');
    setSelectedLeaderId('');
    setIsChargeable(false);
  };

  const handleSubmit = async () => {
    if (!selectedCourseId) { toast.error('Please select a course'); return; }
    if (!selectedLeaderId) { toast.error('Please select a leader'); return; }

    setSaving(true);
    try {
      const result = await createSignupLink({
        company_id: companyId,
        leader_id: parseInt(selectedLeaderId, 10),
        course_id: parseInt(selectedCourseId, 10),
        creator_id: creatorId,
        is_chargeable: isChargeable,
      });
      const signupUrl = `${window.location.origin}/signup/${result.key}`;
      await navigator.clipboard.writeText(signupUrl);
      toast.success('Signup link created and copied to clipboard!');
      reset();
      onSaved();
      onOpenChange(false);
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to create signup link');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={(v) => { if (!v) { reset(); onOpenChange(false); } else onOpenChange(v); }}>
      <DialogContent className="max-w-md">
        <DialogHeader>
          <DialogTitle>Create Signup Link</DialogTitle>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label>Course <span className="text-red-500">*</span></Label>
            {loadingCourses ? (
              <div className="flex items-center gap-2 text-sm text-[#94a3b8]">
                <Loader2 className="w-4 h-4 animate-spin" /> Loading courses...
              </div>
            ) : (
              <Select value={selectedCourseId} onValueChange={setSelectedCourseId}>
                <SelectTrigger><SelectValue placeholder="Select a course" /></SelectTrigger>
                <SelectContent>
                  {courses.map(c => (
                    <SelectItem key={c.id} value={String(c.id)}>{c.title}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
          </div>

          <div className="space-y-2">
            <Label>Leader <span className="text-red-500">*</span></Label>
            {leaders.length === 0 ? (
              <p className="text-sm text-amber-600">No leaders linked to this company. Add a leader first.</p>
            ) : (
              <Select value={selectedLeaderId} onValueChange={setSelectedLeaderId}>
                <SelectTrigger><SelectValue placeholder="Select a leader" /></SelectTrigger>
                <SelectContent>
                  {leaders.map(l => (
                    <SelectItem key={l.id} value={String(l.id)}>{l.first_name} {l.last_name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            )}
          </div>

          <div className="flex items-center gap-2">
            <Checkbox id="is-chargeable" checked={isChargeable} onCheckedChange={(c) => setIsChargeable(c === true)} />
            <Label htmlFor="is-chargeable" className="text-sm font-normal cursor-pointer">Chargeable enrolment</Label>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => { reset(); onOpenChange(false); }} disabled={saving}>Cancel</Button>
          <Button
            className="bg-[#3b82f6] hover:bg-[#2563eb] text-white"
            disabled={saving || !selectedCourseId || !selectedLeaderId || leaders.length === 0}
            onClick={handleSubmit}
          >
            {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />} Create Link
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
