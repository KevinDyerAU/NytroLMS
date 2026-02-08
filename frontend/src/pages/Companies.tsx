/**
 * Companies Page - Manage employer/company records
 */
import React, { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Search, Plus, Building2, Users, MapPin, Eye } from 'lucide-react';
import { toast } from 'sonner';

const mockCompanies = [
  { id: 1, name: 'Acme Corp', contact: 'John Smith', email: 'john@acme.com', students: 45, location: 'Sydney, NSW', status: 'active' },
  { id: 2, name: 'TechStart Pty Ltd', contact: 'Jane Doe', email: 'jane@techstart.com', students: 28, location: 'Melbourne, VIC', status: 'active' },
  { id: 3, name: 'BuildRight Construction', contact: 'Mike Brown', email: 'mike@buildright.com', students: 67, location: 'Brisbane, QLD', status: 'active' },
  { id: 4, name: 'Healthcare Plus', contact: 'Sarah Lee', email: 'sarah@healthcareplus.com', students: 34, location: 'Perth, WA', status: 'active' },
  { id: 5, name: 'Green Energy Solutions', contact: 'Tom Wilson', email: 'tom@greenenergy.com', students: 12, location: 'Adelaide, SA', status: 'inactive' },
];

export default function Companies() {
  const [search, setSearch] = useState('');
  const filtered = mockCompanies.filter(c => c.name.toLowerCase().includes(search.toLowerCase()));

  return (
    <DashboardLayout title="Companies" subtitle="Manage employer and company relationships">
      <div className="space-y-4 animate-fade-in-up">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input placeholder="Search companies..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9 border-[#e2e8f0] h-9" />
          </div>
          <Button size="sm" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" onClick={() => toast('Add company coming soon')}><Plus className="w-4 h-4 mr-1.5" /> Add Company</Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          {filtered.map((company) => (
            <Card key={company.id} className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow cursor-pointer" onClick={() => toast('Company details coming soon')}>
              <div className="flex items-start justify-between mb-3">
                <div className="p-2 rounded-lg bg-[#eff6ff]">
                  <Building2 className="w-5 h-5 text-[#3b82f6]" />
                </div>
                <Badge variant="outline" className={company.status === 'active' ? 'bg-[#f0fdf4] text-[#22c55e] border-[#22c55e]/20 text-xs' : 'bg-[#f1f5f9] text-[#94a3b8] border-[#94a3b8]/20 text-xs'}>
                  {company.status}
                </Badge>
              </div>
              <h3 className="font-heading font-semibold text-[#1e293b] mb-1">{company.name}</h3>
              <p className="text-xs text-[#94a3b8] mb-3">{company.contact} &middot; {company.email}</p>
              <div className="flex items-center justify-between pt-3 border-t border-[#f1f5f9]">
                <div className="flex items-center gap-1 text-xs text-[#94a3b8]">
                  <Users className="w-3.5 h-3.5" /> {company.students} students
                </div>
                <div className="flex items-center gap-1 text-xs text-[#94a3b8]">
                  <MapPin className="w-3.5 h-3.5" /> {company.location}
                </div>
              </div>
            </Card>
          ))}
        </div>
      </div>
    </DashboardLayout>
  );
}
