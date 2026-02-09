/**
 * Leaders Page — Leader management with real Supabase data
 */
import { useState } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useSupabaseQuery } from '@/hooks/useSupabaseQuery';
import { fetchAllLeaders } from '@/lib/api';
import { Shield, Search, Loader2, Mail } from 'lucide-react';
import { StatusBadge } from '@/components/StatusBadge';

export default function Leaders() {
  const [search, setSearch] = useState('');
  const { data: leaders, loading, error } = useSupabaseQuery(() => fetchAllLeaders(), []);

  const filtered = (leaders ?? []).filter(l => {
    if (!search) return true;
    const q = search.toLowerCase();
    return `${l.first_name} ${l.last_name}`.toLowerCase().includes(q) || l.email.toLowerCase().includes(q);
  });

  return (
    <DashboardLayout title="Leaders" subtitle="Manage leader profiles and company associations">
      <div className="space-y-4 animate-fade-in-up">
        <div className="flex items-center gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94a3b8]" />
            <Input
              placeholder="Search leaders..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-10 h-10 border-[#e2e8f0]"
            />
          </div>
        </div>

        {loading ? (
          <div className="py-16 text-center">
            <Loader2 className="mx-auto h-6 w-6 animate-spin text-[#3b82f6]" />
            <p className="mt-2 text-sm text-[#64748b]">Loading leaders...</p>
          </div>
        ) : error ? (
          <Card className="p-5 text-center text-red-500 text-sm">Failed to load: {error}</Card>
        ) : filtered.length === 0 ? (
          <Card className="p-8 text-center border-[#e2e8f0]/50">
            <Shield className="mx-auto mb-3 h-10 w-10 text-[#94a3b8]" />
            <h3 className="text-lg font-semibold text-[#1e293b]">
              {search ? 'No leaders match your search' : 'No leaders yet'}
            </h3>
            <p className="mt-1 text-sm text-[#64748b] max-w-md mx-auto">
              {search ? 'Try a different search term.' : 'Leaders will appear here once assigned the Leader role.'}
            </p>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filtered.map((leader) => (
              <Card key={leader.id} className="p-5 border-[#e2e8f0]/50 shadow-card hover:shadow-md transition-shadow">
                <div className="flex items-start gap-4">
                  <div className="w-10 h-10 rounded-full bg-[#8b5cf6] flex items-center justify-center flex-shrink-0">
                    <span className="text-white text-sm font-semibold">
                      {leader.first_name?.[0]}{leader.last_name?.[0]}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <h4 className="text-sm font-semibold text-[#1e293b] truncate">
                      {leader.first_name} {leader.last_name}
                    </h4>
                    <div className="flex items-center gap-1.5 mt-1 text-xs text-[#64748b]">
                      <Mail className="w-3 h-3 flex-shrink-0" />
                      <span className="truncate">{leader.email}</span>
                    </div>
                    <div className="mt-2">
                      <StatusBadge status={leader.is_active === 1 ? 'active' : 'inactive'} />
                    </div>
                  </div>
                </div>
              </Card>
            ))}
          </div>
        )}

        <p className="text-xs text-[#94a3b8] text-right">
          {filtered.length} leader{filtered.length !== 1 ? 's' : ''} found
        </p>
      </div>
    </DashboardLayout>
  );
}
