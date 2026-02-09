/**
 * EditCompanyDialog — Edit an existing company
 */
import { useState, useEffect } from 'react';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { fetchCompanyById, updateCompany } from '@/lib/api';
import { Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface EditCompanyDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  companyId: number;
  onSaved: () => void;
}

export function EditCompanyDialog({ open, onOpenChange, companyId, onSaved }: EditCompanyDialogProps) {
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');

  useEffect(() => {
    if (open && companyId) {
      setLoading(true);
      fetchCompanyById(companyId)
        .then((data) => {
          if (data) {
            setName(data.name || '');
            setEmail(data.email || '');
            setPhone(data.number || '');
            setAddress(data.address || '');
          }
        })
        .catch(() => toast.error('Failed to load company data'))
        .finally(() => setLoading(false));
    }
  }, [open, companyId]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!name.trim() || !email.trim()) {
      toast.error('Company name and email are required');
      return;
    }

    setSaving(true);
    try {
      await updateCompany(companyId, {
        name: name.trim(),
        email: email.trim(),
        number: phone.trim(),
        address: address.trim() || null,
      });

      toast.success(`Company "${name.trim()}" updated successfully`);
      onOpenChange(false);
      onSaved();
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to update company');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Edit Company {name ? `— ${name}` : ''}</DialogTitle>
        </DialogHeader>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="w-6 h-6 animate-spin text-[#3b82f6]" />
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-3">
              <div>
                <Label htmlFor="editCompanyName">Company Name *</Label>
                <Input id="editCompanyName" value={name} onChange={(e) => setName(e.target.value)} required />
              </div>

              <div>
                <Label htmlFor="editCompanyEmail">Email *</Label>
                <Input id="editCompanyEmail" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
              </div>

              <div>
                <Label htmlFor="editCompanyPhone">Phone</Label>
                <Input id="editCompanyPhone" value={phone} onChange={(e) => setPhone(e.target.value)} />
              </div>

              <div>
                <Label htmlFor="editCompanyAddress">Address</Label>
                <Input id="editCompanyAddress" value={address} onChange={(e) => setAddress(e.target.value)} />
              </div>
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={saving}>
                Cancel
              </Button>
              <Button type="submit" className="bg-[#3b82f6] hover:bg-[#2563eb] text-white" disabled={saving}>
                {saving && <Loader2 className="w-4 h-4 mr-1.5 animate-spin" />}
                Save Changes
              </Button>
            </DialogFooter>
          </form>
        )}
      </DialogContent>
    </Dialog>
  );
}
