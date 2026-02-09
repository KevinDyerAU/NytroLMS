/**
 * CompanyDetail — Full company detail view matching Laravel's companies/show.blade.php
 * Shows company info, leaders, students, and signup links
 */
import { useState, useEffect, useCallback } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatusBadge } from './StatusBadge';
import { fetchCompanyFullDetail, type CompanyFullDetail as CompanyFullDetailType } from '@/lib/api';
import {
  ArrowLeft, Edit, Building2, Users, Mail, Phone, MapPin,
  Link2, UserCircle, Loader2, Hash,
} from 'lucide-react';

interface CompanyDetailProps {
  companyId: number;
  onBack: () => void;
  onEdit: (companyId: number) => void;
}

export function CompanyDetail({ companyId, onBack, onEdit }: CompanyDetailProps) {
  const [company, setCompany] = useState<CompanyFullDetailType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

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
            <CardHeader>
              <CardTitle className="text-base text-[#3b82f6]">Signup Links ({company.signup_links.length})</CardTitle>
            </CardHeader>
            <CardContent>
              {company.signup_links.length === 0 ? (
                <p className="text-sm text-[#94a3b8] text-center py-8">No signup links</p>
              ) : (
                <div className="space-y-3">
                  {company.signup_links.map(link => (
                    <div key={link.id} className="border border-[#e2e8f0] rounded-lg p-4">
                      <div className="flex items-start justify-between">
                        <div>
                          <h4 className="font-medium text-[#1e293b]">{link.course_title}</h4>
                          <p className="text-xs text-[#94a3b8] font-mono mt-1">{link.key}</p>
                        </div>
                        <StatusBadge status={link.is_active === 1 ? 'Active' : 'Inactive'} />
                      </div>
                      {link.created_at && (
                        <p className="text-xs text-[#94a3b8] mt-2">
                          Created: {new Date(link.created_at).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' })}
                        </p>
                      )}
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
