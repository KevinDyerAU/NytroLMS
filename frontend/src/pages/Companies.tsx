/**
 * Companies Page — Manage employer/company records
 * Connected to Supabase: companies, signup_links, user_details, users
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { CompanyDetail } from '../components/CompanyDetail';
import { AddCompanyDialog } from '../components/AddCompanyDialog';
import { EditCompanyDialog } from '../components/EditCompanyDialog';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, Plus, Building2, Users, Mail, Eye, Loader2, AlertCircle, UserCircle } from 'lucide-react';
import { toast } from 'sonner';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchCompanies, CompanyWithCounts } from '@/lib/api';

export default function Companies() {
  const [search, setSearch] = useState('');
  const [selectedCompanyId, setSelectedCompanyId] = useState<number | null>(null);
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  const [editCompanyId, setEditCompanyId] = useState<number | null>(null);

  const { data, loading, error, refetch } = useSupabaseQuery(
    () => fetchCompanies({ search, limit: 50 }),
    [search]
  );

  const companies = data?.data ?? [];

  if (selectedCompanyId !== null) {
    return (
      <DashboardLayout title="Companies" subtitle="Manage employer and company relationships">
        <CompanyDetail
          companyId={selectedCompanyId}
          onBack={() => setSelectedCompanyId(null)}
          onEdit={(id) => setEditCompanyId(id)}
        />
        {editCompanyId !== null && (
          <EditCompanyDialog
            open={true}
            onOpenChange={(open) => { if (!open) setEditCompanyId(null); }}
            companyId={editCompanyId}
            onSaved={() => { refetch(); setEditCompanyId(null); setSelectedCompanyId(null); }}
          />
        )}
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout title="Companies" subtitle="Manage employer and company relationships">
      <div className="space-y-4 animate-fade-in-up">
        {/* Toolbar */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input placeholder="Search companies..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9 border-[#e2e8f0] h-9" />
          </div>
          <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => setAddDialogOpen(true)}>
            <Plus className="w-4 h-4 mr-1.5" /> Add Company
          </Button>
        </div>

        {/* Error State */}
        {error && (
          <Card className="p-6 border-red-200 bg-red-50">
            <div className="flex items-center gap-3 text-red-700">
              <AlertCircle className="w-5 h-5" />
              <p className="text-sm">{error}</p>
              <Button variant="outline" size="sm" onClick={refetch}>Retry</Button>
            </div>
          </Card>
        )}

        {/* Loading State */}
        {loading && (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {[1, 2, 3, 4, 5, 6].map(i => (
              <Card key={i} className="p-5 border-[#e2e8f0]/50 animate-pulse">
                <div className="h-8 w-8 bg-[#e2e8f0] rounded-lg mb-3" />
                <div className="h-5 w-3/4 bg-[#e2e8f0] rounded mb-2" />
                <div className="h-3 w-1/2 bg-[#e2e8f0] rounded mb-4" />
                <div className="h-3 w-full bg-[#e2e8f0] rounded" />
              </Card>
            ))}
          </div>
        )}

        {/* Company Cards */}
        {!loading && !error && (
          <>
            {companies.length === 0 ? (
              <Card className="p-12 text-center border-[#e2e8f0]/50">
                <Building2 className="w-10 h-10 text-[#94a3b8] mx-auto mb-3" />
                <p className="text-sm text-[#94a3b8]">No companies found.</p>
              </Card>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                {companies.map((company) => (
                  <Card
                    key={company.id}
                    className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow cursor-pointer"
                    onClick={() => setSelectedCompanyId(company.id)}
                  >
                    <div className="flex items-start justify-between mb-3">
                      <div className="p-2 rounded-lg bg-[#eff6ff]">
                        <Building2 className="w-5 h-5 text-[#3b82f6]" />
                      </div>
                      <Badge variant="outline" className="bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20 text-xs">
                        active
                      </Badge>
                    </div>
                    <h3 className="font-heading font-semibold text-[#1e293b] mb-1">{company.name}</h3>
                    {company.email && (
                      <p className="text-xs text-[#94a3b8] mb-1 flex items-center gap-1">
                        <Mail className="w-3 h-3" /> {company.email}
                      </p>
                    )}
                    {company.leader_name && (
                      <p className="text-xs text-[#94a3b8] mb-3 flex items-center gap-1">
                        <UserCircle className="w-3 h-3" /> POC: {company.leader_name}
                      </p>
                    )}
                    <div className="flex items-center justify-between pt-3 border-t border-[#f1f5f9]">
                      <div className="flex items-center gap-1 text-xs text-[#94a3b8]">
                        <Users className="w-3.5 h-3.5" /> {company.student_count} students
                      </div>
                      {company.number && (
                        <span className="text-xs text-[#94a3b8]">{company.number}</span>
                      )}
                    </div>
                  </Card>
                ))}
              </div>
            )}
            <p className="text-xs text-[#94a3b8] text-center">
              Showing {companies.length} of {data?.total ?? 0} companies
            </p>
          </>
        )}
      </div>
      <AddCompanyDialog
        open={addDialogOpen}
        onOpenChange={setAddDialogOpen}
        onSaved={() => { refetch(); }}
      />

      {editCompanyId !== null && (
        <EditCompanyDialog
          open={true}
          onOpenChange={(open) => { if (!open) setEditCompanyId(null); }}
          companyId={editCompanyId}
          onSaved={() => { refetch(); setEditCompanyId(null); }}
        />
      )}
    </DashboardLayout>
  );
}
